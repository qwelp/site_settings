<?php
namespace Qwelp\SiteSettings\UserType; // Изменено с Property на UserType для консистентности с KeyValueUserType

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserField\TypeBase;
use Bitrix\Main\Text\HtmlFilter;
use CUserTypeManager; // Добавляем use для CUserTypeManager
use CHTMLEditor;    // Добавляем use для CHTMLEditor

/**
 * Класс пользовательского типа поля "HTML-блок (Заголовок + Текст с редактором)" для Bitrix D7.
 * Предназначен для сохранения и отображения пары: текстовый заголовок и HTML-содержимое
 * с использованием встроенного HTML-редактора Bitrix.
 *
 * Данные хранятся в базе данных в формате JSON-строки.
 * Наследуется от TypeBase для базовой функциональности пользовательских полей.
 */
class HtmlBlockType extends TypeBase
{
    const USER_TYPE_ID = 'qwelp_html_block';

    /**
     * Возвращает описание пользовательского типа поля.
     * @return array
     */
    public static function getUserTypeDescription(): array
    {
        Loc::loadMessages(__FILE__); // Загружаем языковые фразы для текущего файла

        return [
            'USER_TYPE_ID' => static::USER_TYPE_ID,
            'CLASS_NAME' => __CLASS__,
            'DESCRIPTION' => Loc::getMessage('QWELP_HTML_BLOCK_DESCRIPTION'),
            'BASE_TYPE' => CUserTypeManager::BASE_TYPE_STRING, // Хранится как строка (JSON)
        ];
    }

    /**
     * Определяет тип колонки в базе данных для хранения значения.
     * Используется 'text' для возможности хранения длинных JSON-строк.
     * @return string
     */
    public static function getDbColumnType(): string
    {
        return 'text'; // MySQL TEXT тип для строки до 65,535 символов
    }

    /**
     * Подготавливает и возвращает настройки поля.
     * @param array $userField Текущие настройки поля.
     * @return array Обработанные настройки поля.
     */
    public static function prepareSettings(array $userField): array
    {
        $height = (int)($userField['SETTINGS']['height'] ?? 200);
        return ['height' => ($height < 200 ? 200 : $height)]; // Минимальная высота 200px
    }

    /**
     * Возвращает HTML для формы настройки поля в административной части.
     * Позволяет настроить высоту HTML-редактора.
     *
     * @param array|false $userField   Массив с описанием пользовательского поля (false при первом создании).
     * @param array $htmlControl       Массив с элементами управления HTML.
     * @param bool $bVarsFromForm      Флаг, указывающий, были ли настройки взяты из формы.
     * @return string HTML-код формы настроек.
     */
    public static function getSettingsHTML($userField = false, $htmlControl, $bVarsFromForm): string
    {
        Loc::loadMessages(__FILE__); // Загружаем языковые фразы

        $height = 200;
        if ($bVarsFromForm) {
            $height = (int)($GLOBALS[$htmlControl['NAME']]['height'] ?? $height);
        } elseif (is_array($userField)) {
            $height = (int)($userField['SETTINGS']['height'] ?? $height);
        }
        return '<tr><td>' . Loc::getMessage('USER_TYPE_HTML_HEIGHT') . ':</td><td><input type="text" name="' . $htmlControl['NAME'] . '[height]" size="10" maxlength="10" value="' . $height . '"></td></tr>';
    }

    /**
     * Обрабатывает значение поля перед сохранением в базу данных.
     * Сериализует массив ['title' => '...', 'html' => '...'] в JSON-строку.
     * Метод должен быть статическим.
     *
     * @param array $userField Описание пользовательского поля.
     * @param mixed $value     Значение поля (для этого типа поля, это значение текстового input для title).
     * @return string          JSON-строка или пустая строка для сохранения в БД.
     */
    public static function onBeforeSave(array $userField, $value): string
    {
        $titleValue = is_string($value) ? trim($value) : '';
        // Значение HTML-редактора приходит в отдельном $_REQUEST переменной.
        $htmlValue = $_REQUEST[$userField['FIELD_NAME'] . '_html'] ?? '';

        // Если оба поля (заголовок и HTML) пустые, сохраняем пустую строку.
        if (empty($titleValue) && empty($htmlValue)) {
            return '';
        }

        $dataToSave = [
            'title' => $titleValue,
            'html' => $htmlValue,
        ];

        // Кодируем данные в JSON.
        return json_encode($dataToSave, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Обрабатывает значение поля после извлечения из базы данных.
     * Десериализует JSON-строку (или PHP-сериализованные данные для совместимости)
     * обратно в PHP-массив ['title' => '...', 'html' => '...'].
     * Метод должен быть статическим.
     *
     * @param array $userField Описание пользовательского поля.
     * @param array $fetched   Массив с данными, извлеченными из БД, включая 'VALUE' и 'VALUE_RAW'.
     * @return array           Десериализованный массив ['title' => '...', 'html' => '...'] или пустой массив.
     */
    public static function onAfterFetch(array $userField, array $fetched): array
    {
        $value = $fetched['VALUE'];
        $rawValue = $fetched['VALUE_RAW'];

        // Попытка 1: Декодировать как JSON из $value
        if (is_string($value) && !empty($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        // Попытка 2: Если $value уже является массивом (может быть результатом внутренней десериализации Bitrix из PHP-сериализации)
        if (is_array($value) && (isset($value['title']) || isset($value['html']))) {
            return $value;
        }

        // Попытка 3: Десериализовать из $rawValue как PHP-массив (для старых/некорректных данных)
        if (is_string($rawValue) && !empty($rawValue)) {
            $unserialized = @unserialize(htmlspecialchars_decode($rawValue), ['allowed_classes' => false]);
            if ($unserialized !== false && is_array($unserialized) && isset($unserialized['title'], $unserialized['html'])) {
                return $unserialized;
            }
        }

        // Если ничего не удалось или данные некорректны, возвращаем пустой массив.
        return ['title' => '', 'html' => ''];
    }

    /**
     * Возвращает HTML для формы редактирования поля.
     * Метод должен быть статическим.
     *
     * @param array $userField   Описание пользовательского поля.
     * @param array $htmlControl Массив с элементами управления HTML, включая 'NAME' и 'VALUE'.
     * @return string HTML-код поля.
     */
    public static function getEditFormHTML(array $userField, array $htmlControl): string
    {
        Loc::loadMessages(__FILE__); // Загружаем языковые фразы

        // $htmlControl['VALUE'] содержит десериализованные данные из onAfterFetch
        $data = $htmlControl['VALUE'];

        // Инициализируем данные, если они отсутствуют или некорректны
        if (!is_array($data) || (!isset($data['title']) && !isset($data['html']))) {
            $data = ['title' => '', 'html' => ''];
        }

        // Если данные были отправлены через форму (при перегрузке страницы после ошибки),
        // используем их для сохранения введенных значений.
        if (isset($_REQUEST[$htmlControl['NAME']]) || isset($_REQUEST[$userField['FIELD_NAME'] . '_html'])) {
            // Берем значение из request, если оно есть, иначе текущее из $data
            $data['title'] = $_REQUEST[$htmlControl['NAME']] ?? $data['title'];
            $data['html'] = $_REQUEST[$userField['FIELD_NAME'] . '_html'] ?? $data['html'];
        }

        $title = htmlspecialcharsbx($data['title']); // Экранируем заголовок для вывода
        $htmlValue = $data['html']; // HTML-значение не экранируем, оно будет в редакторе

        // Имена полей ввода
        $titleControlName = $htmlControl['NAME'];
        $htmlControlName = $userField['FIELD_NAME'] . '_html'; // Отдельное имя для HTML-редактора

        // ID для HTML-редактора
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
                // Проверяем, что класс CHTMLEditor существует перед использованием
                if (class_exists(CHTMLEditor::class)) {
                    $LHE = new CHTMLEditor;
                    $LHE->Show([
                        'name' => $htmlControlName,
                        'id' => $htmlEditorId,
                        'width' => '100%',
                        'height' => $userField['SETTINGS']['height'] ?? 200,
                        'content' => $htmlValue,
                        'bAllowPhp' => false,
                        'toolbarConfig' => false, // <-- Отключаем стандартные конфигурации тулбара (скрывает сниппеты)
                    ]);
                } else {
                    // Альтернатива, если HTML-редактор недоступен (например, в старых версиях Bitrix)
                    echo '<textarea name="' . htmlspecialchars($htmlControlName) . '" style="width: 100%; height:' . ($userField['SETTINGS']['height'] ?? 200) . 'px;">' . htmlspecialchars($htmlValue) . '</textarea>';
                }
                ?>
            </div>
        </div>
        <script>
            // JS-код для сохранения содержимого HTML-редактора при отправке формы
            (function() {
                // Ищем форму по имени (например, form1 для элементов) или по классу/тегу
                var form = document.forms['form1'] || document.querySelector('form[name^="form_"]');
                if (form) {
                    BX.bind(form, 'submit', function() {
                        var editor = BX.admin.CEditor.Get('<?= \CUtil::JSEscape($htmlEditorId) ?>');
                        if (editor && editor.IsShown()) { // Проверяем, что редактор показан
                            editor.SaveContent(); // Сохраняем содержимое редактора в скрытое поле
                        }
                    });
                }
            })();
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Возвращает HTML для отображения поля в списке административной части (например, список элементов инфоблока).
     * @param array $userField   Описание пользовательского поля.
     * @param array|null $htmlControl Массив с элементами управления HTML, включая 'VALUE'.
     * @return string           HTML-код для отображения значения.
     */
    public static function getAdminListViewHTML(array $userField, ?array $htmlControl): string
    {
        $value = $htmlControl['VALUE'] ?? '';

        // $value здесь уже будет десериализованным массивом ['title' => '...', 'html' => '...'] из onAfterFetch
        if (is_array($value) && isset($value['title']) && !empty($value['title'])) {
            return htmlspecialcharsbx($value['title']); // Отображаем только заголовок, экранированный
        }

        return ' '; // Неразрывный пробел, если значение пустое
    }

    /**
     * Возвращает HTML для поля фильтра в административной части.
     * Позволяет фильтровать по заголовку блока.
     * @param array $userField   Описание пользовательского поля.
     * @param array $htmlControl Массив с элементами управления HTML.
     * @return string           HTML-код поля фильтра.
     */
    public static function getFilterHTML(array $userField, array $htmlControl): string
    {
        return '<input type="text" name="' . htmlspecialchars($htmlControl['NAME']) . '" size="20" value="">';
    }

    /**
     * Проверяет значения поля перед сохранением.
     * В данном примере валидация не реализована, метод возвращает пустой массив ошибок.
     * @param array $userField Описание пользовательского поля.
     * @param mixed $value     Значение поля.
     * @return array           Массив сообщений об ошибках.
     */
    public static function checkFields(array $userField, $value): array
    {
        return [];
    }

    // Метод convertFromDB удален, т.к. onAfterFetch полностью покрывает логику десериализации,
    // и его наличие могло вызывать дублирование или конфликты.
}