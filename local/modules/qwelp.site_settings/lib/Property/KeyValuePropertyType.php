<?php
/**
 * Пользовательское свойство "Ключ-Значение".
 * Хранит сериализованный массив пар.
 *
 * @package qwelp.site_settings
 */

namespace Qwelp\SiteSettings\Property;

use Bitrix\Main\Localization\Loc;
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

    /**
     * Генерирует HTML для поля редактирования свойства
     */
    public static function getPropertyFieldHtml(array $arProperty, array $value, array $strHTMLControlName): string
    {
        $fieldName = $strHTMLControlName['VALUE'];
        $currentValues = $value['VALUE'] ?? '';

        // Парсим текущее значение
        $data = self::parseValue($currentValues);

        ob_start();
        ?>
        <div class="qwelp-key-value-wrapper">
            <div class="qwelp-key-value-item" style="display: flex; gap: 10px; align-items: center;">
                <?php
                $key = htmlspecialchars($data['key'] ?? '');
                $val = htmlspecialchars($data['value'] ?? '');
                ?>
                <input type="text"
                       name="<?= htmlspecialchars($fieldName) ?>[key]"
                       value="<?= $key ?>"
                       placeholder="<?= Loc::getMessage('QWELP_KEY_VALUE_PLACEHOLDER_KEY') ?: 'Ключ' ?>"
                       class="adm-input"
                       style="width: 200px;" />
                <input type="text"
                       name="<?= htmlspecialchars($fieldName) ?>[value]"
                       value="<?= $val ?>"
                       placeholder="<?= Loc::getMessage('QWELP_KEY_VALUE_PLACEHOLDER_VALUE') ?: 'Значение' ?>"
                       class="adm-input"
                       style="width: 200px;" />
            </div>
        </div>
        <?php
        $html = ob_get_clean();
        return $html;
    }

    /**
     * Конвертирует значение для сохранения в БД
     */
    public static function convertToDB(array $arProperty, array $value): array
    {
        $result = $value;

        // Если пришел массив с ключами key и value
        if (is_array($value['VALUE']) && isset($value['VALUE']['key'], $value['VALUE']['value'])) {
            $keyValue = trim($value['VALUE']['key']);
            $valueValue = trim($value['VALUE']['value']);

            // Если оба поля пустые, сохраняем пустое значение
            if ($keyValue === '' && $valueValue === '') {
                $result['VALUE'] = '';
            } else {
                // Иначе сохраняем как JSON
                $result['VALUE'] = Json::encode([
                    'key' => $keyValue,
                    'value' => $valueValue
                ]);
            }
        }

        return $result;
    }

    /**
     * Конвертирует значение при получении из БД
     */
    public static function convertFromDB(array $arProperty, array $value): array
    {
        return $value;
    }

    /**
     * Отображение в списке администратора
     */
    public static function getAdminListViewHTML(array $arProperty, array $value): string
    {
        if (empty($value['VALUE'])) {
            return '';
        }

        $data = self::parseValue($value['VALUE']);

        $key = htmlspecialchars($data['key'] ?? '');
        $val = htmlspecialchars($data['value'] ?? '');

        // Если оба поля пустые, не показываем ничего
        if (trim($key) === '' && trim($val) === '') {
            return '';
        }

        return '<strong>' . $key . ':</strong> ' . $val;
    }

    /**
     * Парсит значение в массив
     */
    private static function parseValue(string $value): array
    {
        if (empty($value)) {
            return ['key' => '', 'value' => ''];
        }

        // Пытаемся распарсить как JSON
        if (self::isJson($value)) {
            $decoded = Json::decode($value);
            return is_array($decoded) ? $decoded : ['key' => '', 'value' => ''];
        }

        return ['key' => '', 'value' => ''];
    }

    /**
     * Проверяет, является ли строка валидным JSON
     */
    private static function isJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}