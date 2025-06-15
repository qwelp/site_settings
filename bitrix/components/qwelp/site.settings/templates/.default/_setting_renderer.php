<?php
/**
 * Рендерер для одного элемента настройки
 * Использует переменные из области видимости вызывающего скрипта
 * @var array $setting Данные настройки
 * @var CBitrixComponentTemplate $template Контекст родительского шаблона
 * @var bool|null $isHeaderRender Флаг для рендеринга в компактном режиме (в заголовке)
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

// Определяем базовые переменные
$code = $setting['code'];
$type = $setting['type'];
if ($type === '' && isset($setting['options']['color'])) {
    $type = 'color';
}
$width = qwelpSiteSettingsWidth($setting['percent'] ?? null);
$showLabel = !empty($setting['label']);

$isHeader = !empty($isHeaderRender);

if ($isHeader) {
    // ==================================================================
    // РЕЖИМ КОМПАКТНОГО РЕНДЕРИНГА (ДЛЯ ЗАГОЛОВКА ГРУППЫ)
    // ==================================================================
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
            <?php else: ?>
                <?php /* Неподдерживаемый для хедера тип */ ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
} else {
    // ==================================================================
    // СТАНДАРТНЫЙ РЕЖИМ РЕНДЕРИНГА
    // ==================================================================
    ?>
    <div class="setting-item setting-item--type-<?= htmlspecialcharsbx($type) ?>"
         data-setting-code="<?= htmlspecialcharsbx($code) ?>"
         data-setting-type="<?= htmlspecialcharsbx($type) ?>"
         style="flex-basis: <?= htmlspecialcharsbx($width) ?>; max-width: <?= htmlspecialcharsbx($width) ?>;"
    >
        <?php if ($showLabel): ?>
            <div class="setting-item__label">
                <?= htmlspecialcharsbx($setting['label']) ?>
                <?php if (!empty($setting['helpText']) || !empty($setting['helpImage'])): ?>
                    <span class="help-icon"
                          data-help-text="<?= htmlspecialcharsbx($setting['helpText']) ?>"
                          data-help-image="<?= htmlspecialcharsbx($setting['helpImage']) ?>"
                          title="<?= Loc::getMessage('QWELP_SITE_SETTINGS_HELP_ICON_TITLE') ?>">
                    ?
                </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="setting-item__control">
            <?php if ($type === 'checkbox'): ?>
                <label class="toggle-switch">
                    <input type="checkbox"
                           id="setting_<?= htmlspecialcharsbx($code) ?>"
                           name="<?= htmlspecialcharsbx($code) ?>"
                           class="toggle-switch__input"
                           data-code="<?= htmlspecialcharsbx($code) ?>"
                    >
                    <span class="toggle-switch__slider"></span>
                </label>

            <?php elseif ($type === 'radio' && is_array($setting['options'])): ?>
                <div class="radio-pills">
                    <?php foreach ($setting['options'] as $opt): ?>
                        <?php
                        $optValue = htmlspecialcharsbx($opt['value'] ?? '');
                        $optLabel = htmlspecialcharsbx($opt['label'] ?? $optValue);

                        if (isset($isCommonGroup) && $isCommonGroup) {
                            $inputName = $commonRadioName;
                            $dataCode = $commonRadioName;
                            $dataOwnerCode = 'data-owner-code="' . htmlspecialcharsbx($code) . '"';
                            $optId = 'setting_' . $inputName . '_' . htmlspecialcharsbx($code) . '_' . preg_replace('/[^a-z0-9_]/i', '_', $optValue);
                        } else {
                            $inputName = htmlspecialcharsbx($code);
                            $dataCode = htmlspecialcharsbx($code);
                            $dataOwnerCode = '';
                            $optId = 'setting_' . $inputName . '_' . preg_replace('/[^a-z0-9_]/i', '_', $optValue);
                        }
                        ?>
                        <input type="radio"
                               id="<?= $optId ?>"
                               name="<?= $inputName ?>"
                               value="<?= $optValue ?>"
                               class="radio-pills__input"
                               data-code="<?= $dataCode ?>"
                            <?= $dataOwnerCode ?>
                        >
                        <label for="<?= $optId ?>" class="radio-pills__label"><?= $optLabel ?></label>
                    <?php endforeach; ?>
                </div>

            <?php elseif ($type === 'text'): ?>
                <input type="text"
                       id="setting_<?= htmlspecialcharsbx($code) ?>"
                       name="<?= htmlspecialcharsbx($code) ?>"
                       class="text-input"
                       value="<?= htmlspecialcharsbx($setting['value'] ?? '') ?>"
                       data-code="<?= htmlspecialcharsbx($code) ?>"
                >

            <?php elseif ($type === 'select' && is_array($setting['options'])): ?>
                <div class="select-wrapper">
                    <select name="<?= htmlspecialcharsbx($code) ?>"
                            id="setting_<?= htmlspecialcharsbx($code) ?>"
                            class="custom-select"
                            data-code="<?= htmlspecialcharsbx($code) ?>"
                    >
                        <?php foreach ($setting['options'] as $opt): ?>
                            <option value="<?= htmlspecialcharsbx($opt['value'] ?? '') ?>">
                                <?= htmlspecialcharsbx($opt['label'] ?? $opt['value']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            <?php elseif ($type === 'color'): ?>
                <?php
                $colorOpts = $setting['options']['color'] ?? ($setting['options'] ?? []);
                $nativePickerId = 'native_picker_' . htmlspecialcharsbx($code);
                ?>
                <div class="color-picker">
                    <?php if (!empty($colorOpts) && is_array($colorOpts)): ?>
                        <div class="color-picker__swatches">
                            <?php foreach ($colorOpts as $idx => $opt):
                                $val = htmlspecialcharsbx($opt['value'] ?? '');
                                $id = 'setting_' . htmlspecialcharsbx($code) . '_' . $idx;
                                ?>
                                <label class="color-picker__swatch-label" title="<?= $val ?>">
                                    <input type="radio" id="<?= $id ?>" name="<?= htmlspecialcharsbx($code) ?>" value="<?= $val ?>" class="color-picker__radio" data-code="<?= htmlspecialcharsbx($code) ?>">
                                    <span class="color-picker__swatch" style="background-color: <?= $val ?>;"></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="color-input-container">
                        <input type="color"
                               class="visually-hidden"
                               id="<?= $nativePickerId ?>"
                               value="<?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>"
                               data-code="<?= htmlspecialcharsbx($code) ?>"
                        >
                        <input type="text"
                               class="color-input-container__hex"
                               value="<?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>"
                               data-code="<?= htmlspecialcharsbx($code) ?>"
                               id="color_input_<?= htmlspecialcharsbx($code) ?>"
                               maxlength="7"
                               placeholder="#000000"
                        >
                        <label for="<?= $nativePickerId ?>" class="color-input-container__picker-btn" title="<?= Loc::getMessage('QWELP_SITE_SETTINGS_COLOR_PICKER_TITLE') ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.34l.66-3.33a2 2 0 00-1.92-2.39H12a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        </label>
                    </div>
                </div>

            <?php elseif ($type === 'radioImage' && is_array($setting['options'])): ?>
                <div class="radio-image-group">
                    <?php foreach ($setting['options'] as $opt):
                        $optValue = htmlspecialcharsbx($opt['value'] ?? '');
                        $optPath = htmlspecialcharsbx($opt['pathFile'] ?? '');
                        $optLabelText = htmlspecialcharsbx($opt['label'] ?? $optValue);
                        $optId = 'setting_' . htmlspecialcharsbx($code) . '_' . preg_replace('/[^a-z0-9_]/i', '_', $optValue);
                        ?>
                        <div class="radio-image-item">
                            <input type="radio"
                                   id="<?= $optId ?>"
                                   name="<?= htmlspecialcharsbx($code) ?>"
                                   value="<?= $optValue ?>"
                                   class="radio-image__input"
                                   data-code="<?= htmlspecialcharsbx($code) ?>"
                            >
                            <label for="<?= $optId ?>" class="radio-image" title="<?= $optLabelText ?>">
                                <?php if (!empty($optLabelText)): ?>
                                    <span class="radio-image__label-text"><?= $optLabelText ?></span>
                                <?php endif; ?>
                                <img src="<?= $optPath ?>"
                                     alt="<?= $optLabelText ?>"
                                     class="radio-image__picture"
                                     loading="lazy"
                                     width="100"
                                >
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <p><?= Loc::getMessage('QWELP_SITE_SETTINGS_UNKNOWN_SETTING_TYPE', ['#TYPE#' => htmlspecialcharsbx($type)]) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}