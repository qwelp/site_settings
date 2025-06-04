<?php
/**
 * AJAX-точка для получения настроек сайта в формате JSON
 * 
 * @package qwelp.site_settings
 */

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Web\Json;
use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;
use Qwelp\SiteSettings\SettingsManager;

// Определяем корневую директорию Битрикса
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);

// Подключаем ядро Битрикса
$_SERVER['DOCUMENT_ROOT'] = realpath(dirname(__FILE__) . '/../../../../..');
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

Loc::loadMessages(__FILE__);

// Устанавливаем заголовок для JSON
header('Content-Type: application/json');

// Получаем параметры запроса
$request = Application::getInstance()->getContext()->getRequest();
$siteId = $request->get('site_id');

try {
    // Проверяем, установлен ли модуль
    if (!Loader::includeModule('qwelp.site_settings')) {
        throw new SystemException(Loc::getMessage('QWELP_SITE_SETTINGS_AJAX_MODULE_NOT_INSTALLED'));
    }

    // Получаем настройки
    $settings = SettingsManager::getSettings($siteId);

    // Возвращаем настройки в формате JSON
    echo Json::encode($settings);
} catch (Exception $e) {
    // В случае ошибки возвращаем сообщение об ошибке
    $response = [
        'error' => true,
        'message' => $e->getMessage()
    ];

    echo Json::encode($response);
}

// Завершаем выполнение скрипта
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
