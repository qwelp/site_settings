document.addEventListener('DOMContentLoaded', function() {
    document.body.addEventListener('click', function(e) {
        if (e.target.matches('.key-value-add-row')) {
            e.preventDefault();
            const propertyContainer = e.target.closest('.key-value-property');
            if (propertyContainer) {
                const list = propertyContainer.querySelector('.key-value-list');
                const newRow = createRowHtml();
                list.insertAdjacentHTML('beforeend', newRow);
            }
        }

        if (e.target.matches('.key-value-delete-row')) {
            e.preventDefault();
            const row = e.target.closest('.key-value-item');
            const list = e.target.closest('.key-value-list');
            if (row && list) {
                if (list.children.length > 1) {
                    row.remove();
                } else {
                    row.querySelector('.key-value-key').value = '';
                    row.querySelector('.key-value-value').value = '';
                }
                updateHiddenField(row.closest('.key-value-property'));
            }
        }
    });

    document.body.addEventListener('input', function(e) {
        if (e.target.matches('.key-value-input')) {
            const propertyContainer = e.target.closest('.key-value-property');
            if (propertyContainer) {
                updateHiddenField(propertyContainer);
            }
        }
    });

    function createRowHtml() {
        // [FIXED] Используем языковые фразы
        const M = window.KEY_VALUE_CONTROL_MESSAGES || {
            KEY: 'Ключ', VALUE: 'Значение', DELETE: 'Удалить'
        };
        return `
            <div class="key-value-item">
                <input type="text" class="key-value-input key-value-key" placeholder="${M.KEY}">
                <input type="text" class="key-value-input key-value-value" placeholder="${M.VALUE}">
                <button type="button" class="key-value-delete-row" title="${M.DELETE}">×</button>
            </div>
        `;
    }

    function updateHiddenField(propertyContainer) {
        if (!propertyContainer) return;

        const controlName = propertyContainer.dataset.controlName;
        const hiddenInput = propertyContainer.querySelector(`input[type="hidden"][name="${controlName}"]`);
        const rows = propertyContainer.querySelectorAll('.key-value-item');

        let data = [];
        rows.forEach(row => {
            const keyInput = row.querySelector('.key-value-key');
            const valueInput = row.querySelector('.key-value-value');
            if (keyInput.value.trim() !== '' || valueInput.value.trim() !== '') {
                data.push({
                    key: keyInput.value,
                    value: valueInput.value
                });
            }
        });

        // [FIXED] Передаем чистый JSON, а не пытаемся сериализовать
        if (hiddenInput) {
            hiddenInput.value = JSON.stringify(data);
        }
    }
});