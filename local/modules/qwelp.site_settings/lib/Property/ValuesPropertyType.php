<?php
/**
 * Класс для кастомного свойства
 *
 * @package qwelp.site_settings
 */

namespace Qwelp\SiteSettings\Property;

use Bitrix\Main\Application;
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

        // Получаем код элемента инфоблока из контекста
        $elementCode = self::getElementCodeFromContext();
        $apiCodeHtml = self::generateApiCodeBlock($elementCode);

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

            function copyApiCode(button) {
                const codeElement = button.parentElement.querySelector("code");
                const text = codeElement.textContent;

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(function() {
                        button.textContent = "' . \CUtil::JSEscape(Loc::getMessage('QWELP_SITE_SETTINGS_COPIED_MESSAGE')) . '";
                        setTimeout(function() {
                            button.textContent = "' . \CUtil::JSEscape(Loc::getMessage('QWELP_SITE_SETTINGS_COPY_BUTTON')) . '";
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
                    button.textContent = "' . \CUtil::JSEscape(Loc::getMessage('QWELP_SITE_SETTINGS_COPIED_MESSAGE')) . '";
                    setTimeout(function() {
                        button.textContent = "' . \CUtil::JSEscape(Loc::getMessage('QWELP_SITE_SETTINGS_COPY_BUTTON')) . '";
                    }, 2000);
                }
            }
        </script>';

        Asset::getInstance()->addJs("/bitrix/js/{$moduleId}/options_control.js");
        $APPLICATION->SetAdditionalCss("/bitrix/css/{$moduleId}/options_control.css");

        $fieldValue = htmlspecialcharsbx($strHTMLControlName['VALUE']);
        $fieldMode  = htmlspecialcharsbx($strHTMLControlName['DESCRIPTION']);

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

        // [FIXED] Ключевое исправление. Более надежное определение активного режима.
        $mode = $value['DESCRIPTION'] ?: '';
        if ($mode === '') {
            // Задаем приоритетный порядок проверки
            $priorityOrder = ['color', 'radioImage', 'radio', 'select', 'pathFile', 'checkbox'];
            foreach ($priorityOrder as $k) {
                // Проверяем, что ключ существует и его значение не является пустым массивом
                if (isset($data[$k]) && !empty($data[$k])) {
                    $mode = $k;
                    break;
                }
            }
            // Если после всего перебора режим не найден (все массивы пусты), ставим по умолчанию
            if ($mode === '') {
                $mode = 'checkbox';
            }
        }

        $rows = $data[$mode] ?? [];

        $html = $apiCodeHtml
            . '<div class="settings-form"'
            . ' data-control-value="' . $fieldValue . '"'
            . ' data-control-mode="' . $fieldMode . '"'
            . ' data-initial-json=\'' . htmlspecialcharsbx($jsonWithUrls) . '\'>'
            . self::generateHtmlForm($rows, $strHTMLControlName, $mode, $showPicker)
            . '</div>'
            . '<input type="hidden" name="' . $fieldValue . '" value="">'
            . '<input type="hidden" name="' . $fieldMode .  '" value="' . htmlspecialcharsbx($mode) . '">';

        return $html;
    }

    /**
     * Получает код элемента инфоблока из контекста администрирования
     *
     * @return string|null Код элемента или null если элемент не найден или нет ID
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
     * Генерирует блок с API кодом для получения значения свойства
     *
     * @param string|null $elementCode Код элемента инфоблока
     * @return string HTML блок с API кодом
     */
    private static function generateApiCodeBlock(?string $elementCode): string
    {
        // Если код элемента не найден, не показываем блок с API
        if (!$elementCode) {
            return '';
        }

        $apiCode = "\\Qwelp\\SiteSettings\\OptionsManager::get('{$elementCode}', null, 's1')";

        $html = '<div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px;">';
        $html .= '<h4 style="margin: 0 0 10px 0; color: #495057; font-size: 14px;">' . Loc::getMessage('QWELP_SITE_SETTINGS_API_METHOD_TITLE') . '</h4>';
        $html .= '<div style="display: flex; align-items: center; gap: 10px;">';
        $html .= '<code style="background: #e9ecef; padding: 8px 12px; border-radius: 3px; font-family: monospace; flex: 1; user-select: all;">' . htmlspecialchars($apiCode) . '</code>';
        $html .= '<button type="button" onclick="copyApiCode(this)" style="background: #007bff; color: white; border: none; padding: 8px 16px; border-radius: 3px; cursor: pointer; font-size: 12px; white-space: nowrap;">' . Loc::getMessage('QWELP_SITE_SETTINGS_COPY_BUTTON') . '</button>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    private static function generateHtmlForm(array $rows, array $strHTMLControlName, string $mode, bool $showPicker): string
    {
        $arTypes = [
            'checkbox' => Loc::getMessage('QWELP_SITE_SETTINGS_TYPE_CHECKBOX'),
            'radio' => Loc::getMessage('QWELP_SITE_SETTINGS_TYPE_RADIO'),
            'radioImage' => Loc::getMessage('QWELP_SITE_SETTINGS_TYPE_RADIO_IMAGE'),
            'pathFile' => Loc::getMessage('QWELP_SITE_SETTINGS_TYPE_PATH_FILE'),
            'select' => Loc::getMessage('QWELP_SITE_SETTINGS_TYPE_SELECT'),
            'color' => Loc::getMessage('QWELP_SITE_SETTINGS_TYPE_COLOR')
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
        $html .= '</select></div>';
        $html .= '<div class="settings-form__group">';
        $html .= '<h3 class="settings-form__group-title">' . Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_ELEMENTS_SETTINGS') . '</h3>';

        $pickerStyle = ($mode === 'color') ? '' : 'style="display:none;"';
        $checked = $showPicker ? 'checked' : '';
        $html .= '<div class="settings-form__color-option" ' . $pickerStyle . '>';
        $html .= '<input type="checkbox" id="color-picker-toggle-'.$uniqueId.'" class="settings-form__color-picker-toggle" '.$checked.'>';
        $html .= '<label for="color-picker-toggle-'.$uniqueId.'">'.Loc::getMessage('QWELP_SITE_SETTINGS_SHOW_COLOR_PICKER').'</label></div>';
        $html .= '<div class="settings-form__elements">';

        foreach ($rows as $item) {
            $value = htmlspecialchars($item['value'] ?? '');
            $label = htmlspecialchars($item['label'] ?? '');
            $pathFile = htmlspecialchars($item['pathFile'] ?? '');
            $html .= '<div class="settings-form__element" data-element>';
            if ($mode === 'color') {
                $color = $value !== '' ? $value : '#000000';
                $html .= '<input type="color" class="settings-form__color" value="' . $color . '">';
            } else {
                $html .= '<input type="text" class="settings-form__input" placeholder="' . Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_VALUE') . '" value="' . $value . '">';
            }
            $html .= '<input type="text" class="settings-form__input" placeholder="' . Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_LABEL') . '" value="' . $label . '">';
            if ($mode === 'pathFile') {
                $html .= '<input type="text" class="settings-form__input" placeholder="' . Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_PATH_TO_FILE') . '" value="' . $pathFile . '">';
            }
            if ($mode === 'radioImage') {
                $html .= '<span class="adm-input-file"><span>' . Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_ADD_FILE') . '</span><input type="file" class="settings-form__file adm-designed-file"></span>';
            }
            $html .= '<button type="button" class="settings-form__delete-row" title="'.Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_DELETE').'">×</button></div>';
        }
        $html .= '</div>';

        $buttonStyle = ($mode === 'checkbox') ? 'style="display:none;"' : '';
        $html .= '<input type="button" class="settings-form__button" value="' . Loc::getMessage('QWELP_SITE_SETTINGS_PROPERTY_ADD_ROW') . '" '.$buttonStyle.'/>';
        $html .= '</div>';
        return $html;
    }

    public static function convertToDB($arProperty, $value): array
    {
        return ['VALUE' => $value['VALUE'] ?? '{}', 'DESCRIPTION' => $value['DESCRIPTION'] ?? ''];
    }

    public static function convertFromDB($arProperty, $value)
    {
        return $value;
    }
}
