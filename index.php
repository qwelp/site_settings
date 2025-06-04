<?php
require_once __DIR__.'/local/templates/.default/header.php';

if (class_exists('CMain')) {
    global $APPLICATION;
}

$APPLICATION->IncludeComponent(
    'qwelp:site.settings',
    '',
    []
);

require_once __DIR__.'/local/templates/.default/footer.php';
?>
