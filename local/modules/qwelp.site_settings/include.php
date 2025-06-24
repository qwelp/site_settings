<?php
/**
 * Файл автозагрузки классов модуля qwelp.site_settings
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */

\Bitrix\Main\Loader::registerAutoLoadClasses(
    'qwelp.site_settings',
    [
        'Qwelp\SiteSettings\EventHandler' => 'lib/EventHandler.php',
        'Qwelp\SiteSettings\OptionsManager' => 'lib/OptionsManager.php',
        'Qwelp\SiteSettings\Property\ValuesPropertyType' => 'lib/Property/ValuesPropertyType.php',
        'Qwelp\SiteSettings\Property\HtmlBlockType' => 'lib/Property/HtmlBlockType.php',
        'Qwelp\SiteSettings\Property\KeyValuePropertyType' => 'lib/Property/KeyValuePropertyType.php',
        'Qwelp\SiteSettings\SettingsManager' => 'lib/SettingsManager.php',
    ]
);