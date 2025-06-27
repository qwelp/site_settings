class KeyValueProperty {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error(`KeyValueProperty: container with id #${containerId} not found.`);
            return;
        }

        this.tbody = this.container.querySelector('tbody');
        this.addButton = this.container.querySelector('.kv-add-row');
        this.form = this.container.closest('form');

        this.init();
    }

    init() {
        this.addButton.addEventListener('click', () => this.addRow());

        // Используем делегирование событий для кнопок удаления
        this.tbody.addEventListener('click', (event) => {
            if (event.target && event.target.classList.contains('kv-delete-row')) {
                this.deleteRow(event.target);
            }
        });

        // Валидация перед отправкой формы
        if (this.form) {
            this.form.addEventListener('submit', (event) => {
                if (!this.validate()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
            });
        }
    }

    createRowHtml() {
        // Получаем имя из первого инпута, чтобы новые строки имели правильное имя
        const firstKeyInput = this.tbody.querySelector('input[name$="[KEY][]"]');
        if (!firstKeyInput) {
            // Если таблица была пуста, имя нужно конструировать
            console.error('Cannot determine input names for new row.');
            return ''; // Не удалось создать строку
        }

        const keyName = firstKeyInput.getAttribute('name');
        const valueName = keyName.replace('[KEY][]', '[VALUE][]');

        return `
            <td><input type="text" name="${keyName}" maxlength="255" required></td>
            <td><input type="text" name="${valueName}" maxlength="255"></td>
            <td><button type="button" class="kv-delete-row" title="Удалить строку">×</button></td>
        `;
    }

    addRow() {
        const tr = document.createElement('tr');
        tr.innerHTML = this.createRowHtml();
        this.tbody.appendChild(tr);
    }

    deleteRow(button) {
        const row = button.closest('tr');
        // Не даем удалить последнюю строку, чтобы шаблон для добавления не ломался
        if (this.tbody.rows.length > 1) {
            row.remove();
        } else {
            // Если строка последняя, просто очищаем поля
            const inputs = row.querySelectorAll('input');
            inputs.forEach(input => input.value = '');
        }
    }

    validate() {
        const keyInputs = this.tbody.querySelectorAll('input[name$="[KEY][]"]');
        const seenKeys = new Set();
        let isValid = true;

        for (const input of keyInputs) {
            const key = input.value.trim();
            input.style.border = ''; // Сброс стиля

            if (key === '') {
                // 'required' атрибут сработает, но для подстраховки можно добавить свою логику
                continue;
            }

            if (seenKeys.has(key)) {
                alert(`Ошибка: Ключ "${key}" не является уникальным.`);
                input.style.border = '1px solid red';
                input.focus();
                isValid = false;
                break; // Прерываем проверку после первой ошибки
            }
            seenKeys.add(key);
        }
        return isValid;
    }
}