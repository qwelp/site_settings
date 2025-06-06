// script.js — управление панелью настроек с улучшенным drag&drop

let currentSettings = {};
let originalSettings = {};

document.addEventListener('DOMContentLoaded', () => {
    console.log('DOMContentLoaded: скрипт сработал');

    initSettingsPanel();
    initializeSettings();
    handleResponsive();
    document.querySelector('.btn-apply')?.addEventListener('click', applySettings);
    document.querySelector('.btn-reset')?.addEventListener('click', resetSettings);

    let dragSrcEl = null;

    // Находим все подразделы второго уровня, у которых data-enable-drag="1"
    const draggables = document.querySelectorAll('.settings-subsection[data-enable-drag="1"]');
    console.log('Найдено draggable подразделов:', draggables.length);

    draggables.forEach(subsec => {
        subsec.setAttribute('draggable', 'true');

        subsec.addEventListener('dragstart', event => {
            dragSrcEl = subsec;
            console.log('dragstart на:', subsec.id);

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

            // Проверяем, что hovered элемент тоже разрешён для дропа (data-enable-drag="1")
            if (target.dataset.enableDrag !== "1") return;

            // Убираем подсветку со всех, добавляем только к текущему
            document.querySelectorAll('.settings-subsection.over').forEach(el => el.classList.remove('over'));
            target.classList.add('over');

            // Определяем, над какой половиной блока курсор: для более плавного swap
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

            // Если нужно вставить до и srcIndex !== tgtIndex-1 или вставить после и srcIndex !== tgtIndex+1
            if (insertBefore && srcIndex !== tgtIndex - 1) {
                parent.insertBefore(dragSrcEl, target);
            } else if (!insertBefore && srcIndex !== tgtIndex + 1) {
                parent.insertBefore(dragSrcEl, target.nextSibling);
            }
        });

        subsec.addEventListener('dragleave', event => {
            // Убираем подсветку, когда мышь уходит
            if (event.currentTarget.contains(event.relatedTarget)) return;
            event.currentTarget.classList.remove('over');
        });

        subsec.addEventListener('drop', event => {
            event.stopPropagation();
            console.log('drop на:', event.currentTarget.id, '— завершаем перетаскивание:', dragSrcEl ? dragSrcEl.id : 'null');

            document.querySelectorAll('.settings-subsection.over').forEach(el => el.classList.remove('over'));
            if (dragSrcEl) {
                dragSrcEl.classList.remove('dragging');
                dragSrcEl.style.opacity = '';
            }
            dragSrcEl = null;
            return false;
        });

        subsec.addEventListener('dragend', () => {
            console.log('dragend на:', subsec.id);
            document.querySelectorAll('.settings-subsection.dragging').forEach(el => {
                el.classList.remove('dragging');
                el.style.opacity = '';
            });
            document.querySelectorAll('.settings-subsection.over').forEach(el => el.classList.remove('over'));
            dragSrcEl = null;
        });
    });
});


/* ------------------------------------------------------------------
   БЛОК ФУНКЦИЙ ДЛЯ УПРАВЛЕНИЯ ПАНЕЛЬЮ НАСТРОЕК
   ------------------------------------------------------------------ */

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

function initHelpIcons() {
    document.querySelectorAll('.help-icon').forEach(icon => {
        icon.addEventListener('click', showHelp);
    });
}

function openSettings() {
    const overlay = document.querySelector('.settings-overlay');
    if (overlay) {
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeSettings() {
    const overlay = document.querySelector('.settings-overlay');
    if (overlay) {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
        hideHelp();
    }
}

function switchSection(sectionId) {
    document.querySelectorAll('.settings-nav li').forEach(item => {
        item.classList.toggle('active', item.dataset.section === sectionId);
    });
    document.querySelectorAll('.settings-section').forEach(sec => {
        sec.classList.toggle('active', sec.id === `section-${sectionId}`);
    });
}

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
    initHelpIcons();
}

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
    });
}

function applySettings() {
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
        fetch(ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'save_settings',
                settings: currentSettings,
                sessid: BX.bitrix_sessid()
            })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    originalSettings = JSON.parse(JSON.stringify(currentSettings));
                    alert(SETTINGS_MESSAGES.SETTINGS_SAVED);
                } else {
                    alert(SETTINGS_MESSAGES.SAVE_ERROR + (data.message || SETTINGS_MESSAGES.UNKNOWN_ERROR));
                }
            })
            .catch(() => alert(SETTINGS_MESSAGES.SAVE_ERROR_SIMPLE));
    } else {
        originalSettings = JSON.parse(JSON.stringify(currentSettings));
        alert(SETTINGS_MESSAGES.SETTINGS_SAVED);
        console.warn("ajaxUrl is not defined. Settings saved locally (simulated).");
    }
}

function resetSettings() {
    currentSettings = JSON.parse(JSON.stringify(originalSettings));
    updateSettingsUI();
}

function handleResponsive() {
    const mq = window.matchMedia('(max-width:768px)');
    const cb = e => {
        if (e.matches) {
            // mobile behavior
        } else {
            // desktop behavior
        }
    };
    mq.addEventListener('change', cb);
    cb(mq);
}
