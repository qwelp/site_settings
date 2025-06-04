<?php
// local/modules/qwelp.site_settings/delete.php

use Bitrix\Main\Localization\Loc;

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
use CFile;

Loc::loadMessages(__FILE__);

header('Content-Type: application/json; charset=utf-8');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $fileId = isset($input['fileId']) ? (int)$input['fileId'] : 0;
    if ($fileId <= 0 && isset($_POST['fileId'])) {
        $fileId = (int)$_POST['fileId'];
    }

    if ($fileId > 0) {
        // Пытаемся удалить — но даже если вернётся false, мы не считаем это фатальной ошибкой
        @CFile::Delete($fileId);
    }

    echo json_encode(['status' => Loc::getMessage('QWELP_SITE_SETTINGS_DELETE_SUCCESS')]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => Loc::getMessage('QWELP_SITE_SETTINGS_DELETE_ERROR'),
        'message' => $e->getMessage(),
    ]);
}

die();
