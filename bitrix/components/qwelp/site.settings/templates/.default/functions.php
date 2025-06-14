<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

if (!function_exists('qwelpSiteSettingsWidth')) {
    /**
     * Нормализует значение ширины в процентах для flex-элементов.
     * @param string|null $value
     * @return string
     */
    function qwelpSiteSettingsWidth(?string $value): string
    {
        if ($value === null || $value === '') {
            return '100%';
        }
        $value = trim($value);
        if ($value === '') {
            return '100%';
        }
        if (preg_match('/^\d+(?:\.\d+)?$/', $value)) {
            return $value . '%';
        }
        return $value;
    }
}