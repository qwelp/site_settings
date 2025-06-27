<?php

namespace Qwelp\SiteSettings\UserType;

use Bitrix\Main\Localization\Loc;

/**
 * Класс пользовательского типа поля "Ключ-значение" для Bitrix D7.
 * Предназначен для сохранения и отображения пар ключ-значение в одном поле.
 * Сохраняет данные в формате JSON-строки.
 *
 * Примечание: Данный UserType предназначен для использования с множественным полем (MULTIPLE = Y),
 * где Bitrix сам управляет добавлением/удалением строк значений, а каждая строка содержит
 * пару "ключ-значение".
 */
class KeyValueUserType
{
    /**
     * Возвращает описание пользовательского типа поля.
     * @return array
     */
    public static function GetUserTypeDescription()
    {
        // Загружаем языковые фразы для текущего файла
        Loc::loadMessages(__FILE__);

        return array(
            "USER_TYPE_ID" => 'QWELP_SECTION_KEY_VALUE',
            "CLASS_NAME" => __CLASS__,
            // Используем языковую фразу для описания
            "DESCRIPTION" => Loc::getMessage('QWELP_SITE_SETTINGS_USER_TYPE_KEY_VALUE_DESCRIPTION'),
            "BASE_TYPE" => \CUserTypeManager::BASE_TYPE_STRING
        );
    }

    /**
     * Определяет тип колонки в базе данных для хранения значения.
     * Используется 'text' для возможности хранения длинных JSON-строк.
     * @return string
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
     * Метод должен быть статическим, чтобы Bitrix его вызывал.
     *
     * @param array $arUserField Массив с описанием пользовательского поля.
     * @param mixed $value       Значение поля, пришедшее из формы (ожидается массив ['key' => '...', 'value' => '...']).
     * @return string            JSON-строка или пустая строка для сохранения в БД.
     */
    public static function OnBeforeSave($arUserField, $value)
    {
        if (is_array($value) && (isset($value['key']) || isset($value['value']))) {
            $filteredValue = [
                'key' => trim((string)($value['key'] ?? '')),
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
     * Метод должен быть статическим, чтобы Bitrix его вызывал.
     *
     * @param array $userfield Массив с описанием пользовательского поля.
     * @param array $fetched   Массив с данными, извлеченными из БД, включая 'VALUE' и 'VALUE_RAW'.
     * @return array           PHP-массив ['key' => '...', 'value' => '...'] или пустой массив.
     */
    public static function onAfterFetch($userfield, $fetched)
    {
        $value = $fetched["VALUE"];
        $rawValue = $fetched["VALUE_RAW"];

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
            $phpDecoded = @unserialize($rawValue);
            if ($phpDecoded !== false && is_array($phpDecoded)) {
                if (isset($phpDecoded['key']) || isset($phpDecoded['value'])) {
                    return $phpDecoded;
                }
                $filtered = [];
                foreach ($phpDecoded as $item) {
                    if (is_array($item) && (isset($item['key']) || isset($item['value']))) {
                        $filtered[] = $item;
                    } else if (is_string($item) && $item === "Array") {
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
     * Поскольку поле множественное, этот метод вызывается для каждого значения.
     * Метод должен быть статическим, чтобы Bitrix его вызывал.
     *
     * @param array $arUserField  Массив с описанием пользовательского поля.
     * @param array $arHtmlControl Массив с элементами управления HTML, включая 'NAME' и 'VALUE'.
     * @return string HTML-код поля.
     */
    public static function GetEditFormHTML($arUserField, $arHtmlControl)
    {
        // Загружаем языковые фразы для текущего файла
        Loc::loadMessages(__FILE__);

        $fieldName = $arHtmlControl["NAME"];
        $currentValues = $arHtmlControl['VALUE'];

        ob_start();
        ?>
        <div class="qwelp-key-value-wrapper">
            <div class="qwelp-key-value-item">
                <?php
                $key = '';
                $value = '';
                if (is_array($currentValues)) {
                    $key = htmlspecialchars($currentValues['key'] ?? '');
                    $value = htmlspecialchars($currentValues['value'] ?? '');
                }
                ?>
                <input type="text" name="<?= htmlspecialchars($fieldName) ?>[key]" value="<?= $key ?>" placeholder="<?= Loc::getMessage('QWELP_SITE_SETTINGS_USER_TYPE_KEY_VALUE_PLACEHOLDER_KEY') ?>" class="adm-input" />
                <input type="text" name="<?= htmlspecialchars($fieldName) ?>[value]" value="<?= $value ?>" placeholder="<?= Loc::getMessage('QWELP_SITE_SETTINGS_USER_TYPE_KEY_VALUE_PLACEHOLDER_VALUE') ?>" class="adm-input" />
            </div>
        </div>
        <?php
        $html = ob_get_clean();
        return $html;
    }
}
