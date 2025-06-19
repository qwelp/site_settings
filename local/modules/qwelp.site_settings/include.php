<?php
/** @noinspection PhpFullyQualifiedNameUsageInspection */

\Bitrix\Main\Loader::registerAutoLoadClasses(
    'qwelp.site_settings',
    [
        'Qwelp\SiteSettings\EventHandler' => 'lib/EventHandler.php',
        'Qwelp\SiteSettings\OptionsManager' => 'lib/OptionsManager.php', // Добавили новый класс
        'Qwelp\SiteSettings\Property\ValuesPropertyType' => 'lib/Property/ValuesPropertyType.php',
        'Qwelp\SiteSettings\SettingsManager' => 'lib/SettingsManager.php', // Исправили путь
    ]
);

// Регистрируем обработчик события для кастомного свойства
\Bitrix\Main\EventManager::getInstance()->addEventHandler(
    'iblock',
    'OnIBlockPropertyBuildList',
    ['Qwelp\SiteSettings\EventHandler', 'onIBlockPropertyBuildList']
);