<?php
/**
 * Параметры компонента панели настроек сайта
 * 
 * @package qwelp.site_settings
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

// Получаем список сайтов
$sites = [];
$by = 'sort';
$order = 'asc';
$filter = ['ACTIVE' => 'Y'];
$rsSites = \CSite::GetList($by, $order, $filter);
while ($arSite = $rsSites->Fetch()) {
    $sites[$arSite['LID']] = '['.$arSite['LID'].'] '.$arSite['NAME'];
}

$arComponentParameters = [
    'GROUPS' => [
        'SETTINGS' => [
            'NAME' => Loc::getMessage('QWELP_SITE_SETTINGS_GROUP_SETTINGS'),
            'SORT' => 100,
        ],
    ],
    'PARAMETERS' => [
        'SITE_ID' => [
            'PARENT' => 'SETTINGS',
            'NAME' => Loc::getMessage('QWELP_SITE_SETTINGS_PARAM_SITE_ID'),
            'TYPE' => 'LIST',
            'VALUES' => $sites,
            'DEFAULT' => '',
            'ADDITIONAL_VALUES' => 'Y',
            'REFRESH' => 'Y',
        ],
        'AJAX_MODE' => [
            'PARENT' => 'SETTINGS',
            'NAME' => Loc::getMessage('QWELP_SITE_SETTINGS_PARAM_AJAX_MODE'),
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'N',
        ],
        'CACHE_TIME' => [
            'DEFAULT' => 3600,
        ],
    ],
];
