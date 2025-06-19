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
     *
     * @param string $code Символьный код настройки.
     * @param mixed|null $defaultValue Значение по умолчанию, если настройка не найдена.
     * @param string|null $siteId ID сайта. Если null, используется текущий.
     * @return mixed
     */
    public static function get(string $code, $defaultValue = null, ?string $siteId = null)
    {
        $options = self::getAll($siteId);
        return $options[$code] ?? $defaultValue;
    }

    /**
     * Получает все сохраненные настройки для сайта.
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
            // В случае ошибки (например, битый JSON) возвращаем пустой массив
            self::$optionsCache[$siteId] = [];
        }

        return self::$optionsCache[$siteId];
    }

    /**
     * Сохраняет массив настроек в JSON-файл.
     *
     * @param array $settings Ассоциативный массив настроек ['CODE' => 'VALUE', ...].
     * @param string $siteId ID сайта.
     * @return bool
     * @throws \Bitrix\Main\IO\FileNotFoundException
     */
    public static function save(array $settings, string $siteId): bool
    {
        $filePath = self::getFilePath($siteId, true);

        // Получаем текущие настройки, чтобы обновить их, а не перезаписать полностью
        $currentOptions = self::getAll($siteId);
        $newOptions = array_merge($currentOptions, $settings);

        $json = Json::encode($newOptions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        // Сбрасываем кэш перед записью
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
            // FIX: Метод isExists() должен вызываться на экземпляре класса Directory.
            $directory = new Directory($siteStorageDir);
            if (!$directory->isExists()) {
                // Статический метод createDirectory вызывается корректно.
                Directory::createDirectory($siteStorageDir);
            }
        }

        return $siteStorageDir . 'settings.json';
    }
}