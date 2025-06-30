<?php
/**
 * Класс для управления ЗНАЧЕНИЯМИ настроек сайта.
 * Работает с JSON-файлами для хранения и получения данных.
 *
 * @package qwelp.site_settings
 */

namespace Qwelp\SiteSettings;

use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;
use Bitrix\Main\Web\Json;
use Exception;

class OptionsManager
{
    /**
     * @var string Директория для хранения файлов настроек относительно /upload/
     */
    private const STORAGE_DIR = 'qwelp.site_settings';

    /**
     * @var array Кэш загруженных настроек в рамках одного хита
     */
    protected static array $optionsCache = [];

    /**
     * Получает значение конкретной настройки.
     * Является основным методом для получения простых значений.
     *
     * @param string $code Символьный код настройки.
     * @param mixed|null $defaultValue Значение по умолчанию, если настройка не найдена.
     * @param string|null $siteId ID сайта. Если null, используется текущий.
     * @return mixed
     *
     * @example
     * // Получить базовый цвет сайта
     * $baseColor = \Qwelp\SiteSettings\OptionsManager::get('bazovyy-tsvet-elementy', '#007bff');
     *
     * // Получить ширину сайта
     * $siteWidth = \Qwelp\SiteSettings\OptionsManager::get('shirina-sayta', '1464');
     */
    public static function get(string $code, $defaultValue = null, ?string $siteId = null)
    {
        $options = self::getAll($siteId);
        return $options[$code] ?? $defaultValue;
    }

    /**
     * Получает массив отсортированных и активных блоков для указанной группы.
     * Идеально для рендеринга на главной странице.
     *
     * @param string $groupCode Символьный код группы блоков (например, 'bloki').
     * @param string|null $siteId ID сайта. Если null, используется текущий.
     * @return array Массив активных блоков в правильном порядке. Каждый элемент - ассоциативный массив настроек блока.
     *
     * @example
     * // Получить активные и отсортированные блоки для главной страницы
     * $mainPageBlocks = \Qwelp\SiteSettings\OptionsManager::getSortedBlocks('bloki');
     * foreach ($mainPageBlocks as $block) {
     *     // $block['code'] - 'brendy', 'tizery' и т.д.
     *     // $block['activity'] - true/false
     *     // $block['nizhniy-otstup'] - '30' и т.д.
     *     $APPLICATION->IncludeComponent(
     *         "bitrix:news.list",
     *         "main_{$block['code']}", // например, "main_brendy"
     *         [
     *              // ... параметры компонента
     *              "BLOCK_SETTINGS" => $block // Передаем все настройки блока в компонент
     *         ]
     *     );
     * }
     */
    public static function getSortedBlocks(string $groupCode, ?string $siteId = null): array
    {
        $options = self::getAll($siteId);
        $blocks = $options[$groupCode] ?? [];

        if (!is_array($blocks)) {
            return [];
        }

        // Фильтруем только активные блоки
        $activeBlocks = array_filter($blocks, function ($block) {
            return isset($block['activity']) && ($block['activity'] === true || $block['activity'] === 'true');
        });

        return array_values($activeBlocks); // Возвращаем с переиндексацией
    }

    /**
     * Проверяет, активен ли конкретный блок в группе.
     *
     * @param string $groupCode Код группы блоков.
     * @param string $blockCode Код конкретного блока.
     * @param string|null $siteId ID сайта.
     * @return bool
     *
     * @example
     * if (\Qwelp\SiteSettings\OptionsManager::isBlockActive('bloki', 'tizery')) {
     *     // ... делать что-то, если блок тизеров включен
     * }
     */
    public static function isBlockActive(string $groupCode, string $blockCode, ?string $siteId = null): bool
    {
        $options = self::getAll($siteId);
        $blocks = $options[$groupCode] ?? [];

        if (!is_array($blocks)) {
            return false;
        }

        foreach ($blocks as $block) {
            if (isset($block['code']) && $block['code'] === $blockCode) {
                return isset($block['activity']) && ($block['activity'] === true || $block['activity'] === 'true');
            }
        }

        return false; // По умолчанию блок неактивен, если не найден
    }

    /**
     * Получает все сохраненные настройки для сайта.
     * Внутренний метод, для публичного использования лучше применять get() или getSortedBlocks().
     *
     * @param string|null $siteId ID сайта. Если null, используется текущий.
     * @return array
     */
    public static function getAll(?string $siteId = null): array
    {
        $siteId = $siteId ?? Application::getInstance()->getContext()->getSite();

        if (isset(self::$optionsCache[$siteId])) {
            return self::$optionsCache[$siteId];
        }

        try {
            $filePath = self::getFilePath($siteId, false);
            $file = new File($filePath);

            if (!$file->isExists()) {
                self::$optionsCache[$siteId] = [];
                return [];
            }

            $json = $file->getContents();
            $options = Json::decode($json);

            self::$optionsCache[$siteId] = is_array($options) ? $options : [];
        } catch (Exception $e) {
            self::$optionsCache[$siteId] = [];
        }

        return self::$optionsCache[$siteId];
    }

    /**
     * Сохраняет массив настроек в JSON-файл.
     *
     * @param array $settings Ассоциативный массив настроек.
     * @param string $siteId ID сайта.
     * @return bool
     * @throws \Bitrix\Main\IO\FileNotFoundException
     */
    public static function save(array $settings, string $siteId): bool
    {
        $filePath = self::getFilePath($siteId, true);

        // Обновляем только те ключи, которые пришли
        $currentOptions = self::getAll($siteId);
        $newOptions = array_merge($currentOptions, $settings);

        $json = Json::encode($newOptions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        unset(self::$optionsCache[$siteId]);

        return (new File($filePath))->putContents($json) !== false;
    }

    /**
     * Формирует путь к файлу настроек.
     *
     * @param string $siteId ID сайта.
     * @param bool $createDir Создавать ли директорию, если она не существует.
     * @return string
     * @throws \Bitrix\Main\IO\FileNotFoundException
     */
    public static function getFilePath(string $siteId, bool $createDir = false): string
    {
        $docRoot = Application::getInstance()->getContext()->getServer()->getDocumentRoot();
        $uploadDir = $docRoot . '/upload/';
        $siteStorageDir = $uploadDir . self::STORAGE_DIR . '/' . $siteId . '/';

        if ($createDir) {
            $directory = new Directory($siteStorageDir);
            if (!$directory->isExists()) {
                Directory::createDirectory($siteStorageDir);
            }
        }

        return $siteStorageDir . 'settings.json';
    }


    /**
     * Получает технические данные элемента по его коду.
     * Возвращает ассоциативный массив, где ключи - это значения 'key', а значения - это значения 'value'.
     *
     * @param string $elementCode Код элемента инфоблока.
     * @return array Ассоциативный массив значений из свойства TECH_DATA.
     *
     * @example
     * // Получить все технические данные элемента
     * $techDataArray = \Qwelp\SiteSettings\OptionsManager::getTechData('api-settings');
     * // Результат: [
     * //   'api_url' => 'https://api.example.com',
     * //   'api_token' => 'secret_token_123'
     * // ]
     */
    public static function getTechData(string $elementCode): array
    {
        if (!\Bitrix\Main\Loader::includeModule('iblock')) {
            return [];
        }

        // Находим инфоблок настроек
        $iblockRes = \CIBlock::GetList(
            [],
            ['CODE' => 'site_settings', 'TYPE' => 'site_settings', 'CHECK_PERMISSIONS' => 'N']
        );

        if (!$iblock = $iblockRes->Fetch()) {
            return [];
        }

        $iblockId = (int)$iblock['ID'];

        // Ищем элемент по коду
        $elementRes = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $iblockId,
                'CODE' => $elementCode,
                'ACTIVE' => 'Y'
            ],
            false,
            false,
            ['ID', 'CODE']
        );

        if ($element = $elementRes->Fetch()) {
            // Получаем все значения множественного свойства TECH_DATA
            $properties = \CIBlockElement::GetProperty(
                $iblockId,
                $element['ID'],
                [],
                ['CODE' => 'TECH_DATA']
            );

            $result = [];
            while ($property = $properties->Fetch()) {
                $techDataValue = $property['VALUE'];

                // Если значение не пустое, пытаемся распарсить JSON
                if (!empty($techDataValue)) {
                    $decoded = json_decode($techDataValue, true);

                    // Если это JSON с ключами 'key' и 'value', добавляем в результат
                    if (is_array($decoded) && isset($decoded['key'], $decoded['value'])) {
                        $key = $decoded['key'];
                        $value = $decoded['value'];

                        // Добавляем в ассоциативный массив, используя 'key' как ключ массива
                        if (!empty($key)) {
                            $result[$key] = $value;
                        }
                    }
                }
            }

            return $result;
        }

        return [];
    }
}