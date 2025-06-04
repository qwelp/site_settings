<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

// Отключаем статистику и проверки прав
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

// Подключаем ядро D7
require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';

// Определяем константу для пропорционального изменения размера изображения
if (!defined('BX_RESIZE_IMAGE_PROPORTIONAL')) {
    define('BX_RESIZE_IMAGE_PROPORTIONAL', 1);
}

use CFile;

Loc::loadMessages(__FILE__);

header('Content-Type: application/json; charset=utf-8');

try {
    // Проверяем наличие загруженного файла
    if (
        empty($_FILES['file']) ||
        $_FILES['file']['error'] !== UPLOAD_ERR_OK ||
        !is_uploaded_file($_FILES['file']['tmp_name'])
    ) {
        throw new \Exception(Loc::getMessage('QWELP_SITE_SETTINGS_UPLOAD_FILE_NOT_SENT'));
    }

    // Сохраняем оригинал
    $savedFileId = CFile::SaveFile(
        $_FILES['file'],
        'qwelp.site_settings/options_control'
    );
    if (!$savedFileId) {
        throw new \Exception(Loc::getMessage('QWELP_SITE_SETTINGS_UPLOAD_SAVE_ERROR'));
    }

    // Генерируем превью 100×100
    $thumb = CFile::ResizeImageGet(
        $savedFileId,
        ['width' => 100, 'height' => 100],
        BX_RESIZE_IMAGE_PROPORTIONAL,
        true
    );
    if (empty($thumb['src'])) {
        throw new \Exception(Loc::getMessage('QWELP_SITE_SETTINGS_UPLOAD_PREVIEW_ERROR'));
    }

    // Возвращаем JSON с ID и URL миниатюры
    echo json_encode([
        'status'  => Loc::getMessage('QWELP_SITE_SETTINGS_UPLOAD_SUCCESS'),
        'fileId'  => (int)$savedFileId,
        'fileUrl' => $thumb['src'],
    ]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => Loc::getMessage('QWELP_SITE_SETTINGS_UPLOAD_ERROR'),
        'message' => $e->getMessage(),
    ]);
}

die();
