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

if (!function_exists('renderSettingControl')) {
    /**
     * Рендерит HTML для элемента управления настройкой.
     * @param string $type Тип контрола.
     * @param array $setting Массив с данными настройки.
     * @param string|null $commonRadioName Имя для общей группы радио-кнопок.
     * @return void
     */
    function renderSettingControl(string $type, array $setting, ?string $commonRadioName): void {
        $code = $setting['code'];
        ?>
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
                <?php foreach ($setting['options'] as $opt):
                    $optValue = htmlspecialcharsbx($opt['value'] ?? '');
                    $optLabel = htmlspecialcharsbx($opt['label'] ?? $optValue);
                    $sanitizedOptValue = preg_replace('/[^a-z0-9_]/i', '_', $optValue);

                    $inputName = isset($commonRadioName) ? $commonRadioName : htmlspecialcharsbx($code);
                    $dataCode = isset($commonRadioName) ? $commonRadioName : htmlspecialcharsbx($code);
                    $dataOwnerCode = isset($commonRadioName) ? 'data-owner-code="' . htmlspecialcharsbx($code) . '"' : '';
                    $optId = 'setting_' . $inputName . '_' . ($dataOwnerCode ? htmlspecialcharsbx($code) . '_' : '') . $sanitizedOptValue;
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
        <?php
    }
}