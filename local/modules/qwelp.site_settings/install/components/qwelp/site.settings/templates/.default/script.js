// script.js — управление панелью настроек с улучшенным drag&drop и collapse-блоками

let currentSettings = {};
let originalSettings = {};

document.addEventListener('DOMContentLoaded', () => {
    initSettingsPanel();
    initializeSettings();
    handleResponsive();
    initColorSwatches();

    document.querySelector('.btn-apply')?.addEventListener('click', applySettings);
    document.querySelector('.btn-reset')?.addEventListener('click', resetSettings);

    initDragAndDrop();
    initHelpIcons();
    initCollapseToggles();
});

/**
 * Инициализируем панель настроек: кнопки открытия/закрытия, переключение разделов
 */
function initSettingsPanel() {
    document.getElementById('open-settings')?.addEventListener('click', openSettings);
    document.querySelector('.settings-close')?.addEventListener('click', closeSettings);
    document.querySelector('.settings-overlay')?.addEventListener('click', e => {
        if (e.target === e.currentTarget) closeSettings();
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeSettings();
    });
    document.querySelectorAll('.settings-nav li').forEach(item => {
        item.addEventListener('click', () => switchSection(item.dataset.section));
    });
}

/**
 * Открыть панель настроек
 */
function openSettings() {
    const overlay = document.querySelector('.settings-overlay');
    if (overlay) {
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * Закрыть панель настроек
 */
function closeSettings() {
    const overlay = document.querySelector('.settings-overlay');
    if (overlay) {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
}

/**
 * Переключение видимого раздела
 * @param {string} sectionId
 */
function switchSection(sectionId) {
    document.querySelectorAll('.settings-nav li').forEach(item => {
        item.classList.toggle('active', item.dataset.section === sectionId);
    });
    document.querySelectorAll('.settings-section').forEach(sec => {
        sec.classList.toggle('active', sec.id === `section-${sectionId}`);
    });
}

/**
 * Собираем текущие настройки в объект currentSettings и сохраняем оригинал
 */
function initializeSettings() {
    if (typeof settingsData === 'undefined' || !Array.isArray(settingsData.sections)) {
        console.warn('settingsData.sections ожидается массив, получили:', settingsData);
        return;
    }

    settingsData.sections.forEach(sec1 => {
        if (Array.isArray(sec1.settings)) {
            sec1.settings.forEach(setting => {
                if (setting && setting.code) {
                    currentSettings[setting.code] = (setting.type === 'checkbox' ? false : null);
                }
            });
        }
        if (sec1.SUBSECTIONS && typeof sec1.SUBSECTIONS === 'object') {
            Object.values(sec1.SUBSECTIONS).forEach(sec2 => {
                if (Array.isArray(sec2.settings)) {
                    sec2.settings.forEach(setting => {
                        if (setting && setting.code) {
                            currentSettings[setting.code] = (setting.type === 'checkbox' ? false : null);
                        }
                    });
                }
                if (sec2.SUBSECTIONS && typeof sec2.SUBSECTIONS === 'object') {
                    Object.values(sec2.SUBSECTIONS).forEach(sec3 => {
                        if (Array.isArray(sec3.settings)) {
                            sec3.settings.forEach(setting => {
                                if (setting && setting.code) {
                                    currentSettings[setting.code] = (setting.type === 'checkbox' ? false : null);
                                }
                            });
                        }
                    });
                }
            });
        }
    });

    originalSettings = JSON.parse(JSON.stringify(currentSettings));
    updateSettingsUI();
}

/**
 * Обновляем UI (checkbox, radio, select) в соответствии с currentSettings
 */
function updateSettingsUI() {
    Object.entries(currentSettings).forEach(([code, value]) => {
        const cb = document.querySelector(`input[type="checkbox"][id="setting_${code}"]`);
        if (cb) {
            cb.checked = value === true;
        }
        const radios = document.querySelectorAll(`input[type="radio"][name="${code}"]`);
        radios.forEach(radio => {
            radio.checked = (radio.value === value);
        });
        const sel = document.querySelector(`select[name="${code}"]`);
        if (sel) {
            sel.value = value;
        }
        const col = document.querySelector(`input[type="color"][id="setting_${code}"]`);
        if (col && typeof value === 'string') {
            col.value = value;
            col.dispatchEvent(new Event('input'));
        }
    });
}

/**
 * Сохраняем настройки через AJAX (BX.ajax.runComponentAction) или симулируем сохранение локально
 */
function applySettings() {
    // Собираем значения из формы
    settingsData.sections.forEach(sec1 => {
        if (Array.isArray(sec1.settings)) {
            sec1.settings.forEach(setting => {
                const code = setting.code;
                if (setting.type === 'checkbox') {
                    const cb = document.querySelector(`input[type="checkbox"][id="setting_${code}"]`);
                    currentSettings[code] = cb ? cb.checked : currentSettings[code];
                } else if (setting.type === 'radio') {
                    const rd = document.querySelector(`input[name="${code}"]:checked`);
                    if (rd) currentSettings[code] = rd.value;
                } else if (setting.type === 'color') {
                    const col = document.querySelector(`input[type="color"][id="setting_${code}"]`);
                    if (col) currentSettings[code] = col.value;
                } else if (setting.type === 'select') {
                    const sel = document.querySelector(`select[name="${code}"]`);
                    if (sel) currentSettings[code] = sel.value;
                }
            });
        }
        if (sec1.SUBSECTIONS && typeof sec1.SUBSECTIONS === 'object') {
            Object.values(sec1.SUBSECTIONS).forEach(sec2 => {
                if (Array.isArray(sec2.settings)) {
                    sec2.settings.forEach(setting => {
                        const code = setting.code;
                        if (setting.type === 'checkbox') {
                            const cb = document.querySelector(`input[type="checkbox"][id="setting_${code}"]`);
                            currentSettings[code] = cb ? cb.checked : currentSettings[code];
                        } else if (setting.type === 'radio') {
                            const rd = document.querySelector(`input[name="${code}"]:checked`);
                            if (rd) currentSettings[code] = rd.value;
                        } else if (setting.type === 'color') {
                            const col = document.querySelector(`input[type="color"][id="setting_${code}"]`);
                            if (col) currentSettings[code] = col.value;
                        } else if (setting.type === 'select') {
                            const sel = document.querySelector(`select[name="${code}"]`);
                            if (sel) currentSettings[code] = sel.value;
                        }
                    });
                }
                if (sec2.SUBSECTIONS && typeof sec2.SUBSECTIONS === 'object') {
                    Object.values(sec2.SUBSECTIONS).forEach(sec3 => {
                        if (Array.isArray(sec3.settings)) {
                            sec3.settings.forEach(setting => {
                                const code = setting.code;
                                if (setting.type === 'checkbox') {
                                    const cb = document.querySelector(`input[type="checkbox"][id="setting_${code}"]`);
                                    currentSettings[code] = cb ? cb.checked : currentSettings[code];
                                } else if (setting.type === 'radio') {
                                    const rd = document.querySelector(`input[name="${code}"]:checked`);
                                    if (rd) currentSettings[code] = rd.value;
                                } else if (setting.type === 'color') {
                                    const col = document.querySelector(`input[type="color"][id="setting_${code}"]`);
                                    if (col) currentSettings[code] = col.value;
                                } else if (setting.type === 'select') {
                                    const sel = document.querySelector(`select[name="${code}"]`);
                                    if (sel) currentSettings[code] = sel.value;
                                }
                            });
                        }
                    });
                }
            });
        }
    });

    if (typeof ajaxUrl !== 'undefined' && ajaxUrl) {
        // Используем BX.ajax.runComponentAction, если в компоненте реализовано серверное действие
        BX.ajax.runComponentAction('qwelp:site.settings', 'saveSettings', {
            mode: 'class',
            data: {
                action: 'save_settings',
                settings: currentSettings,
                siteId: currentSiteId,
                sessid: BX.bitrix_sessid()
            }
        }).then(res => {
            if (res.data && res.data.success) {
                originalSettings = JSON.parse(JSON.stringify(currentSettings));
                alert(SETTINGS_MESSAGES.SETTINGS_SAVED);
            } else {
                const msg = (res.data && res.data.message) || SETTINGS_MESSAGES.UNKNOWN_ERROR;
                alert(SETTINGS_MESSAGES.SAVE_ERROR + msg);
            }
        }).catch(() => {
            alert(SETTINGS_MESSAGES.SAVE_ERROR_SIMPLE);
        });
    } else {
        // Симулируем сохранение локально
        originalSettings = JSON.parse(JSON.stringify(currentSettings));
        alert(SETTINGS_MESSAGES.SETTINGS_SAVED);
        console.warn("ajaxUrl не определён. Настройки сохранены локально (симуляция).");
    }
}

/**
 * Сбросить настройки к изначальным
 */
function resetSettings() {
    currentSettings = JSON.parse(JSON.stringify(originalSettings));
    updateSettingsUI();
}

function initColorSwatches() {
    document.querySelectorAll('.color-options-wrapper').forEach(wrapper => {
        const inputId = wrapper.dataset.colorInput;
        const colorInput = document.getElementById(inputId);
        if (!colorInput) return;
        const swatches = wrapper.querySelectorAll('.color-swatch');
        const update = () => {
            const val = colorInput.value.toLowerCase();
            swatches.forEach(s => s.classList.toggle('selected', s.dataset.color && s.dataset.color.toLowerCase() === val));
        };
        swatches.forEach(sw => {
            sw.addEventListener('click', () => {
                colorInput.value = sw.dataset.color;
                update();
            });
        });
        colorInput.addEventListener('input', update);
        update();
    });
}

/**
 * Инициализация drag&drop для второго уровня подразделов
 */
function initDragAndDrop() {
    let dragSrcEl = null;

    const draggables = document.querySelectorAll('.settings-subsection[data-enable-drag="1"]');
    draggables.forEach(subsec => {
        subsec.setAttribute('draggable', 'true');

        subsec.addEventListener('dragstart', event => {
            dragSrcEl = subsec;
            subsec.classList.add('dragging');
            subsec.style.opacity = '0.5';

            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', '');
        });

        subsec.addEventListener('dragover', event => {
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';

            const target = event.currentTarget;
            if (!dragSrcEl || target === dragSrcEl) return;
            if (target.dataset.enableDrag !== "1") return;

            document.querySelectorAll('.settings-subsection.over').forEach(el => el.classList.remove('over'));
            target.classList.add('over');

            const rect = target.getBoundingClientRect();
            const midpoint = rect.top + rect.height / 2;
            const insertBefore = event.clientY < midpoint;

            const parent = target.parentNode;
            const items = Array.from(parent.children).filter(child =>
                child.classList.contains('settings-subsection') &&
                child.dataset.enableDrag === "1"
            );
            const srcIndex = items.indexOf(dragSrcEl);
            const tgtIndex = items.indexOf(target);
            if (srcIndex === -1 || tgtIndex === -1) return;

            if (insertBefore && srcIndex !== tgtIndex - 1) {
                parent.insertBefore(dragSrcEl, target);
            } else if (!insertBefore && srcIndex !== tgtIndex + 1) {
                parent.insertBefore(dragSrcEl, target.nextSibling);
            }
        });

        subsec.addEventListener('dragleave', event => {
            if (event.currentTarget.contains(event.relatedTarget)) return;
            event.currentTarget.classList.remove('over');
        });

        subsec.addEventListener('drop', event => {
            event.stopPropagation();
            document.querySelectorAll('.settings-subsection.over').forEach(el => el.classList.remove('over'));
            if (dragSrcEl) {
                dragSrcEl.classList.remove('dragging');
                dragSrcEl.style.opacity = '';
            }
            dragSrcEl = null;
            return false;
        });

        subsec.addEventListener('dragend', () => {
            document.querySelectorAll('.settings-subsection.dragging').forEach(el => {
                el.classList.remove('dragging');
                el.style.opacity = '';
            });
            document.querySelectorAll('.settings-subsection.over').forEach(el => el.classList.remove('over'));
            dragSrcEl = null;
        });
    });
}

/**
 * Инициализация тултипов для help-icon
 */
function initHelpIcons() {
    function ensureHelpTooltip() {
        let tooltip = document.querySelector('.help-tooltip');
        if (!tooltip) {
            tooltip = document.createElement('div');
            tooltip.className = 'help-tooltip';
            document.body.appendChild(tooltip);
        }
        return tooltip;
    }

    function showHelp(event) {
        event.stopPropagation();
        const icon = event.currentTarget;
        const text = icon.dataset.helpText;
        const image = icon.dataset.helpImage;
        const tooltip = ensureHelpTooltip();

        tooltip.innerHTML = '';
        if (text) {
            const textEl = document.createElement('div');
            textEl.className = 'help-tooltip-text';
            textEl.textContent = text;
            tooltip.appendChild(textEl);
        }
        if (image) {
            const imgEl = document.createElement('img');
            imgEl.className = 'help-tooltip-image';
            imgEl.src = image;
            imgEl.alt = '';
            tooltip.appendChild(imgEl);
        }

        const rect = icon.getBoundingClientRect();
        tooltip.style.top = `${window.scrollY + rect.bottom + 8}px`;
        tooltip.style.left = `${window.scrollX + rect.left}px`;
        tooltip.classList.add('active');
        setTimeout(() => document.addEventListener('click', hideHelp), 0);
    }

    function hideHelp() {
        const tooltip = document.querySelector('.help-tooltip');
        if (tooltip) tooltip.classList.remove('active');
        document.removeEventListener('click', hideHelp);
    }

    document.querySelectorAll('.help-icon').forEach(icon => {
        icon.addEventListener('click', showHelp);
    });
}

/**
 * Инициализация collapse-блоков: плавное раскрытие/сворачивание
 */
function initCollapseToggles() {
    document.querySelectorAll('.collapse-toggle').forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            const content = document.querySelector(targetId);
            if (content) {
                const isOpen = content.classList.contains('open');
                if (isOpen) {
                    content.classList.remove('open');
                    this.textContent = this.textContent.replace('Скрыть', 'Показать');
                } else {
                    content.classList.add('open');
                    this.textContent = this.textContent.replace('Показать', 'Скрыть');
                }
            }
        });
    });
}

/**
 * Обработка адаптива для каких-то дополнительных случаев
 */
function handleResponsive() {
    const mq = window.matchMedia('(max-width:768px)');
    const cb = e => {
        if (e.matches) {
            // мобильное поведение, если нужно
        } else {
            // десктопное поведение, если нужно
        }
    };
    mq.addEventListener('change', cb);
    cb(mq);
}
