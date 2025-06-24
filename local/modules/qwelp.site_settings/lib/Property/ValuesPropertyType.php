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
                VALUE: "' . \CUtil::JSEscape(Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_VALUE')) . '",
                LABEL: "' . \CUtil::JSEscape(Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_LABEL')) . '",
                PATH_TO_FILE: "' . \CUtil::JSEscape(Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_PATH_TO_FILE')) . '",
                ADD_FILE: "' . \CUtil::JSEscape(Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_ADD_FILE')) . '",
                PREVIEW: "' . \CUtil::JSEscape(Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_PREVIEW')) . '",
                DELETE: "' . \CUtil::JSEscape(Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_DELETE')) . '",
                ADD_ROW: "' . \CUtil::JSEscape(Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_ADD_ROW')) . '",
                UPLOAD_ERROR: "' . \CUtil::JSEscape(Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_UPLOAD_ERROR')) . '",
                FETCH_ERROR: "' . \CUtil::JSEscape(Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_FETCH_ERROR')) . '",
                DELETE_ERROR: "' . \CUtil::JSEscape(Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_DELETE_ERROR')) . '"
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

        if (!is_array($data)) {
            $data = [];
        }

        $showPicker = (bool)($data['color_show_picker'] ?? false);

        $data = array_merge([
            'checkbox'   => [],
            'radio'      => [],
            'radioImage' => [],
            'pathFile'   => [],
            'select'     => [],
            'color'      => [],
        ], $data);

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

        $jsonWithUrls = json_encode($data, JSON_UNESCAPED_UNICODE);

        $mode = $value['DESCRIPTION'] ?: '';
        if ($mode === '') {
            foreach (['checkbox','radio','radioImage','pathFile','select','color'] as $k) {
                if (!empty($data[$k])) { $mode = $k; break; }
            }
            if ($mode === '') { $mode = 'checkbox'; }
        }

        $rows = $data[$mode] ?? [];

        $html = '<div class="settings-form"'
            . ' data-control-value="' . $fieldValue . '"'
            . ' data-control-mode="' . $fieldMode . '"'
            . ' data-initial-json=\'' . htmlspecialcharsbx($jsonWithUrls) . '\'>'
            . self::generateHtmlForm($rows, $strHTMLControlName, $mode, $showPicker)
            . '</div>'
            . '<input type="hidden" name="' . $fieldValue . '" value="">'
            . '<input type="hidden" name="' . $fieldMode .  '" value="' . htmlspecialcharsbx($mode) . '">';

        return $html;
    }

    private static function generateHtmlForm(array $rows, array $strHTMLControlName, string $mode, bool $showPicker): string
    {
        $arTypes = [
            'checkbox'   => 'Checkbox',
            'radio'      => 'Radio',
            'radioImage' => 'Radio Image',
            'pathFile'   => 'Path to File',
            'select'     => 'Select',
            'color'      => 'Color',
        ];

        $uniqueId = htmlspecialcharsbx($strHTMLControlName['VALUE']);

        $html = '';

        $html .= '<div class="settings-form__dropdown">';
        $html .= '<label for="settings-type-' . $uniqueId . '" class="settings-form__label">' . Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_SELECT_TYPE') . '</label>';
        $html .= '<select id="settings-type-' . $uniqueId . '" class="settings-form__select">';
        foreach ($arTypes as $typeKey => $typeLabel) {
            $selected = ($typeKey === $mode) ? ' selected' : '';
            $html .= '<option value="' . htmlspecialchars($typeKey) . '"' . $selected . '>' . htmlspecialchars($typeLabel) . '</option>';
        }
        $html .= '</select>';
        $html .= '</div>';

        $html .= '<div class="settings-form__group">';
        $html .= '<h3 class="settings-form__group-title">' . Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_ELEMENTS_SETTINGS') . '</h3>';

        $pickerStyle = ($mode === 'color') ? '' : 'style="display:none;"';
        $checked = $showPicker ? 'checked' : '';
        $html .= '<div class="settings-form__color-option" ' . $pickerStyle . '>';
        $html .= '<input type="checkbox" id="color-picker-toggle-'.$uniqueId.'" class="settings-form__color-picker-toggle" '.$checked.'>';
        $html .= '<label for="color-picker-toggle-'.$uniqueId.'">'.(Loc::getMessage('QWELP_SITE_SETTINGS_SHOW_COLOR_PICKER') ?: 'Показывать выбор цвета').'</label>';
        $html .= '</div>';

        $html .= '<div class="settings-form__elements">';

        if (count($rows) === 0 && $mode !== 'checkbox') {
            $rows[] = [];
        }

        foreach ($rows as $item) {
            $value     = htmlspecialchars($item['value']   ?? '');
            $label     = htmlspecialchars($item['label']   ?? '');
            $pathFile  = htmlspecialchars($item['pathFile'] ?? '');

            $html .= '<div class="settings-form__element" data-element>';
            if ($mode === 'color') {
                $color = $value !== '' ? $value : '#000000';
                $html .= '<input type="color" class="settings-form__color" value="' . $color . '">';
            } else {
                $html .= '<input type="text" class="settings-form__input" placeholder="' . Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_VALUE') . '" value="' . $value . '">';
            }
            $html .= '<input type="text" class="settings-form__input" placeholder="' . Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_LABEL') . '"   value="' . $label . '">';

            if ($mode === 'pathFile') {
                $html .= '<input type="text" class="settings-form__input" placeholder="' . Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_PATH_TO_FILE') . '" value="' . $pathFile . '">';
            }
            if ($mode === 'radioImage') {
                $html .= '<span class="adm-input-file">';
                $html .= '<span>' . Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_ADD_FILE') . '</span>';
                $html .= '<input type="file" class="settings-form__file adm-designed-file">';
                $html .= '</span>';
            }

            // [NEW] Добавляем кнопку удаления для каждой строки
            $html .= '<button type="button" class="settings-form__delete-row" title="'.Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_DELETE').'">×</button>';

            $html .= '</div>'; // .settings-form__element
        }

        $html .= '</div>'; // .settings-form__elements

        $buttonStyle = ($mode === 'checkbox') ? 'style="display:none;"' : '';
        $html .= '<input type="button" class="settings-form__button" value="' . Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_ADD_ROW') . '" '.$buttonStyle.'/>';

        $html .= '</div>';

        return $html;
    }

    public static function convertToDB($arProperty, $value): array
    {
        return [
            'VALUE'       => $value['VALUE'] ?? '{}',
            'DESCRIPTION' => $value['DESCRIPTION'] ?? '',
        ];
    }

    public static function convertFromDB($arProperty, $value)
    {
        return $value;
    }
}