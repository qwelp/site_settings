document.addEventListener("DOMContentLoaded", function () {
    const root = document.querySelector('.settings-form[data-control-value]');
    if (!root) return;

    const dropdown = root.querySelector('.settings-form__select');
    const elementsContainer = root.querySelector('.settings-form__elements');
    const addRowButton = root.querySelector('.settings-form__button');
    const hiddenValueInput = document.querySelector(`input[name="${root.dataset.controlValue}"]`);
    const hiddenModeInput = document.querySelector(`input[name="${root.dataset.controlMode}"]`);

    const colorPickerOption = root.querySelector('.settings-form__color-option');
    const colorPickerToggle = colorPickerOption ? colorPickerOption.querySelector('.settings-form__color-picker-toggle') : null;

    // [FIXED] Используем initialData только для первоначального рендеринга, а не как постоянное хранилище.
    const initialData = JSON.parse(root.dataset.initialJson || '{}');

    function generateRowHtml(type, item = {}) {
        const v = item.value || "";
        const l = item.label || "";
        const p = item.pathFile || "";
        let html = '<div class="settings-form__element" data-element>';
        if (type === "color") {
            const color = v || "#000000";
            html += `<input type="color" class="settings-form__color" value="${color}">`;
        } else {
            html += `<input type="text" class="settings-form__input" placeholder="${OPTIONS_CONTROL_MESSAGES.VALUE}" value="${v}">`;
        }
        html += `<input type="text" class="settings-form__input" placeholder="${OPTIONS_CONTROL_MESSAGES.LABEL}" value="${l}">`;
        if (type === "pathFile") {
            html += `<input type="text" class="settings-form__input" placeholder="${OPTIONS_CONTROL_MESSAGES.PATH_TO_FILE}" value="${p}">`;
        }
        if (type === "radioImage") {
            html += '<span class="adm-input-file"><span>' + OPTIONS_CONTROL_MESSAGES.ADD_FILE + '</span>';
            html += '<input type="file" name="file" class="settings-form__file adm-designed-file">';
            html += '</span>';
        }
        html += `<button type="button" class="settings-form__delete-row" title="${OPTIONS_CONTROL_MESSAGES.DELETE}">×</button>`;
        html += '</div>';
        return html;
    }

    function renderPreview(row, src) {
        let preview = row.querySelector('.settings-form__preview');
        if (!preview) {
            preview = document.createElement('div');
            preview.className = 'settings-form__preview';
            row.appendChild(preview);
        }
        preview.innerHTML = `<img src="${src}" class="settings-form__thumb" alt="${OPTIONS_CONTROL_MESSAGES.PREVIEW}"><button type="button" class="settings-form__delete-file">${OPTIONS_CONTROL_MESSAGES.DELETE}</button>`;
    }

    function clearPreview(row) {
        const pr = row.querySelector('.settings-form__preview');
        if (pr) pr.remove();
        delete row.dataset.fileId;
    }

    function getItemsFromDOM() {
        return Array.from(elementsContainer.querySelectorAll('.settings-form__element')).map(row => {
            const colorInput = row.querySelector('input[type="color"]');
            return {
                value: colorInput ? colorInput.value : (row.querySelector(`input[placeholder="${OPTIONS_CONTROL_MESSAGES.VALUE}"]`)?.value || ''),
                label: row.querySelector(`input[placeholder="${OPTIONS_CONTROL_MESSAGES.LABEL}"]`)?.value || '',
                pathFile: row.querySelector(`input[placeholder="${OPTIONS_CONTROL_MESSAGES.PATH_TO_FILE}"]`)?.value || null,
                fileId: row.dataset.fileId ? parseInt(row.dataset.fileId, 10) : null
            };
        });
    }

    function updateHiddenFields() {
        const mode = dropdown.value;
        const items = getItemsFromDOM();

        // [FIXED] Создаем чистый объект для сохранения. Никакого накопления.
        const dataToSave = {};

        // Записываем данные ТОЛЬКО для активного режима.
        dataToSave[mode] = items;

        // Если активный режим - color, добавляем состояние чекбокса.
        if (mode === 'color' && colorPickerToggle) {
            dataToSave.color_show_picker = colorPickerToggle.checked;
        }

        // Сохраняем "чистый" объект и текущий режим.
        hiddenValueInput.value = JSON.stringify(dataToSave);
        hiddenModeInput.value = mode;
    }

    function renderControl(mode) {
        if (colorPickerOption) {
            colorPickerOption.style.display = (mode === 'color') ? 'flex' : 'none';
        }

        // Используем initialData только для поиска нужных опций
        const itemsToRender = initialData[mode] || [];
        elementsContainer.innerHTML = '';

        if (itemsToRender.length === 0 && mode !== 'checkbox') {
            itemsToRender.push({});
        }

        itemsToRender.forEach(item => {
            elementsContainer.insertAdjacentHTML('beforeend', generateRowHtml(mode, item));
        });

        if (mode === 'radioImage' && itemsToRender.length) {
            itemsToRender.forEach((item, idx) => {
                const row = elementsContainer.children[idx];
                if (row && item.fileId && item.fileUrl) {
                    row.dataset.fileId = item.fileId;
                    renderPreview(row, item.fileUrl);
                }
            });
        }

        addRowButton.style.display = (mode === 'checkbox') ? 'none' : 'inline-block';
        updateHiddenFields();
    }

    // --- Инициализация ---
    renderControl(dropdown.value);

    // --- Обработчики ---
    addRowButton.addEventListener('click', function () {
        const t = dropdown.value;
        if (t === 'checkbox') return;
        elementsContainer.insertAdjacentHTML('beforeend', generateRowHtml(t));
        updateHiddenFields();
    });

    dropdown.addEventListener('change', function () {
        // [FIXED] При смене режима просто перерисовываем контрол,
        // данные из initialData будут взяты заново.
        renderControl(this.value);
    });

    root.addEventListener('change', function(e) {
        if (!e.target.matches('input')) return;
        if (e.target.type === 'file' && dropdown.value === 'radioImage') {
            const file = e.target.files[0];
            if (file) {
                const row = e.target.closest('.settings-form__element');
                uploadFile(file, row);
            }
        } else {
            updateHiddenFields();
        }
    });

    root.addEventListener('click', function(e) {
        if (e.target.matches('.settings-form__delete-row')) {
            e.preventDefault();
            const row = e.target.closest('.settings-form__element');
            if (row) {
                if (elementsContainer.children.length > 1) {
                    row.remove();
                } else {
                    const inputs = row.querySelectorAll('input:not([type="color"])');
                    inputs.forEach(input => input.value = '');
                    const colorInput = row.querySelector('input[type="color"]');
                    if(colorInput) colorInput.value = '#000000';
                }
                updateHiddenFields();
            }
        }

        if (e.target.matches('.settings-form__delete-file')) {
            e.preventDefault();
            const row = e.target.closest('.settings-form__element');
            const fid = parseInt(row.dataset.fileId, 10);
            fetch('/local/modules/qwelp.site_settings/delete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ fileId: fid, sessid: BX.bitrix_sessid() })
            })
                .then(r => r.json())
                .then(resp => {
                    if (resp.status === 'success') {
                        clearPreview(row);
                    } else {
                        console.error(OPTIONS_CONTROL_MESSAGES.DELETE_ERROR, resp.message);
                    }
                    updateHiddenFields();
                })
                .catch(err => console.error(OPTIONS_CONTROL_MESSAGES.FETCH_ERROR, err));
        }
    });

    function uploadFile(file, row) {
        const fm = new FormData();
        fm.append('file', file);
        fm.append('sessid', BX.bitrix_sessid());
        fetch('/local/modules/qwelp.site_settings/upload.php', {
            method: 'POST',
            body: fm
        })
            .then(r => r.json())
            .then(resp => {
                if (resp.status === 'success') {
                    row.dataset.fileId = resp.fileId;
                    renderPreview(row, resp.fileUrl);
                } else {
                    console.error(OPTIONS_CONTROL_MESSAGES.UPLOAD_ERROR, resp.message);
                }
                updateHiddenFields();
            })
            .catch(err => {
                console.error(OPTIONS_CONTROL_MESSAGES.FETCH_ERROR, err);
            });
    }
});