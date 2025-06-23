<?php
/**
 * Рендерер для одного элемента настройки
 * Использует переменные из области видимости вызывающего скрипта
 * @var array $setting Данные настройки
 * @var CBitrixComponentTemplate $template Контекст родительского шаблона
 * @var bool|null $isHeaderRender Флаг для рендеринга в компактном режиме (в заголовке)
 * @var string|null $commonRadioName Имя для общей группы радио-кнопок
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

$code = $setting['code'];
$type = $setting['type'];
if ($type === '' && isset($setting['options']['color'])) {
    $type = 'color';
}
$width = qwelpSiteSettingsWidth($setting['percent'] ?? null);
$showLabel = !empty($setting['label']);
$isHeader = !empty($isHeaderRender);

// Определяем, используется ли механизм скрытого чекбокса
$hasHiddenCheckbox = !$isHeader && !empty($setting['hiddenCheckbox']);

// Теперь функция renderSettingControl() вызывается из functions.php, а здесь мы её не объявляем

if ($isHeader) {
    // Рендеринг для заголовка остается без изменений
    ?>
    <div class="header-control header-control--type-<?= htmlspecialcharsbx($type) ?>" data-setting-code="<?= htmlspecialcharsbx($code) ?>">
        <?php if ($showLabel): ?>
            <span class="header-control__label-text" title="<?= htmlspecialcharsbx($setting['label']) ?>"><?= htmlspecialcharsbx($setting['label']) ?></span>
        <?php endif; ?>

        <div class="header-control__control-wrapper">
            <?php if ($type === 'checkbox'): ?>
                <label class="toggle-switch">
                    <input type="checkbox"
                           id="setting_<?= htmlspecialcharsbx($code) ?>_header"
                           name="<?= htmlspecialcharsbx($code) ?>"
                           class="toggle-switch__input"
                           data-code="<?= htmlspecialcharsbx($code) ?>"
                           data-is-header-control="true"
                    >
                    <span class="toggle-switch__slider"></span>
                </label>
            <?php elseif ($type === 'select' && is_array($setting['options'])): ?>
                <div class="select-wrapper select-wrapper--header">
                    <select name="<?= htmlspecialcharsbx($code) ?>"
                            id="setting_<?= htmlspecialcharsbx($code) ?>_header"
                            class="custom-select custom-select--header"
                            data-code="<?= htmlspecialcharsbx($code) ?>"
                            data-is-header-control="true"
                    >
                        <?php foreach ($setting['options'] as $opt): ?>
                            <option value="<?= htmlspecialcharsbx($opt['value'] ?? '') ?>">
                                <?= htmlspecialcharsbx($opt['label'] ?? $opt['value']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
} else {
    // Основной рендеринг
    ?>
    <div class="setting-item setting-item--type-<?= htmlspecialcharsbx($type) ?>"
         data-setting-code="<?= htmlspecialcharsbx($code) ?>"
         data-setting-type="<?= htmlspecialcharsbx($type) ?>"
         <?php if ($hasHiddenCheckbox): ?>data-has-hidden-checkbox="true"<?php endif; ?>
         style="flex-basis: <?= htmlspecialcharsbx($width) ?>; max-width: <?= htmlspecialcharsbx($width) ?>;">
        <?php if ($showLabel): ?>
            <div class="setting-item__label">
                <?= htmlspecialcharsbx($setting['label']) ?>
                <?php if (!empty($setting['helpText']) || !empty($setting['helpImage'])): ?>
                    <span class="help-icon"
                          data-help-text="<?= htmlspecialcharsbx($setting['helpText']) ?>"
                          data-help-image="<?= htmlspecialcharsbx($setting['helpImage']) ?>"
                          title="<?= Loc::getMessage('QWELP_SITE_SETTINGS_HELP_ICON_TITLE') ?>">?</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="setting-item__control">
            <?php if ($hasHiddenCheckbox): ?>
                <?php
                $defaultValue = $setting['options'][0]['value'] ?? ($type === 'color' ? '#000000' : '');
                $contentId = 'hidden_content_' . htmlspecialcharsbx($code);
                ?>
                <label class="toggle-switch">
                    <input type="checkbox"
                           id="enabler_<?= htmlspecialcharsbx($code) ?>"
                           class="hidden-checkbox-enabler"
                           data-code="<?= htmlspecialcharsbx($code) ?>"
                           data-default-value="<?= htmlspecialcharsbx($defaultValue) ?>"
                           data-controls-id="<?= $contentId ?>"
                    >
                    <span class="toggle-switch__slider"></span>
                </label>
                <div class="hidden-checkbox-content" id="<?= $contentId ?>">
                    <?php
                    $innerSetting = $setting;
                    renderSettingControl($type, $innerSetting, $commonRadioName);
                    ?>
                </div>
            <?php else: ?>
                <?php renderSettingControl($type, $setting, $commonRadioName); ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
}