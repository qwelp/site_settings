<?php
/**
 * Файл для подключения интерактивного контрола управления вариантами значений
 * в административном интерфейсе Битрикс
 * 
 * @package qwelp.site_settings
 */

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

// Проверяем, что скрипт вызван из административного интерфейса Битрикс
if (!defined('ADMIN_SECTION') || ADMIN_SECTION !== true) {
    return;
}

// Проверяем, что мы находимся на странице редактирования элемента инфоблока
$curPage = $GLOBALS['APPLICATION']->GetCurPage();
if (strpos($curPage, '/bitrix/admin/iblock_element_edit.php') === false) {
    return;
}

// Проверяем, что редактируется элемент нашего инфоблока
$iblockId = (int)$_REQUEST['IBLOCK_ID'];
$iblockType = $_REQUEST['type'] ?? '';

if ($iblockType !== 'site_settings' && $iblockId <= 0) {
    // Пытаемся определить тип инфоблока по его ID
    if ($iblockId > 0) {
        $iblock = \CIBlock::GetByID($iblockId)->Fetch();
        if ($iblock && $iblock['IBLOCK_TYPE_ID'] !== 'site_settings') {
            return;
        }
    } else {
        return;
    }
}

// Подключаем наши файлы
$moduleDir = '/bitrix/modules/qwelp.site_settings/admin/';
$GLOBALS['APPLICATION']->SetAdditionalCSS($moduleDir . 'options_control.css');
$GLOBALS['APPLICATION']->AddHeadScript($moduleDir . 'options_control.js');

// Добавляем языковые константы для JavaScript
echo '<script>
    var OPTIONS_CONTROL_MESSAGES = {
        VALUE: "' . Loc::getMessage('QWELP_SITE_SETTINGS_JS_VALUE') . '",
        LABEL: "' . Loc::getMessage('QWELP_SITE_SETTINGS_JS_LABEL') . '",
        PATH_TO_FILE: "' . Loc::getMessage('QWELP_SITE_SETTINGS_JS_PATH_TO_FILE') . '",
        ADD_FILE: "' . Loc::getMessage('QWELP_SITE_SETTINGS_JS_ADD_FILE') . '",
        PREVIEW: "' . Loc::getMessage('QWELP_SITE_SETTINGS_JS_PREVIEW') . '",
        DELETE: "' . Loc::getMessage('QWELP_SITE_SETTINGS_JS_DELETE') . '",
        ADD_ROW: "' . Loc::getMessage('QWELP_SITE_SETTINGS_JS_ADD_ROW') . '",
        UPLOAD_ERROR: "' . Loc::getMessage('QWELP_SITE_SETTINGS_JS_UPLOAD_ERROR') . '",
        FETCH_ERROR: "' . Loc::getMessage('QWELP_SITE_SETTINGS_JS_FETCH_ERROR') . '",
        DELETE_ERROR: "' . Loc::getMessage('QWELP_SITE_SETTINGS_JS_DELETE_ERROR') . '"
    };
</script>';

// Добавляем отладочный скрипт
echo '<script>console.log("' . Loc::getMessage('QWELP_SITE_SETTINGS_OPTIONS_CONTROL_FILES_INCLUDED') . '");</script>';
