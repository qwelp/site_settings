<?php
/** @noinspection PhpFullyQualifiedNameUsageInspection */

\Bitrix\Main\Loader::registerAutoLoadClasses(
    'qwelp.site_settings',
    [
        'Qwelp\SiteSettings\EventHandler' => 'lib/EventHandler.php',
        'Qwelp\SiteSettings\OptionsManager' => 'lib/OptionsManager.php',
        'Qwelp\SiteSettings\Property\ValuesPropertyType' => 'lib/Property/ValuesPropertyType.php',
        'Qwelp\SiteSettings\Property\HtmlBlockType' => 'lib/Property/HtmlBlockType.php',
        'Qwelp\SiteSettings\SettingsManager' => 'lib/SettingsManager.php',
    ]
);

$eventManager = \Bitrix\Main\EventManager::getInstance();

// Регистрируем обработчик события для кастомного свойства инфоблока
$eventManager->addEventHandler(
    'iblock',
    'OnIBlockPropertyBuildList',
    ['Qwelp\SiteSettings\EventHandler', 'onIBlockPropertyBuildList']
);

// Регистрируем обработчик события для кастомного типа пользовательского поля
$eventManager->addEventHandler(
    'main',
    'OnUserTypeBuildList',
    ['Qwelp\SiteSettings\EventHandler', 'onUserTypeBuildList']
);