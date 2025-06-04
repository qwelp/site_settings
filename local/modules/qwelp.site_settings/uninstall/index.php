<?php
/**
 * Файл удаления модуля qwelp.site_settings
 * 
 * @package qwelp.site_settings
 */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Application;

Loc::loadMessages(__FILE__);

/**
 * Удаляет модуль qwelp.site_settings
 * 
 * @return void
 */
function UninstallModule()
{
    $moduleId = 'qwelp.site_settings';
    
    // Получаем объект модуля
    if (!($moduleObject = CModule::CreateModuleObject($moduleId))) {
        return;
    }
    
    // Удаляем модуль
    $moduleObject->DoUninstall();
}

// Запускаем удаление модуля
UninstallModule();