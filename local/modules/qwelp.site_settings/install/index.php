<?php
/**
 * Файл установки модуля qwelp.site_settings
 * 
 * @package qwelp.site_settings
 */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;

Loc::loadMessages(__FILE__);

/**
 * Класс qwelp_site_settings для установки/удаления модуля
 */
class qwelp_site_settings extends CModule
{
    /**
     * ID модуля
     * @var string
     */
    public $MODULE_ID = 'qwelp.site_settings';

    /**
     * Версия модуля
     * @var string
     */
    public $MODULE_VERSION;

    /**
     * Дата версии модуля
     * @var string
     */
    public $MODULE_VERSION_DATE;

    /**
     * Название модуля
     * @var string
     */
    public $MODULE_NAME;

    /**
     * Описание модуля
     * @var string
     */
    public $MODULE_DESCRIPTION;

    /**
     * Группа модуля в админке
     * @var string
     */
    public $MODULE_GROUP_RIGHTS = 'N';

    /**
     * Путь к модулю
     * @var string
     */
    public $MODULE_PATH;

    /**
     * Конструктор класса
     */
    public function __construct()
    {
        $arModuleVersion = [];

        include(__DIR__ . '/version.php');

        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_NAME = Loc::getMessage('QWELP_SITE_SETTINGS_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('QWELP_SITE_SETTINGS_MODULE_DESCRIPTION');

        $this->MODULE_PATH = $this->getModulePath();
    }

    /**
     * Получает путь к модулю
     * 
     * @return string
     */
    protected function getModulePath()
    {
        $modulePath = str_replace('\\', '/', __DIR__);
        $modulePath = substr($modulePath, 0, strpos($modulePath, '/install'));

        return $modulePath;
    }

    /**
     * Устанавливает модуль
     * 
     * @return void
     */
    public function DoInstall()
    {
        global $APPLICATION;

        if (!$this->isVersionCompatible()) {
            $APPLICATION->ThrowException(
                Loc::getMessage('QWELP_SITE_SETTINGS_INSTALL_ERROR_VERSION')
            );
            return false;
        }

        ModuleManager::registerModule($this->MODULE_ID);

        $this->InstallFiles();
        $this->InstallDB();

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage('QWELP_SITE_SETTINGS_INSTALL_TITLE'),
            $this->MODULE_PATH . '/install/step.php'
        );
    }

    /**
     * Удаляет модуль
     * 
     * @return void
     */
    public function DoUninstall()
    {
        global $APPLICATION;

        $this->UnInstallFiles();
        $this->UnInstallDB();

        ModuleManager::unRegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage('QWELP_SITE_SETTINGS_UNINSTALL_TITLE'),
            $this->MODULE_PATH . '/install/unstep.php'
        );
    }

    /**
     * Проверяет совместимость версии Битрикса
     * 
     * @return bool
     */
    protected function isVersionCompatible()
    {
        return CheckVersion(ModuleManager::getVersion('main'), '14.00.00');
    }

    /**
     * Устанавливает файлы модуля
     * 
     * @return bool
     */
    public function InstallFiles()
    {
        // 1) Копируем компоненты
        CopyDirFiles(
            $this->MODULE_PATH . '/install/components',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/components',
            true, true
        );

        // 2) Копируем административные прокси (необязательно, если их нет)
        CopyDirFiles(
            $this->MODULE_PATH . '/install/admin',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/admin',
            true, true
        );

        // 3) Копируем JS в публичную папку
        CopyDirFiles(
            $this->MODULE_PATH . '/install/admin/options_control.js',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/js/' . $this->MODULE_ID . '/options_control.js',
            true, true
        );

        // 4) Копируем CSS в публичную папку
        CopyDirFiles(
            $this->MODULE_PATH . '/install/admin/options_control.css',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/css/' . $this->MODULE_ID . '/options_control.css',
            true, true
        );

        // 4.1) Копируем языковые файлы для JS
        CopyDirFiles(
            $this->MODULE_PATH . '/install/admin/lang',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/js/' . $this->MODULE_ID . '/lang',
            true, true
        );

        // 5) Копируем шаблоны настроек
        CopyDirFiles(
            $this->MODULE_PATH . '/templates',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/templates',
            true, true
        );

        // 6) Регистрируем обработчики событий
        RegisterModuleDependences(
            'main',
            'OnAdminContextMenuShow',
            $this->MODULE_ID,
            '\Qwelp\SiteSettings\EventHandler',
            'addAdminContextMenu',
            100
        );
        RegisterModuleDependences(
            'iblock',
            'OnIBlockPropertyBuildList',
            $this->MODULE_ID,
            '\Qwelp\SiteSettings\EventHandler',
            'onIBlockPropertyBuildList'
        );

        return true;
    }

    /**
     * Удаляет файлы модуля
     * 
     * @return bool
     */
    public function UnInstallFiles()
    {
        // 1) Удаляем компоненты
        \Bitrix\Main\IO\Directory::deleteDirectory(
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/components/qwelp/site.settings'
        );

        // 2) Удаляем административные прокси
        \Bitrix\Main\IO\Directory::deleteDirectory(
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/admin'
        );

        // 3) Удаляем публичный JS-каталог
        \Bitrix\Main\IO\Directory::deleteDirectory(
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/js/' . $this->MODULE_ID
        );

        // 4) Удаляем публичный CSS-каталог
        \Bitrix\Main\IO\Directory::deleteDirectory(
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/css/' . $this->MODULE_ID
        );

        // 5) Удаляем шаблоны настроек
        \Bitrix\Main\IO\Directory::deleteDirectory(
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/templates'
        );

        // 6) Снимаем регистрацию обработчиков
        UnRegisterModuleDependences(
            'main',
            'OnAdminContextMenuShow',
            $this->MODULE_ID,
            '\Qwelp\SiteSettings\EventHandler',
            'addAdminContextMenu'
        );
        UnRegisterModuleDependences(
            'iblock',
            'OnIBlockPropertyBuildList',
            $this->MODULE_ID,
            '\Qwelp\SiteSettings\EventHandler',
            'onIBlockPropertyBuildList'
        );

        return true;
    }

    /**
     * Устанавливает базу данных модуля
     * 
     * @return bool
     */
    public function InstallDB()
    {
        global $DB, $APPLICATION;

        // Подключаем файл с функцией InstallDB
        require_once($this->MODULE_PATH . '/install/db.php');

        // Вызываем функцию создания инфоблока
        if (!InstallDB()) {
            $APPLICATION->ThrowException(Loc::getMessage('QWELP_SITE_SETTINGS_INSTALL_ERROR_DB'));
            return false;
        }

        return true;
    }

    /**
     * Удаляет базу данных модуля
     * 
     * @return bool
     */
    public function UnInstallDB()
    {
        global $DB, $APPLICATION;

        // Подключаем файл с функцией UnInstallDB
        require_once($this->MODULE_PATH . '/install/db.php');

        // Вызываем функцию удаления инфоблока
        UnInstallDB();

        // Удаляем опции модуля
        Option::delete($this->MODULE_ID);

        return true;
    }
}
