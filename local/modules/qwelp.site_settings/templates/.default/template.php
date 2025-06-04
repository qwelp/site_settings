<?php
/**
 * Шаблон для отображения всех настроек сайта
 * Основан на верстке из файла context.js
 * 
 * @package qwelp.site_settings
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Page\Asset;
use Qwelp\SiteSettings\SettingsManager;

// Подключаем стили и скрипты
Asset::getInstance()->addCss('https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css');

// Настройки сайта уже получены в компоненте и переданы в $arResult['SETTINGS']
$settings = $arResult['SETTINGS'];

// Устанавливаем активный раздел по умолчанию
$activeSection = isset($_GET['section']) ? $_GET['section'] : 'general';

// Цветовая палитра для демонстрации
$colorPalette = [
    '#e74c3c', '#e67e22', '#f1c40f', '#2ecc71',
    '#3498db', '#9b59b6', '#ecf0f1', '#34495e',
    '#f39c12', '#ffeb3b', '#48bb78', '#4286f4'
];
?>

<div class="min-h-screen bg-gray-50">
    <div class="flex">
        <!-- Боковая панель навигации -->
        <div class="w-64 bg-white border-r border-gray-200 p-6 min-h-screen">
            <h2 class="text-xl font-bold mb-6">Настройки сайта</h2>

            <nav class="space-y-2">
                <?php foreach ($settings['sections'] as $section): ?>
                <a 
                    href="?section=<?= $section['id'] ?>"
                    class="block w-full text-left px-4 py-2 rounded-md transition-colors <?= 
                        $activeSection === $section['id'] 
                            ? 'bg-blue-50 text-blue-700' 
                            : 'text-gray-700 hover:bg-gray-100' 
                    ?>"
                >
                    <?= $section['title'] ?>
                </a>
                <?php endforeach; ?>
            </nav>

            <div class="mt-10">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">Дополнительно</h3>
                <nav class="space-y-2">
                    <button class="w-full text-left px-4 py-2 rounded-md text-gray-700 hover:bg-gray-100">
                        Поделиться настройками
                    </button>
                    <button class="w-full text-left px-4 py-2 rounded-md text-gray-700 hover:bg-gray-100">
                        Обновления
                    </button>
                    <button class="w-full text-left px-4 py-2 rounded-md text-gray-700 hover:bg-gray-100">
                        Получить демодоступ
                    </button>
                </nav>
            </div>
        </div>

        <!-- Основной контент -->
        <div class="flex-1 p-8">
            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <?php foreach ($settings['sections'] as $section): ?>
                        <?php if ($activeSection === $section['id']): ?>
                            <div class="space-y-6">
                                <?php foreach ($section['settings'] as $setting): ?>
                                    <div>
                                        <h3 class="text-lg font-medium mb-3"><?= $setting['label'] ?></h3>

                                        <?php if ($setting['type'] === 'checkbox'): ?>
                                            <div class="flex items-center gap-4">
                                                <label class="flex items-center cursor-pointer">
                                                    <div class="relative">
                                                        <input
                                                            type="checkbox"
                                                            class="sr-only"
                                                        />
                                                        <div class="block w-14 h-8 rounded-full bg-gray-300"></div>
                                                        <div class="dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition"></div>
                                                    </div>
                                                    <span class="ml-3"><?= $setting['label'] ?></span>
                                                </label>
                                            </div>

                                        <?php elseif ($setting['type'] === 'select'): ?>
                                            <div class="flex gap-2">
                                                <select class="px-3 py-2 border rounded-md">
                                                    <?php if (isset($setting['options']) && is_array($setting['options'])): ?>
                                                        <?php foreach ($setting['options'] as $option): ?>
                                                            <option value="<?= $option['value'] ?>"><?= $option['label'] ?></option>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </select>
                                            </div>

                                        <?php elseif ($setting['type'] === 'radio'): ?>
                                            <div class="flex gap-2">
                                                <?php if (isset($setting['options']) && is_array($setting['options'])): ?>
                                                    <?php foreach ($setting['options'] as $option): ?>
                                                        <button class="px-4 py-2 rounded-md bg-gray-200">
                                                            <?= $option['label'] ?>
                                                        </button>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>

                                        <?php elseif ($setting['type'] === 'radioImage'): ?>
                                            <div class="flex flex-wrap gap-2 mb-4">
                                                <?php if (isset($setting['options']) && is_array($setting['options'])): ?>
                                                    <?php foreach ($setting['options'] as $option): ?>
                                                        <button class="w-8 h-8 rounded-full border-2 border-transparent" 
                                                                style="background-image: url('<?= $option['image'] ?>')">
                                                        </button>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <!-- Демонстрационная цветовая палитра -->
                                                    <?php foreach ($colorPalette as $color): ?>
                                                        <button class="w-8 h-8 rounded-full border-2 border-transparent" 
                                                                style="background-color: <?= $color ?>">
                                                        </button>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>

                                        <?php else: ?>
                                            <div class="flex items-center gap-2">
                                                <input
                                                    type="text"
                                                    value="<?= isset($setting['value']) ? $setting['value'] : '' ?>"
                                                    class="px-3 py-2 border rounded-md w-full"
                                                />
                                            </div>
                                        <?php endif; ?>

                                        <?php if (isset($setting['helpText']) && !empty($setting['helpText'])): ?>
                                            <div class="mt-2 text-sm text-gray-500">
                                                <?= $setting['helpText'] ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Простой скрипт для переключения чекбоксов
    document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('.relative input[type="checkbox"]');

        checkboxes.forEach(checkbox => {
            const block = checkbox.nextElementSibling;
            const dot = block.nextElementSibling;

            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    block.classList.remove('bg-gray-300');
                    block.classList.add('bg-blue-500');
                    dot.classList.add('transform', 'translate-x-6');
                } else {
                    block.classList.remove('bg-blue-500');
                    block.classList.add('bg-gray-300');
                    dot.classList.remove('transform', 'translate-x-6');
                }
            });

            // Имитация клика для инициализации состояния
            checkbox.parentElement.addEventListener('click', function(e) {
                if (e.target !== checkbox) {
                    checkbox.checked = !checkbox.checked;
                    checkbox.dispatchEvent(new Event('change'));
                }
            });
        });
    });
</script>
