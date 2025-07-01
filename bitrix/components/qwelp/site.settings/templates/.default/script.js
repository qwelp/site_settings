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
                tooltip: null,
                htmlBlockPopup: null,
            };

            this.state = this.createInitialState();
            this.originalState = JSON.parse(JSON.stringify(this.state));

            this.bindEvents();
            this.updateUIFromState();
            this.initSortable();
            this.createTooltipElement();
            this.createHtmlBlockPopup();
            this.checkStateChanges();
        }

        createInitialState() {
            const state = {};
            const allSections = this.config.settings?.sections || [];
            const savedValues = this.config.savedValues || {};

            this.traverseSections({ SUBSECTIONS: allSections }, (setting) => {
                const code = setting.code;
                if (!code || state.hasOwnProperty(code)) return;

                const savedValue = savedValues[code];
                let finalValue = (savedValue !== undefined && savedValue !== null && savedValue !== '') ? savedValue : setting.value;

                if (setting.hiddenCheckbox) {
                    if (finalValue === 'false') finalValue = false;
                    state[code] = (finalValue === null || finalValue === undefined) ? false : finalValue;
                } else if (setting.type === 'checkbox') {
                    state[code] = (finalValue === true || finalValue === 'true' || finalValue === 'Y');
                } else {
                    state[code] = finalValue ?? '';
                }
            });

            this.elements.panel.querySelectorAll('.js-sortable-container').forEach(container => {
                const groupCode = container.dataset.sortGroupCode;
                if (!groupCode) return;

                const sortKey = `blocks_sort_${groupCode}`;
                if (savedValues[sortKey] && Array.isArray(savedValues[sortKey])) {
                    state[sortKey] = savedValues[sortKey];
                } else {
                    state[sortKey] = Array.from(container.children).map(child => child.dataset.sortableId);
                }
            });

            this.elements.panel.querySelectorAll('.setting-group__activity-toggle .toggle-switch__input').forEach(toggle => {
                const code = toggle.dataset.code;
                if(savedValues[code] !== undefined) {
                    state[code] = (savedValues[code] === true || savedValues[code] === 'true');
                } else {
                    state[code] = true; // По умолчанию активны
                }
            });

            return state;
        }

        bindEvents() {
            this.elements.openBtn?.addEventListener('click', () => this.open());
            this.elements.closeBtn?.addEventListener('click', () => this.close());
            this.elements.overlay?.addEventListener('click', (e) => {
                if (e.target === this.elements.overlay || e.target.classList.contains('settings-panel')) {
                    this.hideHelp();
                }
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.close();
                    this.hideHelp();
                    this.hideHtmlBlockPopup();
                }
            });

            this.elements.applyBtn?.addEventListener('click', () => this.apply());
            this.elements.resetBtn?.addEventListener('click', () => this.reset());

            this.elements.panel.querySelectorAll('.settings-panel__nav-list li').forEach(item => {
                item.addEventListener('click', () => this.switchSection(item.dataset.sectionId));
            });

            this.elements.panel.querySelectorAll('.tab').forEach(tab => {
                tab.addEventListener('click', (e) => {
                    if (e.target.closest('.help-icon')) return;
                    this.switchTab(tab.closest('.tabs-container'), tab.dataset.tabId);
                });
            });

            this.elements.panel.addEventListener('click', (e) => {
                const collapsibleTitle = e.target.closest('.is-collapsible > .setting-group__title');
                if (collapsibleTitle && !e.target.closest('.setting-group__header-controls, .help-icon-wrapper, .setting-group__activity-toggle, .html-block-key')) {
                    this.toggleCollapsibleBlock(collapsibleTitle.parentElement);
                    return;
                }

                const detailToggle = e.target.closest('.detail-settings-toggle');
                if (detailToggle) {
                    this.toggleDetailSettings(detailToggle);
                    return;
                }

                const htmlBlockKey = e.target.closest('.html-block-key');
                if (htmlBlockKey) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.showHtmlBlockPopup(htmlBlockKey);
                    return;
                }

                const helpIcon = e.target.closest('.help-icon');
                if (helpIcon) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.toggleHelp(helpIcon);
                } else if (!e.target.closest('.qwelp-help-tooltip')) {
                    this.hideHelp();
                }
            });

            this.elements.panel.querySelector('.settings-panel__content').addEventListener('scroll', () => this.hideHelp());

            this.elements.panel.addEventListener('change', this.handleControlChange.bind(this));
            this.elements.panel.addEventListener('input', this.handleControlChange.bind(this));
        }

        handleControlChange(e) {
            const target = e.target;
            if (e.type === 'input' && (target.type === 'checkbox' || target.type === 'radio')) {
                return;
            }

            const code = target.dataset.code;
            if (!code) return;

            if (target.classList.contains('hidden-checkbox-enabler')) {
                if (target.checked) { // Включаем
                    // Восстанавливаем из data-атрибута или берем значение по умолчанию
                    const restoredValue = target.dataset.lastValue ?? target.dataset.defaultValue;
                    this.state[code] = restoredValue;
                } else { // Выключаем
                    // Сохраняем текущее значение в data-атрибут перед тем, как сбросить его в false
                    target.dataset.lastValue = this.state[code];
                    this.state[code] = false;
                }
                this.updateUIForEnabler(target);
            } else {
                const settingItem = target.closest('[data-setting-type], .header-control, .radio-card, .setting-group__activity-toggle');
                if (!settingItem) return;

                let type;
                if(settingItem.dataset.settingType) type = settingItem.dataset.settingType;
                else if (settingItem.matches('.header-control')) type = settingItem.className.match(/header-control--type-(\w+)/)[1];
                else if (settingItem.matches('.radio-card')) type = 'radio';
                else if (settingItem.matches('.setting-group__activity-toggle')) type = 'checkbox';
                if(!type) return;

                if (type === 'checkbox') {
                    this.state[code] = target.checked;
                    const toggleSwitch = target.closest('.toggle-switch');
                    if(toggleSwitch) toggleSwitch.classList.toggle('is-checked', target.checked);
                } else if (type === 'radio' || type === 'radioImage') {
                    if(target.checked) {
                        const group = target.closest('.setting-group[data-common-group="true"]');
                        if (group) {
                            const ownerCode = target.dataset.ownerCode;
                            if (ownerCode) {
                                group.querySelectorAll('[data-owner-code]').forEach(siblingInput => {
                                    const siblingOwnerCode = siblingInput.dataset.ownerCode;
                                    if (siblingOwnerCode && siblingOwnerCode !== ownerCode) {
                                        this.state[siblingOwnerCode] = '';
                                    }
                                });
                                this.state[ownerCode] = target.value;
                            }
                        } else {
                            this.state[code] = target.value;
                        }
                    }
                } else if (type === 'color') {
                    if (/^#([0-9a-f]{3}){1,2}$/i.test(target.value)) {
                        this.state[code] = target.value;
                        this.updateColorPickerUI(target.closest('.color-picker'), target.value);
                    }
                } else {
                    this.state[code] = target.value;
                }
            }

            this.checkStateChanges();
        }

        updateUIFromState() {
            this.elements.panel.querySelectorAll('.hidden-checkbox-enabler').forEach(this.updateUIForEnabler.bind(this));

            Object.entries(this.state).forEach(([code, value]) => {
                this.elements.panel.querySelectorAll(`[data-code="${code}"]:not([data-owner-code])`).forEach(control => {
                    if (control.closest('.hidden-checkbox-content')) return;

                    if (control.type === 'checkbox') {
                        control.checked = !!value;
                        const toggleSwitch = control.closest('.toggle-switch');
                        if (toggleSwitch) {
                            toggleSwitch.classList.toggle('is-checked', !!value);
                        }
                    } else if (control.type === 'radio') {
                        control.checked = (String(control.value) === String(value));
                    } else if (control.value !== value) {
                        control.value = value;
                    }
                });
            });

            this.elements.panel.querySelectorAll('.setting-group[data-common-group="true"]').forEach(group => {
                group.querySelectorAll('[data-owner-code]').forEach(control => {
                    const ownerCode = control.dataset.ownerCode;
                    if (this.state.hasOwnProperty(ownerCode)) {
                        control.checked = (String(this.state[ownerCode]) === String(control.value));
                    }
                });
            });

            this.checkStateChanges();
        }

        updateUIForEnabler(enabler) {
            const code = enabler.dataset.code;
            const value = this.state[code];
            const isEnabled = value !== false;
            const contentBlock = this.elements.panel.querySelector(`#${enabler.dataset.controlsId}`);
            const toggleSwitch = enabler.closest('.toggle-switch');

            enabler.checked = isEnabled;
            if (toggleSwitch) {
                toggleSwitch.classList.toggle('is-checked', isEnabled);
            }

            if (contentBlock) {
                contentBlock.classList.toggle('is-visible', isEnabled);
                if (isEnabled) {
                    const innerValue = value;
                    const innerColorPicker = contentBlock.querySelector('.color-picker');
                    const innerRadios = contentBlock.querySelectorAll('input[type="radio"]');
                    const innerSelect = contentBlock.querySelector('select');

                    if (innerColorPicker) {
                        this.updateColorPickerUI(innerColorPicker, innerValue);
                    } else if (innerRadios.length > 0) {
                        innerRadios.forEach(radio => {
                            radio.checked = (String(radio.value) === String(innerValue));
                        });
                    } else if (innerSelect) {
                        innerSelect.value = innerValue;
                    }
                }
            }
        }

        initSortable() {
            if (typeof Sortable === 'undefined') { return; }

            const containers = this.elements.panel.querySelectorAll('.js-sortable-container');
            containers.forEach(container => {
                new Sortable(container, {
                    handle: '.drag-handle-icon',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    onEnd: (evt) => {
                        const groupCode = evt.to.dataset.sortGroupCode;
                        if (!groupCode) return;

                        const sortKey = `blocks_sort_${groupCode}`;
                        const newOrder = Array.from(evt.to.children).map(child => child.dataset.sortableId);

                        this.state[sortKey] = newOrder;
                        this.checkStateChanges();
                    }
                });
            });
        }

        checkStateChanges() { const hasChanges = JSON.stringify(this.state) !== JSON.stringify(this.originalState); this.elements.applyBtn.disabled = !hasChanges; this.elements.resetBtn.disabled = !hasChanges; }
        createTooltipElement() { const tooltip = document.createElement('div'); tooltip.className = 'qwelp-help-tooltip'; document.body.appendChild(tooltip); this.elements.tooltip = tooltip; }
        createHtmlBlockPopup() { 
            const popup = document.createElement('div'); 
            popup.className = 'html-block-popup'; 

            const content = document.createElement('div');
            content.className = 'html-block-popup__content';

            const closeBtn = document.createElement('button');
            closeBtn.className = 'html-block-popup__close';
            closeBtn.innerHTML = '×';
            closeBtn.addEventListener('click', () => this.hideHtmlBlockPopup());

            popup.appendChild(content);
            popup.appendChild(closeBtn);

            popup.addEventListener('click', (e) => {
                if (e.target === popup) {
                    this.hideHtmlBlockPopup();
                }
            });

            document.body.appendChild(popup); 
            this.elements.htmlBlockPopup = popup; 
        }
        showHtmlBlockPopup(keyElement) {
            if (!keyElement || !this.elements.htmlBlockPopup) return;

            const value = keyElement.dataset.htmlBlockValue || '';
            if (!value) return;

            const content = this.elements.htmlBlockPopup.querySelector('.html-block-popup__content');
            if (content) {
                content.innerHTML = value;
            }

            this.elements.htmlBlockPopup.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        hideHtmlBlockPopup() {
            if (this.elements.htmlBlockPopup) {
                this.elements.htmlBlockPopup.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
        open() { this.elements.overlay?.classList.add('active'); document.body.style.overflow = 'hidden'; }
        close() { 
            this.elements.overlay?.classList.remove('active'); 
            document.body.style.overflow = ''; 
            this.hideHtmlBlockPopup();
        }
        apply() { this.elements.applyBtn.disabled = true; this.elements.resetBtn.disabled = true; BX.ajax.runComponentAction(this.config.componentName, 'saveSettings', { mode: 'class', signedParameters: this.config.signedParams, data: { settings: this.state, siteId: this.config.siteId } }).then(response => { if (response.data.success) { alert(this.config.messages.SETTINGS_SAVED); this.originalState = JSON.parse(JSON.stringify(this.state)); window.location.reload(); } else { alert(this.config.messages.SAVE_ERROR + (response.data.message || '')); this.checkStateChanges(); } }).catch(error => { alert(this.config.messages.SAVE_ERROR_SIMPLE); console.error('Save settings error:', error); this.checkStateChanges(); }); }
        reset() { if (confirm(this.config.messages.RESET_CONFIRM)) { this.state = JSON.parse(JSON.stringify(this.originalState)); this.updateUIFromState(); this.checkStateChanges(); } }
        traverseSections(node, callback) { if (node.settings && Array.isArray(node.settings)) { node.settings.forEach((setting) => callback(setting, node)); } if (node.HEADER_SETTINGS && Array.isArray(node.HEADER_SETTINGS)) { node.HEADER_SETTINGS.forEach((setting) => callback(setting, node)); } if (node.SUBSECTIONS && typeof node.SUBSECTIONS === 'object') { Object.values(node.SUBSECTIONS).forEach(subSection => { this.traverseSections(subSection, callback); }); } }
        toggleCollapsibleBlock(block) { const isCollapsed = block.dataset.collapsed === 'true'; block.dataset.collapsed = isCollapsed ? 'false' : 'true'; }
        toggleDetailSettings(button) { const context = button.closest('.setting-group__content, .radio-card__content'); if (!context) return; context.classList.toggle('details-shown'); if (!button.dataset.textShow) { const isShown = context.classList.contains('details-shown'); button.textContent = isShown ? this.config.messages.HIDE_DETAILS_TEXT : this.config.messages.SHOW_DETAILS_TEXT; } }
        toggleHelp(icon) { if (icon.classList.contains('active')) { this.hideHelp(); } else { this.showHelp(icon); } }
        showHelp(icon) { this.hideHelp(); const text = icon.dataset.helpText || icon.dataset.sectionTooltip || ''; const image = icon.dataset.helpImage || ''; if (!text && !image) return; const tooltip = this.elements.tooltip; let tooltipContent = ''; if (text) tooltipContent += `<div class="qwelp-help-tooltip__text">${text.replace(/\n/g, '<br>')}</div>`; if (image) tooltipContent += `<img class="qwelp-help-tooltip__image" src="${image}" alt="Подсказка">`; tooltip.innerHTML = tooltipContent; const iconRect = icon.getBoundingClientRect(); tooltip.style.left = `${iconRect.left + window.scrollX + (iconRect.width / 2)}px`; tooltip.style.top = `${iconRect.bottom + window.scrollY + 5}px`; tooltip.classList.add('active'); icon.classList.add('active'); }
        hideHelp() { const activeIcon = this.elements.panel.querySelector('.help-icon.active'); if (activeIcon) activeIcon.classList.remove('active'); if (this.elements.tooltip) this.elements.tooltip.classList.remove('active'); }
        isColorDark(hexColor) { if (!hexColor || typeof hexColor !== 'string') return false; let hex = hexColor.replace('#', ''); if (hex.length === 3) { hex = hex.split('').map(c => c + c).join(''); } const r = parseInt(hex.substring(0, 2), 16); const g = parseInt(hex.substring(2, 4), 16); const b = parseInt(hex.substring(4, 6), 16); return (0.299 * r + 0.587 * g + 0.114 * b) < 140; }
        updateColorPickerUI(pickerContainer, value) { if (!pickerContainer || typeof value !== 'string' || !/^#([0-9a-f]{3}){1,2}$/i.test(value)) return; const hexInput = pickerContainer.querySelector('.color-input-container__hex'); const nativePicker = pickerContainer.querySelector('.visually-hidden[type="color"]'); const pickerBtn = pickerContainer.querySelector('.color-input-container__picker-btn'); const normalizedValue = value.toLowerCase(); if (hexInput && hexInput.value !== normalizedValue) hexInput.value = normalizedValue; if (nativePicker && nativePicker.value !== normalizedValue) nativePicker.value = normalizedValue; if (pickerBtn) { pickerBtn.style.backgroundColor = normalizedValue; pickerBtn.classList.toggle('is-dark', this.isColorDark(normalizedValue)); } pickerContainer.querySelectorAll('.color-picker__radio').forEach(r => { r.checked = (r.value.toLowerCase() === normalizedValue); }); }
        switchSection(sectionId) { this.elements.panel.querySelectorAll('.settings-panel__nav-list li').forEach(item => { item.classList.toggle('active', item.dataset.sectionId === sectionId); }); this.elements.panel.querySelectorAll('.settings-section').forEach(sec => { sec.classList.toggle('active', sec.id === `section-${sectionId}`); }); }
        switchTab(container, tabId) { container.querySelectorAll('.tab').forEach(tab => tab.classList.toggle('active', tab.dataset.tabId === tabId)); container.querySelectorAll('.tab-content').forEach(content => content.classList.toggle('active', content.dataset.tabId === tabId)); }
    }

    new SiteSettingsManager(window.QwelpSettingsConfig);
});
