<?php
/**
 * Описание компонента панели настроек сайта
 * 
 * @package qwelp.site_settings
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

$arComponentDescription = [
    'NAME' => Loc::getMessage('QWELP_SITE_SETTINGS_COMPONENT_NAME'),
    'DESCRIPTION' => Loc::getMessage('QWELP_SITE_SETTINGS_COMPONENT_DESCRIPTION'),
    'ICON' => '/images/icon.gif',
    'SORT' => 10,
    'CACHE_PATH' => 'Y',
    'PATH' => [
        'ID' => 'qwelp',
        'NAME' => Loc::getMessage('QWELP_SITE_SETTINGS_COMPONENT_PATH_NAME'),
        'CHILD' => [
            'ID' => 'site_settings',
            'NAME' => Loc::getMessage('QWELP_SITE_SETTINGS_COMPONENT_CHILD_NAME')
        ]
    ],
];
