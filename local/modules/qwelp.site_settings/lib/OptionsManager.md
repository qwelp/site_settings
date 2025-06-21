Конечно, я понял. Вам нужен полный текст документации, но строго в формате предоставленного вами образца.

Вот готовый текст для `README.md`.

***

# API для работы с настройками сайта (Модуль qwelp.site_settings)

Для получения значений настроек сайта на стороне PHP используется статический класс `\Qwelp\SiteSettings\OptionsManager`.

## 1. Получение простого значения

Для получения одиночных настроек (цвет, ширина сайта, опции) используется метод `get()`.

**Сигнатура:**
`get(string $code, mixed $defaultValue = null, ?string $siteId = null): mixed`

- `$code` - символьный код настройки (из инфоблока).
- `$defaultValue` - значение, которое вернется, если настройка не найдена.
- `$siteId` - ID сайта (необязательно, по умолчанию текущий).

**Пример:**
```php
use Qwelp\SiteSettings\OptionsManager;

// Получить базовый цвет сайта, по умолчанию - синий
$baseColor = OptionsManager::get('bazovyy-tsvet-elementy', '#007bff');

// Проверить, включен ли какой-то чекбокс
$isFeatureEnabled = OptionsManager::get('drugoy-shrift-zagolovkov-element', false);
if ($isFeatureEnabled) {
    // ...
}
```

## 2. Работа с сортируемыми блоками

Для групп блоков, у которых включена сортировка (например, блоки на главной), используется метод `getSortedBlocks()`. Он возвращает только активные блоки в том порядке, который установил пользователь.

**Сигнатура:**
`getSortedBlocks(string $groupCode, ?string $siteId = null): array`

- `$groupCode` - символьный код родительского раздела, для которого включена сортировка (например, `bloki`).
- `$siteId` - ID сайта (необязательно, по умолчанию текущий).

**Возвращаемое значение:**
Массив, где каждый элемент — это ассоциативный массив, описывающий один блок.
```json
[
    {
        "code": "tizery",
        "activity": true,
        "verkhniy-otstup2312": "0"
    },
    {
        "code": "brendy",
        "activity": true,
        "brendy": "karuselyu-br",
        "nizhniy-otstup": "30"
    }
]
```

**Пример:**
```php
use Qwelp\SiteSettings\OptionsManager;

// 1. Получаем отсортированные и активные блоки для группы "bloki"
$mainPageBlocks = OptionsManager::getSortedBlocks('bloki');

// 2. В цикле подключаем компоненты для каждого блока
foreach ($mainPageBlocks as $block) {
    $APPLICATION->IncludeComponent(
        "qwelp:main.{$block['code']}", // например, "qwelp:main.brendy"
        ".default",
        [
             "BLOCK_SETTINGS" => $block // Передаем все настройки блока в компонент
        ]
    );
}
```

## 3. Проверка активности отдельного блока

Для быстрой проверки, включен ли конкретный сортируемый блок, используется `isBlockActive()`.

**Сигнатура:**
`isBlockActive(string $groupCode, string $blockCode, ?string $siteId = null): bool`

- `$groupCode` - код группы (например, `bloki`).
- `$blockCode` - код проверяемого блока (например, `tizery`).
- `$siteId` - ID сайта (необязательно, по умолчанию текущий).

**Пример:**
```php
use Qwelp\SiteSettings\OptionsManager;

if (OptionsManager::isBlockActive('bloki', 'obrazy')) {
    // Показать ссылку в меню, ведущую на якорь этого блока
    echo '<li><a href="#images">Наши образы</a></li>';
}
```

## 4. Получение всех настроек (для отладки)

Метод `getAll()` возвращает полный массив всех сохраненных настроек. **Не рекомендуется** для использования в логике сайта, предназначен в основном для отладки.

**Сигнатура:**
`getAll(?string $siteId = null): array`

- `$siteId` - ID сайта (необязательно, по умолчанию текущий).

**Пример:**
```php
// Только для отладочных целей!
echo '<pre>';
print_r(\Qwelp\SiteSettings\OptionsManager::getAll());
echo '</pre>';
```