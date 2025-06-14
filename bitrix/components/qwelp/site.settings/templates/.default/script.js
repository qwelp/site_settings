/**
 * Управление панелью настроек сайта на основе BX.ajax.runComponentAction.
 * @package qwelp.site_settings
 */
BX.ready(function() {
    if (typeof window.QwelpSettingsConfig === 'undefined') {
        console.error('QwelpSettings: Конфигурация не найдена.');
        return;
    }

    class SiteSettingsManager {
        constructor(config) {
            this.config = config;
            this.elements = {
                root: document.getElementById('qwelp-site-settings-root'),
                openBtn: document.getElementById('open-settings-btn'),
                overlay: document.querySelector('.settings-overlay'),
                panel: document.querySelector('.settings-panel'),
                closeBtn: document.querySelector('.settings-panel__close-btn'),
                applyBtn: document.querySelector('.btn--apply'),
                resetBtn: document.querySelector('.btn--reset'),
            };

            this.state = this.createInitialState();
            this.originalState = JSON.parse(JSON.stringify(this.state));

            this.bindEvents();
            this.updateUIFromState();
            this.initSortable();
        }

        createInitialState() {
            const state = {};
            this.config.settings.sections?.forEach(sec1 => this.traverseSections(sec1, (setting) => {
                state[setting.code] = setting.value ?? (setting.type === 'checkbox' ? false : '');
            }));
            return state;
        }

        traverseSections(section, callback) {
            section.settings?.forEach(callback);
            if (section.SUBSECTIONS) {
                Object.values(section.SUBSECTIONS).forEach(subSection => this.traverseSections(subSection, callback));
            }
        }

        bindEvents() {
            this.elements.openBtn?.addEventListener('click', () => this.open());
            this.elements.closeBtn?.addEventListener('click', () => this.close());
            this.elements.overlay?.addEventListener('click', (e) => {
                if (e.target === this.elements.overlay) this.close();
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.close();
                    this.hideHelp();
                }
            });

            this.elements.applyBtn?.addEventListener('click', () => this.apply());
            this.elements.resetBtn?.addEventListener('click', () => this.reset());

            this.elements.panel.querySelectorAll('.settings-panel__nav-list li').forEach(item => {
                item.addEventListener('click', () => this.switchSection(item.dataset.sectionId));
            });

            this.elements.panel.querySelectorAll('.tab').forEach(tab => {
                tab.addEventListener('click', () => this.switchTab(tab.closest('.tabs-container'), tab.dataset.tabId));
            });

            this.elements.panel.addEventListener('click', (e) => {
                const collapsibleTitle = e.target.closest('.is-collapsible > .setting-group__title');
                if (collapsibleTitle) {
                    this.toggleCollapsibleBlock(collapsibleTitle.parentElement);
                    return;
                }

                const helpIcon = e.target.closest('.help-icon');
                if (helpIcon) {
                    e.stopPropagation();
                    this.toggleHelp(helpIcon);
                } else {
                    if (!e.target.closest('.qwelp-help-tooltip')) {
                        this.hideHelp();
                    }
                }
            });

            this.elements.panel.addEventListener('input', this.handleControlChange.bind(this));
            this.elements.panel.addEventListener('change', this.handleControlChange.bind(this));
        }

        initSortable() {
            if (typeof Sortable === 'undefined') {
                console.error('SortableJS is not defined. Make sure the library is connected.');
                return;
            }

            const containers = this.elements.panel.querySelectorAll('.js-sortable-container');
            containers.forEach(container => {
                new Sortable(container, {
                    handle: '.drag-handle-icon',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    // onEnd обработчик удален, т.к. сохранение не требуется
                });
            });
        }

        // Метод saveSort удален

        toggleCollapsibleBlock(block) {
            const isCollapsed = block.dataset.collapsed === 'true';
            block.dataset.collapsed = isCollapsed ? 'false' : 'true';
        }

        toggleHelp(icon) {
            const parentLabel = icon.parentElement;
            const existingTooltip = parentLabel.querySelector('.qwelp-help-tooltip');
            this.hideHelp();
            if (!existingTooltip) {
                this.showHelp(icon);
            }
        }

        showHelp(icon) {
            const text = icon.dataset.helpText;
            const image = icon.dataset.helpImage;
            if (!text && !image) return;

            const tooltip = document.createElement('div');
            tooltip.className = 'qwelp-help-tooltip';

            if (text) {
                const textEl = document.createElement('div');
                textEl.className = 'qwelp-help-tooltip__text';
                textEl.textContent = text;
                tooltip.appendChild(textEl);
            }
            if (image) {
                const imgEl = document.createElement('img');
                imgEl.className = 'qwelp-help-tooltip__image';
                imgEl.src = image;
                imgEl.alt = 'Подсказка';
                tooltip.appendChild(imgEl);
            }

            icon.parentElement.appendChild(tooltip);

            requestAnimationFrame(() => {
                tooltip.classList.add('active');
            });
        }

        hideHelp() {
            const activeTooltip = this.elements.panel.querySelector('.qwelp-help-tooltip.active');
            if (activeTooltip) {
                activeTooltip.remove();
            }
        }

        handleControlChange(e) {
            const target = e.target;
            const code = target.dataset.code;
            if (!code) return;

            const settingItem = target.closest('.setting-item');
            if (!settingItem) return;

            const type = settingItem.dataset.settingType;

            if (type === 'checkbox') {
                this.state[code] = target.checked;
            } else if ((type === 'radio' || type === 'radioImage') && target.checked) {
                const group = target.closest('.setting-group[data-common-group="true"]');
                if (group) {
                    const ownerCode = target.dataset.ownerCode;
                    if (ownerCode) {
                        this.state[ownerCode] = target.value;
                        group.querySelectorAll('[data-owner-code]').forEach(siblingInput => {
                            const siblingOwnerCode = siblingInput.dataset.ownerCode;
                            if (siblingOwnerCode && siblingOwnerCode !== ownerCode) {
                                this.state[siblingOwnerCode] = '';
                            }
                        });
                    }
                } else {
                    this.state[code] = target.value;
                }

                const colorPickerContainer = target.closest('.color-picker');
                if (colorPickerContainer) {
                    this.updateColorPickerUI(colorPickerContainer, target.value);
                }
            } else if (target.tagName.toLowerCase() === 'select') {
                this.state[code] = target.value;
            } else if (type === 'color') {
                let value = target.value.toLowerCase();
                if (/^#([0-9a-f]{3}){1,2}$/i.test(value)) {
                    this.state[code] = value;
                    this.updateColorPickerUI(settingItem.querySelector('.color-picker'), value);
                }
            }
        }

        open() {
            this.elements.overlay?.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        close() {
            this.elements.overlay?.classList.remove('active');
            document.body.style.overflow = '';
        }

        apply() {
            this.elements.applyBtn.disabled = true;

            BX.ajax.runComponentAction(this.config.componentName, 'saveSettings', {
                mode: 'class',
                signedParameters: this.config.signedParams,
                data: {
                    settings: this.state,
                    siteId: this.config.siteId
                }
            }).then(response => {
                if (response.data.success) {
                    alert(this.config.messages.SETTINGS_SAVED);
                    this.originalState = JSON.parse(JSON.stringify(this.state));
                    window.location.reload();
                } else {
                    alert(this.config.messages.SAVE_ERROR + (response.data.message || ''));
                }
            }).catch(error => {
                alert(this.config.messages.SAVE_ERROR_SIMPLE);
                console.error('Save settings error:', error);
            }).finally(() => {
                this.elements.applyBtn.disabled = false;
            });
        }

        reset() {
            if (confirm(this.config.messages.RESET_CONFIRM)) {
                this.state = JSON.parse(JSON.stringify(this.originalState));
                this.updateUIFromState();
            }
        }

        updateUIFromState() {
            Object.entries(this.state).forEach(([code, value]) => {
                const items = this.elements.panel.querySelectorAll(`.setting-item[data-setting-code="${code}"]`);
                if (items.length === 0) return;

                items.forEach(item => {
                    const type = item.dataset.settingType;

                    if (type === 'checkbox') {
                        const input = item.querySelector(`input[data-code="${code}"]`);
                        if (input) input.checked = value === true || value === 'Y';
                    } else if (type === 'radio' || type === 'radioImage') {
                        item.querySelectorAll(`input[type="radio"]`).forEach(input => {
                            input.checked = (String(input.value) === String(value) && value !== '');
                        });
                    } else if (type === 'select') {
                        const select = item.querySelector(`select[data-code="${code}"]`);
                        if (select) select.value = value;
                    } else if (type === 'color') {
                        const colorPicker = item.querySelector('.color-picker');
                        if (colorPicker) this.updateColorPickerUI(colorPicker, value);
                    }
                });
            });
        }

        updateColorPickerUI(pickerContainer, value) {
            if (!pickerContainer || typeof value !== 'string') return;

            const hexInput = pickerContainer.querySelector('.color-input-container__hex');
            const nativePicker = pickerContainer.querySelector('.visually-hidden[type="color"]');
            const normalizedValue = value.toLowerCase();

            if (hexInput && hexInput.value !== normalizedValue) {
                hexInput.value = normalizedValue;
            }

            if (nativePicker && nativePicker.value !== normalizedValue) {
                nativePicker.value = normalizedValue;
            }

            pickerContainer.querySelectorAll('.color-picker__radio').forEach(r => {
                r.checked = (r.value.toLowerCase() === normalizedValue);
            });
        }

        switchSection(sectionId) {
            this.elements.panel.querySelectorAll('.settings-panel__nav-list li').forEach(item => {
                item.classList.toggle('active', item.dataset.sectionId === sectionId);
            });
            this.elements.panel.querySelectorAll('.settings-section').forEach(sec => {
                sec.classList.toggle('active', sec.id === `section-${sectionId}`);
            });
        }

        switchTab(container, tabId) {
            container.querySelectorAll('.tab').forEach(tab => tab.classList.toggle('active', tab.dataset.tabId === tabId));
            container.querySelectorAll('.tab-content').forEach(content => content.classList.toggle('active', content.dataset.tabId === tabId));
        }
    }

    new SiteSettingsManager(window.QwelpSettingsConfig);
});