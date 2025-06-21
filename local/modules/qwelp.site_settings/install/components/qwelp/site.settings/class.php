<?php
/**
 * Класс компонента панели настроек сайта
 *
 * @package qwelp.site_settings
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Application;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Exception;
use Qwelp\SiteSettings\OptionsManager;
use Qwelp\SiteSettings\SettingsManager;

class QwelpSiteSettingsComponent extends CBitrixComponent implements Controllerable
{
    public function configureActions(): array
    {
        return [
            'saveSettings' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                ],
            ],
        ];
    }

    public function saveSettingsAction(array $settings, string $siteId): array
    {
        if (!Loader::includeModule('qwelp.site_settings')) {
            return ['success' => false, 'message' => 'Module not installed'];
        }

        try {
            $result = OptionsManager::save($settings, $siteId);
            return [
                'success' => $result,
                'message' => $result ? Loc::getMessage('QWELP_SITE_SETTINGS_AJAX_SETTINGS_SAVED') : 'Save error',
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function mergeSettingsWithOptions(array &$sections, array $options): void
    {
        foreach ($sections as &$section) {
            if (!empty($section['settings'])) {
                foreach ($section['settings'] as &$setting) {
                    if (isset($options[$setting['code']])) {
                        $setting['value'] = $options[$setting['code']];
                    }
                }
                unset($setting);
            }

            if (!empty($section['SUBSECTIONS'])) {
                $this->mergeSettingsWithOptions($section['SUBSECTIONS'], $options);
            }
        }
        unset($section);
    }

    private function prepareRadioCardGroups(array &$sections): void
    {
        foreach ($sections as &$section) {
            $isRadioCardParent = !empty($section['SUBSECTIONS']) && (int)reset($section['SUBSECTIONS'])['DEPTH'] === 4;

            if ($isRadioCardParent) {
                $firstCardId = reset($section['SUBSECTIONS'])['id'] ?? null;
                if ($firstCardId) {
                    $section['settings'][] = [
                        'code' => $section['id'],
                        'type' => 'radioCard',
                        'value' => $firstCardId,
                        'options' => []
                    ];
                }
            }

            if (!empty($section['SUBSECTIONS'])) {
                $this->prepareRadioCardGroups($section['SUBSECTIONS']);
            }
        }
        unset($section);
    }

    private function extractHeaderSettings(array $items): array
    {
        $headerSettings = [];
        $remainingItems = [];

        foreach ($items as $item) {
            if (isset($item['code']) && !empty($item['headerTitle'])) {
                $item['isHeaderSetting'] = true;
                $headerSettings[] = $item;
            } else {
                $remainingItems[] = $item;
            }
        }
        return [$headerSettings, $remainingItems];
    }

    private function prepareSectionsRecursive(array &$sections): void
    {
        foreach ($sections as &$section) {
            $section['HEADER_SETTINGS'] = $section['HEADER_SETTINGS'] ?? [];
            if (!empty($section['UF_COLLAPSED_BLOCK'])) {
                $isRadioCardParent = !empty($section['SUBSECTIONS']) && (int)reset($section['SUBSECTIONS'])['DEPTH'] === 4;
                if ($isRadioCardParent) {
                    foreach ($section['SUBSECTIONS'] as &$subSection) {
                        if (!empty($subSection['settings'])) {
                            [$header, $remaining] = $this->extractHeaderSettings($subSection['settings']);
                            $subSection['HEADER_SETTINGS'] = $header;
                            $subSection['settings'] = $remaining;
                        } else {
                            $subSection['HEADER_SETTINGS'] = [];
                        }
                    }
                    unset($subSection);
                }
                $itemsToScan = array_merge($section['settings'] ?? [], $section['SUBSECTIONS'] ?? []);
                if (!$isRadioCardParent) {
                    foreach ($itemsToScan as &$itemToScan) {
                        if (isset($itemToScan['SUBSECTIONS'])) {
                            $this->prepareSectionsRecursive($itemToScan['SUBSECTIONS']);
                        }
                    }
                    unset($itemToScan);
                }
                [$header, $remaining] = $this->extractHeaderSettings($itemsToScan);
                if (!empty($header)) {
                    $section['HEADER_SETTINGS'] = array_merge($section['HEADER_SETTINGS'], $header);
                    $section['settings'] = array_values(array_filter($remaining, fn($child) => isset($child['code'])));
                    $section['SUBSECTIONS'] = array_values(array_filter($remaining, fn($child) => !isset($child['code'])));
                }
            }
            if (!empty($section['SUBSECTIONS']) && empty($section['UF_COLLAPSED_BLOCK'])) {
                $this->prepareSectionsRecursive($section['SUBSECTIONS']);
            }
        }
        unset($section);
    }

    private function prepareDefaultValuesRecursive(array &$sections): void
    {
        foreach ($sections as &$section) {
            if (!empty($section['settings']) && is_array($section['settings'])) {
                foreach ($section['settings'] as &$setting) {
                    $valueIsSet = isset($setting['value']) && $setting['value'] !== null && $setting['value'] !== '';
                    if (!$valueIsSet) {
                        $type = $setting['type'];
                        if (empty($type) && !empty($setting['options']) && is_array($setting['options'])) {
                            if (!isset($setting['options']['color'])) $type = 'select';
                        }
                        if (in_array($type, ['select', 'radio', 'radioImage'])) {
                            $options = $setting['options'];
                            if (!empty($options) && is_array($options)) {
                                $firstOption = reset($options);
                                if (is_array($firstOption) && isset($firstOption['value'])) {
                                    $setting['value'] = $firstOption['value'];
                                }
                            }
                        }
                    }
                }
                unset($setting);
            }
            if (!empty($section['SUBSECTIONS']) && is_array($section['SUBSECTIONS'])) {
                $this->prepareDefaultValuesRecursive($section['SUBSECTIONS']);
            }
        }
        unset($section);
    }

    public function executeComponent()
    {
        global $APPLICATION, $USER;

        try {
            if (!Loader::includeModule('qwelp.site_settings')) {
                throw new Exception(Loc::getMessage('QWELP_SITE_SETTINGS_MODULE_NOT_INSTALLED'));
            }

            $siteId = $this->arParams['SITE_ID'] ?? Application::getInstance()->getContext()->getSite();
            $cacheTime = ($USER->IsAdmin()) ? 0 : ($this->arParams['CACHE_TIME'] ?? 3600);

            $cacheId = $siteId . '|' . $USER->GetGroups();

            if ($cacheTime > 0) {
                $optionsFilePath = OptionsManager::getFilePath($siteId);
                $optionsFileMTime = file_exists($optionsFilePath) ? filemtime($optionsFilePath) : 'nofile';
                $cacheId .= '|' . $optionsFileMTime;
            }

            if ($this->startResultCache($cacheTime, $cacheId)) {
                $settingsStructure = SettingsManager::getSettings($siteId);
                $savedOptions = OptionsManager::getAll($siteId);

                if (!empty($settingsStructure['sections'])) {
                    $this->prepareDefaultValuesRecursive($settingsStructure['sections']);
                    if (!empty($savedOptions)) {
                        $this->mergeSettingsWithOptions($settingsStructure['sections'], $savedOptions);
                    }
                    $this->prepareRadioCardGroups($settingsStructure['sections']);
                    $this->prepareSectionsRecursive($settingsStructure['sections']);
                }

                $this->arResult['SETTINGS'] = $settingsStructure;
                $this->arResult['SITE_ID'] = $siteId;
                $this->setResultCacheKeys([]);
                $this->includeComponentTemplate();
            }

            $APPLICATION->SetTitle(Loc::getMessage('QWELP_SITE_SETTINGS_PAGE_TITLE'));

        } catch (Exception $e) {
            $this->abortResultCache();
            ShowError($e->getMessage());
        }
    }
}