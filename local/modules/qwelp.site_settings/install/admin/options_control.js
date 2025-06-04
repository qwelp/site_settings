(function() {
    document.addEventListener("DOMContentLoaded", function () {
        const root              = document.querySelector('.settings-form[data-control-value]');
        if (!root) return;

        const dropdown          = root.querySelector('.settings-form__select');
        const elementsContainer = root.querySelector('.settings-form__elements');
        const addRowButton      = root.querySelector('.settings-form__button');
        const hiddenValueInput  = document.querySelector(`input[name="${root.dataset.controlValue}"]`);
        const hiddenModeInput   = document.querySelector(`input[name="${root.dataset.controlMode}"]`);

        // Парсим весь JSON, в т.ч. fileUrl
        const initialData = JSON.parse(root.dataset.initialJson || '{}');

        // Временное хранилище
        const tempValues = {
            checkbox:   initialData.checkbox   || [],
            radio:      initialData.radio      || [],
            radioImage: initialData.radioImage || [],
            pathFile:   initialData.pathFile   || [],
            select:   initialData.select   || [],
            color:      initialData.color      || [],
        };

        /**
         * Генерация HTML для строки по типу и данным
         */
        function generateRowHtml(type, item = {}) {
            const v = item.value    || "";
            const l = item.label    || "";
            const p = item.pathFile || "";
            let html = '<div class="settings-form__element">';
            if (type === "color") {
                const color = v || "#000000";
                html += `<input type="color" class="settings-form__color" value="${color}">`;
            } else {
                html += `<input type="text" class="settings-form__input" placeholder="${OPTIONS_CONTROL_MESSAGES.VALUE}" value="${v}">`;
            }
            html += `<input type="text" class="settings-form__input" placeholder="${OPTIONS_CONTROL_MESSAGES.LABEL}"   value="${l}">`;
            if (type === "pathFile") {
                html += `<input type="text" class="settings-form__input" placeholder="${OPTIONS_CONTROL_MESSAGES.PATH_TO_FILE}" value="${p}">`;
            }
            if (type === "radioImage") {
                html += '<span class="adm-input-file"><span>' + OPTIONS_CONTROL_MESSAGES.ADD_FILE + '</span>';
                html += '<input type="file" name="file" class="settings-form__file adm-designed-file">';
                html += '</span>';
            }
            html += '</div>';
            return html;
        }

        /**
         * Отрисовка превью + кнопка удаления
         */
        function renderPreview(row, src) {
            let preview = row.querySelector('.settings-form__preview');
            if (!preview) {
                preview = document.createElement('div');
                preview.className = 'settings-form__preview';
                row.appendChild(preview);
            }
            preview.innerHTML = `
                <img src="${src}" class="settings-form__thumb" alt="${OPTIONS_CONTROL_MESSAGES.PREVIEW}">
                <button type="button" class="settings-form__delete-file">${OPTIONS_CONTROL_MESSAGES.DELETE}</button>
            `;
        }

        /**
         * Сбрасывает превью и fileId
         */
        function clearPreview(row) {
            const pr = row.querySelector('.settings-form__preview');
            if (pr) pr.remove();
            delete row.dataset.fileId;
        }

        /**
         * Возвращает данные из DOM
         */
        function getCurrentRows() {
            return Array.from(elementsContainer.children).map(row => {
                const colorInput = row.querySelector('input[type="color"]');
                return {
                    value:    colorInput ? colorInput.value : row.querySelector(`input[placeholder="${OPTIONS_CONTROL_MESSAGES.VALUE}"]`).value,
                    label:    row.querySelector(`input[placeholder="${OPTIONS_CONTROL_MESSAGES.LABEL}"]`).value,
                    pathFile: row.querySelector(`input[placeholder="${OPTIONS_CONTROL_MESSAGES.PATH_TO_FILE}"]`)?.value || null,
                    fileId:   row.dataset.fileId ? parseInt(row.dataset.fileId, 10) : null
                };
            });
        }

        /**
         * Обновляет скрытые поля JSON и режим
         */
        function updateHiddenFields() {
            const mode = dropdown.value;
            tempValues[mode] = getCurrentRows();
            hiddenValueInput.value = JSON.stringify({ [mode]: tempValues[mode] });
            hiddenModeInput.value  = mode;
        }

        /**
         * Определяем тип режима по select
         */
        function detectType() {
            return dropdown.value;
        }

        /**
         * Обертка fetch для загрузки файла
         */
        function uploadFile(file, row) {
            const fm = new FormData();
            fm.append('file', file);
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
                    updateHiddenFields();
                });
        }

        // --- Инициализация: отрисуем строки и превью из initialData ---
        let currentMode = detectType();
        dropdown.value = currentMode;
        elementsContainer.innerHTML = '';
        (tempValues[currentMode].length ? tempValues[currentMode] : [{}]).forEach(item => {
            elementsContainer.insertAdjacentHTML('beforeend', generateRowHtml(currentMode, item));
        });
        if (currentMode === 'radioImage') {
            tempValues.radioImage.forEach((item, idx) => {
                if (item.fileId && item.fileUrl) {
                    const row = elementsContainer.children[idx];
                    row.dataset.fileId = item.fileId;
                    renderPreview(row, item.fileUrl);
                }
            });
        }
        updateHiddenFields();

        // Добавить строку
        addRowButton.addEventListener('click', function() {
            const t = detectType();
            if (t === 'checkbox') return;
            elementsContainer.insertAdjacentHTML('beforeend', generateRowHtml(t));
            updateHiddenFields();
        });

        // Смена режима
        dropdown.addEventListener('change', function() {
            // сохраняем данные предыдущего режима
            tempValues[currentMode] = getCurrentRows();
            // новый режим
            const nm = detectType();
            currentMode = nm;
            elementsContainer.innerHTML = '';
            (tempValues[nm].length ? tempValues[nm] : [{}]).forEach(item => {
                elementsContainer.insertAdjacentHTML('beforeend', generateRowHtml(nm, item));
            });
            if (nm === 'radioImage') {
                tempValues.radioImage.forEach((item, idx) => {
                    if (item.fileId && item.fileUrl) {
                        const row = elementsContainer.children[idx];
                        row.dataset.fileId = item.fileId;
                        renderPreview(row, item.fileUrl);
                    }
                });
            }
            updateHiddenFields();
        });

        // Объединенный change: загрузка файла + обновление полей
        elementsContainer.addEventListener('change', function(e) {
            const input = e.target;
            if (input.type === 'file' && detectType() === 'radioImage') {
                const file = input.files[0];
                if (file) {
                    const row = input.closest('.settings-form__element');
                    uploadFile(file, row);
                    return;
                }
            }
            updateHiddenFields();
        });

        // Удаление файла
        elementsContainer.addEventListener('click', function(e) {
            if (e.target.matches('.settings-form__delete-file')) {
                const row = e.target.closest('.settings-form__element');
                const fid = parseInt(row.dataset.fileId, 10);
                fetch('/local/modules/qwelp.site_settings/delete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ fileId: fid })
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
    });
})();
    document.addEventListener("DOMContentLoaded", function () {        const root              = document.querySelector('.settings-form[data-control-value]');        if (!root) return;        const dropdown          = root.querySelector('.settings-form__select');        const elementsContainer = root.querySelector('.settings-form__elements');        const addRowButton      = root.querySelector('.settings-form__button');        const hiddenValueInput  = document.querySelector(`input[name="${root.dataset.controlValue}"]`);        const hiddenModeInput   = document.querySelector(`input[name="${root.dataset.controlMode}"]`);        // Парсим весь JSON, в т.ч. fileUrl        const initialData = JSON.parse(root.dataset.initialJson || '{}');        // Временное хранилище        const tempValues = {            checkbox:   initialData.checkbox   || [],            radio:      initialData.radio      || [],            radioImage: initialData.radioImage || [],            pathFile:   initialData.pathFile   || [],            select:   initialData.select   || [],        };        /**         * Генерация HTML для строки по типу и данным         */        function generateRowHtml(type, item = {}) {            const v = item.value    || "";            const l = item.label    || "";            const p = item.pathFile || "";            let html = '<div class="settings-form__element">';            html += `<input type="text" class="settings-form__input" placeholder="${OPTIONS_CONTROL_MESSAGES.VALUE}" value="${v}">`;            html += `<input type="text" class="settings-form__input" placeholder="${OPTIONS_CONTROL_MESSAGES.LABEL}"   value="${l}">`;            if (type === "pathFile") {                html += `<input type="text" class="settings-form__input" placeholder="${OPTIONS_CONTROL_MESSAGES.PATH_TO_FILE}" value="${p}">`;            }            if (type === "radioImage") {                html += '<span class="adm-input-file"><span>' + OPTIONS_CONTROL_MESSAGES.ADD_FILE + '</span>';                html += '<input type="file" name="file" class="settings-form__file adm-designed-file">';                html += '</span>';            }            html += '</div>';            return html;        }        /**         * Отрисовка превью + кнопка удаления         */        function renderPreview(row, src) {            let preview = row.querySelector('.settings-form__preview');            if (!preview) {                preview = document.createElement('div');                preview.className = 'settings-form__preview';                row.appendChild(preview);            }            preview.innerHTML = `                <img src="${src}" class="settings-form__thumb" alt="${OPTIONS_CONTROL_MESSAGES.PREVIEW}">                <button type="button" class="settings-form__delete-file">${OPTIONS_CONTROL_MESSAGES.DELETE}</button>            `;        }        /**         * Сбрасывает превью и fileId         */        function clearPreview(row) {            const pr = row.querySelector('.settings-form__preview');            if (pr) pr.remove();            delete row.dataset.fileId;        }        /**         * Возвращает данные из DOM         */        function getCurrentRows() {            return Array.from(elementsContainer.children).map(row => {                return {                    value:    row.querySelector(`input[placeholder="${OPTIONS_CONTROL_MESSAGES.VALUE}"]`).value,                    label:    row.querySelector(`input[placeholder="${OPTIONS_CONTROL_MESSAGES.LABEL}"]`).value,                    pathFile: row.querySelector(`input[placeholder="${OPTIONS_CONTROL_MESSAGES.PATH_TO_FILE}"]`)?.value || null,                    fileId:   row.dataset.fileId ? parseInt(row.dataset.fileId, 10) : null                };            });        }        /**         * Обновляет скрытые поля JSON и режим         */        function updateHiddenFields() {            const mode = dropdown.value;            tempValues[mode] = getCurrentRows();            hiddenValueInput.value = JSON.stringify({ [mode]: tempValues[mode] });            hiddenModeInput.value  = mode;        }        /**         * Определяем тип режима по select         */        function detectType() {            return dropdown.value;        }        /**         * Обертка fetch для загрузки файла         */        function uploadFile(file, row) {            const fm = new FormData();            fm.append('file', file);            fetch('/local/modules/qwelp.site_settings/upload.php', {                method: 'POST',                body: fm            })                .then(r => r.json())                .then(resp => {                    if (resp.status === 'success') {                        row.dataset.fileId = resp.fileId;                        renderPreview(row, resp.fileUrl);                    } else {                        console.error(OPTIONS_CONTROL_MESSAGES.UPLOAD_ERROR, resp.message);                    }                    updateHiddenFields();                })                .catch(err => {                    console.error(OPTIONS_CONTROL_MESSAGES.FETCH_ERROR, err);                    updateHiddenFields();                });        }        // --- Инициализация: отрисуем строки и превью из initialData ---        let currentMode = detectType();        dropdown.value = currentMode;        elementsContainer.innerHTML = '';        (tempValues[currentMode].length ? tempValues[currentMode] : [{}]).forEach(item => {            elementsContainer.insertAdjacentHTML('beforeend', generateRowHtml(currentMode, item));        });        if (currentMode === 'radioImage') {            tempValues.radioImage.forEach((item, idx) => {                if (item.fileId && item.fileUrl) {                    const row = elementsContainer.children[idx];                    row.dataset.fileId = item.fileId;                    renderPreview(row, item.fileUrl);                }            });        }        updateHiddenFields();        // Добавить строку        addRowButton.addEventListener('click', function() {            const t = detectType();            if (t === 'checkbox') return;            elementsContainer.insertAdjacentHTML('beforeend', generateRowHtml(t));            updateHiddenFields();        });        // Смена режима        dropdown.addEventListener('change', function() {            // сохраняем данные предыдущего режима            tempValues[currentMode] = getCurrentRows();            // новый режим            const nm = detectType();            currentMode = nm;            elementsContainer.innerHTML = '';            (tempValues[nm].length ? tempValues[nm] : [{}]).forEach(item => {                elementsContainer.insertAdjacentHTML('beforeend', generateRowHtml(nm, item));            });            if (nm === 'radioImage') {                tempValues.radioImage.forEach((item, idx) => {                    if (item.fileId && item.fileUrl) {                        const row = elementsContainer.children[idx];                        row.dataset.fileId = item.fileId;                        renderPreview(row, item.fileUrl);                    }                });            }            updateHiddenFields();        });        // Объединенный change: загрузка файла + обновление полей        elementsContainer.addEventListener('change', function(e) {            const input = e.target;            if (input.type === 'file' && detectType() === 'radioImage') {                const file = input.files[0];                if (file) {                    const row = input.closest('.settings-form__element');                    uploadFile(file, row);                    return;                }            }            updateHiddenFields();        });        // Удаление файла        elementsContainer.addEventListener('click', function(e) {            if (e.target.matches('.settings-form__delete-file')) {                const row = e.target.closest('.settings-form__element');                const fid = parseInt(row.dataset.fileId, 10);                fetch('/local/modules/qwelp.site_settings/delete.php', {                    method: 'POST',                    headers: { 'Content-Type': 'application/json' },                    body: JSON.stringify({ fileId: fid })                })                    .then(r => r.json())                    .then(resp => {                        if (resp.status === 'success') {                            clearPreview(row);                        } else {                            console.error(OPTIONS_CONTROL_MESSAGES.DELETE_ERROR, resp.message);                        }                        updateHiddenFields();                    })                    .catch(err => console.error(OPTIONS_CONTROL_MESSAGES.FETCH_ERROR, err));            }        });    });})();