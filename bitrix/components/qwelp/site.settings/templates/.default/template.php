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

// Подключаем JS-библиотеку для Drag-n-Drop из папки шаблона
$this->addExternalJS($this->GetFolder().'/Sortable.min.js');

// Подключаем файл со вспомогательными функциями
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

        public function renderGroup(array $section, bool $isSortable = false): void
        {
            $template = $this->template;
            $templatePath = $this->templatePath;

            $hasSettings = !empty($section['settings']);
            $hasSubsections = !empty($section['SUBSECTIONS']);
            $isCommonGroup = !empty($section['UF_COMMON_PROPERTY']);
            $isCollapsible = !empty($section['UF_COLLAPSED_BLOCK']);

            $groupClasses = ['setting-group'];
            $groupAttributes = 'data-sortable-id="' . htmlspecialcharsbx($section['id']) . '"';

            if ($isCollapsible) {
                $groupClasses[] = 'is-collapsible';
                $groupAttributes .= ' data-collapsed="true"';
            }
            if ($isCommonGroup) {
                $groupAttributes .= ' data-common-group="true" data-group-code="' . htmlspecialcharsbx($section['id']) . '"';
            }

            $isRadioCardGroup = $hasSubsections && (int)reset($section['SUBSECTIONS'])['DEPTH'] === 4;

            if ($isRadioCardGroup) {
                ?>
                <div class="<?= implode(' ', $groupClasses) ?>" <?= $groupAttributes ?>>
                    <div class="setting-group__title">
                        <?php if ($isSortable): ?><span class="drag-handle-icon"></span><?php endif; ?>
                        <?= htmlspecialcharsbx($section['title']) ?>
                    </div>
                    <div class="setting-group__content">
                        <div class="radio-card-group">
                            <?php foreach ($section['SUBSECTIONS'] as $subSection): ?>
                                <?php $this->renderRadioCard($subSection); ?>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($hasSettings): ?>
                            <div class="setting-group__sub-controls">
                                <?php $this->renderSettings($section['settings'], $isCommonGroup, $section['id']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
            } elseif ($hasSettings || $hasSubsections) {
                ?>
                <div class="<?= implode(' ', $groupClasses) ?>" <?= $groupAttributes ?>>
                    <div class="setting-group__title">
                        <?php if ($isSortable): ?><span class="drag-handle-icon"></span><?php endif; ?>
                        <?= htmlspecialcharsbx($section['title']) ?>
                    </div>
                    <div class="setting-group__content">
                        <?php if ($hasSettings) $this->renderSettings($section['settings'], $isCommonGroup, $section['id']); ?>
                        <?php
                        if ($hasSubsections) {
                            $isChildrenSortable = !empty($section['UF_ENABLE_DRAG_AND_DROP']);
                            echo $isChildrenSortable ? '<div class="js-sortable-container">' : '';
                            foreach ($section['SUBSECTIONS'] as $subSection) {
                                $this->renderGroup($subSection, $isChildrenSortable);
                            }
                            echo $isChildrenSortable ? '</div>' : '';
                        }
                        ?>
                    </div>
                </div>
                <?php
            }
        }

        private function renderRadioCard(array $subSection): void {
            $templatePath = $this->templatePath;
            $isFirstCard = false;
            $radioGroupName = 'radio_card_group_' . htmlspecialcharsbx($subSection['PARENT_ID']);
            $radioId = 'radio_card_' . htmlspecialcharsbx($subSection['id']);
            ?>
            <div class="radio-card">
                <input type="radio" name="<?= $radioGroupName ?>" id="<?= $radioId ?>" class="radio-card__input" <?= $isFirstCard ? 'checked' : '' ?>>
                <label for="<?= $radioId ?>" class="radio-card__label">
                    <div class="radio-card__title"><?= htmlspecialcharsbx($subSection['title']) ?></div>
                    <?php
                    if (!empty($subSection['PICTURE'])) {
                        $fileId = (int)$subSection['PICTURE'];
                        if ($fileId > 0 && ($filePath = CFile::GetPath($fileId))) {
                            ?><img class="radio-card__image" src="<?= htmlspecialcharsbx($filePath) ?>" alt="<?= htmlspecialcharsbx($subSection['title']) ?>" loading="lazy"><?php
                        }
                    }
                    ?>
                    <?php if (!empty($subSection['settings'])): ?>
                        <div class="radio-card__content">
                            <?php foreach ($subSection['settings'] as $setting) include $templatePath; ?>
                        </div>
                    <?php endif; ?>
                </label>
            </div>
            <?php
        }

        private function renderSettings(array $settings, bool $isCommonGroup, string $groupId): void {
            $templatePath = $this->templatePath;
            if ($isCommonGroup) $commonRadioName = htmlspecialcharsbx($groupId);
            foreach ($settings as $setting) include $templatePath;
            unset($commonRadioName);
        }
    }
}

$renderer = new TemplateRenderer($this, __DIR__ . '/_setting_renderer.php');
?>
<div id="qwelp-site-settings-root" class="qwelp-site-settings">
    <script>
        window.QwelpSettingsConfig = {
            settings: <?= Json::encode($arResult['SETTINGS']) ?>,
            siteId: '<?= CUtil::JSEscape($arResult['SITE_ID']) ?>',
            componentName: '<?= CUtil::JSEscape($this->getComponent()->getName()) ?>',
            signedParams: '<?= CUtil::JSEscape($this->getComponent()->getSignedParameters()) ?>',
            messages: {
                SETTINGS_SAVED: '<?= CUtil::JSEscape(Loc::getMessage('QWELP_SITE_SETTINGS_SETTINGS_SAVED')) ?>',
                SAVE_ERROR: '<?= CUtil::JSEscape(Loc::getMessage('QWELP_SITE_SETTINGS_SAVE_ERROR')) ?>',
                RESET_CONFIRM: '<?= CUtil::JSEscape(Loc::getMessage('QWELP_SITE_SETTINGS_RESET_CONFIRM')) ?>'
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
                                                        <?= htmlspecialcharsbx($sectionLevel2['title']) ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php foreach (array_values($sectionsLevel2) as $j => $sectionLevel2): ?>
                                            <div class="tab-content <?= $j === 0 ? 'active' : '' ?>" data-tab-id="<?= htmlspecialcharsbx($sectionLevel2['id']) ?>">
                                                <?php
                                                $isChildrenSortable = !empty($sectionLevel2['UF_ENABLE_DRAG_AND_DROP']);
                                                $sectionsLevel3 = array_filter($sectionLevel2['SUBSECTIONS'] ?? [], fn($sec) => (int)$sec['DEPTH'] === 3);
                                                if ($isChildrenSortable) echo '<div class="js-sortable-container">';
                                                foreach ($sectionsLevel3 as $sectionLevel3) {
                                                    $renderer->renderGroup($sectionLevel3, $isChildrenSortable);
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