<?php
/**
 * Шаг удаления модуля qwelp.site_settings
 * 
 * @package qwelp.site_settings
 */

use Bitrix\Main\Localization\Loc;

global $APPLICATION;

if (!check_bitrix_sessid()) {
    return;
}

Loc::loadMessages(__FILE__);

if ($ex = $APPLICATION->GetException()) {
    CAdminMessage::ShowMessage([
        'TYPE' => 'ERROR',
        'MESSAGE' => Loc::getMessage('QWELP_SITE_SETTINGS_UNINSTALL_ERROR'),
        'DETAILS' => $ex->GetString(),
        'HTML' => true
    ]);
} else {
    CAdminMessage::ShowMessage([
        'TYPE' => 'OK',
        'MESSAGE' => Loc::getMessage('QWELP_SITE_SETTINGS_UNINSTALL_SUCCESS'),
        'DETAILS' => Loc::getMessage('QWELP_SITE_SETTINGS_UNINSTALL_BACK'),
        'HTML' => true
    ]);
}
?>

<form action="<?= $APPLICATION->GetCurPage(); ?>">
    <input type="hidden" name="lang" value="<?= $_REQUEST['lang'] ?? 'ru'; ?>">
    <input type="submit" value="<?= Loc::getMessage('QWELP_SITE_SETTINGS_UNINSTALL_BACK_BUTTON'); ?>">
</form>