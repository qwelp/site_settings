<?php
namespace Qwelp\SiteSettings\Property;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserField\TypeBase;
use CUserTypeManager;
use Bitrix\Main\Text\HtmlFilter;

Loc::loadMessages(__FILE__);

class HtmlBlockType extends TypeBase
{
    const USER_TYPE_ID = 'qwelp_html_block';

    public static function getUserTypeDescription(): array
    {
        return [
            'USER_TYPE_ID' => static::USER_TYPE_ID,
            'CLASS_NAME' => __CLASS__,
            'DESCRIPTION' => Loc::getMessage('QWELP_HTML_BLOCK_DESCRIPTION'),
            'BASE_TYPE' => CUserTypeManager::BASE_TYPE_STRING,
        ];
    }

    public static function getDbColumnType(): string
    {
        return 'text';
    }

    public static function prepareSettings(array $userField): array
    {
        $height = (int)($userField['SETTINGS']['height'] ?? 200);
        return ['height' => ($height < 200 ? 200 : $height)];
    }

    public static function getSettingsHTML($userField = false, $htmlControl, $bVarsFromForm): string
    {
        $height = 200;
        if ($bVarsFromForm) {
            $height = (int)($GLOBALS[$htmlControl['NAME']]['height'] ?? $height);
        } elseif (is_array($userField)) {
            $height = (int)($userField['SETTINGS']['height'] ?? $height);
        }
        return '<tr><td>' . Loc::getMessage('USER_TYPE_HTML_HEIGHT') . ':</td><td><input type="text" name="' . $htmlControl['NAME'] . '[height]" size="10" maxlength="10" value="' . $height . '"></td></tr>';
    }

    public static function onBeforeSave(array $userField, $value): string
    {
        $htmlValue = $_REQUEST[$userField['FIELD_NAME'] . '_html'] ?? '';
        $titleValue = is_string($value) ? trim($value) : '';

        if (!empty($titleValue) || !empty($htmlValue)) {
            return serialize(['title' => $titleValue, 'html' => $htmlValue]);
        }
        return '';
    }

    public static function getEditFormHTML(array $userField, array $htmlControl): string
    {
        $data = ['title' => '', 'html' => ''];
        $inputValue = $htmlControl['VALUE'];

        if (isset($_REQUEST[$htmlControl['NAME']])) {
            $data['title'] = $_REQUEST[$htmlControl['NAME']];
            $data['html'] = $_REQUEST[$userField['FIELD_NAME'] . '_html'];
        }
        elseif (!empty($inputValue) && is_string($inputValue)) {
            $decodedString = htmlspecialchars_decode($inputValue);
            $unserialized = unserialize($decodedString, ['allowed_classes' => false]);
            if (is_array($unserialized)) {
                $data = $unserialized;
            }
        }

        $data = array_merge(['title' => '', 'html' => ''], $data);

        $title = htmlspecialcharsbx($data['title']);
        $htmlValue = $data['html'];

        $titleControlName = $htmlControl['NAME'];
        $htmlControlName = $userField['FIELD_NAME'] . '_html';
        $htmlEditorId = 'editor_' . HtmlFilter::encode($userField['FIELD_NAME'] . '_' . $userField['ID']);

        ob_start();
        ?>
        <div class="qwelp-html-block-control" style="border: 1px solid #dce7ed; padding: 10px; border-radius: 4px; background: #f5f9f9;">
            <div style="margin-bottom: 10px;">
                <input type="text"
                       name="<?= $titleControlName ?>"
                       value="<?= $title ?>"
                       placeholder="<?= Loc::getMessage('QWELP_HTML_BLOCK_TITLE_PLACEHOLDER') ?>"
                       style="width: 98%;"
                >
            </div>
            <div>
                <?php
                $LHE = new \CHTMLEditor;
                $LHE->Show([
                    'name' => $htmlControlName,
                    'id' => $htmlEditorId,
                    'width' => '100%',
                    'height' => $userField['SETTINGS']['height'] ?? 200,
                    'content' => $htmlValue,
                    'bAllowPhp' => false,
                ]);
                ?>
            </div>
        </div>
        <script>
            (function() {
                var form = document.forms['form1'] || document.querySelector('form[name^="form_"]');
                if (form) {
                    BX.bind(form, 'submit', function() {
                        var editor = BX.admin.CEditor.Get('<?= \CUtil::JSEscape($htmlEditorId) ?>');
                        if (editor && editor.IsShown()) {
                            editor.SaveContent();
                        }
                    });
                }
            })();
        </script>
        <?php
        return ob_get_clean();
    }

    public static function getAdminListViewHTML(array $userField, ?array $htmlControl): string
    {
        $value = $htmlControl['VALUE'] ?? '';
        if (empty($value)) return ' ';

        $data = unserialize(htmlspecialchars_decode($value), ['allowed_classes' => false]);
        if (!is_array($data) || empty($data['title'])) return ' ';

        return htmlspecialcharsbx($data['title']);
    }

    public static function getFilterHTML(array $userField, array $htmlControl): string
    {
        return '<input type="text" name="' . $htmlControl['NAME'] . '" size="20" value="">';
    }

    public static function checkFields(array $userField, $value): array
    {
        return [];
    }

    /**
     * Преобразование данных из БД
     * 
     * @param array $userField Описание пользовательского поля
     * @param array|string $value Значение из БД
     * @return array|string Преобразованное значение
     */
    public static function convertFromDB($userField, $value)
    {
        if (empty($value)) {
            return '';
        }

        try {
            // Проверяем, что значение является строкой перед обработкой
            if (is_string($value)) {
                // Пробуем десериализовать значение
                $unserialized = @unserialize(htmlspecialchars_decode($value));
                if ($unserialized !== false && is_array($unserialized)) {
                    return $value;
                }
            } else {
                // Логируем ошибку, если значение не является строкой
                error_log('HtmlBlockType::convertFromDB error: value is not a string but ' . gettype($value));
                // Возвращаем пустую строку для безопасности
                return '';
            }
        } catch (\Exception $e) {
            error_log('HtmlBlockType::convertFromDB error: ' . $e->getMessage());
        }

        return $value;
    }
}
