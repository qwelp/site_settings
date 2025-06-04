<?php
/**
 * AJAX-обработчик для компонента панели настроек сайта
 * 
 * @package qwelp.site_settings
 */

define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Localization\Loc;
use Qwelp\SiteSettings\SettingsManager;

// Устанавливаем заголовок для JSON
header('Content-Type: application/json');

// Получаем параметры запроса
$request = Application::getInstance()->getContext()->getRequest();
$requestData = Json::decode($request->getInput());

// Проверяем, установлен ли модуль
if (!Loader::includeModule('qwelp.site_settings')) {
    echo Json::encode([
        'success' => false,
        'message' => Loc::getMessage('QWELP_SITE_SETTINGS_AJAX_MODULE_NOT_INSTALLED')
    ]);
    die();
}

// Получаем действие
$action = $requestData['action'] ?? '';

// Обрабатываем действие
switch ($action) {
    case 'save_settings':
        // Получаем настройки и ID сайта
        $settings = $requestData['settings'] ?? [];
        $siteId = $request->get('site_id') ?? Application::getInstance()->getContext()->getSite();

        // Преобразуем настройки в формат для сохранения
        $sectionsData = [];

        // Получаем текущие настройки для определения разделов
        $currentSettings = SettingsManager::getSettings($siteId);

        // Создаем структуру данных для сохранения
        foreach ($currentSettings['sections'] as $section) {
            $sectionSettings = [];

            foreach ($section['settings'] as $setting) {
                $code = $setting['code'];
                if (isset($settings[$code])) {
                    $sectionSettings[$code] = $settings[$code];
                }
            }

            if (!empty($sectionSettings)) {
                $sectionsData[] = [
                    'id' => $section['id'],
                    'settings' => $sectionSettings
                ];
            }
        }

        // Сохраняем настройки
        try {
            $data = ['sections' => $sectionsData];
            SettingsManager::saveSettings($siteId, $data);

            echo Json::encode([
                'success' => true,
                'message' => Loc::getMessage('QWELP_SITE_SETTINGS_AJAX_SETTINGS_SAVED')
            ]);
        } catch (Exception $e) {
            echo Json::encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        break;

    default:
        echo Json::encode([
            'success' => false,
            'message' => Loc::getMessage('QWELP_SITE_SETTINGS_AJAX_UNKNOWN_ACTION')
        ]);
        break;
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
