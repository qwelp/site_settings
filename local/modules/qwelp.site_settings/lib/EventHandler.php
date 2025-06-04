<?php
/**
 * Обработчик событий для модуля qwelp.site_settings
 * 
 * @package qwelp.site_settings
 */

namespace Qwelp\SiteSettings;

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Класс EventHandler для обработки событий Битрикс
 */
class EventHandler
{
    /**
     * Обработчик события OnIBlockPropertyBuildList
     * Регистрирует кастомное свойство "Варианты значений"
     * 
     * @return array
     */
    public static function onIBlockPropertyBuildList()
    {
        return \Qwelp\SiteSettings\Property\ValuesPropertyType::getUserTypeDescription();
    }

    /**
     * Обработчик события OnAdminContextMenuShow
     * Подключает файлы для интерактивного контрола в административном интерфейсе
     * 
     * @param array $params Параметры события
     * @return void
     */
    public static function addAdminContextMenu($params)
    {
        return null;
    }
}
