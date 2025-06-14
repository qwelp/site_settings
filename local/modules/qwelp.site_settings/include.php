<?php
\Bitrix\Main\Loader::registerAutoLoadClasses(
  'qwelp.site_settings',
  [
    'Qwelp\SiteSettings\EventHandler' => 'lib/EventHandler.php',
    'Qwelp\SiteSettings\Property\ValuesPropertyType' => 'lib/Property/ValuesPropertyType.php',
   '\Qwelp\SiteSettings\SettingsManager' => 'local/modules/qwelp.site_settings/lib/SettingsManager.php',
  ]
);

// Регистрируем обработчик события для кастомного свойства
\Bitrix\Main\EventManager::getInstance()->addEventHandler(
  'iblock', 
  'OnIBlockPropertyBuildList', 
  ['Qwelp\SiteSettings\EventHandler', 'onIBlockPropertyBuildList']
);
