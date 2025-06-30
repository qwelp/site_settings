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
use Bitrix\Main\Application;

Loc::loadMessages(__FILE__);

class KeyValuePropertyType
{
    const USER_TYPE = 'QwelpSettingsKeyValue';

    public static function getUserTypeDescription(): array
    {
        return [
            'PROPERTY_TYPE' => 'S',
            'USER_TYPE' => self::USER_TYPE,
            'DESCRIPTION' => Loc::getMessage('QWELP_KEY_VALUE_PROPERTY_DESC'),
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

        // Используем статическую переменную для отслеживания показа API блока для каждого свойства
        static $apiBlockShown = [];
        $propertyId = $arProperty['ID'] ?? 'unknown';

        $apiCodeHtml = '';

        // Показываем API блок только один раз для каждого свойства
        if (!isset($apiBlockShown[$propertyId])) {
            $elementCode = self::getElementCodeFromContext();
            $apiCodeHtml = self::generateApiCodeBlock($elementCode);
            $apiBlockShown[$propertyId] = true;

            // Добавляем JavaScript для копирования (только один раз)
            static $scriptAdded = false;
            if (!$scriptAdded) {
                echo '<script>
                    function copyApiCode(button) {
                        const codeElement = button.parentElement.querySelector("code");
                        const text = codeElement.textContent;

                        if (navigator.clipboard && window.isSecureContext) {
                            navigator.clipboard.writeText(text).then(function() {
                                button.textContent = "' . Loc::getMessage('QWELP_KEY_VALUE_COPIED') . '";
                                setTimeout(function() {
                                    button.textContent = "' . Loc::getMessage('QWELP_KEY_VALUE_COPY') . '";
                                }, 2000);
                            });
                        } else {
                            // Fallback для старых браузеров
                            const textArea = document.createElement("textarea");
                            textArea.value = text;
                            textArea.style.position = "fixed";
                            textArea.style.left = "-999999px";
                            textArea.style.top = "-999999px";
                            document.body.appendChild(textArea);
                            textArea.focus();
                            textArea.select();
                            document.execCommand("copy");
                            textArea.remove();
                            button.textContent = "' . Loc::getMessage('QWELP_KEY_VALUE_COPIED') . '";
                            setTimeout(function() {
                                button.textContent = "' . Loc::getMessage('QWELP_KEY_VALUE_COPY') . '";
                            }, 2000);
                        }
                    }
                </script>';
                $scriptAdded = true;
            }
        }

        ob_start();
        ?>
        <?= $apiCodeHtml ?>
        <div class="qwelp-key-value-wrapper">
            <div class="qwelp-key-value-item" style="display: flex; gap: 10px; align-items: center;">
                <?php
                $key = htmlspecialchars($data['key'] ?? '');
                $val = htmlspecialchars($data['value'] ?? '');
                ?>
                <input type="text"
                       name="<?= htmlspecialchars($fieldName) ?>[key]"
                       value="<?= $key ?>"
                       placeholder="<?= Loc::getMessage('QWELP_KEY_VALUE_PLACEHOLDER_KEY') ?>"
                       class="adm-input"
                       style="width: 200px;" />
                <input type="text"
                       name="<?= htmlspecialchars($fieldName) ?>[value]"
                       value="<?= $val ?>"
                       placeholder="<?= Loc::getMessage('QWELP_KEY_VALUE_PLACEHOLDER_VALUE') ?>"
                       class="adm-input"
                       style="width: 200px;" />
            </div>
        </div>
        <?php
        $html = ob_get_clean();
        return $html;
    }

    /**
     * Получает код элемента из контекста администрирования
     *
     * @return string|null Код элемента или null если не найден
     */
    private static function getElementCodeFromContext(): ?string
    {
        // Используем методы Битрикса для получения GET параметров
        $request = Application::getInstance()->getContext()->getRequest();
        $elementId = (int)$request->get('ID');

        if ($elementId > 0) {
            // Загружаем модуль iblock если не загружен
            if (!\Bitrix\Main\Loader::includeModule('iblock')) {
                return null;
            }

            // Получаем элемент по ID
            $element = \CIBlockElement::GetByID($elementId)->GetNext();
            if ($element && !empty($element['CODE'])) {
                return $element['CODE'];
            }
        }

        return null;
    }

    /**
     * Генерирует блок с API кодом для получения технических данных
     *
     * @param string|null $elementCode Код элемента
     * @return string HTML блок с API кодом
     */
    private static function generateApiCodeBlock(?string $elementCode): string
    {
        // Если код элемента не найден, не показываем блок с API
        if (!$elementCode) {
            return '';
        }

        $apiCode = "\\Qwelp\\SiteSettings\\OptionsManager::getTechData('{$elementCode}')";

        $html = '<div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px;">';
        $html .= '<h4 style="margin: 0 0 10px 0; color: #495057; font-size: 14px;">' . Loc::getMessage('QWELP_KEY_VALUE_API_METHOD_TITLE') . '</h4>';
        $html .= '<div style="display: flex; align-items: center; gap: 10px;">';
        $html .= '<code style="background: #e9ecef; padding: 8px 12px; border-radius: 3px; font-family: monospace; flex: 1; user-select: all;">' . htmlspecialchars($apiCode) . '</code>';
        $html .= '<button type="button" onclick="copyApiCode(this)" style="background: #007bff; color: white; border: none; padding: 8px 16px; border-radius: 3px; cursor: pointer; font-size: 12px; white-space: nowrap;">' . Loc::getMessage('QWELP_KEY_VALUE_COPY') . '</button>';
        $html .= '</div>';
        $html .= '</div>';

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
