<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Новая страница");
$APPLICATION->SetPageProperty("description", "Текст твоего description для этой страницы");

// Только для отладочных целей!

if (!\Bitrix\Main\Loader::includeModule('qwelp.site_settings')) {
	die('Модуль qwelp.site_settings не установлен');
}

echo "<pre>";
print_r(\Qwelp\SiteSettings\OptionsManager::getSectionTechData('header-1'));
echo "</pre>";

echo '<pre>';
print_r(\Qwelp\SiteSettings\OptionsManager::getAll());
echo '</pre>';
?>
<div class="container">
	<?php $APPLICATION->IncludeComponent(
		"qwelp:site.settings",
		"",
		Array(
			"AJAX_MODE" => "N",
			"CACHE_TIME" => "3600",
			"CACHE_TYPE" => "A",
			"SITE_ID" => "s2"
		)
	);?>
</div>
<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>