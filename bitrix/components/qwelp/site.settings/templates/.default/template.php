<?php
/**
 * Шаблон компонента панели настроек сайта с поддержкой группового скрытия полей detailProperty
 *
 * @package qwelp.site_settings
 * @var CBitrixComponentTemplate $this
 * @var CBitrixComponent         $component
 * @var array                    $arParams
 * @var array                    $arResult
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
echo "<pre>";
print_r($arResult);
echo "</pre>";
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Localization\Loc;
?>

<script>
    // Передаём JSON-настройки из PHP в JS
    var settingsData   = <?= CUtil::PhpToJSObject($arResult['SETTINGS']) ?>;
    var ajaxUrl        = '<?= CUtil::JSEscape($arResult['AJAX_URL']) ?>';
    var currentSiteId  = '<?= CUtil::JSEscape($arResult['SITE_ID']) ?>';

    // Сообщения для алертов (используются в script.js)
    var SETTINGS_MESSAGES = {
        SETTINGS_SAVED:    '<?= CUtil::JSEscape(Loc::getMessage('QWELP_SITE_SETTINGS_SETTINGS_SAVED')) ?>',
        SAVE_ERROR:        '<?= CUtil::JSEscape(Loc::getMessage('QWELP_SITE_SETTINGS_SAVE_ERROR')) ?>',
        UNKNOWN_ERROR:     '<?= CUtil::JSEscape(Loc::getMessage('QWELP_SITE_SETTINGS_UNKNOWN_ERROR')) ?>',
        SAVE_ERROR_SIMPLE: '<?= CUtil::JSEscape(Loc::getMessage('QWELP_SITE_SETTINGS_SAVE_ERROR_SIMPLE')) ?>'
    };
</script>

<?php
// Подключаем внешний CSS и JS
Asset::getInstance()->addCss($this->GetFolder() . '/style.css');
Asset::getInstance()->addJs($this->GetFolder() . '/script.js');
?>

<?php
if (!function_exists('qwelpSiteSettingsWidth')) {
    /**
     * Normalize percent width value for flex items.
     * Empty values return 100%, numbers are treated as percentages.
     */
    function qwelpSiteSettingsWidth($value)
    {
        if ($value === null || $value === '') {
            return '100%';
        }

        $value = trim((string)$value);
        if ($value === '') {
            return '100%';
        }

        if (preg_match('/^\d+(?:\.\d+)?$/', $value)) {
            return $value . '%';
        }

        return $value;
    }
}
?>

<div class="container">
    <button id="open-settings" class="settings-button">
        <?= Loc::getMessage('QWELP_SITE_SETTINGS_OPEN_BUTTON') ?>
    </button>
</div>

<?php if (!empty($arResult['SETTINGS']['sections'])): ?>
    <div class="settings-overlay">
        <div class="settings-panel">
            <div class="settings-main-wrapper">
                <!-- Навигация: первый уровень (DEPTH=1) -->
                <div class="settings-nav">
                    <ul class="settings-nav-list nav-left-aligned">
                        <?php
                        $firstNav = true;
                        foreach ($arResult['SETTINGS']['sections'] as $sec1):
                            if ((int)$sec1['DEPTH'] !== 1):
                                continue;
                            endif;
                            ?>
                            <li
                                    data-section="<?= htmlspecialcharsbx($sec1['id']) ?>"
                                    class="<?= $firstNav ? 'active' : '' ?>"
                            >
                                <?= htmlspecialcharsbx($sec1['title']) ?>
                            </li>
                            <?php
                            $firstNav = false;
                        endforeach;
                        ?>
                    </ul>
                </div>

                <!-- Основной контент -->
                <div class="settings-content">
                <div class="settings-header">
                    <div class="settings-title">
                        <?= Loc::getMessage('QWELP_SITE_SETTINGS_TITLE') ?>
                    </div>
                    <button class="settings-close">×</button>
                </div>

                <?php
                $firstSectionContent = true;
                foreach ($arResult['SETTINGS']['sections'] as $sec1):
                    if ((int)$sec1['DEPTH'] !== 1):
                        continue;
                    endif;

                    $parentAllowsDrag = ((int)$sec1['UF_ENABLE_DRAG_AND_DROP'] === 1);

                    // Разбиваем Level 1 на видимые/скрытые (по detailProperty)
                    $visibleLevel1 = [];
                    $hiddenLevel1  = [];
                    if (!empty($sec1['settings'])):
                        foreach ($sec1['settings'] as $setting):
                            if (!empty($setting['detailProperty'])):
                                $hiddenLevel1[] = $setting;
                            else:
                                $visibleLevel1[] = $setting;
                            endif;
                        endforeach;
                    endif;
                    ?>
                    <div
                            id="section-<?= htmlspecialcharsbx($sec1['id']) ?>"
                            class="settings-section <?= $firstSectionContent ? 'active' : '' ?>"
                    >
                        <?php $firstSectionContent = false; ?>

                        <!-- 1) Level 1 (видимые) -->
                        <?php if (!empty($visibleLevel1)): ?>
                            <?php foreach ($visibleLevel1 as $setting): ?>
                                <?php
                                $width     = qwelpSiteSettingsWidth($setting['percent'] ?? null);
                                $typeData  = $setting['type'];
                                if ($typeData === '' && isset($setting['options']['color'])) {
                                    $typeData = 'color';
                                }
                                ?>
                                <div class="setting-item"
                                     data-setting-code="<?= htmlspecialcharsbx($setting['code']) ?>"
                                     data-setting-type="<?= htmlspecialcharsbx($typeData) ?>"
                                     style="flex-basis: <?= htmlspecialcharsbx($width) ?>; max-width: <?= htmlspecialcharsbx($width) ?>;"
                                >
                                    <div class="setting-label">
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

                                    <?php
                                    $type = $setting['type'];
                                    if ($type === '' && isset($setting['options']['color'])) {
                                        $type = 'color';
                                    }
                                    if ($type === 'checkbox'): ?>
                                        <label class="toggle-wrapper">
                                            <div class="toggle-relative">
                                                <input
                                                        type="checkbox"
                                                        id="setting_<?= htmlspecialcharsbx($setting['code']) ?>"
                                                        name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                        class="toggle-input"
                                                >
                                                <div class="toggle-bg"></div>
                                                <div class="toggle-dot"></div>
                                            </div>
                                        </label>
                                    <?php elseif ($type === 'radio' && is_array($setting['options'])): ?>
                                        <div class="radio-options-wrapper">
                                            <?php foreach ($setting['options'] as $opt): ?>
                                                <?php
                                                $optValue = htmlspecialcharsbx($opt['value'] ?? '');
                                                $optLabel = htmlspecialcharsbx($opt['label'] ?? $optValue);
                                                $optId    = 'setting_' . htmlspecialcharsbx($setting['code']) . '_' . preg_replace('/[^a-z0-9_]/i', '_', $optValue);
                                                ?>
                                                <input
                                                        type="radio"
                                                        id="<?= $optId ?>"
                                                        name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                        value="<?= $optValue ?>"
                                                        class="option-input"
                                                >
                                                <label
                                                        for="<?= $optId ?>"
                                                        class="option-label"
                                                >
                                                    <?= $optLabel ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php elseif ($type === 'select' && is_array($setting['options'])): ?>
                                        <div class="select-wrapper">
                                            <select
                                                    name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                    id="setting_<?= htmlspecialcharsbx($setting['code']) ?>"
                                                    class="custom-select"
                                            >
                                                <?php foreach ($setting['options'] as $opt): ?>
                                                    <?php
                                                    $optValue = htmlspecialcharsbx($opt['value'] ?? '');
                                                    $optLabel = htmlspecialcharsbx($opt['label'] ?? $optValue);
                                                    ?>
                                                    <option value="<?= $optValue ?>">
                                                        <?= $optLabel ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php elseif ($type === 'color'): ?>
                                        <?php $colorOpts = $setting['options']['color'] ?? ($setting['options'] ?? []); ?>
                                        <div class="color-options-wrapper">
                                            <?php if (!empty($colorOpts) && is_array($colorOpts)): ?>
                                                <?php foreach ($colorOpts as $idx => $opt): ?>
                                                    <?php
                                                    $val = htmlspecialcharsbx($opt['value'] ?? '');
                                                    $lab = htmlspecialcharsbx($opt['label'] ?? $val);
                                                    $id  = 'setting_' . htmlspecialcharsbx($setting['code']) . '_' . $idx;
                                                    ?>
                                                    <label class="color-option" title="<?= $lab ?>">
                                                        <input type="radio" id="<?= $id ?>" name="<?= htmlspecialcharsbx($setting['code']) ?>" value="<?= $val ?>" class="color-option-input">
                                                        <span class="color-swatch" style="background-color: <?= $val ?>;"></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                            <div class="custom-color-block">
                                                <label class="color-option" title="<?= Loc::getMessage('QWELP_SITE_SETTINGS_CUSTOM_COLOR') ?>">
                                                    <input type="radio" id="setting_<?= htmlspecialcharsbx($setting['code']) ?>_custom" name="<?= htmlspecialcharsbx($setting['code']) ?>" value="<?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>" class="color-option-input custom-color-radio">
                                                    <span class="color-swatch custom-color-swatch" style="background-color: <?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>;"></span>
                                                </label>
                                                <input type="text" class="color-hex-input" value="<?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>">
                                                <input type="color" value="<?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>" class="color-picker-input">
                                            </div>
                                        </div>
                                    <?php elseif ($type === 'radioImage' && is_array($setting['options'])): ?>
                                        <div class="radio-image-options-wrapper">
                                            <?php foreach ($setting['options'] as $opt): ?>
                                                <?php
                                                $optValue = htmlspecialcharsbx($opt['value'] ?? '');
                                                $optLabel = htmlspecialcharsbx($opt['label'] ?? $optValue);
                                                $optId    = 'setting_' . htmlspecialcharsbx($setting['code']) . '_' . preg_replace('/[^a-z0-9_]/i', '_', $optValue);
                                                $imgPath  = htmlspecialcharsbx($opt['pathFile'] ?? '');
                                                ?>
                                                <div class="radio-image-option">
                                                    <input
                                                            type="radio"
                                                            id="<?= $optId ?>"
                                                            name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                            value="<?= $optValue ?>"
                                                            class="option-input"
                                                    >
                                                    <label for="<?= $optId ?>">
                                                        <?php if ($imgPath): ?>
                                                            <img src="<?= $imgPath ?>" alt="<?= $optLabel ?>" title="<?= $optLabel ?>">
                                                        <?php else: ?>
                                                            <span><?= $optLabel ?></span>
                                                        <?php endif; ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p>
                                            <?= Loc::getMessage(
                                                    'QWELP_SITE_SETTINGS_UNKNOWN_SETTING_TYPE',
                                                    ['#TYPE#' => htmlspecialcharsbx($setting['type'])]
                                            ) ?>
                                        </p>
                                    <?php endif; ?>

                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- 1.1) Level 1 (скрытые) -->
                        <?php if (!empty($hiddenLevel1)): ?>
                            <div class="collapse-toggle" data-target="#hidden-level1-<?= htmlspecialcharsbx($sec1['id']) ?>">
                                Показать дополнительные настройки
                            </div>
                            <div id="hidden-level1-<?= htmlspecialcharsbx($sec1['id']) ?>" class="collapse-content">
                                <?php foreach ($hiddenLevel1 as $setting): ?>
                                    <?php
                                    $width    = qwelpSiteSettingsWidth($setting['percent'] ?? null);
                                    $typeData = $setting['type'];
                                    if ($typeData === '' && isset($setting['options']['color'])) {
                                        $typeData = 'color';
                                    }
                                    ?>
                                    <div class="hidden-setting-item"
                                         data-setting-code="<?= htmlspecialcharsbx($setting['code']) ?>"
                                         data-setting-type="<?= htmlspecialcharsbx($typeData) ?>"
                                         style="flex-basis: <?= htmlspecialcharsbx($width) ?>; max-width: <?= htmlspecialcharsbx($width) ?>;"
                                    >
                                        <div class="setting-label">
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

                                        <?php
                                        $type = $setting['type'];
                                        if ($type === '' && isset($setting['options']['color'])) {
                                            $type = 'color';
                                        }
                                        if ($type === 'checkbox'): ?>
                                            <label class="toggle-wrapper">
                                                <div class="toggle-relative">
                                                    <input
                                                            type="checkbox"
                                                            id="setting_<?= htmlspecialcharsbx($setting['code']) ?>"
                                                            name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                            class="toggle-input"
                                                    >
                                                    <div class="toggle-bg"></div>
                                                    <div class="toggle-dot"></div>
                                                </div>
                                            </label>
                                        <?php elseif ($type === 'radio' && is_array($setting['options'])): ?>
                                            <div class="radio-options-wrapper">
                                                <?php foreach ($setting['options'] as $opt): ?>
                                                    <?php
                                                    $optValue = htmlspecialcharsbx($opt['value'] ?? '');
                                                    $optLabel = htmlspecialcharsbx($opt['label'] ?? $optValue);
                                                    $optId    = 'setting_' . htmlspecialcharsbx($setting['code']) . '_' . preg_replace('/[^a-z0-9_]/i', '_', $optValue);
                                                    ?>
                                                    <input
                                                            type="radio"
                                                            id="<?= $optId ?>"
                                                            name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                            value="<?= $optValue ?>"
                                                            class="option-input"
                                                    >
                                                    <label
                                                            for="<?= $optId ?>"
                                                            class="option-label"
                                                    >
                                                        <?= $optLabel ?>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php elseif ($type === 'select' && is_array($setting['options'])): ?>
                                            <div class="select-wrapper">
                                                <select
                                                        name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                        id="setting_<?= htmlspecialcharsbx($setting['code']) ?>"
                                                        class="custom-select"
                                                >
                                                    <?php foreach ($setting['options'] as $opt): ?>
                                                        <?php
                                                        $optValue = htmlspecialcharsbx($opt['value'] ?? '');
                                                        $optLabel = htmlspecialcharsbx($opt['label'] ?? $optValue);
                                                        ?>
                                                        <option value="<?= $optValue ?>">
                                                            <?= $optLabel ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        <?php elseif ($type === 'color'): ?>
                                            <?php $colorOpts = $setting['options']['color'] ?? ($setting['options'] ?? []); ?>
                                            <div class="color-options-wrapper">
                                                <?php if (!empty($colorOpts) && is_array($colorOpts)): ?>
                                                    <?php foreach ($colorOpts as $idx => $opt): ?>
                                                        <?php
                                                        $val = htmlspecialcharsbx($opt['value'] ?? '');
                                                        $lab = htmlspecialcharsbx($opt['label'] ?? $val);
                                                        $id  = 'setting_' . htmlspecialcharsbx($setting['code']) . '_' . $idx;
                                                        ?>
                                                        <label class="color-option" title="<?= $lab ?>">
                                                            <input type="radio" id="<?= $id ?>" name="<?= htmlspecialcharsbx($setting['code']) ?>" value="<?= $val ?>" class="color-option-input">
                                                            <span class="color-swatch" style="background-color: <?= $val ?>;"></span>
                                                        </label>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                                <div class="custom-color-block">
                                                    <label class="color-option" title="<?= Loc::getMessage('QWELP_SITE_SETTINGS_CUSTOM_COLOR') ?>">
                                                        <input type="radio" id="setting_<?= htmlspecialcharsbx($setting['code']) ?>_custom" name="<?= htmlspecialcharsbx($setting['code']) ?>" value="<?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>" class="color-option-input custom-color-radio">
                                                        <span class="color-swatch custom-color-swatch" style="background-color: <?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>;"></span>
                                                    </label>
                                                    <input type="text" class="color-hex-input" value="<?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>">
                                                    <input
                                                            type="color"
                                                            id="setting_<?= htmlspecialcharsbx($setting['code']) ?>"
                                                            value="<?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>"
                                                            class="color-picker-input"
                                                    >
                                                </div>
                                            </div>
                                        <?php elseif ($type === 'radioImage' && is_array($setting['options'])): ?>
                                            <div class="radio-image-options-wrapper">
                                                <?php foreach ($setting['options'] as $opt): ?>
                                                    <?php
                                                    $optValue = htmlspecialcharsbx($opt['value'] ?? '');
                                                    $optLabel = htmlspecialcharsbx($opt['label'] ?? $optValue);
                                                    $optId    = 'setting_' . htmlspecialcharsbx($setting['code']) . '_' . preg_replace('/[^a-z0-9_]/i', '_', $optValue);
                                                    $imgPath  = htmlspecialcharsbx($opt['pathFile'] ?? '');
                                                    ?>
                                                    <div class="radio-image-option">
                                                        <input
                                                                type="radio"
                                                                id="<?= $optId ?>"
                                                                name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                value="<?= $optValue ?>"
                                                                class="option-input"
                                                        >
                                                        <label for="<?= $optId ?>">
                                                            <?php if ($imgPath): ?>
                                                                <img src="<?= $imgPath ?>" alt="<?= $optLabel ?>" title="<?= $optLabel ?>">
                                                            <?php else: ?>
                                                                <span><?= $optLabel ?></span>
                                                            <?php endif; ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <p>
                                                <?= Loc::getMessage(
                                                        'QWELP_SITE_SETTINGS_UNKNOWN_SETTING_TYPE',
                                                        ['#TYPE#' => htmlspecialcharsbx($setting['type'])]
                                                ) ?>
                                            </p>
                                        <?php endif; ?>

                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- 2) Второй уровень (DEPTH=2) как табы -->
                        <?php if (!empty($sec1['SUBSECTIONS'])): ?>
                            <?php
                            // Фильтруем только разделы второго уровня
                            $level2Sections = array_filter($sec1['SUBSECTIONS'], function($sec) {
                                return (int)$sec['DEPTH'] === 2;
                            });

                            // Если есть только один таб, не показываем табы, а сразу отображаем содержимое
                            $showTabs = count($level2Sections) > 1;
                            $tabsContainerId = 'tabs-container-' . htmlspecialcharsbx($sec1['id']);
                            ?>

                            <div id="<?= $tabsContainerId ?>" class="tabs-container">
                                <?php if ($showTabs): ?>
                                    <div class="tabs-header">
                                        <?php 
                                        $firstTab = true;
                                        foreach ($level2Sections as $sec2): ?>
                                            <div class="tab <?= $firstTab ? 'active' : '' ?>" data-tab="<?= htmlspecialcharsbx($sec2['id']) ?>">
                                            <?php $firstTab = false; ?>
                                                <?= htmlspecialcharsbx($sec2['title']) ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php 
                                $firstTabContent = true;
                                foreach ($level2Sections as $sec2): ?>
                                    <div class="tab-content <?= $firstTabContent ? 'active' : '' ?>" data-tab="<?= htmlspecialcharsbx($sec2['id']) ?>">
                                    <?php $firstTabContent = false; ?>
                                        <?php
                                        // Разделяем настройки второго уровня на видимые и скрытые
                                        $visibleLevel2 = [];
                                        $hiddenLevel2 = [];
                                        if (!empty($sec2['settings'])):
                                            foreach ($sec2['settings'] as $setting):
                                                if (!empty($setting['detailProperty'])):
                                                    $hiddenLevel2[] = $setting;
                                                else:
                                                    $visibleLevel2[] = $setting;
                                                endif;
                                            endforeach;
                                        endif;
                                        ?>

                                        <!-- 2.1) Настройки второго уровня (видимые) -->
                                        <?php if (!empty($visibleLevel2)): ?>
                                            <?php foreach ($visibleLevel2 as $setting): ?>
                                                <?php
                                                $width    = qwelpSiteSettingsWidth($setting['percent'] ?? null);
                                                $typeData = $setting['type'];
                                                if ($typeData === '' && isset($setting['options']['color'])) {
                                                    $typeData = 'color';
                                                }
                                                ?>
                                                <div class="setting-item"
                                                     data-setting-code="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                     data-setting-type="<?= htmlspecialcharsbx($typeData) ?>"
                                                     style="flex-basis: <?= htmlspecialcharsbx($width) ?>; max-width: <?= htmlspecialcharsbx($width) ?>;"
                                                >
                                                    <div class="setting-label">
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

                                                    <?php
                                                    $type = $setting['type'];
                                                    if ($type === '' && isset($setting['options']['color'])) {
                                                        $type = 'color';
                                                    }
                                                    if ($type === 'checkbox'): ?>
                                                        <label class="toggle-wrapper">
                                                            <div class="toggle-relative">
                                                                <input
                                                                        type="checkbox"
                                                                        id="setting_<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                        name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                        class="toggle-input"
                                                                >
                                                                <div class="toggle-bg"></div>
                                                                <div class="toggle-dot"></div>
                                                            </div>
                                                        </label>
                                                    <?php elseif ($type === 'radio' && is_array($setting['options'])): ?>
                                                        <div class="radio-options-wrapper">
                                                            <?php foreach ($setting['options'] as $opt): ?>
                                                                <?php
                                                                $optValue = htmlspecialcharsbx($opt['value'] ?? '');
                                                                $optLabel = htmlspecialcharsbx($opt['label'] ?? $optValue);
                                                                $optId    = 'setting_' . htmlspecialcharsbx($setting['code']) . '_' . preg_replace('/[^a-z0-9_]/i', '_', $optValue);
                                                                ?>
                                                                <input
                                                                        type="radio"
                                                                        id="<?= $optId ?>"
                                                                        name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                        value="<?= $optValue ?>"
                                                                        class="option-input"
                                                                >
                                                                <label
                                                                        for="<?= $optId ?>"
                                                                        class="option-label"
                                                                >
                                                                    <?= $optLabel ?>
                                                                </label>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php elseif ($type === 'select' && is_array($setting['options'])): ?>
                                                        <div class="select-wrapper">
                                                            <select
                                                                    name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                    id="setting_<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                    class="custom-select"
                                                            >
                                                                <?php foreach ($setting['options'] as $opt): ?>
                                                                    <?php
                                                                    $optValue = htmlspecialcharsbx($opt['value'] ?? '');
                                                                    $optLabel = htmlspecialcharsbx($opt['label'] ?? $optValue);
                                                                    ?>
                                                                    <option value="<?= $optValue ?>">
                                                                        <?= $optLabel ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                    <?php elseif ($type === 'color'): ?>
                                                        <?php $colorOpts = $setting['options']['color'] ?? ($setting['options'] ?? []); ?>
                                                        <div class="color-options-wrapper">
                                                            <?php if (!empty($colorOpts) && is_array($colorOpts)): ?>
                                                                <?php foreach ($colorOpts as $idx => $opt): ?>
                                                                    <?php
                                                                    $val = htmlspecialcharsbx($opt['value'] ?? '');
                                                                    $lab = htmlspecialcharsbx($opt['label'] ?? $val);
                                                                    $id  = 'setting_' . htmlspecialcharsbx($setting['code']) . '_' . $idx;
                                                                    ?>
                                                                    <label class="color-option" title="<?= $lab ?>">
                                                                        <input type="radio" id="<?= $id ?>" name="<?= htmlspecialcharsbx($setting['code']) ?>" value="<?= $val ?>" class="color-option-input">
                                                                        <span class="color-swatch" style="background-color: <?= $val ?>;"></span>
                                                                    </label>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                            <div class="custom-color-block">
                                                                <label class="color-option" title="<?= Loc::getMessage('QWELP_SITE_SETTINGS_CUSTOM_COLOR') ?>">
                                                                    <input type="radio" id="setting_<?= htmlspecialcharsbx($setting['code']) ?>_custom" name="<?= htmlspecialcharsbx($setting['code']) ?>" value="<?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>" class="color-option-input custom-color-radio">
                                                                    <span class="color-swatch custom-color-swatch"></span>
                                                                </label>
                                                                <input type="text" class="color-hex-input" value="<?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>">
                                                                <input
                                                                        type="color"
                                                                        id="setting_<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                        value="<?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>"
                                                                        class="color-picker-input"
                                                                >
                                                            </div>
                                                        </div>
                                                    <?php elseif ($type === 'radioImage' && is_array($setting['options'])): ?>
                                                        <div class="radio-image-options-wrapper">
                                                            <?php foreach ($setting['options'] as $opt): ?>
                                                                <?php
                                                                $optValue = htmlspecialcharsbx($opt['value'] ?? '');
                                                                $optLabel = htmlspecialcharsbx($opt['label'] ?? $optValue);
                                                                $optId    = 'setting_' . htmlspecialcharsbx($setting['code']) . '_' . preg_replace('/[^a-z0-9_]/i', '_', $optValue);
                                                                $imgPath  = htmlspecialcharsbx($opt['pathFile'] ?? '');
                                                                ?>
                                                                <div class="radio-image-option">
                                                                    <input
                                                                            type="radio"
                                                                            id="<?= $optId ?>"
                                                                            name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                            value="<?= $optValue ?>"
                                                                            class="option-input"
                                                                    >
                                                                    <label for="<?= $optId ?>">
                                                                        <?php if ($imgPath): ?>
                                                                            <img src="<?= $imgPath ?>" alt="<?= $optLabel ?>" title="<?= $optLabel ?>">
                                                                        <?php else: ?>
                                                                            <span><?= $optLabel ?></span>
                                                                        <?php endif; ?>
                                                                    </label>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <p>
                                                            <?= Loc::getMessage(
                                                                    'QWELP_SITE_SETTINGS_UNKNOWN_SETTING_TYPE',
                                                                    ['#TYPE#' => htmlspecialcharsbx($setting['type'])]
                                                            ) ?>
                                                        </p>
                                                    <?php endif; ?>

                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>

                                        <!-- 2.2) Скрытые настройки второго уровня -->
                                        <?php if (!empty($hiddenLevel2)): ?>
                                            <div class="collapse-toggle" data-target="#hidden-level2-<?= htmlspecialcharsbx($sec2['id']) ?>">
                                                Показать дополнительные настройки
                                            </div>
                                            <div id="hidden-level2-<?= htmlspecialcharsbx($sec2['id']) ?>" class="collapse-content">
                                                <?php foreach ($hiddenLevel2 as $setting): ?>
                                                    <?php
                                                    $width    = qwelpSiteSettingsWidth($setting['percent'] ?? null);
                                                    $typeData = $setting['type'];
                                                    if ($typeData === '' && isset($setting['options']['color'])) {
                                                        $typeData = 'color';
                                                    }
                                                    ?>
                                                    <div class="hidden-setting-item"
                                                         data-setting-code="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                         data-setting-type="<?= htmlspecialcharsbx($typeData) ?>"
                                                         style="flex-basis: <?= htmlspecialcharsbx($width) ?>; max-width: <?= htmlspecialcharsbx($width) ?>;"
                                                    >
                                                        <div class="setting-label">
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

                                                        <?php
                                                        $type = $setting['type'];
                                                        if ($type === '' && isset($setting['options']['color'])) {
                                                            $type = 'color';
                                                        }
                                                        if ($type === 'checkbox'): ?>
                                                            <label class="toggle-wrapper">
                                                                <div class="toggle-relative">
                                                                    <input
                                                                            type="checkbox"
                                                                            id="setting_<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                            name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                            class="toggle-input"
                                                                    >
                                                                    <div class="toggle-bg"></div>
                                                                    <div class="toggle-dot"></div>
                                                                </div>
                                                            </label>
                                                        <?php elseif ($type === 'radio' && is_array($setting['options'])): ?>
                                                            <div class="radio-options-wrapper">
                                                                <?php foreach ($setting['options'] as $opt): ?>
                                                                    <?php
                                                                    $optValue = htmlspecialcharsbx($opt['value'] ?? '');
                                                                    $optLabel = htmlspecialcharsbx($opt['label'] ?? $optValue);
                                                                    $optId    = 'setting_' . htmlspecialcharsbx($setting['code']) . '_' . preg_replace('/[^a-z0-9_]/i', '_', $optValue);
                                                                    ?>
                                                                    <input
                                                                            type="radio"
                                                                            id="<?= $optId ?>"
                                                                            name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                            value="<?= $optValue ?>"
                                                                            class="option-input"
                                                                    >
                                                                    <label
                                                                            for="<?= $optId ?>"
                                                                            class="option-label"
                                                                    >
                                                                        <?= $optLabel ?>
                                                                    </label>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php elseif ($type === 'select' && is_array($setting['options'])): ?>
                                                            <div class="select-wrapper">
                                                                <select
                                                                        name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                        id="setting_<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                        class="custom-select"
                                                                >
                                                                    <?php foreach ($setting['options'] as $opt): ?>
                                                                        <?php
                                                                        $optValue = htmlspecialcharsbx($opt['value'] ?? '');
                                                                        $optLabel = htmlspecialcharsbx($opt['label'] ?? $optValue);
                                                                        ?>
                                                                        <option value="<?= $optValue ?>">
                                                                            <?= $optLabel ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                        <?php elseif ($type === 'color'): ?>
                                                        <?php $colorOpts = $setting['options']['color'] ?? ($setting['options'] ?? []); ?>
                                                        <div class="color-options-wrapper">
                                                            <?php if (!empty($colorOpts) && is_array($colorOpts)): ?>
                                                                <?php foreach ($colorOpts as $idx => $opt): ?>
                                                                    <?php
                                                                    $val = htmlspecialcharsbx($opt['value'] ?? '');
                                                                    $lab = htmlspecialcharsbx($opt['label'] ?? $val);
                                                                    $id  = 'setting_' . htmlspecialcharsbx($setting['code']) . '_' . $idx;
                                                                    ?>
                                                                    <label class="color-option" title="<?= $lab ?>">
                                                                        <input type="radio" id="<?= $id ?>" name="<?= htmlspecialcharsbx($setting['code']) ?>" value="<?= $val ?>" class="color-option-input">
                                                                        <span class="color-swatch" style="background-color: <?= $val ?>;"></span>
                                                                    </label>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                            <div class="custom-color-block">
                                                                <label class="color-option" title="<?= Loc::getMessage('QWELP_SITE_SETTINGS_CUSTOM_COLOR') ?>">
                                                                    <input type="radio" id="setting_<?= htmlspecialcharsbx($setting['code']) ?>_custom" name="<?= htmlspecialcharsbx($setting['code']) ?>" value="<?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>" class="color-option-input custom-color-radio">
                                                                    <span class="color-swatch custom-color-swatch"></span>
                                                                </label>
                                                                <input type="text" class="color-hex-input" value="<?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>">
                                                                <input
                                                                        type="color"
                                                                        id="setting_<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                        value="<?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>"
                                                                        class="color-picker-input"
                                                                >
                                                            </div>
                                                        </div>
                                                        <?php elseif ($type === 'radioImage' && is_array($setting['options'])): ?>
                                                            <div class="radio-image-options-wrapper">
                                                                <?php foreach ($setting['options'] as $opt): ?>
                                                                    <?php
                                                                    $optValue = htmlspecialcharsbx($opt['value'] ?? '');
                                                                    $optLabel = htmlspecialcharsbx($opt['label'] ?? $optValue);
                                                                    $optId    = 'setting_' . htmlspecialcharsbx($setting['code']) . '_' . preg_replace('/[^a-z0-9_]/i', '_', $optValue);
                                                                    $imgPath  = htmlspecialcharsbx($opt['pathFile'] ?? '');
                                                                    ?>
                                                                    <div class="radio-image-option">
                                                                        <input
                                                                                type="radio"
                                                                                id="<?= $optId ?>"
                                                                                name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                                value="<?= $optValue ?>"
                                                                                class="option-input"
                                                                        >
                                                                        <label for="<?= $optId ?>">
                                                                            <?php if ($imgPath): ?>
                                                                                <img src="<?= $imgPath ?>" alt="<?= $optLabel ?>" title="<?= $optLabel ?>">
                                                                            <?php else: ?>
                                                                                <span><?= $optLabel ?></span>
                                                                            <?php endif; ?>
                                                                        </label>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <p>
                                                                <?= Loc::getMessage(
                                                                        'QWELP_SITE_SETTINGS_UNKNOWN_SETTING_TYPE',
                                                                        ['#TYPE#' => htmlspecialcharsbx($setting['type'])]
                                                                ) ?>
                                                            </p>
                                                        <?php endif; ?>

                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <!-- 3) Третий уровень (DEPTH=3) -->
                                        <?php
                                        // Разделяем третий уровень на "видимые секции" и "скрытые секции" по UF_DETAIL_PROPERTY
                                        $visibleSections3 = [];
                                        $hiddenSections3  = [];
                                        foreach ($sec2['SUBSECTIONS'] as $sec3):
                                            if ((int)$sec3['DEPTH'] !== 3):
                                                continue;
                                            endif;
                                            if (!empty($sec3['UF_DETAIL_PROPERTY'])):
                                                $hiddenSections3[] = $sec3;
                                            else:
                                                $visibleSections3[] = $sec3;
                                            endif;
                                        endforeach;
                                        ?>

                                        <!-- 3.1) Видимые секции третьего уровня -->
                                        <?php foreach ($visibleSections3 as $sec3): ?>
                                            <div class="third-level-frame">
                                                <div class="third-level-title">
                                                    <?= htmlspecialcharsbx($sec3['title']) ?>
                                                </div>

                                                <?php if (!empty($sec3['settings'])): ?>
                                                    <?php 
                                                    // Проверяем, есть ли вложенные разделы в разделе третьего уровня
                                                    $hasSubsections = !empty($sec3['SUBSECTIONS']) && is_array($sec3['SUBSECTIONS']);

                                                    // Если есть вложенные разделы, выводим их как единое свойство
                                                    if ($hasSubsections): 
                                                    ?>
                                                        <div class="setting-item nested-sections-property" data-setting-code="nested_<?= htmlspecialcharsbx($sec3['id']) ?>">
                                                            <div class="setting-label">
                                                                <?= Loc::getMessage('QWELP_SITE_SETTINGS_NESTED_SECTIONS', ['#TITLE#' => htmlspecialcharsbx($sec3['title'])]) ?>
                                                            </div>
                                                            <div class="nested-sections-container">
                                                                <?php 
                                                                    $isFirst = true;
                                                                    foreach ($sec3['SUBSECTIONS'] as $sec4): ?>
                                                                    <div class="nested-section">
                                                                        <input 
                                                                            type="radio" 
                                                                            id="visible_section_<?= htmlspecialcharsbx($sec4['id']) ?>" 
                                                                            name="section_<?= htmlspecialcharsbx($sec3['id']) ?>_subsection" 
                                                                            value="<?= htmlspecialcharsbx($sec4['id']) ?>"
                                                                            class="nested-section-radio"
                                                                            <?= $isFirst ? 'checked' : '' ?>
                                                                        >
                                                                        <?php $isFirst = false; ?>
                                                                        <label for="visible_section_<?= htmlspecialcharsbx($sec4['id']) ?>" class="nested-section-title">
                                                                            <?= htmlspecialcharsbx($sec4['title']) ?>
                                                                        </label>
                                                                        <?php if (!empty($sec4['settings'])): ?>
                                                                            <div class="nested-section-settings">
                                                                                <?php foreach ($sec4['settings'] as $setting): ?>
                                                                                    <?php
                                                                                    $width    = qwelpSiteSettingsWidth($setting['percent'] ?? null);
                                                                                    $typeData = $setting['type'];
                                                                                    if ($typeData === '' && isset($setting['options']['color'])) {
                                                                                        $typeData = 'color';
                                                                                    }
                                                                                    ?>
                                                                                    <div class="nested-setting-item"
                                                                                         data-setting-code="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                                         data-setting-type="<?= htmlspecialcharsbx($typeData) ?>"
                                                                                         style="flex-basis: <?= htmlspecialcharsbx($width) ?>; max-width: <?= htmlspecialcharsbx($width) ?>;"
                                                                                    >
                                                                                        <div class="setting-label">
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

                                                                                        <?php
                                                                                        $type = $setting['type'];
                                                                                        if ($type === '' && isset($setting['options']['color'])) {
                                                                                            $type = 'color';
                                                                                        }
                                                                                        if ($type === 'checkbox'): ?>
                                                                                            <label class="toggle-wrapper">
                                                                                                <div class="toggle-relative">
                                                                                                    <input
                                                                                                            type="checkbox"
                                                                                                            id="setting_<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                                                            name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                                                            class="toggle-input"
                                                                                                    >
                                                                                                    <div class="toggle-bg"></div>
                                                                                                    <div class="toggle-dot"></div>
                                                                                                </div>
                                                                                            </label>
                                                                                        <?php elseif ($type === 'radio' && is_array($setting['options'])): ?>
                                                                                            <div class="radio-options-wrapper">
                                                                                                <?php foreach ($setting['options'] as $opt): ?>
                                                                                                    <?php
                                                                                                    $optValue = htmlspecialcharsbx($opt['value'] ?? '');
                                                                                                    $optLabel = htmlspecialcharsbx($opt['label'] ?? $optValue);
                                                                                                    $optId    = 'setting_' . htmlspecialcharsbx($setting['code']) . '_' . preg_replace('/[^a-z0-9_]/i', '_', $optValue);
                                                                                                    ?>
                                                                                                    <input
                                                                                                            type="radio"
                                                                                                            id="<?= $optId ?>"
                                                                                                            name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                                                            value="<?= $optValue ?>"
                                                                                                            class="option-input"
                                                                                                    >
                                                                                                    <label
                                                                                                            for="<?= $optId ?>"
                                                                                                            class="option-label"
                                                                                                    >
                                                                                                        <?= $optLabel ?>
                                                                                                    </label>
                                                                                                <?php endforeach; ?>
                                                                                            </div>
                                                                                        <?php elseif ($type === 'select' && is_array($setting['options'])): ?>
                                                                                            <div class="select-wrapper">
                                                                                                <select
                                                                                                        name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                                                        id="setting_<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                                                        class="custom-select"
                                                                                                >
                                                                                                    <?php foreach ($setting['options'] as $opt): ?>
                                                                                                        <?php
                                                                                                        $optValue = htmlspecialcharsbx($opt['value'] ?? '');
                                                                                                        $optLabel = htmlspecialcharsbx($opt['label'] ?? $optValue);
                                                                                                        ?>
                                                                                                        <option value="<?= $optValue ?>">
                                                                                                            <?= $optLabel ?>
                                                                                                        </option>
                                                                                                    <?php endforeach; ?>
                                                                                                </select>
                                                                                            </div>
                                                                                        <?php elseif ($type === 'color'): ?>
                                                                                            <?php $colorOpts = $setting['options']['color'] ?? ($setting['options'] ?? []); ?>
                                                                                            <div class="color-options-wrapper">
                                                                                                <?php if (!empty($colorOpts) && is_array($colorOpts)): ?>
                                                                                                    <?php foreach ($colorOpts as $idx => $opt): ?>
                                                                                                        <?php
                                                                                                        $val = htmlspecialcharsbx($opt['value'] ?? '');
                                                                                                        $lab = htmlspecialcharsbx($opt['label'] ?? $val);
                                                                                                        $id  = 'setting_' . htmlspecialcharsbx($setting['code']) . '_' . $idx;
                                                                                                        ?>
                                                                                                        <label class="color-option" title="<?= $lab ?>">
                                                                                                            <input type="radio" id="<?= $id ?>" name="<?= htmlspecialcharsbx($setting['code']) ?>" value="<?= $val ?>" class="color-option-input">
                                                                                                            <span class="color-swatch" style="background-color: <?= $val ?>;"></span>
                                                                                                        </label>
                                                                                                    <?php endforeach; ?>
                                                                                                <?php endif; ?>
                                                                                                <div class="custom-color-block">
                                                                                                    <label class="color-option" title="<?= Loc::getMessage('QWELP_SITE_SETTINGS_CUSTOM_COLOR') ?>">
                                                                                                        <input type="radio" id="setting_<?= htmlspecialcharsbx($setting['code']) ?>_custom" name="<?= htmlspecialcharsbx($setting['code']) ?>" value="<?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>" class="color-option-input custom-color-radio">
                                                                                                        <span class="color-swatch custom-color-swatch"></span>
                                                                                                    </label>
                                                                                                    <input type="text" class="color-hex-input" value="<?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>">
                                                                                                    <input
                                                                                                            type="color"
                                                                                                            id="setting_<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                                                            value="<?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>"
                                                                                                            class="color-picker-input"
                                                                                                    >
                                                                                                </div>
                                                                                            </div>
                                                                                        <?php elseif ($type === 'radioImage' && is_array($setting['options'])): ?>
                                                                                            <div class="radio-image-options-wrapper">
                                                                                                <?php foreach ($setting['options'] as $opt): ?>
                                                                                                    <?php
                                                                                                    $optValue = htmlspecialcharsbx($opt['value'] ?? '');
                                                                                                    $optLabel = htmlspecialcharsbx($opt['label'] ?? $optValue);
                                                                                                    $optId    = 'setting_' . htmlspecialcharsbx($setting['code']) . '_' . preg_replace('/[^a-z0-9_]/i', '_', $optValue);
                                                                                                    $imgPath  = htmlspecialcharsbx($opt['pathFile'] ?? '');
                                                                                                    ?>
                                                                                                    <div class="radio-image-option">
                                                                                                        <input
                                                                                                                type="radio"
                                                                                                                id="<?= $optId ?>"
                                                                                                                name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                                                                value="<?= $optValue ?>"
                                                                                                                class="option-input"
                                                                                                        >
                                                                                                        <label for="<?= $optId ?>">
                                                                                                            <?php if ($imgPath): ?>
                                                                                                                <img src="<?= $imgPath ?>" alt="<?= $optLabel ?>" title="<?= $optLabel ?>">
                                                                                                            <?php else: ?>
                                                                                                                <span><?= $optLabel ?></span>
                                                                                                            <?php endif; ?>
                                                                                                        </label>
                                                                                                    </div>
                                                                                                <?php endforeach; ?>
                                                                                            </div>
                                                                                        <?php else: ?>
                                                                                            <p>
                                                                                                <?= Loc::getMessage(
                                                                                                        'QWELP_SITE_SETTINGS_UNKNOWN_SETTING_TYPE',
                                                                                                        ['#TYPE#' => htmlspecialcharsbx($setting['type'])]
                                                                                                ) ?>
                                                                                            </p>
                                                                                        <?php endif; ?>
                                                                                    </div>
                                                                                <?php endforeach; ?>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <?php foreach ($sec3['settings'] as $setting): ?>
                                                            <?php
                                                            $width    = qwelpSiteSettingsWidth($setting['percent'] ?? null);
                                                            $typeData = $setting['type'];
                                                            if ($typeData === '' && isset($setting['options']['color'])) {
                                                                $typeData = 'color';
                                                            }
                                                            ?>
                                                            <div class="setting-item"
                                                                 data-setting-code="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                 data-setting-type="<?= htmlspecialcharsbx($typeData) ?>"
                                                                 style="flex-basis: <?= htmlspecialcharsbx($width) ?>; max-width: <?= htmlspecialcharsbx($width) ?>;"
                                                            >
                                                                <div class="setting-label">
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

                                                                <?php
                                                                $type = $setting['type'];
                                                                if ($type === '' && isset($setting['options']['color'])) {
                                                                    $type = 'color';
                                                                }
                                                                if ($type === 'checkbox'): ?>
                                                                    <label class="toggle-wrapper">
                                                                        <div class="toggle-relative">
                                                                            <input
                                                                                    type="checkbox"
                                                                                    id="setting_<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                                    name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                                    class="toggle-input"
                                                                            >
                                                                            <div class="toggle-bg"></div>
                                                                            <div class="toggle-dot"></div>
                                                                        </div>
                                                                    </label>
                                                                <?php elseif ($type === 'radio' && is_array($setting['options'])): ?>
                                                                    <div class="radio-options-wrapper">
                                                                        <?php foreach ($setting['options'] as $opt): ?>
                                                                            <?php
                                                                            $optValue = htmlspecialcharsbx($opt['value'] ?? '');
                                                                            $optLabel = htmlspecialcharsbx($opt['label'] ?? $optValue);
                                                                            $optId    = 'setting_' . htmlspecialcharsbx($setting['code']) . '_' . preg_replace('/[^a-z0-9_]/i', '_', $optValue);
                                                                            ?>
                                                                            <input
                                                                                    type="radio"
                                                                                    id="<?= $optId ?>"
                                                                                    name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                                    value="<?= $optValue ?>"
                                                                                    class="option-input"
                                                                            >
                                                                            <label
                                                                                    for="<?= $optId ?>"
                                                                                    class="option-label"
                                                                            >
                                                                                <?= $optLabel ?>
                                                                            </label>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                <?php elseif ($type === 'select' && is_array($setting['options'])): ?>
                                                                    <div class="select-wrapper">
                                                                        <select
                                                                                name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                                id="setting_<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                                class="custom-select"
                                                                        >
                                                                            <?php foreach ($setting['options'] as $opt): ?>
                                                                                <?php
                                                                                $optValue = htmlspecialcharsbx($opt['value'] ?? '');
                                                                                $optLabel = htmlspecialcharsbx($opt['label'] ?? $optValue);
                                                                                ?>
                                                                                <option value="<?= $optValue ?>">
                                                                                    <?= $optLabel ?>
                                                                                </option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    </div>
                                                                <?php elseif ($type === 'color'): ?>
                                                                    <?php $colorOpts = $setting['options']['color'] ?? ($setting['options'] ?? []); ?>
                                                                    <div class="color-options-wrapper">
                                                                        <?php if (!empty($colorOpts) && is_array($colorOpts)): ?>
                                                                            <?php foreach ($colorOpts as $idx => $opt): ?>
                                                                                <?php
                                                                                $val = htmlspecialcharsbx($opt['value'] ?? '');
                                                                                $lab = htmlspecialcharsbx($opt['label'] ?? $val);
                                                                                $id  = 'setting_' . htmlspecialcharsbx($setting['code']) . '_' . $idx;
                                                                                ?>
                                                                                <label class="color-option" title="<?= $lab ?>">
                                                                                    <input type="radio" id="<?= $id ?>" name="<?= htmlspecialcharsbx($setting['code']) ?>" value="<?= $val ?>" class="color-option-input">
                                                                                    <span class="color-swatch" style="background-color: <?= $val ?>;"></span>
                                                                                </label>
                                                                            <?php endforeach; ?>
                                                                        <?php endif; ?>
                                                                        <div class="custom-color-block">
                                                                            <label class="color-option" title="<?= Loc::getMessage('QWELP_SITE_SETTINGS_CUSTOM_COLOR') ?>">
                                                                                <input type="radio" id="setting_<?= htmlspecialcharsbx($setting['code']) ?>_custom" name="<?= htmlspecialcharsbx($setting['code']) ?>" value="<?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>" class="color-option-input custom-color-radio">
                                                                                <span class="color-swatch custom-color-swatch"></span>
                                                                            </label>
                                                                            <input type="text" class="color-hex-input" value="<?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>">
                                                                            <input
                                                                                    type="color"
                                                                                    id="setting_<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                                    value="<?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>"
                                                                                    class="color-picker-input"
                                                                            >
                                                                        </div>
                                                                    </div>
                                                                <?php elseif ($type === 'radioImage' && is_array($setting['options'])): ?>
                                                                    <div class="radio-image-options-wrapper">
                                                                        <?php foreach ($setting['options'] as $opt): ?>
                                                                            <?php
                                                                            $optValue = htmlspecialcharsbx($opt['value'] ?? '');
                                                                            $optLabel = htmlspecialcharsbx($opt['label'] ?? $optValue);
                                                                            $optId    = 'setting_' . htmlspecialcharsbx($setting['code']) . '_' . preg_replace('/[^a-z0-9_]/i', '_', $optValue);
                                                                            $imgPath  = htmlspecialcharsbx($opt['pathFile'] ?? '');
                                                                            ?>
                                                                            <div class="radio-image-option">
                                                                                <input
                                                                                        type="radio"
                                                                                        id="<?= $optId ?>"
                                                                                        name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                                        value="<?= $optValue ?>"
                                                                                        class="option-input"
                                                                                >
                                                                                <label for="<?= $optId ?>">
                                                                                    <?php if ($imgPath): ?>
                                                                                        <img src="<?= $imgPath ?>" alt="<?= $optLabel ?>" title="<?= $optLabel ?>">
                                                                                    <?php else: ?>
                                                                                        <span><?= $optLabel ?></span>
                                                                                    <?php endif; ?>
                                                                                </label>
                                                                            </div>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <p>
                                                                        <?= Loc::getMessage(
                                                                                'QWELP_SITE_SETTINGS_UNKNOWN_SETTING_TYPE',
                                                                                ['#TYPE#' => htmlspecialcharsbx($setting['type'])]
                                                                        ) ?>
                                                                    </p>
                                                                <?php endif; ?>

                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>

                                        <!-- 3.2) Скрытые секции третьего уровня -->
                                        <?php if (!empty($hiddenSections3)): ?>
                                            <div class="collapse-toggle" data-target="#hidden-sections3-<?= htmlspecialcharsbx($sec2['id']) ?>">
                                                Показать скрытые разделы
                                            </div>
                                            <div id="hidden-sections3-<?= htmlspecialcharsbx($sec2['id']) ?>" class="collapse-content">
                                                <?php foreach ($hiddenSections3 as $sec3): ?>
                                                    <div class="third-level-frame">
                                                        <div class="third-level-title">
                                                            <?= htmlspecialcharsbx($sec3['title']) ?>
                                                        </div>
                                                        <?php if (!empty($sec3['settings'])): ?>
                                                            <?php 
                                                            // Проверяем, есть ли вложенные разделы в разделе третьего уровня
                                                            $hasSubsections = !empty($sec3['SUBSECTIONS']) && is_array($sec3['SUBSECTIONS']);

                                                            // Если есть вложенные разделы, выводим их как единое свойство
                                                            if ($hasSubsections): 
                                                            ?>
                                                                <div class="hidden-setting-item nested-sections-property" data-setting-code="nested_<?= htmlspecialcharsbx($sec3['id']) ?>">
                                                                    <div class="setting-label">
                                                                        <?= Loc::getMessage('QWELP_SITE_SETTINGS_NESTED_SECTIONS', ['#TITLE#' => htmlspecialcharsbx($sec3['title'])]) ?>
                                                                    </div>
                                                                    <div class="nested-sections-container">
                                                                        <?php 
                                                                            $isFirst = true;
                                                                            foreach ($sec3['SUBSECTIONS'] as $sec4): ?>
                                                                            <div class="nested-section">
                                                                                <input 
                                                                                    type="radio" 
                                                                                    id="hidden_section_<?= htmlspecialcharsbx($sec4['id']) ?>" 
                                                                                    name="section_<?= htmlspecialcharsbx($sec3['id']) ?>_subsection" 
                                                                                    value="<?= htmlspecialcharsbx($sec4['id']) ?>"
                                                                                    class="nested-section-radio"
                                                                                    <?= $isFirst ? 'checked' : '' ?>
                                                                                >
                                                                                <?php $isFirst = false; ?>
                                                                                <label for="hidden_section_<?= htmlspecialcharsbx($sec4['id']) ?>" class="nested-section-title">
                                                                                    <?= htmlspecialcharsbx($sec4['title']) ?>
                                                                                </label>
                                                                                <?php if (!empty($sec4['settings'])): ?>
                                                                                    <div class="nested-section-settings">
                                                                                        <?php foreach ($sec4['settings'] as $setting): ?>
                                                                                            <?php
                                                                                            $width    = qwelpSiteSettingsWidth($setting['percent'] ?? null);
                                                                                            $typeData = $setting['type'];
                                                                                            if ($typeData === '' && isset($setting['options']['color'])) {
                                                                                                $typeData = 'color';
                                                                                            }
                                                                                            ?>
                                                                                            <div class="nested-setting-item"
                                                                                                 data-setting-code="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                                                 data-setting-type="<?= htmlspecialcharsbx($typeData) ?>"
                                                                                                 style="flex-basis: <?= htmlspecialcharsbx($width) ?>; max-width: <?= htmlspecialcharsbx($width) ?>;"
                                                                                            >
                                                                                                <div class="setting-label">
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

                                                                                                <?php
                                                                                                $type = $setting['type'];
                                                                                                if ($type === '' && isset($setting['options']['color'])) {
                                                                                                    $type = 'color';
                                                                                                }
                                                                                                if ($type === 'checkbox'): ?>
                                                                                                    <label class="toggle-wrapper">
                                                                                                        <div class="toggle-relative">
                                                                                                            <input
                                                                                                                    type="checkbox"
                                                                                                                    id="setting_<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                                                                    name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                                                                    class="toggle-input"
                                                                                                            >
                                                                                                            <div class="toggle-bg"></div>
                                                                                                            <div class="toggle-dot"></div>
                                                                                                        </div>
                                                                                                    </label>
                                                                                                <?php elseif ($type === 'radio' && is_array($setting['options'])): ?>
                                                                                                    <div class="radio-options-wrapper">
                                                                                                        <?php foreach ($setting['options'] as $opt): ?>
                                                                                                            <?php
                                                                                                            $optValue = htmlspecialcharsbx($opt['value'] ?? '');
                                                                                                            $optLabel = htmlspecialcharsbx($opt['label'] ?? $optValue);
                                                                                                            $optId    = 'setting_' . htmlspecialcharsbx($setting['code']) . '_' . preg_replace('/[^a-z0-9_]/i', '_', $optValue);
                                                                                                            ?>
                                                                                                            <input
                                                                                                                    type="radio"
                                                                                                                    id="<?= $optId ?>"
                                                                                                                    name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                                                                    value="<?= $optValue ?>"
                                                                                                                    class="option-input"
                                                                                                            >
                                                                                                            <label
                                                                                                                    for="<?= $optId ?>"
                                                                                                                    class="option-label"
                                                                                                            >
                                                                                                                <?= $optLabel ?>
                                                                                                            </label>
                                                                                                        <?php endforeach; ?>
                                                                                                    </div>
                                                                                                <?php elseif ($type === 'select' && is_array($setting['options'])): ?>
                                                                                                    <div class="select-wrapper">
                                                                                                        <select
                                                                                                                name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                                                                id="setting_<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                                                                class="custom-select"
                                                                                                        >
                                                                                                            <?php foreach ($setting['options'] as $opt): ?>
                                                                                                                <?php
                                                                                                                $optValue = htmlspecialcharsbx($opt['value'] ?? '');
                                                                                                                $optLabel = htmlspecialcharsbx($opt['label'] ?? $optValue);
                                                                                                                ?>
                                                                                                                <option value="<?= $optValue ?>">
                                                                                                                    <?= $optLabel ?>
                                                                                                                </option>
                                                                                                            <?php endforeach; ?>
                                                                                                        </select>
                                                                                                    </div>
                                                                                                <?php elseif ($type === 'color'): ?>
                                                                                                    <?php $colorOpts = $setting['options']['color'] ?? ($setting['options'] ?? []); ?>
                                                                                                    <div class="color-options-wrapper">
                                                                                                        <?php if (!empty($colorOpts) && is_array($colorOpts)): ?>
                                                                                                            <?php foreach ($colorOpts as $idx => $opt): ?>
                                                                                                                <?php
                                                                                                                $val = htmlspecialcharsbx($opt['value'] ?? '');
                                                                                                                $lab = htmlspecialcharsbx($opt['label'] ?? $val);
                                                                                                                $id  = 'setting_' . htmlspecialcharsbx($setting['code']) . '_' . $idx;
                                                                                                                ?>
                                                                                                                <label class="color-option" title="<?= $lab ?>">
                                                                                                                    <input type="radio" id="<?= $id ?>" name="<?= htmlspecialcharsbx($setting['code']) ?>" value="<?= $val ?>" class="color-option-input">
                                                                                                                    <span class="color-swatch" style="background-color: <?= $val ?>;"></span>
                                                                                                                </label>
                                                                                                            <?php endforeach; ?>
                                                                                                        <?php endif; ?>
                                                                                                        <div class="custom-color-block">
                                                                                                            <label class="color-option" title="<?= Loc::getMessage('QWELP_SITE_SETTINGS_CUSTOM_COLOR') ?>">
                                                                                                                <input type="radio" id="setting_<?= htmlspecialcharsbx($setting['code']) ?>_custom" name="<?= htmlspecialcharsbx($setting['code']) ?>" value="<?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>" class="color-option-input custom-color-radio">
                                                                                                                <span class="color-swatch custom-color-swatch"></span>
                                                                                                            </label>
                                                                                                            <input type="text" class="color-hex-input" value="<?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>">
                                                                                                            <input
                                                                                                                    type="color"
                                                                                                                    id="setting_<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                                                                    value="<?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>"
                                                                                                                    class="color-picker-input"
                                                                                                            >
                                                                                                        </div>
                                                                                                    </div>
                                                                                                <?php elseif ($type === 'radioImage' && is_array($setting['options'])): ?>
                                                                                                    <div class="radio-image-options-wrapper">
                                                                                                        <?php foreach ($setting['options'] as $opt): ?>
                                                                                                            <?php
                                                                                                            $optValue = htmlspecialcharsbx($opt['value'] ?? '');
                                                                                                            $optLabel = htmlspecialcharsbx($opt['label'] ?? $optValue);
                                                                                                            $optId    = 'setting_' . htmlspecialcharsbx($setting['code']) . '_' . preg_replace('/[^a-z0-9_]/i', '_', $optValue);
                                                                                                            $imgPath  = htmlspecialcharsbx($opt['pathFile'] ?? '');
                                                                                                            ?>
                                                                                                            <div class="radio-image-option">
                                                                                                                <input
                                                                                                                        type="radio"
                                                                                                                        id="<?= $optId ?>"
                                                                                                                        name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                                                                        value="<?= $optValue ?>"
                                                                                                                        class="option-input"
                                                                                                                >
                                                                                                                <label for="<?= $optId ?>">
                                                                                                                    <?php if ($imgPath): ?>
                                                                                                                        <img src="<?= $imgPath ?>" alt="<?= $optLabel ?>" title="<?= $optLabel ?>">
                                                                                                                    <?php else: ?>
                                                                                                                        <span><?= $optLabel ?></span>
                                                                                                                    <?php endif; ?>
                                                                                                                </label>
                                                                                                            </div>
                                                                                                        <?php endforeach; ?>
                                                                                                    </div>
                                                                                                <?php else: ?>
                                                                                                    <p>
                                                                                                        <?= Loc::getMessage(
                                                                                                                'QWELP_SITE_SETTINGS_UNKNOWN_SETTING_TYPE',
                                                                                                                ['#TYPE#' => htmlspecialcharsbx($setting['type'])]
                                                                                                        ) ?>
                                                                                                    </p>
                                                                                                <?php endif; ?>
                                                                                            </div>
                                                                                        <?php endforeach; ?>
                                                                                    </div>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                </div>
                                                            <?php else: ?>
                                                                <?php foreach ($sec3['settings'] as $setting): ?>
                                                                    <?php
                                                                    $width    = qwelpSiteSettingsWidth($setting['percent'] ?? null);
                                                                    $typeData = $setting['type'];
                                                                    if ($typeData === '' && isset($setting['options']['color'])) {
                                                                        $typeData = 'color';
                                                                    }
                                                                    ?>
                                                                    <div class="hidden-setting-item"
                                                                         data-setting-code="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                         data-setting-type="<?= htmlspecialcharsbx($typeData) ?>"
                                                                         style="flex-basis: <?= htmlspecialcharsbx($width) ?>; max-width: <?= htmlspecialcharsbx($width) ?>;"
                                                                    >
                                                                    <div class="setting-label">
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

                                                                    <?php
                                                                    $type = $setting['type'];
                                                                    if ($type === '' && isset($setting['options']['color'])) {
                                                                        $type = 'color';
                                                                    }
                                                                    if ($type === 'checkbox'): ?>
                                                                        <label class="toggle-wrapper">
                                                                            <div class="toggle-relative">
                                                                                <input
                                                                                        type="checkbox"
                                                                                        id="setting_<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                                        name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                                        class="toggle-input"
                                                                                >
                                                                                <div class="toggle-bg"></div>
                                                                                <div class="toggle-dot"></div>
                                                                            </div>
                                                                        </label>
                                                                    <?php elseif ($type === 'radio' && is_array($setting['options'])): ?>
                                                                        <div class="radio-options-wrapper">
                                                                            <?php foreach ($setting['options'] as $opt): ?>
                                                                                <?php
                                                                                $optValue = htmlspecialcharsbx($opt['value'] ?? '');
                                                                                $optLabel = htmlspecialcharsbx($opt['label'] ?? $optValue);
                                                                                $optId    = 'setting_' . htmlspecialcharsbx($setting['code']) . '_' . preg_replace('/[^a-z0-9_]/i', '_', $optValue);
                                                                                ?>
                                                                                <input
                                                                                        type="radio"
                                                                                        id="<?= $optId ?>"
                                                                                        name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                                        value="<?= $optValue ?>"
                                                                                        class="option-input"
                                                                                >
                                                                                <label
                                                                                        for="<?= $optId ?>"
                                                                                        class="option-label"
                                                                                >
                                                                                    <?= $optLabel ?>
                                                                                </label>
                                                                            <?php endforeach; ?>
                                                                        </div>
                                                                    <?php elseif ($type === 'select' && is_array($setting['options'])): ?>
                                                                        <div class="select-wrapper">
                                                                            <select
                                                                                    name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                                    id="setting_<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                                    class="custom-select"
                                                                            >
                                                                                <?php foreach ($setting['options'] as $opt): ?>
                                                                                    <?php
                                                                                    $optValue = htmlspecialcharsbx($opt['value'] ?? '');
                                                                                    $optLabel = htmlspecialcharsbx($opt['label'] ?? $optValue);
                                                                                    ?>
                                                                                    <option value="<?= $optValue ?>">
                                                                                        <?= $optLabel ?>
                                                                                    </option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                    <?php elseif ($type === 'color'): ?>
                                                                        <?php $colorOpts = $setting['options']['color'] ?? ($setting['options'] ?? []); ?>
                                                                        <?php if (!empty($colorOpts) && is_array($colorOpts)): ?>
                                                                            <div class="color-options-wrapper">
                                                                                <?php foreach ($colorOpts as $idx => $opt): ?>
                                                                                    <?php
                                                                                    $val = htmlspecialcharsbx($opt['value'] ?? '');
                                                                                    $lab = htmlspecialcharsbx($opt['label'] ?? $val);
                                                                                    $id  = 'setting_' . htmlspecialcharsbx($setting['code']) . '_' . $idx;
                                                                                    ?>
                                                                                    <label class="color-option" title="<?= $lab ?>">
                                                                                        <input type="radio" id="<?= $id ?>" name="<?= htmlspecialcharsbx($setting['code']) ?>" value="<?= $val ?>" class="color-option-input">
                                                                                        <span class="color-swatch" style="background-color: <?= $val ?>;"></span>
                                                                                    </label>
                                                                                <?php endforeach; ?>
                                                                                <div class="custom-color-block">
                                                                                    <label class="color-option" title="<?= Loc::getMessage('QWELP_SITE_SETTINGS_CUSTOM_COLOR') ?>">
                                                                                        <input type="radio" id="setting_<?= htmlspecialcharsbx($setting['code']) ?>_custom" name="<?= htmlspecialcharsbx($setting['code']) ?>" value="<?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>" class="color-option-input custom-color-radio">
                                                                                        <span class="color-swatch custom-color-swatch" style="background-color: <?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>;"></span>
                                                                                    </label>
                                                                                    <input type="text" class="color-hex-input" value="<?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>">
                                                                                    <input type="color" value="<?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>" class="color-picker-input">
                                                                                </div>
                                                                            </div>
                                                                        <?php else: ?>
                                                                            <div class="color-options-wrapper">
                                                                                <div class="custom-color-block">
                                                                                    <label class="color-option" title="<?= Loc::getMessage('QWELP_SITE_SETTINGS_CUSTOM_COLOR') ?>">
                                                                                        <input type="radio" id="setting_<?= htmlspecialcharsbx($setting['code']) ?>_custom" name="<?= htmlspecialcharsbx($setting['code']) ?>" value="<?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>" class="color-option-input custom-color-radio" checked>
                                                                                    </label>
                                                                                    <input type="text" class="color-hex-input" value="<?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>">
                                                                                    <input type="color" value="<?= htmlspecialcharsbx($setting['value'] ?? '#000000') ?>" class="color-picker-input">
                                                                                </div>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    <?php elseif ($type === 'radioImage' && is_array($setting['options'])): ?>
                                                                        <div class="radio-image-options-wrapper">
                                                                            <?php foreach ($setting['options'] as $opt): ?>
                                                                                <?php
                                                                                $optValue = htmlspecialcharsbx($opt['value'] ?? '');
                                                                                $optLabel = htmlspecialcharsbx($opt['label'] ?? $optValue);
                                                                                $optId    = 'setting_' . htmlspecialcharsbx($setting['code']) . '_' . preg_replace('/[^a-z0-9_]/i', '_', $optValue);
                                                                                $imgPath  = htmlspecialcharsbx($opt['pathFile'] ?? '');
                                                                                ?>
                                                                                <div class="radio-image-option">
                                                                                    <input
                                                                                            type="radio"
                                                                                            id="<?= $optId ?>"
                                                                                            name="<?= htmlspecialcharsbx($setting['code']) ?>"
                                                                                            value="<?= $optValue ?>"
                                                                                            class="option-input"
                                                                                    >
                                                                                    <label for="<?= $optId ?>">
                                                                                        <?php if ($imgPath): ?>
                                                                                            <img src="<?= $imgPath ?>" alt="<?= $optLabel ?>" title="<?= $optLabel ?>">
                                                                                        <?php else: ?>
                                                                                            <span><?= $optLabel ?></span>
                                                                                        <?php endif; ?>
                                                                                    </label>
                                                                                </div>
                                                                            <?php endforeach; ?>
                                                                        </div>
                                                                    <?php else: ?>
                                                                        <p>
                                                                            <?= Loc::getMessage(
                                                                                    'QWELP_SITE_SETTINGS_UNKNOWN_SETTING_TYPE',
                                                                                    ['#TYPE#' => htmlspecialcharsbx($setting['type'])]
                                                                            ) ?>
                                                                        </p>
                                                                    <?php endif; ?>

                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div><!-- /.tab-content -->
                                <?php endforeach; ?>
                            </div><!-- /.tabs-container -->
                        <?php endif; ?>

                    </div><!-- /.settings-section -->
                <?php endforeach; ?>

            </div><!-- /.settings-content -->
            </div><!-- /.settings-main-wrapper -->

            <div class="settings-actions">
                <button class="btn-reset" type="button">
                    <?= Loc::getMessage('QWELP_SITE_SETTINGS_RESET_BUTTON') ?>
                </button>
                <button class="btn-apply" type="button">
                    <?= Loc::getMessage('QWELP_SITE_SETTINGS_APPLY_BUTTON') ?>
                </button>
            </div>

        </div><!-- /.settings-panel -->
    </div><!-- /.settings-overlay -->
<?php endif; ?>
