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
            // Экшен saveSort удален
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
     * {@inheritdoc}
     */
    public function executeComponent()
    {
        global $APPLICATION;

        try {
            if (!Loader::includeModule('qwelp.site_settings')) {
                throw new \Exception(Loc::getMessage('QWELP_SITE_SETTINGS_MODULE_NOT_INSTALLED'));
            }

            $siteId = $this->arParams['SITE_ID'] ?? Application::getInstance()->getContext()->getSite();

            // Отключаем кеширование для администраторов, чтобы они всегда видели актуальный порядок
            // при будущей реализации сохранения сортировки.
            global $USER;
            $cacheTime = ($USER->IsAdmin()) ? 0 : ($this->arParams['CACHE_TIME'] ?? 3600);

            if ($this->startResultCache($cacheTime, [$siteId, $USER->GetGroups()])) {
                $this->arResult['SETTINGS'] = SettingsManager::getSettings($siteId);
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