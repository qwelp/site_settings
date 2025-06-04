<?php
/**
 * Класс для кастомного свойства
 *
 * @package qwelp.site_settings
 */

namespace Qwelp\SiteSettings\Property;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use CFile;

Loc::loadMessages(__FILE__);

/**
 * Заготовка для пользовательского свойства
 */
class ValuesPropertyType
{
    /**
     * Возвращает описание типа свойства
     *
     * @return array
     */
    public static function getUserTypeDescription()
    {
        return [
            'PROPERTY_TYPE' => 'S',
            'USER_TYPE' => 'QwelpSettingsValues',
            'DESCRIPTION' => Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_CUSTOM_PROPERTY'),
            'GetPropertyFieldHtml' => [__CLASS__, 'getPropertyFieldHtml'],
            'ConvertToDB' => [__CLASS__, 'convertToDB'],
            'ConvertFromDB' => [__CLASS__, 'convertFromDB'],
        ];
    }

    /**
     * Отображение поля ввода свойства
     *
     * @param array $arProperty
     * @param array $value
     * @param array $strHTMLControlName
     * @return string
     */
    public static function getPropertyFieldHtml($arProperty, $value, $strHTMLControlName): string
    {
        global $APPLICATION;
        $moduleId = 'qwelp.site_settings';

        // Добавляем языковые константы для JavaScript
        echo '<script>
            var OPTIONS_CONTROL_MESSAGES = {
                VALUE: "' . Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_VALUE') . '",
                LABEL: "' . Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_LABEL') . '",
                PATH_TO_FILE: "' . Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_PATH_TO_FILE') . '",
                ADD_FILE: "' . Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_ADD_FILE') . '",
                PREVIEW: "' . Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_PREVIEW') . '",
                DELETE: "' . Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_DELETE') . '",
                ADD_ROW: "' . Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_ADD_ROW') . '",
                UPLOAD_ERROR: "' . Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_UPLOAD_ERROR') . '",
                FETCH_ERROR: "' . Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_FETCH_ERROR') . '",
                DELETE_ERROR: "' . Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_DELETE_ERROR') . '"
            };
        </script>';

        Asset::getInstance()->addJs("/bitrix/js/{$moduleId}/options_control.js");
        $APPLICATION->SetAdditionalCss("/bitrix/css/{$moduleId}/options_control.css");

        $fieldValue = htmlspecialcharsbx($strHTMLControlName['VALUE']);
        $fieldMode  = htmlspecialcharsbx($strHTMLControlName['DESCRIPTION']);

        // Сырые данные из БД
        if ($value['VALUE'] == 'checkbox') {
            $currentJson = '{}';
        } else {
            $currentJson = $value['VALUE'] ?? '{}';
        }

        $data = json_decode(htmlspecialcharsback($currentJson), true);

        // Если $data не массив (например, json_decode вернул null, true, false), устанавливаем его в пустой массив
        if (!is_array($data)) {
            $data = [];
        }

        // Убедимся, что массивы есть
        $data = array_merge([
            'checkbox'   => [],
            'radio'      => [],
            'radioImage' => [],
            'pathFile'   => [],
            'select'     => [],
        ], $data);

        // Для каждого radioImage-темплейта добавляем URL превью
        foreach ($data['radioImage'] as &$item) {
            if (!empty($item['fileId'])) {
                $thumb = CFile::ResizeImageGet(
                    (int)$item['fileId'],
                    ['width' => 100, 'height' => 100],
                    BX_RESIZE_IMAGE_PROPORTIONAL,
                    true
                );
                $item['fileUrl'] = $thumb['src'];
            } else {
                $item['fileUrl'] = '';
            }
        }
        unset($item);

        // Перекодируем JSON уже с fileUrl
        $jsonWithUrls = json_encode($data, JSON_UNESCAPED_UNICODE);

        // Определяем режим
        $mode = $value['DESCRIPTION'] ?: '';
        if ($mode === '') {
            foreach (['checkbox','radio','radioImage','pathFile','select'] as $k) {
                if (!empty($data[$k])) { $mode = $k; break; }
            }
            if ($mode === '') { $mode = 'checkbox'; }
        }

        // Рендерим только текущий режим
        $rows = $data[$mode];

        // Собираем HTML
        $html = '<div class="settings-form"'
            . ' data-control-value="' . $fieldValue . '"'
            . ' data-control-mode="' . $fieldMode . '"'
            . ' data-initial-json=\'' . htmlspecialcharsbx($jsonWithUrls) . '\'>'
            . self::generateHtmlForm($rows, $strHTMLControlName, $mode)
            . '</div>'
            . '<input type="hidden" name="' . $fieldValue . '" value="">'
            . '<input type="hidden" name="' . $fieldMode .  '" value="' . htmlspecialcharsbx($mode) . '">';

        return $html;
    }

    /**
     * Генерация HTML-формы
     *
     * @param array  $rows               Значения элементов для текущего режима
     * @param array  $strHTMLControlName Имена управления ($strHTMLControlName['VALUE'], ['DESCRIPTION'])
     * @param string $mode               Текущий режим: checkbox, radio, radioImage или pathFile
     *
     * @return string HTML-код формы
     */
    private static function generateHtmlForm(array $rows, array $strHTMLControlName, string $mode): string
    {
        // Список доступных режимов и их подписи
        $arTypes = [
            'checkbox'   => 'Checkbox',
            'radio'      => 'Radio',
            'radioImage' => 'Radio Image',
            'pathFile'   => 'Path to File',
            'select'     => 'Select'
        ];

        // Уникальный идентификатор для атрибутов
        $uniqueId = htmlspecialcharsbx($strHTMLControlName['VALUE']);

        $html = '';

        // --- Выпадающий список выбора режима ---
        $html .= '<div class="settings-form__dropdown">';
        $html .= '<label for="settings-type-' . $uniqueId . '" class="settings-form__label">' . Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_SELECT_TYPE') . '</label>';
        $html .= '<select id="settings-type-' . $uniqueId . '" class="settings-form__select">';
        foreach ($arTypes as $typeKey => $typeLabel) {
            $selected = ($typeKey === $mode) ? ' selected' : '';
            $html .= '<option value="' . htmlspecialchars($typeKey) . '"' . $selected . '>' . htmlspecialchars($typeLabel) . '</option>';
        }
        $html .= '</select>';
        $html .= '</div>'; // .settings-form__dropdown

        // --- Группа элементов для настройки выбранного режима ---
        $html .= '<div class="settings-form__group">';
        $html .= '<h3 class="settings-form__group-title">' . Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_ELEMENTS_SETTINGS') . '</h3>';
        $html .= '<div class="settings-form__elements">';

        // Если для выбранного режима есть сохранённые строки — рендерим их
        foreach ($rows as $item) {
            // Общие поля
            $value     = htmlspecialchars($item['value']   ?? '');
            $label     = htmlspecialchars($item['label']   ?? '');
            $pathFile  = htmlspecialchars($item['pathFile'] ?? '');

            $html .= '<div class="settings-form__element" data-element>';
            $html .= '<input type="text" class="settings-form__input" placeholder="' . Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_VALUE') . '" value="' . $value . '">';
            $html .= '<input type="text" class="settings-form__input" placeholder="' . Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_LABEL') . '"   value="' . $label . '">';

            // Только для режима pathFile — дополнительное текстовое поле
            if ($mode === 'pathFile') {
                $html .= '<input type="text" class="settings-form__input" placeholder="' . Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_PATH_TO_FILE') . '" value="' . $pathFile . '">';
            }

            // Только для режима radioImage — кнопка загрузки файла
            if ($mode === 'radioImage') {
                $html .= '<span class="adm-input-file">';
                $html .= '<span>' . Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_ADD_FILE') . '</span>';
                $html .= '<input type="file" class="settings-form__file adm-designed-file">';
                $html .= '</span>';
            }

            $html .= '</div>'; // .settings-form__element
        }

        $html .= '</div>'; // .settings-form__elements

        // Кнопка добавления новой строки (во всех режимах, кроме checkbox)
        $html .= '<input type="button" class="settings-form__button" value="' . Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_ADD_ROW') . '"/>';

        $html .= '</div>'; // .settings-form__group

        return $html;
    }

    /**
     * Преобразования данных перед записью в БД
     *
     * @param array        $arProperty
     * @param array|string $value
     * @return array
     * @throws \Exception
     */
    public static function convertToDB($arProperty, $value): array
    {
        // Пришло от JS: VALUE — JSON-строка всего объекта tempValues, DESCRIPTION — выбранный тип
        $json = $value['VALUE'] ?? '{}';
        $mode = $value['DESCRIPTION'] ?? '';

        return [
            'VALUE'       => is_string($json)
                ? $json
                : json_encode($json, JSON_UNESCAPED_UNICODE),
            'DESCRIPTION' => $mode,
        ];
    }

    /**
     * Прочитать из БД — просто возвращаем
     *
     * @param array       $arProperty
     * @param array|string $value
     * @return array|string
     */
    public static function convertFromDB($arProperty, $value)
    {
        return $value;
    }
}
