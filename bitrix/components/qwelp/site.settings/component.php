<?php
/**
 * Компонент панели настроек сайта
 * 
 * @package qwelp.site_settings
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Localization\Loc;
// Подключаем класс SettingsManager
require_once($_SERVER['DOCUMENT_ROOT'] . '/local/modules/qwelp.site_settings/lib/SettingsManager.php');
use Qwelp\SiteSettings\SettingsManager;

global $APPLICATION;

// Проверяем, установлен ли модуль
if (!Loader::includeModule('qwelp.site_settings')) {
    ShowError(Loc::getMessage('QWELP_SITE_SETTINGS_MODULE_NOT_INSTALLED'));
    return;
}

// Получаем параметры компонента
$siteId = $arParams['SITE_ID'] ?? Application::getInstance()->getContext()->getSite();
$cacheTime = isset($arParams['CACHE_TIME']) ? intval($arParams['CACHE_TIME']) : 3600;
$ajaxMode = $arParams['AJAX_MODE'] ?? 'N';

// Если используется кеширование
if ($this->startResultCache($cacheTime, [$siteId])) {
    try {
        // Получаем настройки
        $settings = SettingsManager::getSettings($siteId);

        // Передаем данные в шаблон
        $arResult['SETTINGS'] = $settings;
        $arResult['SETTINGS_JSON'] = Json::encode($settings);
        $arResult['AJAX_URL'] = $this->GetPath() . '/ajax.php';

        // Устанавливаем заголовок страницы
        $APPLICATION->SetTitle(Loc::getMessage('QWELP_SITE_SETTINGS_PAGE_TITLE'));

        $this->includeComponentTemplate();
    } catch (Exception $e) {
        $this->abortResultCache();
        ShowError($e->getMessage());
    }
}

// Если используется AJAX-режим
if ($ajaxMode === 'Y') {
    $APPLICATION->IncludeComponent(
        'bitrix:main.ajax.form',
        '',
        [
            'COMPONENT_TEMPLATE' => '',
            'FORM_ID' => 'site_settings_form',
            'AJAX_MODE' => 'Y',
            'AJAX_OPTION_JUMP' => 'N',
            'AJAX_OPTION_STYLE' => 'Y',
            'AJAX_OPTION_HISTORY' => 'N',
        ],
        $this
    );
}
