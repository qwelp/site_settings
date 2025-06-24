<?php
/**
 * Обработчик событий для модуля qwelp.site_settings
 *
 * @package qwelp.site_settings
 */

namespace Qwelp\SiteSettings;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Класс EventHandler для обработки событий Битрикс
 */
class EventHandler
{
    /**
     * Обработчик события OnIBlockPropertyBuildList.
     * Регистрирует кастомное свойство "Варианты значений"
     *
     * @return array
     */
    public static function onIBlockPropertyBuildList(): array
    {
        return \Qwelp\SiteSettings\Property\ValuesPropertyType::getUserTypeDescription();
    }

    /**
     * Обработчик события OnUserTypeBuildList.
     * Регистрирует кастомный тип пользовательского поля (User Field Type).
     *
     * @return array
     */
    public static function onUserTypeBuildList(): array
    {
        return \Qwelp\SiteSettings\Property\HtmlBlockType::getUserTypeDescription();
    }

    /**
     * Обработчик события OnAdminContextMenuShow
     *
     * @param array $params Параметры события
     * @return void
     */
    public static function addAdminContextMenu(array $params): void
    {
        // Данный обработчик пока не используется
    }
}