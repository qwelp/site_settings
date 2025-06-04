<?php
/**
 * Описание модуля qwelp.site_settings
 * 
 * @package qwelp.site_settings
 */

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arModuleDescription = [
    'NAME' => Loc::getMessage('QWELP_SITE_SETTINGS_MODULE_NAME'),
    'DESCRIPTION' => Loc::getMessage('QWELP_SITE_SETTINGS_MODULE_DESCRIPTION'),
    'PARTNER' => 'QWELP',
    'PARTNER_URI' => 'https://qwelp.ru/',
    'CATEGORY' => 'content',
    'VERSION' => '1.0.0',
    'DATE' => '2025-05-18'
];