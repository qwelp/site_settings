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
use Qwelp\SiteSettings\SettingsManager;

// Подключаем менеджер настроек, если он еще не был подключен
$managerPath = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/qwelp.site_settings/lib/SettingsManager.php';
if (file_exists($managerPath)) {
    require_once($managerPath);
}

class QwelpSiteSettingsComponent extends CBitrixComponent implements Controllerable
{
    /**
     * {@inheritdoc}
     */
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

    /**
     * AJAX-действие для сохранения настроек.
     * @param array $settings Ключ-значение массив настроек.
     * @param string $siteId ID сайта.
     * @return array
     * @throws \Exception
     */
    public function saveSettingsAction(array $settings, string $siteId): array
    {
        if (!Loader::includeModule('qwelp.site_settings')) {
            throw new \Exception(Loc::getMessage('QWELP_SITE_SETTINGS_MODULE_NOT_INSTALLED'));
        }
        SettingsManager::saveSettings($siteId, $settings);
        return [
            'success' => true,
            'message' => Loc::getMessage('QWELP_SITE_SETTINGS_AJAX_SETTINGS_SAVED'),
        ];
    }

    /**
     * Вспомогательная рекурсивная функция. Собирает "заголовочные" настройки
     * и помечает их в исходном массиве.
     *
     * @param array &$items Массив элементов для обхода (по ссылке).
     * @return array Массив собранных заголовочных настроек.
     */
    private function collectAndMarkHeaderSettings(array &$items): array
    {
        $headerSettings = [];
        $remainingItems = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                $remainingItems[] = $item;
                continue;
            }

            if (isset($item['code'])) {
                if (!empty($item['headerTitle'])) {
                    $headerSettings[] = $item;
                    $item['isHeaderSetting'] = true; // Пометка остается для консистентности
                } else {
                    $remainingItems[] = $item;
                }
            } else {
                $children = array_merge($item['settings'] ?? [], $item['SUBSECTIONS'] ?? []);
                if (!empty($children)) {
                    $collected = $this->collectAndMarkHeaderSettings($children);
                    if (!empty($collected)) {
                        $headerSettings = array_merge($headerSettings, $collected);
                        $item['settings'] = array_values(array_filter($children, fn($child) => isset($child['code'])));
                        $item['SUBSECTIONS'] = array_values(array_filter($children, fn($child) => !isset($child['code'])));
                    }
                }
                $remainingItems[] = $item;
            }
        }

        $items = $remainingItems; // Обновляем исходный массив, удаляя из него header-настройки
        return $headerSettings;
    }

    /**
     * Основная рекурсивная функция для подготовки секций.
     *
     * @param array &$sections Массив секций для обработки (по ссылке).
     * @return void
     */
    private function prepareSectionsRecursive(array &$sections): void
    {
        foreach ($sections as &$section) {
            $section['HEADER_SETTINGS'] = $section['HEADER_SETTINGS'] ?? [];

            // Если блок сворачиваемый, запускаем для него сбор заголовочных настроек
            if (!empty($section['UF_COLLAPSED_BLOCK'])) {
                $isRadioCardParent = !empty($section['SUBSECTIONS']) && (int)reset($section['SUBSECTIONS'])['DEPTH'] === 4;

                if ($isRadioCardParent) {
                    // 1. Собираем header-настройки самого родительского блока
                    if (!empty($section['settings'])) {
                        $section['HEADER_SETTINGS'] = array_merge($section['HEADER_SETTINGS'], $this->collectAndMarkHeaderSettings($section['settings']));
                    }
                    // 2. Для каждой radio-карточки собираем её собственные header-настройки
                    foreach ($section['SUBSECTIONS'] as &$subSection) {
                        if (!empty($subSection['settings'])) {
                            $subSection['HEADER_SETTINGS'] = $this->collectAndMarkHeaderSettings($subSection['settings']);
                        } else {
                            $subSection['HEADER_SETTINGS'] = [];
                        }
                    }
                    unset($subSection);
                } else {
                    // Стандартная логика: собираем все header-настройки из всех потомков
                    $children = array_merge($section['settings'] ?? [], $section['SUBSECTIONS'] ?? []);
                    if (!empty($children)) {
                        $section['HEADER_SETTINGS'] = array_merge($section['HEADER_SETTINGS'], $this->collectAndMarkHeaderSettings($children));
                        $section['settings'] = array_values(array_filter($children, fn($child) => isset($child['code'])));
                        $section['SUBSECTIONS'] = array_values(array_filter($children, fn($child) => !isset($child['code'])));
                    }
                }
            }

            if (!empty($section['SUBSECTIONS'])) {
                $this->prepareSectionsRecursive($section['SUBSECTIONS']);
            }
        }
        unset($section);
    }

    /**
     * Рекурсивно устанавливает значения по умолчанию для настроек, у которых они отсутствуют.
     * Для типов 'select', 'radio', 'radioImage' устанавливает значение первого элемента из опций.
     *
     * @param array &$sections Массив секций для обработки.
     * @return void
     */
    private function prepareDefaultValuesRecursive(array &$sections): void
    {
        foreach ($sections as &$section) {
            if (!empty($section['settings']) && is_array($section['settings'])) {
                foreach ($section['settings'] as &$setting) {
                    $valueIsSet = isset($setting['value']) && $setting['value'] !== null && $setting['value'] !== '';

                    if (!$valueIsSet) {
                        $type = $setting['type'];

                        if (empty($type) && !empty($setting['options']) && is_array($setting['options'])) {
                            if (!isset($setting['options']['color'])) {
                                $type = 'select';
                            }
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


    /**
     * {@inheritdoc}
     */
    public function executeComponent()
    {
        global $APPLICATION, $USER;

        try {
            if (!Loader::includeModule('qwelp.site_settings')) {
                throw new \Exception(Loc::getMessage('QWELP_SITE_SETTINGS_MODULE_NOT_INSTALLED'));
            }

            $siteId = $this->arParams['SITE_ID'] ?? Application::getInstance()->getContext()->getSite();
            $cacheTime = ($USER->IsAdmin()) ? 0 : ($this->arParams['CACHE_TIME'] ?? 3600);

            if ($this->startResultCache($cacheTime, [$siteId, $USER->GetGroups()])) {
                $settings = SettingsManager::getSettings($siteId);

                if (!empty($settings['sections'])) {
                    $this->prepareDefaultValuesRecursive($settings['sections']);
                    $this->prepareSectionsRecursive($settings['sections']);
                }

                $this->arResult['SETTINGS'] = $settings;
                $this->arResult['SITE_ID'] = $siteId;

                $this->setResultCacheKeys([]);
                $this->includeComponentTemplate();
            }

            $APPLICATION->SetTitle(Loc::getMessage('QWELP_SITE_SETTINGS_PAGE_TITLE'));

        } catch (\Exception $e) {
            $this->abortResultCache();
            ShowError($e->getMessage());
        }
    }
}