<?php
/**
 * Шаблон компонента панели настроек сайта.
 * @var CBitrixComponentTemplate $this
 * @var QwelpSiteSettingsComponent $component
 * @var array $arParams
 * @var array $arResult
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Qwelp\SiteSettings\OptionsManager;

$this->addExternalJS($this->GetFolder().'/Sortable.min.js');
require_once __DIR__ . '/functions.php';

if (!class_exists('TemplateRenderer')) {
    class TemplateRenderer
    {
        private CBitrixComponentTemplate $template;
        private string $templatePath;

        public function __construct(CBitrixComponentTemplate $template, string $templatePath)
        {
            $this->template = $template;
            $this->templatePath = $templatePath;
        }

        public function renderGroup(array $section, bool $isSortable = false, string $parentCode = ''): void
        {
            $template = $this->template;
            $templatePath = $this->templatePath;

            $allSettings = array_merge($section['settings'] ?? [], $section['HEADER_SETTINGS'] ?? []);
            $hasSettings = !empty($allSettings);
            $hasSubsections = !empty($section['SUBSECTIONS']);
            $isCommonGroup = !empty($section['UF_COMMON_PROPERTY']);
            $isCollapsible = !empty($section['UF_COLLAPSED_BLOCK']);
            $hasHeaderSettings = !empty($section['HEADER_SETTINGS']);

            if (!$hasSettings && !$hasSubsections) {
                return;
            }

            $groupClasses = ['setting-group'];
            $groupAttributes = 'data-sortable-id="' . htmlspecialcharsbx($section['id']) . '"';

            if ($isCollapsible) {
                $groupClasses[] = 'is-collapsible';
                $groupAttributes .= ' data-collapsed="true"';
            }
            if ($isCommonGroup) {
                $groupAttributes .= ' data-common-group="true" data-group-code="' . htmlspecialcharsbx($section['id']) . '"';
            }
            if ($parentCode) {
                $groupAttributes .= ' data-parent-code="' . htmlspecialcharsbx($parentCode) . '"';
            }

            $isRadioCardGroup = !empty($section['SUBSECTIONS']) && (int)reset($section['SUBSECTIONS'])['DEPTH'] === 4;

            $detailSettings = [];
            $normalSettings = [];

            if ($hasSettings) {
                foreach ($allSettings as $setting) {
                    if (empty($setting['isHeaderSetting']) && $setting['type'] !== 'radioCard') {
                        if (!empty($setting['detailProperty'])) {
                            $detailSettings[] = $setting;
                        } else {
                            $normalSettings[] = $setting;
                        }
                    }
                }
            }
            $hasDetailSettings = !empty($detailSettings);
            ?>
            <div class="<?= implode(' ', $groupClasses) ?>" <?= $groupAttributes ?>>
                <div class="setting-group__title">
                    <?php if ($isSortable): ?>
                        <span class="drag-handle-icon"></span>
                        <div class="setting-group__activity-toggle">
                            <label class="toggle-switch">
                                <input type="checkbox"
                                       class="toggle-switch__input"
                                       data-code="activity_<?= htmlspecialcharsbx($section['id']) ?>"
                                >
                                <span class="toggle-switch__slider"></span>
                            </label>
                        </div>
                    <?php endif; ?>
                    <span class="setting-group__title-text"><?= htmlspecialcharsbx($section['title']) ?></span>
                    <?php if (!empty($section['UF_SECTION_TOOLTIP'])): ?>
                        <span class="help-icon-wrapper">
                            <span class="help-icon"
                                  data-section-tooltip="<?= htmlspecialcharsbx($section['UF_SECTION_TOOLTIP']) ?>"
                                  title="<?= Loc::getMessage('QWELP_SITE_SETTINGS_HELP_ICON_TITLE') ?>">?</span>
                        </span>
                    <?php endif; ?>
                    <?php if ($hasHeaderSettings): ?>
                        <div class="setting-group__header-controls">
                            <?php
                            $isHeaderRender = true;
                            foreach ($section['HEADER_SETTINGS'] as $setting) {
                                include $templatePath;
                            }
                            unset($isHeaderRender);
                            ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="setting-group__content">
                    <?php if ($isRadioCardGroup): ?>
                        <div class="radio-card-group">
                            <?php
                            $groupCode = $section['id'];
                            foreach ($section['SUBSECTIONS'] as $subSection) {
                                $isFullWidth = !empty($subSection['UF_FULL_WIDTH']) && (int)$subSection['UF_FULL_WIDTH'] === 1;
                                $this->renderRadioCard($subSection, $groupCode, $isFullWidth);
                            }
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php
                    if ($isCommonGroup) {
                        $commonRadioName = htmlspecialcharsbx($section['id']);
                    }
                    foreach ($normalSettings as $setting) {
                        include $templatePath;
                    }

                    if ($hasSubsections && !$isRadioCardGroup) {
                        $isChildrenSortable = !empty($section['UF_ENABLE_DRAG_AND_DROP']);
                        echo $isChildrenSortable ? '<div class="js-sortable-container" data-sort-group-code="' . htmlspecialcharsbx($section['id']) . '">' : '';
                        foreach ($section['SUBSECTIONS'] as $subSection) {
                            $this->renderGroup($subSection, $isChildrenSortable, $section['id']);
                        }
                        echo $isChildrenSortable ? '</div>' : '';
                    }

                    if ($hasDetailSettings):
                        $toggleText = !empty($section['UF_HIDDEN_ELEMENTS_TITLE'])
                            ? htmlspecialcharsbx($section['UF_HIDDEN_ELEMENTS_TITLE'])
                            : Loc::getMessage('QWELP_SITE_SETTINGS_SHOW_DETAILS');
                        $toggleDataAttr = !empty($section['UF_HIDDEN_ELEMENTS_TITLE'])
                            ? 'data-text-show="' . htmlspecialcharsbx($section['UF_HIDDEN_ELEMENTS_TITLE']) . '"'
                            : '';
                        ?>
                        <a class="detail-settings-toggle" role="button" <?= $toggleDataAttr ?>>
                            <?= $toggleText ?>
                        </a>
                        <div class="detail-settings-container">
                            <?php
                            foreach ($detailSettings as $setting) {
                                include $templatePath;
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($commonRadioName)) unset($commonRadioName); ?>
                </div>
            </div>
            <?php
        }

        private function renderRadioCard(array $subSection, string $groupCode, bool $isFullWidth = false): void
        {
            $templatePath = $this->templatePath;
            $radioId = 'radio_card_' . htmlspecialcharsbx($subSection['id']);
            $cardId = htmlspecialcharsbx($subSection['id']);

            $allSettings = array_merge($subSection['settings'] ?? [], $subSection['HEADER_SETTINGS'] ?? []);

            $detailSettings = [];
            $normalSettings = [];
            if (!empty($allSettings)) {
                foreach ($allSettings as $setting) {
                    if (!empty($setting['detailProperty'])) {
                        $detailSettings[] = $setting;
                    } else {
                        $normalSettings[] = $setting;
                    }
                }
            }
            $hasDetailSettings = !empty($detailSettings);
            
            $radioCardClasses = ['radio-card'];
            if ($isFullWidth) {
                $radioCardClasses[] = 'radio-card--full-width';
            }
            ?>
            <div class="<?= implode(' ', $radioCardClasses) ?>">
                <input type="radio"
                       name="<?= htmlspecialcharsbx($groupCode) ?>"
                       id="<?= $radioId ?>"
                       class="radio-card__input"
                       data-code="<?= htmlspecialcharsbx($groupCode) ?>"
                       value="<?= $cardId ?>"
                >
                <label for="<?= $radioId ?>" class="radio-card__label">
                    <div class="radio-card__header">
                        <div class="radio-card__title">
                            <span><?= htmlspecialcharsbx($subSection['title']) ?></span>
                            <?php if (!empty($subSection['UF_SECTION_TOOLTIP'])): ?>
                                <span class="help-icon-wrapper">
                                    <span class="help-icon"
                                          data-section-tooltip="<?= htmlspecialcharsbx($subSection['UF_SECTION_TOOLTIP']) ?>"
                                          title="<?= Loc::getMessage('QWELP_SITE_SETTINGS_HELP_ICON_TITLE') ?>">?</span>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                    if (!empty($subSection['PICTURE'])) {
                        $fileId = (int)$subSection['PICTURE'];
                        if ($fileId > 0 && ($filePath = CFile::GetPath($fileId))) {
                            ?>
                            <div class="radio-card__image-wrapper">
                                <img class="radio-card__image" src="<?= htmlspecialcharsbx($filePath) ?>" alt="<?= htmlspecialcharsbx($subSection['title']) ?>" loading="lazy">
                            </div>
                            <?php
                        }
                    }
                    ?>
                    <?php if (!empty($normalSettings) || !empty($detailSettings)): ?>
                        <div class="radio-card__content">
                            <?php foreach ($normalSettings as $setting) {
                                include $templatePath;
                            } ?>

                            <?php if ($hasDetailSettings):
                                $toggleText = !empty($subSection['UF_HIDDEN_ELEMENTS_TITLE'])
                                    ? htmlspecialcharsbx($subSection['UF_HIDDEN_ELEMENTS_TITLE'])
                                    : Loc::getMessage('QWELP_SITE_SETTINGS_SHOW_DETAILS');
                                $toggleDataAttr = !empty($subSection['UF_HIDDEN_ELEMENTS_TITLE'])
                                    ? 'data-text-show="' . htmlspecialcharsbx($subSection['UF_HIDDEN_ELEMENTS_TITLE']) . '"'
                                    : '';
                                ?>
                                <a class="detail-settings-toggle" role="button" <?= $toggleDataAttr ?>>
                                    <?= $toggleText ?>
                                </a>
                                <div class="detail-settings-container">
                                    <?php foreach ($detailSettings as $setting) {
                                        include $templatePath;
                                    } ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </label>
            </div>
            <?php
        }

        private function renderSettings(array $settings, bool $isCommonGroup, string $groupId): void {
            $templatePath = $this->templatePath;
            if ($isCommonGroup) $commonRadioName = htmlspecialcharsbx($groupId);
            foreach ($settings as $setting) {
                if (!empty($setting['isHeaderSetting'])) continue;
                include $templatePath;
            }
            unset($commonRadioName);
        }
    }
}

$renderer = new TemplateRenderer($this, __DIR__ . '/_setting_renderer.php');
$savedValues = OptionsManager::getAll($arResult['SITE_ID']);
?>
<div id="qwelp-site-settings-root" class="qwelp-site-settings">
    <script>
        window.QwelpSettingsConfig = {
            settings: <?= Json::encode($arResult['SETTINGS']) ?>,
            savedValues: <?= Json::encode($savedValues) ?>,
            siteId: '<?= CUtil::JSEscape($arResult['SITE_ID']) ?>',
            componentName: '<?= CUtil::JSEscape($this->getComponent()->getName()) ?>',
            signedParams: '<?= CUtil::JSEscape($this->getComponent()->getSignedParameters()) ?>',
            messages: {
                SETTINGS_SAVED: '<?= CUtil::JSEscape(Loc::getMessage('QWELP_SITE_SETTINGS_SETTINGS_SAVED')) ?>',
                SAVE_ERROR: '<?= CUtil::JSEscape(Loc::getMessage('QWELP_SITE_SETTINGS_SAVE_ERROR')) ?>',
                RESET_CONFIRM: '<?= CUtil::JSEscape(Loc::getMessage('QWELP_SITE_SETTINGS_RESET_CONFIRM')) ?>',
                SHOW_DETAILS_TEXT: '<?= CUtil::JSEscape(Loc::getMessage('QWELP_SITE_SETTINGS_SHOW_DETAILS')) ?>',
                HIDE_DETAILS_TEXT: '<?= CUtil::JSEscape(Loc::getMessage('QWELP_SITE_SETTINGS_HIDE_DETAILS')) ?>'
            }
        };
    </script>
    <button id="open-settings-btn" class="settings-button" type="button">
        <?= Loc::getMessage('QWELP_SITE_SETTINGS_OPEN_BUTTON') ?>
    </button>
    <?php if (!empty($arResult['SETTINGS']['sections'])): ?>
        <div class="settings-overlay">
            <div class="settings-panel">
                <div class="settings-panel__wrapper">
                    <nav class="settings-panel__nav">
                        <ul class="settings-panel__nav-list">
                            <?php foreach (array_values($arResult['SETTINGS']['sections']) as $i => $sectionLevel1): if ((int)$sectionLevel1['DEPTH'] !== 1) continue; ?>
                                <li data-section-id="<?= htmlspecialcharsbx($sectionLevel1['id']) ?>" class="<?= $i === 0 ? 'active' : '' ?>">
                                    <?= htmlspecialcharsbx($sectionLevel1['title']) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </nav>
                    <main class="settings-panel__content">
                        <header class="settings-panel__header">
                            <h2 class="settings-panel__title"><?= Loc::getMessage('QWELP_SITE_SETTINGS_TITLE') ?></h2>
                            <button class="settings-panel__close-btn" type="button" title="<?= Loc::getMessage('QWELP_SITE_SETTINGS_CLOSE_BUTTON_TITLE') ?>">×</button>
                        </header>
                        <?php foreach (array_values($arResult['SETTINGS']['sections']) as $i => $sectionLevel1): if ((int)$sectionLevel1['DEPTH'] !== 1) continue; ?>
                            <section id="section-<?= htmlspecialcharsbx($sectionLevel1['id']) ?>" class="settings-section <?= $i === 0 ? 'active' : '' ?>">
                                <?php $sectionsLevel2 = array_filter($sectionLevel1['SUBSECTIONS'] ?? [], fn($sec) => (int)$sec['DEPTH'] === 2); ?>
                                <?php if (!empty($sectionsLevel2)): ?>
                                    <div class="tabs-container">
                                        <?php if (count($sectionsLevel2) > 1): ?>
                                            <div class="tabs-header">
                                                <?php foreach (array_values($sectionsLevel2) as $j => $sectionLevel2): ?>
                                                    <div class="tab <?= $j === 0 ? 'active' : '' ?>" data-tab-id="<?= htmlspecialcharsbx($sectionLevel2['id']) ?>">
                                                        <span><?= htmlspecialcharsbx($sectionLevel2['title']) ?></span>
                                                        <?php if (!empty($sectionLevel2['UF_SECTION_TOOLTIP'])): ?>
                                                            <span class="help-icon-wrapper">
                                                                <span class="help-icon"
                                                                      data-section-tooltip="<?= htmlspecialcharsbx($sectionLevel2['UF_SECTION_TOOLTIP']) ?>"
                                                                      title="<?= Loc::getMessage('QWELP_SITE_SETTINGS_HELP_ICON_TITLE') ?>">?</span>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php foreach (array_values($sectionsLevel2) as $j => $sectionLevel2): ?>
                                            <div class="tab-content <?= $j === 0 ? 'active' : '' ?>" data-tab-id="<?= htmlspecialcharsbx($sectionLevel2['id']) ?>">
                                                <?php
                                                $isChildrenSortable = !empty($sectionLevel2['UF_ENABLE_DRAG_AND_DROP']);
                                                $sectionsLevel3 = array_filter($sectionLevel2['SUBSECTIONS'] ?? [], fn($sec) => (int)$sec['DEPTH'] === 3);

                                                if ($isChildrenSortable) {
                                                    $sortKey = 'blocks_sort_' . $sectionLevel2['id'];
                                                    if (isset($savedValues[$sortKey]) && is_array($savedValues[$sortKey])) {
                                                        $savedOrder = array_flip($savedValues[$sortKey]);
                                                        uasort($sectionsLevel3, function ($a, $b) use ($savedOrder) {
                                                            $posA = $savedOrder[$a['id']] ?? 999;
                                                            $posB = $savedOrder[$b['id']] ?? 999;
                                                            return $posA <=> $posB;
                                                        });
                                                    }
                                                }

                                                if ($isChildrenSortable) echo '<div class="js-sortable-container" data-sort-group-code="' . htmlspecialcharsbx($sectionLevel2['id']) . '">';
                                                foreach ($sectionsLevel3 as $sectionLevel3) {
                                                    $renderer->renderGroup($sectionLevel3, $isChildrenSortable, $sectionLevel2['id']);
                                                }
                                                if ($isChildrenSortable) echo '</div>';
                                                ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </section>
                        <?php endforeach; ?>
                    </main>
                </div>
                <footer class="settings-panel__footer">
                    <button class="btn btn--reset" type="button"><?= Loc::getMessage('QWELP_SITE_SETTINGS_RESET_BUTTON') ?></button>
                    <button class="btn btn--apply" type="button"><?= Loc::getMessage('QWELP_SITE_SETTINGS_APPLY_BUTTON') ?></button>
                </footer>
            </div>
        </div>
    <?php endif; ?>
</div>