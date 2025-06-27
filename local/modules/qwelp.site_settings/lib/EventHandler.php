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
     * [FIXED] Регистрирует свойство "Варианты значений".
     *
     * @return array
     */
    public static function onIBlockPropertyBuildListValues(): array
    {
        return \Qwelp\SiteSettings\Property\ValuesPropertyType::getUserTypeDescription();
    }

    /**
     * [NEW] Регистрирует свойство "Ключ - Значение".
     *
     * @return array
     */
    public static function onIBlockPropertyBuildListKeyValue(): array
    {
        return \Qwelp\SiteSettings\Property\KeyValuePropertyType::getUserTypeDescription();
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