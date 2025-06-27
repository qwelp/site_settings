<?php
/**
 * Пользовательское свойство "Ключ-Значение".
 * Хранит сериализованный массив пар.
 *
 * @package qwelp.site_settings
 */

namespace Qwelp\SiteSettings\Property;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Web\Json;

Loc::loadMessages(__FILE__);

class KeyValuePropertyType
{
    const USER_TYPE = 'QwelpSettingsKeyValue';

    public static function getUserTypeDescription(): array
    {
        return [
            'PROPERTY_TYPE' => 'S',
            'USER_TYPE' => self::USER_TYPE,
            'DESCRIPTION' => Loc::getMessage('QWELP_KEY_VALUE_PROPERTY_DESC') ?: 'Ключ - Значение (сериализовано)',
            'GetPropertyFieldHtml' => [__CLASS__, 'getPropertyFieldHtml'],
            'ConvertToDB' => [__CLASS__, 'convertToDB'],
            'ConvertFromDB' => [__CLASS__, 'convertFromDB'],
            'GetAdminListViewHTML' => [__CLASS__, 'getAdminListViewHTML'],
        ];
    }

    public static function getPropertyFieldHtml($arProperty, $value, $strHTMLControlName): string
    {
        $moduleId = 'qwelp.site_settings';
        Asset::getInstance()->addJs("/bitrix/js/{$moduleId}/key_value_control.js");
        Asset::getInstance()->addCss("/bitrix/css/{$moduleId}/key_value_control.css");

        echo '<script>
            window.KEY_VALUE_CONTROL_MESSAGES = window.KEY_VALUE_CONTROL_MESSAGES || {
                KEY: "' . \CUtil::JSEscape(Loc::getMessage('QWELP_KEY_VALUE_PROPERTY_KEY')) . '",
                VALUE: "' . \CUtil::JSEscape(Loc::getMessage('QWELP_KEY_VALUE_PROPERTY_VALUE')) . '",
                DELETE: "' . \CUtil::JSEscape(Loc::getMessage('QWELP_KEY_VALUE_PROPERTY_DELETE')) . '",
                ADD: "' . \CUtil::JSEscape(Loc::getMessage('QWELP_KEY_VALUE_PROPERTY_ADD')) . '"
            };
        </script>';

        $controlName = htmlspecialcharsbx($strHTMLControlName['VALUE']);
        $items = is_array($value['VALUE']) ? $value['VALUE'] : [];

        $html = '<div class="key-value-property" data-control-name="' . $controlName . '">';
        $html .= '<div class="key-value-list">';

        if (empty($items)) {
            $items[] = ['key' => '', 'value' => ''];
        }

        foreach ($items as $item) {
            $key = htmlspecialcharsbx($item['key'] ?? '');
            $val = htmlspecialcharsbx($item['value'] ?? '');
            $html .= '<div class="key-value-item">';
            $html .= '<input type="text" class="key-value-input key-value-key" placeholder="' . Loc::getMessage('QWELP_KEY_VALUE_PROPERTY_KEY') . '" value="' . $key . '">';
            $html .= '<input type="text" class="key-value-input key-value-value" placeholder="' . Loc::getMessage('QWELP_KEY_VALUE_PROPERTY_VALUE') . '" value="' . $val . '">';
            $html .= '<button type="button" class="key-value-delete-row" title="' . Loc::getMessage('QWELP_KEY_VALUE_PROPERTY_DELETE') . '">×</button>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '<input type="button" class="key-value-add-row" value="' . Loc::getMessage('QWELP_KEY_VALUE_PROPERTY_ADD') . '">';
        $html .= '<input type="hidden" name="' . $controlName . '" value="' . htmlspecialcharsbx(serialize($value['VALUE'])) . '">';
        $html .= '</div>';

        return $html;
    }

    public static function convertToDB($arProperty, $value)
    {
        if (isset($value['VALUE']) && is_string($value['VALUE'])) {
            try {
                $data = Json::decode($value['VALUE']);
                $value['VALUE'] = serialize($data);
            } catch (\Exception $e) {
                $value['VALUE'] = serialize([]);
            }
        } else {
            $value['VALUE'] = serialize([]);
        }
        return $value;
    }

    public static function convertFromDB($arProperty, $value)
    {
        if (!empty($value['VALUE']) && is_string($value['VALUE'])) {
            $unserialized = @unserialize($value['VALUE']);
            $value['VALUE'] = ($unserialized !== false) ? $unserialized : [];
        } else {
            $value['VALUE'] = [];
        }
        return $value;
    }

    public static function getAdminListViewHTML($arProperty, $value, $strHTMLControlName): string
    {
        if (!empty($value['VALUE'])) {
            $data = is_array($value['VALUE']) ? $value['VALUE'] : unserialize($value['VALUE']);
            if (is_array($data) && count($data) > 0) {
                return Loc::getMessage('QWELP_KEY_VALUE_PROPERTY_COUNT') . ': ' . count($data);
            }
        }
        return '';
    }
}