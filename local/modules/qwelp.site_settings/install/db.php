<?php
/**
 * Файл для создания инфоблока при установке модуля
 *
 * @package qwelp.site_settings
 */

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Iblock\TypeTable;

Loc::loadMessages(__FILE__);

/**
 * Транслитерирует строку с заданными параметрами
 *
 * @param string $string Строка для транслитерации
 * @return string Транслитерированная строка
 */
function transliterateString(string $string): string
{
    // Если строка пустая, возвращаем пустую строку
    if (empty($string)) {
        return '';
    }

    // Транслитерируем строку с помощью CUtil::translit
    $code = \CUtil::translit(
        $string,
        "ru",
        [
            "max_len"                 => 100,
            "change_case"             => "L",
            "replace_space"           => "-",
            "replace_other"           => "-",
            "remove_duplicated_chars" => true,
        ]
    );

    return $code;
}

/**
 * Создает тип инфоблока, инфоблок и свойства для настроек сайта
 *
 * @return bool
 */
function InstallDB(): bool
{ 
    global $DB, $APPLICATION;

    // Подключаем модуль инфоблоков
    if (!Loader::includeModule('iblock')) {
        $APPLICATION->ThrowException(Loc::getMessage('QWELP_SITE_SETTINGS_IBLOCK_MODULE_NOT_INSTALLED'));
        return false;
    }

    // Создаем тип инфоблока, если не существует
    $iblockType = 'site_settings';
    $typeResult = TypeTable::getList([
        'filter' => ['=ID' => $iblockType],
        'select' => ['ID'],
        'limit'  => 1,
    ]);

    if (!$typeResult->fetch()) {
        $arFields = [
            'ID'              => $iblockType,
            'SECTIONS'        => 'Y',
            'EDIT_FILE_BEFORE'=> '',
            'EDIT_FILE_AFTER' => '',
            'IN_RSS'          => 'N',
            'SORT'            => 500,
            'LANG'            => [
                'ru' => [
                    'NAME'         => 'Настройки сайта',
                    'SECTION_NAME' => 'Разделы',
                    'ELEMENT_NAME' => 'Настройки',
                ],
                'en' => [
                    'NAME'         => 'Site Settings',
                    'SECTION_NAME' => 'Sections',
                    'ELEMENT_NAME' => 'Settings',
                ],
            ],
        ];

        $obIblockType = new \CIBlockType();
        $result       = $obIblockType->Add($arFields);
        if (!$result) {
            $APPLICATION->ThrowException($obIblockType->LAST_ERROR);
            return false;
        }
    }

    // Создаем инфоблок
    $iblockCode  = 'site_settings';
    $iblockXmlId = 'site_settings';

    // Проверяем, существует ли уже инфоблок с таким кодом
    $dbIblock = \CIBlock::GetList(
        [],
        [
            'CODE'             => $iblockCode,
            'TYPE'             => $iblockType,
            'CHECK_PERMISSIONS'=> 'N',
        ]
    );

    if (!$dbIblock->Fetch()) {
        // Получаем все сайты
        $sites  = [];
        $sort = 'sort';
        $order = 'desc';
        $rsSites= \CSite::GetList($sort, $order, []);
        while ($arSite = $rsSites->Fetch()) {
            $sites[] = $arSite['LID'];
        }

        // Создаем инфоблок
        $iblockFieldsMain = [
            'ACTIVE'             => 'Y',
            'NAME'               => 'Настройки сайта',
            'CODE'               => $iblockCode,
            'XML_ID'             => $iblockXmlId,
            'IBLOCK_TYPE_ID'     => $iblockType,
            'SITE_ID'            => $sites,
            'SORT'               => 100,
            'GROUP_ID'           => ['2' => 'R'],
            'VERSION'            => 2,
            'INDEX_ELEMENT'      => 'N',
            'INDEX_SECTION'      => 'N',
            'WORKFLOW'           => 'N',
            'BIZPROC'            => 'N',
            'LIST_PAGE_URL'      => '',
            'SECTION_PAGE_URL'   => '',
            'DETAIL_PAGE_URL'    => '',
            'CANONICAL_PAGE_URL' => '',
            'EDIT_FILE_BEFORE'   => '',
            'EDIT_FILE_AFTER'    => '',
        ];

        $iblock   = new \CIBlock();
        $iblockId = $iblock->Add($iblockFieldsMain);
        if (!$iblockId) {
            $APPLICATION->ThrowException($iblock->LAST_ERROR);
            return false;
        }

        // Устанавливаем поле CODE как обязательное для разделов и настраиваем символьный код
        $iblockFields = \CIBlock::GetFields($iblockId);
        $iblockFields["SECTION_CODE"]["IS_REQUIRED"] = "Y";
        // Настройки символьного кода
        $iblockFields["SECTION_CODE"]["DEFAULT_VALUE"]["UNIQUE"] = "Y";          // Проверять на уникальность
        $iblockFields["SECTION_CODE"]["DEFAULT_VALUE"]["TRANSLITERATION"] = "Y"; // Транслитерировать из названия
        $iblockFields["SECTION_CODE"]["DEFAULT_VALUE"]["TRANS_LEN"] = 100;       // Максимальная длина результата
        $iblockFields["SECTION_CODE"]["DEFAULT_VALUE"]["TRANS_CASE"] = "L";      // Приведение к нижнему регистру
        $iblockFields["SECTION_CODE"]["DEFAULT_VALUE"]["TRANS_SPACE"] = "-";     // Замена для символа пробела
        $iblockFields["SECTION_CODE"]["DEFAULT_VALUE"]["TRANS_OTHER"] = "-";     // Замена для прочих символов
        $iblockFields["SECTION_CODE"]["DEFAULT_VALUE"]["TRANS_EAT"] = "Y";       // Удалять лишние символы замены
        $iblockFields["SECTION_CODE"]["DEFAULT_VALUE"]["USE_GOOGLE"] = "Y";      // Использовать внешний сервис для перевода

        // Устанавливаем поле CODE как обязательное для элементов и настраиваем символьный код
        $iblockFields["CODE"]["IS_REQUIRED"] = "Y";
        // Настройки символьного кода
        $iblockFields["CODE"]["DEFAULT_VALUE"]["UNIQUE"] = "Y";          // Проверять на уникальность
        $iblockFields["CODE"]["DEFAULT_VALUE"]["TRANSLITERATION"] = "Y"; // Транслитерировать из названия
        $iblockFields["CODE"]["DEFAULT_VALUE"]["TRANS_LEN"] = 100;       // Максимальная длина результата
        $iblockFields["CODE"]["DEFAULT_VALUE"]["TRANS_CASE"] = "L";      // Приведение к нижнему регистру
        $iblockFields["CODE"]["DEFAULT_VALUE"]["TRANS_SPACE"] = "-";     // Замена для символа пробела
        $iblockFields["CODE"]["DEFAULT_VALUE"]["TRANS_OTHER"] = "-";     // Замена для прочих символов
        $iblockFields["CODE"]["DEFAULT_VALUE"]["TRANS_EAT"] = "Y";       // Удалять лишние символы замены
        $iblockFields["CODE"]["DEFAULT_VALUE"]["USE_GOOGLE"] = "Y";      // Использовать внешний сервис для перевода

        \CIBlock::SetFields($iblockId, $iblockFields);

        $oUserTypeEntity = new \CUserTypeEntity();
        $arUserFieldData = [
            'ENTITY_ID'         => 'IBLOCK_' . $iblockId . '_SECTION',
            'FIELD_NAME'        => 'UF_ENABLE_DRAG_AND_DROP', // Новое, более логичное системное имя
            'USER_TYPE_ID'      => 'boolean',                 // Тип поля - логический (чекбокс Да/Нет)
            'XML_ID'            => 'UF_ENABLE_DRAG_AND_DROP', // Новое, более логичное внешнее имя
            'SORT'              => 500,
            'MULTIPLE'          => 'N',                       // Логическое поле не может быть множественным
            'MANDATORY'         => 'N',                       // Обязательность поля (N - нет)
            'SHOW_FILTER'       => 'N',                       // Показывать в фильтре списка (можно изменить на 'Y')
            'SHOW_IN_LIST'      => 'Y',                       // Показывать в списке элементов
            'EDIT_IN_LIST'      => 'Y',                       // Разрешать редактирование в списке
            'IS_SEARCHABLE'     => 'N',                       // Участвует ли в поиске
            'SETTINGS'          => [
                'DEFAULT_VALUE' => 0,        // Значение по умолчанию: 0 (чекбокс не отмечен) или 1 (отмечен)
                // 'LABEL_CHECKBOX' => 'Текст непосредственно у чекбокса' // Можно использовать, если стандартной метки недостаточно
            ],
            'EDIT_FORM_LABEL'   => [
                'ru' => 'Включить drag and drop',
                'en' => 'Enable drag and drop',
            ],
            'LIST_COLUMN_LABEL' => [
                'ru' => 'Drag and Drop', // Название для колонки в списке
                'en' => 'Drag and Drop',
            ],
            'LIST_FILTER_LABEL' => [
                'ru' => 'Drag and Drop', // Название для фильтра
                'en' => 'Drag and Drop',
            ],
            'HELP_MESSAGE'      => [
                'ru' => 'Активирует или деактивирует функционал drag and drop для раздела.',
                'en' => 'Activates or deactivates the drag and drop functionality for the section.',
            ],
        ];

        $userFieldId = $oUserTypeEntity->Add($arUserFieldData);
        if (!$userFieldId) {
            if ($ex = $APPLICATION->GetException()) {
                $APPLICATION->ThrowException(Loc::getMessage('QWELP_SITE_SETTINGS_UF_ADD_ERROR') . $ex->GetString());
            } else {
                $APPLICATION->ThrowException(Loc::getMessage('QWELP_SITE_SETTINGS_UF_ADD_ERROR_UNKNOWN'));
            }
            return false;
        }

        // Добавляем пользовательское поле для разделов - детальное свойство
        $detailPropertyUserField = [
            'ENTITY_ID'         => 'IBLOCK_' . $iblockId . '_SECTION',
            'FIELD_NAME'        => 'UF_DETAIL_PROPERTY',
            'USER_TYPE_ID'      => 'boolean',
            'XML_ID'            => 'UF_DETAIL_PROPERTY',
            'SORT'              => 510,
            'MULTIPLE'          => 'N',                       // Логическое поле не может быть множественным
            'MANDATORY'         => 'N',                       // Обязательность поля (N - нет)
            'SHOW_FILTER'       => 'N',                       // Показывать в фильтре списка (можно изменить на 'Y')
            'SHOW_IN_LIST'      => 'Y',                       // Показывать в списке элементов
            'EDIT_IN_LIST'      => 'Y',                       // Разрешать редактирование в списке
            'IS_SEARCHABLE'     => 'N',
            'SETTINGS'          => [
                'DEFAULT_VALUE' => '',
                'SIZE'          => 20,
                'ROWS'          => 1,
            ],
            'EDIT_FORM_LABEL'   => [
                'ru' => 'Детальное свойство',
                'en' => 'Detail Property',
            ],
            'LIST_COLUMN_LABEL' => [
                'ru' => 'Детальное свойство',
                'en' => 'Detail Property',
            ],
            'LIST_FILTER_LABEL' => [
                'ru' => 'Детальное свойство',
                'en' => 'Detail Property',
            ],
            'HELP_MESSAGE'      => [
                'ru' => 'Дополнительная информация для раздела.',
                'en' => 'Additional information for the section.',
            ],
        ];

        $detailPropertyUserFieldId = $oUserTypeEntity->Add($detailPropertyUserField);
        if (!$detailPropertyUserFieldId) {
            if ($ex = $APPLICATION->GetException()) {
                $APPLICATION->ThrowException(Loc::getMessage('QWELP_SITE_SETTINGS_UF_ADD_ERROR') . $ex->GetString());
            } else {
                $APPLICATION->ThrowException(Loc::getMessage('QWELP_SITE_SETTINGS_UF_ADD_ERROR_UNKNOWN'));
            }
            return false;
        }

        // Добавляем значения списка
        $obEnum = new \CUserFieldEnum();
        $enumValues = [
            'n0' => [
                'XML_ID' => 'Y',
                'VALUE'  => 'Да',
                'DEF'    => 'N',
                'SORT'   => '100',
            ]
        ];
        $obEnum->SetEnumValues($userFieldId, $enumValues);

        // Добавляем пользовательское поле для разделов - единое свойство
        $commonPropertyUserField = [
            'ENTITY_ID'         => 'IBLOCK_' . $iblockId . '_SECTION',
            'FIELD_NAME'        => 'UF_COMMON_PROPERTY', // Новое системное имя
            'USER_TYPE_ID'      => 'boolean',
            'XML_ID'            => 'UF_COMMON_PROPERTY',
            'SORT'              => 520,
            'MULTIPLE'          => 'N', // Логическое поле не может быть множественным
            'MANDATORY'         => 'N', // Обязательность поля (N - нет)
            'SHOW_FILTER'       => 'N',
            'SHOW_IN_LIST'      => 'Y', // Показывать в списке разделов
            'EDIT_IN_LIST'      => 'Y', // Разрешать редактирование в списке
            'IS_SEARCHABLE'     => 'N',
            'SETTINGS'          => [
                'DEFAULT_VALUE' => '',
                'SIZE'          => 20,
                'ROWS'          => 1,
            ],
            'EDIT_FORM_LABEL'   => [
                'ru' => 'Единое свойство',
                'en' => 'Common Property',
            ],
            'LIST_COLUMN_LABEL' => [
                'ru' => 'Единое свойство',
                'en' => 'Common Property',
            ],
            'LIST_FILTER_LABEL' => [
                'ru' => 'Единое свойство',
                'en' => 'Common Property',
            ],
            'HELP_MESSAGE'      => [
                'ru' => 'Единое свойство для всех разделов.',
                'en' => 'A common property for all sections.',
            ],
        ];
        $commonPropertyUserFieldId = $oUserTypeEntity->Add($commonPropertyUserField);
        if (!$commonPropertyUserFieldId) {
            if ($ex = $APPLICATION->GetException()) {
                $APPLICATION->ThrowException(Loc::getMessage('QWELP_SITE_SETTINGS_UF_ADD_ERROR') . $ex->GetString());
            } else {
                $APPLICATION->ThrowException(Loc::getMessage('QWELP_SITE_SETTINGS_UF_ADD_ERROR_UNKNOWN'));
            }
            return false;
        }

        // Добавляем пользовательское поле для разделов - свернутый блок
        $collapsedBlockUserField = [
            'ENTITY_ID'         => 'IBLOCK_' . $iblockId . '_SECTION',
            'FIELD_NAME'        => 'UF_COLLAPSED_BLOCK',
            'USER_TYPE_ID'      => 'boolean',
            'XML_ID'            => 'UF_COLLAPSED_BLOCK',
            'SORT'              => 530,
            'MULTIPLE'          => 'N',
            'MANDATORY'         => 'N',
            'SHOW_FILTER'       => 'N',
            'SHOW_IN_LIST'      => 'Y',
            'EDIT_IN_LIST'      => 'Y',
            'IS_SEARCHABLE'     => 'N',
            'SETTINGS'          => ['DEFAULT_VALUE' => '', 'SIZE' => 20, 'ROWS' => 1],
            'EDIT_FORM_LABEL'   => ['ru' => 'Свернутый блок', 'en' => 'Collapsed Block'],
            'LIST_COLUMN_LABEL' => ['ru' => 'Свернутый блок', 'en' => 'Collapsed Block'],
            'LIST_FILTER_LABEL' => ['ru' => 'Свернутый блок', 'en' => 'Collapsed Block'],
            'HELP_MESSAGE'      => ['ru' => 'Позволяет скрывать содержимое раздела по умолчанию.', 'en' => 'Allows to hide the section content by default.'],
        ];
        $collapsedBlockUserFieldId = $oUserTypeEntity->Add($collapsedBlockUserField);
        if (!$collapsedBlockUserFieldId) {
            if ($ex = $APPLICATION->GetException()) {
                $APPLICATION->ThrowException(Loc::getMessage('QWELP_SITE_SETTINGS_UF_ADD_ERROR') . $ex->GetString());
            } else {
                $APPLICATION->ThrowException(Loc::getMessage('QWELP_SITE_SETTINGS_UF_ADD_ERROR_UNKNOWN'));
            }
            return false;
        }

        // === Свойства инфоблока ===
        $ibp = new \CIBlockProperty();

        // VALUES — JSON для select/radio/radioImage
        $valuesProperty = [
            'IBLOCK_ID'     => $iblockId,
            'NAME'          => 'Варианты значений',
            'CODE'          => 'VALUES',
            'PROPERTY_TYPE' => 'S',
            'USER_TYPE'     => 'QwelpSettingsValues',
            'MULTIPLE'      => 'N',
            'IS_REQUIRED'   => 'N',
            'HINT'          => 'JSON-массив вариантов для select/radio/radioImage',
        ];
        if (!$ibp->Add($valuesProperty)) {
            $APPLICATION->ThrowException($ibp->LAST_ERROR);
            return false;
        }

        // SHOW_TITLE — флаг отображения заголовка на сайте
        $showTitleProperty = [
            'IBLOCK_ID'     => $iblockId,
            'NAME'          => 'Показать заголовок',
            'CODE'          => 'SHOW_TITLE',
            'PROPERTY_TYPE' => 'L',    // список
            'LIST_TYPE'     => 'C',    // чекбоксы
            'MULTIPLE'      => 'N',
            'IS_REQUIRED'   => 'N',
            'HINT'          => 'Отображать заголовок на странице',
        ];
        $showTitlePropertyId = $ibp->Add($showTitleProperty);
        if (!$showTitlePropertyId) {
            $APPLICATION->ThrowException($ibp->LAST_ERROR);
            return false;
        }

        // Значения списка: «Нет» и «Да» (по умолчанию «Да»)
        $enum = new \CIBlockPropertyEnum();
        $enum->Add(['PROPERTY_ID' => $showTitlePropertyId, 'VALUE' => 'Да',  'DEF' => 'Y']);


        // DETAIL_PROPERTY — флаг отображения заголовка на сайте
        $showDetailProperty = [
            'IBLOCK_ID'     => $iblockId,
            'NAME'          => 'Детальное свойство',
            'CODE'          => 'DETAIL_PROPERTY',
            'PROPERTY_TYPE' => 'L',    // список
            'LIST_TYPE'     => 'C',    // чекбоксы
            'MULTIPLE'      => 'N',
            'IS_REQUIRED'   => 'N',
            'HINT'          => 'Отображать детальное свойство на странице',
        ];
        $showDetailPropertyId = $ibp->Add($showDetailProperty);
        if (!$showDetailPropertyId) {
            $APPLICATION->ThrowException($ibp->LAST_ERROR);
            return false;
        }

        // Значения списка: «Нет» и «Да» (по умолчанию «Да»)
        $enum = new \CIBlockPropertyEnum();
        $enum->Add(['PROPERTY_ID' => $showDetailPropertyId, 'VALUE' => 'Да',  'DEF' => 'Y']);


        // HELP_TEXT — подсказка
        $helpTextProperty = [
            'IBLOCK_ID'     => $iblockId,
            'NAME'          => 'Текст подсказки',
            'CODE'          => 'HELP_TEXT',
            'PROPERTY_TYPE' => 'S',
            'MULTIPLE'      => 'N',
            'IS_REQUIRED'   => 'N',
            'HINT'          => 'Текст, показываемый при клике на ?',
        ];
        if (!$ibp->Add($helpTextProperty)) {
            $APPLICATION->ThrowException($ibp->LAST_ERROR);
            return false;
        }

        // HELP_IMAGE — картинка подсказки
        $helpImageProperty = [
            'IBLOCK_ID'     => $iblockId,
            'NAME'          => 'Изображение подсказки',
            'CODE'          => 'HELP_IMAGE',
            'PROPERTY_TYPE' => 'F',
            'MULTIPLE'      => 'N',
            'IS_REQUIRED'   => 'N',
            'HINT'          => 'Изображение, показываемое в подсказке',
        ];
        if (!$ibp->Add($helpImageProperty)) {
            $APPLICATION->ThrowException($ibp->LAST_ERROR);
            return false;
        }

        // PERCENT — текстовое поле для процента
        $percentProperty = [
            'IBLOCK_ID'     => $iblockId,
            'NAME'          => 'Процент',
            'CODE'          => 'PERCENT',
            'PROPERTY_TYPE' => 'S', // Тип "строка"
            'MULTIPLE'      => 'N',
            'IS_REQUIRED'   => 'N',
            'HINT'          => 'Введите значение в процентах',
        ];
        if (!$ibp->Add($percentProperty)) {
            $APPLICATION->ThrowException($ibp->LAST_ERROR);
            return false;
        }

        // === Разделы ===
        $bs = new \CIBlockSection();
        $sections = [
            [
                'NAME'       => 'Общие',
                'CODE'       => 'general',
                'SORT'       => 100,
                'SUBSECTIONS'=> [
                    [
                        'NAME' => 'Основные',
                        'CODE' => 'general_main',
                        'SORT' => 110,
                        'SUBSECTIONS' => [
                            ['NAME' => 'Базовые настройки', 'CODE' => 'general_main_basic', 'SORT' => 111],
                            ['NAME' => 'Расширенные', 'CODE' => 'general_main_advanced', 'SORT' => 112],
                        ],
                    ],
                    [
                        'NAME' => 'Дополнительные',
                        'CODE' => 'general_additional',
                        'SORT' => 120,
                        'SUBSECTIONS' => [
                            ['NAME' => 'Языковые настройки', 'CODE' => 'general_additional_lang', 'SORT' => 121],
                            ['NAME' => 'Прочие настройки', 'CODE' => 'general_additional_other', 'SORT' => 122],
                        ],
                    ],
                ],
            ],
            [
                'NAME'       => 'Внешний вид',
                'CODE'       => 'appearance',
                'SORT'       => 200,
                'SUBSECTIONS'=> [
                    [
                        'NAME' => 'Темы',
                        'CODE' => 'appearance_themes',
                        'SORT' => 210,
                        'SUBSECTIONS' => [
                            ['NAME' => 'Цветовые схемы', 'CODE' => 'appearance_themes_colors', 'SORT' => 211],
                            ['NAME' => 'Шрифты', 'CODE' => 'appearance_themes_fonts', 'SORT' => 212],
                        ],
                    ],
                    [
                        'NAME' => 'Элементы',
                        'CODE' => 'appearance_elements',
                        'SORT' => 220,
                        'SUBSECTIONS' => [
                            ['NAME' => 'Кнопки и формы', 'CODE' => 'appearance_elements_buttons', 'SORT' => 221],
                            ['NAME' => 'Изображения', 'CODE' => 'appearance_elements_images', 'SORT' => 222],
                        ],
                    ],
                ],
            ],
            [
                'NAME'       => 'Уведомления',
                'CODE'       => 'notifications',
                'SORT'       => 300,
                'SUBSECTIONS'=> [
                    [
                        'NAME' => 'Email',
                        'CODE' => 'notifications_email',
                        'SORT' => 310,
                        'SUBSECTIONS' => [
                            ['NAME' => 'Шаблоны писем', 'CODE' => 'notifications_email_templates', 'SORT' => 311],
                            ['NAME' => 'Настройки отправки', 'CODE' => 'notifications_email_settings', 'SORT' => 312],
                        ],
                    ],
                    [
                        'NAME' => 'Системные',
                        'CODE' => 'notifications_system',
                        'SORT' => 320,
                        'SUBSECTIONS' => [
                            ['NAME' => 'Всплывающие уведомления', 'CODE' => 'notifications_system_popup', 'SORT' => 321],
                            ['NAME' => 'Журнал событий', 'CODE' => 'notifications_system_log', 'SORT' => 322],
                        ],
                    ],
                ],
            ],
            [
                'NAME'       => 'Безопасность',
                'CODE'       => 'security',
                'SORT'       => 400,
                'SUBSECTIONS'=> [
                    [
                        'NAME' => 'Авторизация',
                        'CODE' => 'security_auth',
                        'SORT' => 410,
                        'SUBSECTIONS' => [
                            ['NAME' => 'Методы входа', 'CODE' => 'security_auth_methods', 'SORT' => 411],
                            ['NAME' => 'Пароли', 'CODE' => 'security_auth_passwords', 'SORT' => 412],
                        ],
                    ],
                    [
                        'NAME' => 'Защита',
                        'CODE' => 'security_protection',
                        'SORT' => 420,
                        'SUBSECTIONS' => [
                            ['NAME' => 'Антиспам', 'CODE' => 'security_protection_antispam', 'SORT' => 421],
                            ['NAME' => 'Брандмауэр', 'CODE' => 'security_protection_firewall', 'SORT' => 422],
                        ],
                    ],
                ],
            ],
            [
                'NAME'       => 'Интеграции',
                'CODE'       => 'integration',
                'SORT'       => 500,
                'SUBSECTIONS'=> [
                    [
                        'NAME' => 'API',
                        'CODE' => 'integration_api',
                        'SORT' => 510,
                        'SUBSECTIONS' => [
                            ['NAME' => 'Ключи доступа', 'CODE' => 'integration_api_keys', 'SORT' => 511],
                            ['NAME' => 'Ограничения', 'CODE' => 'integration_api_limits', 'SORT' => 512],
                        ],
                    ],
                    [
                        'NAME' => 'Сервисы',
                        'CODE' => 'integration_services',
                        'SORT' => 520,
                        'SUBSECTIONS' => [
                            ['NAME' => 'Социальные сети', 'CODE' => 'integration_services_social', 'SORT' => 521],
                            ['NAME' => 'Платежные системы', 'CODE' => 'integration_services_payment', 'SORT' => 522],
                        ],
                    ],
                ],
            ],
        ];

        $sectionIds    = [];
        $subsectionIds = [];

        foreach ($sections as $section) {
            // Если код не задан, транслитерируем из названия
            $sectionCode = trim($section['CODE'] ?? '');
            if ($sectionCode === '') {
                $sectionCode = transliterateString($section['NAME']);
            }

            // Проверяем уникальность внутри инфоблока
            $exists = \CIBlockSection::GetList(
                [],
                [
                    'IBLOCK_ID' => $iblockId,
                    'CODE'      => $sectionCode,
                ],
                false,
                ['ID'],
                ['nTopCount' => 1]
            )->Fetch();

            if ($exists) {
                // Если код уже используется, добавляем к нему уникальный суффикс
                $sectionCode .= '-' . rand(100, 999);
            }

            $sectionFields = [
                'ACTIVE'        => 'Y',
                'IBLOCK_ID'     => $iblockId,
                'NAME'          => $section['NAME'],
                'CODE'          => $sectionCode,  // Обязательное поле
                'SORT'          => $section['SORT'],
                'IS_REQUIRED'   => 'Y',
            ];
            $sectionId = $bs->Add($sectionFields);
            if (!$sectionId) {
                $APPLICATION->ThrowException($bs->LAST_ERROR);
            } else {
                // Сохраняем ID раздела по его коду (используем сгенерированный код)
                $sectionIds[$sectionCode] = $sectionId;

                if (!empty($section['SUBSECTIONS'])) {
                    foreach ($section['SUBSECTIONS'] as $sub) {
                        // Если код не задан, транслитерируем из названия
                        $subCode = trim($sub['CODE'] ?? '');
                        if ($subCode === '') {
                            $subCode = transliterateString($sub['NAME']);
                        }

                        // Проверяем уникальность внутри инфоблока
                        $exists = \CIBlockSection::GetList(
                            [],
                            [
                                'IBLOCK_ID' => $iblockId,
                                'CODE'      => $subCode,
                            ],
                            false,
                            ['ID'],
                            ['nTopCount' => 1]
                        )->Fetch();

                        if ($exists) {
                            // Если код уже используется, добавляем к нему уникальный суффикс
                            $subCode .= '-' . rand(100, 999);
                        }

                        $subFields = [
                            'ACTIVE'            => 'Y',
                            'IBLOCK_ID'         => $iblockId,
                            'NAME'              => $sub['NAME'],
                            'CODE'              => $subCode,  // Обязательное поле
                            'SORT'              => $sub['SORT'],
                            'IBLOCK_SECTION_ID' => $sectionId,
                        ];
                        $subId = $bs->Add($subFields);
                        if (!$subId) {
                            $APPLICATION->ThrowException($bs->LAST_ERROR);
                        } else {
                            $subsectionIds[$subCode] = $subId;

                            // Добавляем разделы третьего уровня
                            if (!empty($sub['SUBSECTIONS'])) {
                                foreach ($sub['SUBSECTIONS'] as $sub3) {
                                    // Если код не задан, транслитерируем из названия
                                    $sub3Code = trim($sub3['CODE'] ?? '');
                                    if ($sub3Code === '') {
                                        $sub3Code = transliterateString($sub3['NAME']);
                                    }

                                    // Проверяем уникальность внутри инфоблока
                                    $exists3 = \CIBlockSection::GetList(
                                        [],
                                        [
                                            'IBLOCK_ID' => $iblockId,
                                            'CODE'      => $sub3Code,
                                        ],
                                        false,
                                        ['ID'],
                                        ['nTopCount' => 1]
                                    )->Fetch();

                                    if ($exists3) {
                                        // Если код уже используется, добавляем к нему уникальный суффикс
                                        $sub3Code .= '-' . rand(100, 999);
                                    }

                                    $sub3Fields = [
                                        'ACTIVE'            => 'Y',
                                        'IBLOCK_ID'         => $iblockId,
                                        'NAME'              => $sub3['NAME'],
                                        'CODE'              => $sub3Code,  // Обязательное поле
                                        'SORT'              => $sub3['SORT'],
                                        'IBLOCK_SECTION_ID' => $subId,
                                    ];
                                    $sub3Id = $bs->Add($sub3Fields);
                                    if (!$sub3Id) {
                                        $APPLICATION->ThrowException($bs->LAST_ERROR);
                                    } else {
                                        $subsectionIds[$sub3Code] = $sub3Id;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // === Элементы (настройки) ===
        $settings = [
            // Общие — Основные — Базовые настройки
            [
                'NAME'          => 'Включить функцию',
                'CODE'          => 'ENABLE_FEATURE',
                'SECTION_CODE'  => 'general_main_basic',
                'TYPE'          => 'checkbox',
                'HELP_TEXT'     => 'Включает основную функциональность модуля.',
                'HELP_IMAGE'    => '/img/help-general.png',
                'SORT'          => 100,
            ],
            // Общие — Основные — Расширенные
            [
                'NAME'          => 'Расширенные настройки',
                'CODE'          => 'ADVANCED_SETTINGS',
                'SECTION_CODE'  => 'general_main_advanced',
                'TYPE'          => 'checkbox',
                'HELP_TEXT'     => 'Включает расширенные настройки модуля.',
                'HELP_IMAGE'    => '/img/help-general.png',
                'SORT'          => 110,
            ],
            // Общие — Дополнительные — Языковые настройки
            [
                'NAME'          => 'Язык интерфейса',
                'CODE'          => 'LANGUAGE',
                'SECTION_CODE'  => 'general_additional_lang',
                'TYPE'          => 'select',
                'VALUES'        => [
                    'TEXT' => json_encode([
                        ['value' => 'ru', 'label' => 'Русский'],
                        ['value' => 'en', 'label' => 'English'],
                        ['value' => 'de', 'label' => 'Deutsch'],
                    ]),
                    'TYPE' => 'html',
                ],
                'HELP_TEXT'     => 'Выберите язык для отображения всех подписей.',
                'HELP_IMAGE'    => '',
                'SORT'          => 200,
            ],
            // Общие — Дополнительные — Прочие настройки
            [
                'NAME'          => 'Прочие настройки',
                'CODE'          => 'OTHER_SETTINGS',
                'SECTION_CODE'  => 'general_additional_other',
                'TYPE'          => 'checkbox',
                'HELP_TEXT'     => 'Включает прочие настройки модуля.',
                'HELP_IMAGE'    => '',
                'SORT'          => 210,
            ],

            // Внешний вид — Темы — Цветовые схемы
            [
                'NAME'          => 'Тип оформления',
                'CODE'          => 'LAYOUT_TYPE',
                'SECTION_CODE'  => 'appearance_themes_colors',
                'TYPE'          => 'radio',
                'VALUES'        => [
                    'TEXT' => json_encode([
                        ['value' => 'light', 'label' => 'Светлый'],
                        ['value' => 'dark',  'label' => 'Тёмный'],
                    ]),
                    'TYPE' => 'html',
                ],
                'HELP_TEXT'     => 'Переключает между светлой и тёмной темой.',
                'HELP_IMAGE'    => '/img/help-appearance.png',
                'SORT'          => 100,
            ],
            // Внешний вид — Темы — Шрифты
            [
                'NAME'          => 'Шрифт',
                'CODE'          => 'FONT_FAMILY',
                'SECTION_CODE'  => 'appearance_themes_fonts',
                'TYPE'          => 'select',
                'VALUES'        => [
                    'TEXT' => json_encode([
                        ['value' => 'arial', 'label' => 'Arial'],
                        ['value' => 'times', 'label' => 'Times New Roman'],
                        ['value' => 'verdana', 'label' => 'Verdana'],
                    ]),
                    'TYPE' => 'html',
                ],
                'HELP_TEXT'     => 'Выберите шрифт для интерфейса.',
                'HELP_IMAGE'    => '',
                'SORT'          => 110,
            ],
            // Внешний вид — Элементы — Кнопки и формы
            [
                'NAME'          => 'Показать заголовок',
                'CODE'          => 'SHOW_TITLE',
                'SECTION_CODE'  => 'appearance_elements_buttons',
                'TYPE'          => 'checkbox',
                'HELP_TEXT'     => 'Отображать заголовок на странице',
                'HELP_IMAGE'    => '',
                'SORT'          => 300,
            ],
            // Внешний вид — Элементы — Изображения
            [
                'NAME'          => 'Фоновая тема',
                'CODE'          => 'THEME_IMAGE',
                'SECTION_CODE'  => 'appearance_elements_images',
                'TYPE'          => 'radioImage',
                'VALUES'        => [
                    'TEXT' => json_encode([
                        ['value' => 'mountains', 'image' => '/img/mountains.jpg'],
                        ['value' => 'sea',       'image' => '/img/sea.jpg'],
                        ['value' => 'forest',    'image' => '/img/forest.jpg'],
                    ]),
                    'TYPE' => 'html',
                ],
                'HELP_TEXT'     => 'Выберите изображение для фонового баннера.',
                'HELP_IMAGE'    => '',
                'SORT'          => 200,
            ],

            // Уведомления — Email — Шаблоны писем
            [
                'NAME'          => 'Шаблон письма',
                'CODE'          => 'EMAIL_TEMPLATE',
                'SECTION_CODE'  => 'notifications_email_templates',
                'TYPE'          => 'select',
                'VALUES'        => [
                    'TEXT' => json_encode([
                        ['value' => 'default', 'label' => 'Стандартный'],
                        ['value' => 'custom', 'label' => 'Пользовательский'],
                    ]),
                    'TYPE' => 'html',
                ],
                'HELP_TEXT'     => 'Выберите шаблон для email-уведомлений.',
                'HELP_IMAGE'    => '',
                'SORT'          => 110,
            ],
            // Уведомления — Email — Настройки отправки
            [
                'NAME'          => 'Email-уведомления',
                'CODE'          => 'EMAIL_NOTIFY',
                'SECTION_CODE'  => 'notifications_email_settings',
                'TYPE'          => 'checkbox',
                'HELP_TEXT'     => 'Получать уведомления о важных событиях на почту.',
                'HELP_IMAGE'    => '',
                'SORT'          => 100,
            ],
            // Уведомления — Системные — Всплывающие уведомления
            [
                'NAME'          => 'Всплывающие уведомления',
                'CODE'          => 'POPUP_NOTIFY',
                'SECTION_CODE'  => 'notifications_system_popup',
                'TYPE'          => 'checkbox',
                'HELP_TEXT'     => 'Показывать всплывающие уведомления.',
                'HELP_IMAGE'    => '',
                'SORT'          => 120,
            ],
            // Уведомления — Системные — Журнал событий
            [
                'NAME'          => 'Частота уведомлений',
                'CODE'          => 'NOTIFY_FREQUENCY',
                'SECTION_CODE'  => 'notifications_system_log',
                'TYPE'          => 'select',
                'VALUES'        => [
                    'TEXT' => json_encode([
                        ['value' => 'instant','label' => 'Мгновенно'],
                        ['value' => 'hourly', 'label' => 'Каждый час'],
                        ['value' => 'daily',  'label' => 'Раз в сутки'],
                    ]),
                    'TYPE' => 'html',
                ],
                'HELP_TEXT'     => 'Как часто отправлять уведомления.',
                'HELP_IMAGE'    => '',
                'SORT'          => 200,
            ],

            // Безопасность — Авторизация — Методы входа
            [
                'NAME'          => 'Двухфакторная аутентификация',
                'CODE'          => 'TWO_FACTOR',
                'SECTION_CODE'  => 'security_auth_methods',
                'TYPE'          => 'checkbox',
                'HELP_TEXT'     => 'Добавляет второй уровень защиты при входе.',
                'HELP_IMAGE'    => '/img/help-security.png',
                'SORT'          => 100,
            ],
            // Безопасность — Авторизация — Пароли
            [
                'NAME'          => 'Сложность пароля',
                'CODE'          => 'PASSWORD_COMPLEXITY',
                'SECTION_CODE'  => 'security_auth_passwords',
                'TYPE'          => 'select',
                'VALUES'        => [
                    'TEXT' => json_encode([
                        ['value' => 'low', 'label' => 'Низкая'],
                        ['value' => 'medium', 'label' => 'Средняя'],
                        ['value' => 'high', 'label' => 'Высокая'],
                    ]),
                    'TYPE' => 'html',
                ],
                'HELP_TEXT'     => 'Выберите требуемую сложность пароля.',
                'HELP_IMAGE'    => '',
                'SORT'          => 130,
            ],
            // Безопасность — Защита — Антиспам
            [
                'NAME'          => 'Защита от спама',
                'CODE'          => 'ANTISPAM',
                'SECTION_CODE'  => 'security_protection_antispam',
                'TYPE'          => 'checkbox',
                'HELP_TEXT'     => 'Включает защиту от спама.',
                'HELP_IMAGE'    => '',
                'SORT'          => 140,
            ],
            // Безопасность — Защита — Брандмауэр
            [
                'NAME'          => 'Таймаут сессии',
                'CODE'          => 'SESSION_TIMEOUT',
                'SECTION_CODE'  => 'security_protection_firewall',
                'TYPE'          => 'select',
                'VALUES'        => [
                    'TEXT' => json_encode([
                        ['value' => '15m','label' => '15 минут'],
                        ['value' => '30m','label' => '30 минут'],
                        ['value' => '1h', 'label' => '1 час'],
                    ]),
                    'TYPE' => 'html',
                ],
                'HELP_TEXT'     => 'Время неактивности перед выходом из системы.',
                'HELP_IMAGE'    => '',
                'SORT'          => 200,
            ],

            // Интеграции — API — Ключи доступа
            [
                'NAME'          => 'Включить API-доступ',
                'CODE'          => 'ENABLE_API',
                'SECTION_CODE'  => 'integration_api_keys',
                'TYPE'          => 'checkbox',
                'HELP_TEXT'     => 'Позволяет внешним сервисам обращаться к вашему сайту через API.',
                'HELP_IMAGE'    => '',
                'SORT'          => 100,
            ],
            // Интеграции — API — Ограничения
            [
                'NAME'          => 'Лимит запросов',
                'CODE'          => 'API_RATE_LIMIT',
                'SECTION_CODE'  => 'integration_api_limits',
                'TYPE'          => 'select',
                'VALUES'        => [
                    'TEXT' => json_encode([
                        ['value' => '100', 'label' => '100 запросов/мин'],
                        ['value' => '500', 'label' => '500 запросов/мин'],
                        ['value' => '1000', 'label' => '1000 запросов/мин'],
                    ]),
                    'TYPE' => 'html',
                ],
                'HELP_TEXT'     => 'Ограничение количества запросов к API.',
                'HELP_IMAGE'    => '',
                'SORT'          => 150,
            ],
            // Интеграции — Сервисы — Социальные сети
            [
                'NAME'          => 'Интеграция с соцсетями',
                'CODE'          => 'SOCIAL_INTEGRATION',
                'SECTION_CODE'  => 'integration_services_social',
                'TYPE'          => 'checkbox',
                'HELP_TEXT'     => 'Включает интеграцию с социальными сетями.',
                'HELP_IMAGE'    => '',
                'SORT'          => 160,
            ],
            // Интеграции — Сервисы — Платежные системы
            [
                'NAME'          => 'Режим API',
                'CODE'          => 'API_MODE',
                'SECTION_CODE'  => 'integration_services_payment',
                'TYPE'          => 'radio',
                'VALUES'        => [
                    'TEXT' => json_encode([
                        ['value' => 'read', 'label' => 'Только чтение'],
                        ['value' => 'write','label' => 'Чтение и запись'],
                    ]),
                    'TYPE' => 'html',
                ],
                'HELP_TEXT'     => 'Определяет права операций в API.',
                'HELP_IMAGE'    => '',
                'SORT'          => 200,
            ],
        ];

        $el = new \CIBlockElement();
        foreach ($settings as $setting) {
            $sectionId = $subsectionIds[$setting['SECTION_CODE']] ?? 0;
            $elementFields = [
                'IBLOCK_ID'         => $iblockId,
                'NAME'              => $setting['NAME'],
                'CODE'              => $setting['CODE'],
                'ACTIVE'            => 'Y',
                'SORT'              => $setting['SORT'],
                'IBLOCK_SECTION_ID' => $sectionId,
            ];

            $elementId = $el->Add($elementFields);
            if (!$elementId) {
                $APPLICATION->ThrowException($el->LAST_ERROR);
                continue;
            }

            $propValues = [
                'HELP_TEXT' => $setting['HELP_TEXT'],
            ];

            // Обрабатываем VALUES + TYPE
            if (isset($setting['VALUES'])) {
                $propValues['VALUES'] = [
                    'TEXT' => $setting['VALUES']['TEXT'],
                    'TYPE' => $setting['TYPE'],
                ];
            } else {
                $propValues['VALUES'] = [
                    'TEXT' => '',
                    'TYPE' => $setting['TYPE'],
                ];
            }

            // HELP_IMAGE
            if (!empty($setting['HELP_IMAGE'])) {
                $filePath = $_SERVER['DOCUMENT_ROOT'] . $setting['HELP_IMAGE'];
                if (file_exists($filePath)) {
                    $propValues['HELP_IMAGE'] = \CFile::MakeFileArray($filePath);
                }
            }

            \CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, $propValues);
        }
    }

    return true;
}

/**
 * Удаляет инфоблок при удалении модуля
 *
 * @return bool
 */
function UnInstallDB(): bool
{
    global $DB, $APPLICATION;

    if (!Loader::includeModule('iblock')) {
        return false;
    }

    $iblockCode = 'site_settings';
    $iblockType = 'site_settings';

    $dbIblock = \CIBlock::GetList(
        [],
        [
            'CODE'              => $iblockCode,
            'TYPE'              => $iblockType,
            'CHECK_PERMISSIONS' => 'N',
        ]
    );

    if ($arIblock = $dbIblock->Fetch()) {
        $entity = new \CUserTypeEntity();

        // Удаляем пользовательские поля разделов
        $fieldsToDelete = ['UF_ENABLE_DRAG_AND_DROP', 'UF_DETAIL_PROPERTY'];
        foreach ($fieldsToDelete as $fieldName) {
            $rsData = $entity->GetList([], [
                'ENTITY_ID' => 'IBLOCK_' . $arIblock['ID'] . '_SECTION',
                'FIELD_NAME' => $fieldName
            ]);
            if ($arField = $rsData->Fetch()) {
                $entity->Delete($arField['ID']);
            }
        }

        // Удаляем пользовательские поля элементов
        $rsData = $entity->GetList([], [
            'ENTITY_ID' => 'IBLOCK_' . $arIblock['ID'] . '_ELEMENT',
            'FIELD_NAME' => 'UF_DETAIL_PROPERTY'
        ]);
        if ($arField = $rsData->Fetch()) {
            $entity->Delete($arField['ID']);
        }

        \CIBlock::Delete($arIblock['ID']);
    }

    // Если инфоблоков данного типа больше нет — удаляем тип
    $dbIblocks = \CIBlock::GetList(
        [],
        [
            'TYPE'              => $iblockType,
            'CHECK_PERMISSIONS' => 'N',
        ]
    );
    if (!$dbIblocks->Fetch()) {
        \CIBlockType::Delete($iblockType);
    }

    return true;
}
