<?php
/**
 * Компонент панели настроек сайта (точка входа)
 *
 * @package qwelp.site_settings
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var CBitrixComponent $this */
CBitrixComponent::includeComponentClass($this->__name);

$this->getComponent()->executeComponent();