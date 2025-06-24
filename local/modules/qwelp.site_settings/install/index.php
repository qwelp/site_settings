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
use Bitrix\Main\EventManager;

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
    protected function getModulePath(): string
    {
        $modulePath = str_replace('\\', '/', __DIR__);
        return substr($modulePath, 0, strpos($modulePath, '/install'));
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
            return;
        }

        ModuleManager::registerModule($this->MODULE_ID);

        $this->InstallEvents();
        $this->InstallDB();
        $this->InstallFiles();

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

        $this->UnInstallDB();
        $this->UnInstallFiles();
        $this->UnInstallEvents();

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
    protected function isVersionCompatible(): bool
    {
        return CheckVersion(ModuleManager::getVersion('main'), '14.00.00');
    }

    /**
     * Устанавливает файлы модуля
     *
     * @return bool
     */
    public function InstallFiles(): bool
    {
        CopyDirFiles(
            $this->MODULE_PATH . '/install/components',
            Application::getDocumentRoot() . '/bitrix/components',
            true, true
        );

        CopyDirFiles(
            $this->MODULE_PATH . '/install/js',
            Application::getDocumentRoot() . '/bitrix/js/' . $this->MODULE_ID,
            true, true
        );
        CopyDirFiles(
            $this->MODULE_PATH . '/install/css',
            Application::getDocumentRoot() . '/bitrix/css/' . $this->MODULE_ID,
            true, true
        );

        return true;
    }

    /**
     * Удаляет файлы модуля
     *
     * @return bool
     */
    public function UnInstallFiles(): bool
    {
        Directory::deleteDirectory(
            Application::getDocumentRoot() . '/bitrix/components/qwelp/site.settings'
        );

        Directory::deleteDirectory(
            Application::getDocumentRoot() . '/bitrix/js/' . $this->MODULE_ID
        );
        Directory::deleteDirectory(
            Application::getDocumentRoot() . '/bitrix/css/' . $this->MODULE_ID
        );

        return true;
    }

    /**
     * Регистрирует обработчики событий
     *
     * @return void
     */
    public function InstallEvents(): void
    {
        $eventManager = EventManager::getInstance();

        // [FIXED] Регистрируем ДВА обработчика для ОДНОГО события
        $eventManager->registerEventHandler(
            'iblock',
            'OnIBlockPropertyBuildList',
            $this->MODULE_ID,
            '\Qwelp\SiteSettings\EventHandler',
            'onIBlockPropertyBuildListValues' // Метод для первого свойства
        );
        $eventManager->registerEventHandler(
            'iblock',
            'OnIBlockPropertyBuildList',
            $this->MODULE_ID,
            '\Qwelp\SiteSettings\EventHandler',
            'onIBlockPropertyBuildListKeyValue' // Метод для второго свойства
        );

        // Регистрация для UserType остается прежней
        $eventManager->registerEventHandler(
            'main',
            'OnUserTypeBuildList',
            $this->MODULE_ID,
            '\Qwelp\SiteSettings\EventHandler',
            'onUserTypeBuildList'
        );
    }

    /**
     * Удаляет обработчики событий
     *
     * @return void
     */
    public function UnInstallEvents(): void
    {
        $eventManager = EventManager::getInstance();

        // [FIXED] Удаляем ДВА обработчика
        $eventManager->unRegisterEventHandler(
            'iblock',
            'OnIBlockPropertyBuildList',
            $this->MODULE_ID,
            '\Qwelp\SiteSettings\EventHandler',
            'onIBlockPropertyBuildListValues'
        );
        $eventManager->unRegisterEventHandler(
            'iblock',
            'OnIBlockPropertyBuildList',
            $this->MODULE_ID,
            '\Qwelp\SiteSettings\EventHandler',
            'onIBlockPropertyBuildListKeyValue'
        );

        $eventManager->unRegisterEventHandler(
            'main',
            'OnUserTypeBuildList',
            $this->MODULE_ID,
            '\Qwelp\SiteSettings\EventHandler',
            'onUserTypeBuildList'
        );
    }

    /**
     * Устанавливает базу данных модуля
     *
     * @return bool
     */
    public function InstallDB(): bool
    {
        global $APPLICATION;
        require_once($this->MODULE_PATH . '/install/db.php');

        if (!InstallDB()) {
            $APPLICATION->ThrowException(Loc::getMessage('QWELP_SITE_SETTINGS_INSTALL_ERROR_DB') ?: 'Ошибка при создании инфоблока настроек');
            return false;
        }

        return true;
    }

    /**
     * Удаляет базу данных модуля
     *
     * @return bool
     */
    public function UnInstallDB(): bool
    {
        require_once($this->MODULE_PATH . '/install/db.php');
        UnInstallDB();
        Option::delete($this->MODULE_ID);
        return true;
    }
}