<?php
/**
 * Компонент для отображения всех настроек сайта
 * 
 * @package qwelp.site_settings
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Qwelp\SiteSettings\SettingsManager;

// Проверяем, установлен ли модуль
if (!Loader::includeModule('qwelp.site_settings')) {
    ShowError('Модуль qwelp.site_settings не установлен');
    return;
}

// Получаем параметры компонента
$siteId = $arParams['SITE_ID'] ?? Application::getInstance()->getContext()->getSite();
$cacheTime = isset($arParams['CACHE_TIME']) ? intval($arParams['CACHE_TIME']) : 3600;

// Если используется кеширование
if ($this->startResultCache($cacheTime, [$siteId])) {
    try {
        // Получаем настройки
        $settings = SettingsManager::getSettings($siteId);

        // Передаем данные в шаблон
        $arResult['SETTINGS'] = $settings;
        
        $this->includeComponentTemplate();
    } catch (Exception $e) {
        $this->abortResultCache();
        ShowError($e->getMessage());
    }
}