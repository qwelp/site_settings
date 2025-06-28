<?php
namespace Qwelp\SiteSettings\UserType;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserField\TypeBase;
use CUserTypeManager;
use CHTMLEditor;
use CUtil; // Required for CUtil::JSEscape

/**
 * Класс пользовательского типа поля "HTML-блок (Заголовок + Текст с редактором)" для Bitrix D7.
 * Предназначен для сохранения и отображения пары: текстовый заголовок и HTML-содержимое
 * с использованием встроенного HTML-редактора Bitrix.
 *
 * Данные хранятся в базе данных в формате JSON-строки.
 */
class HtmlBlockType extends TypeBase
{
    const USER_TYPE_ID = 'qwelp_html_block';

    /**
     * Возвращает описание пользовательского типа поля.
     * @return array Массив с описанием пользовательского типа поля
     */
    public static function getUserTypeDescription(): array
    {
        Loc::loadMessages(__FILE__);

        return [
            'USER_TYPE_ID' => static::USER_TYPE_ID,
            'CLASS_NAME'   => __CLASS__,
            'DESCRIPTION'  => Loc::getMessage('QWELP_HTML_BLOCK_DESCRIPTION'),
            'BASE_TYPE'    => CUserTypeManager::BASE_TYPE_STRING,
        ];
    }

    /**
     * Определяет тип колонки в базе данных для хранения значения.
     * Используется 'text' для возможности хранения длинных JSON-строк.
     * @return string Тип колонки в БД
     */
    public static function GetDBColumnType()
    {
        global $DB;
        switch (strtolower($DB->type)) {
            case "mysql":
                return "text";
            case "oracle":
                return "varchar2(4000 char)";
            case "mssql":
                return "varchar(4000)";
        }
        return "text";
    }

    /**
     * Обрабатывает значение поля перед сохранением в базу данных.
     * Преобразует массив ['key' => '...', 'value' => '...'] в JSON-строку.
     * 
     * @param array $arUserField Массив с описанием пользовательского поля.
     * @param mixed $value Значение поля, пришедшее из формы (ожидается массив ['key' => '...', 'value' => '...']).
     * @return string JSON-строка или пустая строка для сохранения в БД.
     */
    public static function OnBeforeSave($arUserField, $value)
    {
        if (is_array($value) && (isset($value['key']) || isset($value['value']))) {
            $filteredValue = [
                'key'   => trim((string)($value['key'] ?? '')),
                'value' => trim((string)($value['value'] ?? ''))
            ];

            if (empty($filteredValue['key']) && empty($filteredValue['value'])) {
                return '';
            }

            return json_encode($filteredValue, JSON_UNESCAPED_UNICODE);
        }

        return '';
    }

    /**
     * Обрабатывает значение поля после извлечения из базы данных.
     * Преобразует JSON-строку (или PHP-сериализованный массив из старых данных) обратно в PHP-массив
     * ['key' => '...', 'value' => '...'].
     *
     * @param array $userfield Массив с описанием пользовательского поля.
     * @param array $fetched Массив с данными, извлеченными из БД, включая 'VALUE' и 'VALUE_RAW'.
     * @return array PHP-массив ['key' => '...', 'value' => '...'] или пустой массив.
     */
    public static function onAfterFetch($userfield, $fetched)
    {
        $value    = $fetched['VALUE'];
        $rawValue = $fetched['VALUE_RAW'];

        // Оптимизация: сначала проверяем JSON, так как это наиболее вероятный формат данных
        if (is_string($value) && !empty($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        if (is_array($value) && (isset($value['key']) || isset($value['value']))) {
            return $value;
        }

        if (is_string($rawValue) && !empty($rawValue)) {
            // Безопасное десериализация с проверкой ошибок
            $phpDecoded = @unserialize($rawValue);
            if ($phpDecoded !== false && is_array($phpDecoded)) {
                if (isset($phpDecoded['key']) || isset($phpDecoded['value'])) {
                    return $phpDecoded;
                }
                $filtered = [];
                foreach ($phpDecoded as $item) {
                    if (is_array($item) && (isset($item['key']) || isset($item['value']))) {
                        $filtered[] = $item;
                    } elseif (is_string($item) && $item === 'Array') {
                        $filtered[] = ['key' => '', 'value' => ''];
                    }
                }
                if (!empty($filtered)) {
                    return $filtered;
                }
            }
        }

        return [];
    }

    /**
     * Генерирует HTML для отображения поля в форме редактирования.
     * Создает текстовое поле для заголовка и HTML-редактор для содержимого.
     *
     * @param array $arUserField Массив с описанием пользовательского поля.
     * @param array $arHtmlControl Массив с элементами управления HTML, включая 'NAME' и 'VALUE'.
     * @return string HTML-код поля.
     */
    public static function GetEditFormHTML($arUserField, $arHtmlControl)
    {
        Loc::loadMessages(__FILE__);

        $fieldName     = $arHtmlControl['NAME'];
        $currentValues = $arHtmlControl['VALUE'];

        // Подготовка значений
        $key   = '';
        $value = '';
        if (is_array($currentValues)) {
            $key   = htmlspecialchars($currentValues['key'] ?? '');
            $value = $currentValues['value'] ?? '';
        }

        // Идентификатор и имя для редактора
        $editorName = $fieldName . '[value]';
        // Оптимизация: используем более эффективный способ генерации ID
        // Генерируем уникальный ID для редактора, заменяем скобки на подчёркивания
        $editorIdRaw = str_replace(['[', ']'], '_', $editorName);
        $editorId = CUtil::JSEscape($editorIdRaw);

        ob_start();
        ?>
        <div class="qwelp-key-value-wrapper">
            <div class="qwelp-key-value-item">
                <input
                        type="text"
                        name="<?= htmlspecialchars($fieldName) ?>[key]"
                        value="<?= $key ?>"
                        placeholder="<?= Loc::getMessage('QWELP_HTML_BLOCK_TITLE_PLACEHOLDER') ?>"
                        class="adm-input"
                />
            </div>
            <div class="qwelp-key-value-item">
                <?php
                // Создаем экземпляр HTML-редактора Bitrix и вызываем метод Show
                $editor = new CHTMLEditor();
                $editor->Show([
                    'id'           => $editorId,
                    'inputId'      => $editorId,
                    'inputName'    => $editorName,
                    'width'        => '100%',
                    'height'       => '200px',
                    'bAllowPhp'    => false,
                    'bResizable'   => true,
                    'toolbarConfig'=> 'standard',
                    'content'      => $value,
                ]);
                ?>
            </div>
        </div>
        <?php
        $html = ob_get_clean();
        return $html;
    }
}
