<?php

/**
 * Файл для создания и удаления структуры данных модуля при установке/деинсталляции.
 *
 * @package qwelp.site_settings
 * @version 1.2.1
 */

declare(strict_types=1);

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Iblock\TypeTable;
use Bitrix\Main\Application;

Loc::loadMessages(__FILE__);

if (!Loader::includeModule('iblock')) {
    throw new \RuntimeException(Loc::getMessage('QWELP_SITE_SETTINGS_IBLOCK_MODULE_NOT_INSTALLED'));
}

/**
 * Транслитерирует строку с заданными параметрами.
 *
 * @param string $string Строка для транслитерации.
 * @return string Транслитерированная строка.
 */
function qwelpSettingsTransliterateString(string $string): string
{
    if (empty($string)) {
        return '';
    }

    return (string)\CUtil::translit(
        $string,
        'ru',
        [
            'max_len' => 100,
            'change_case' => 'L',
            'replace_space' => '-',
            'replace_other' => '-',
            'remove_duplicated_chars' => true,
        ]
    );
}

/**
 * Рекурсивно создает разделы, их элементы и подразделы.
 * Гарантирует сохранение иерархии из demodata.
 *
 * @param array<int, array<string, mixed>> $items Массив разделов для создания на текущем уровне.
 * @param int $iblockId ID инфоблока, в котором создаются сущности.
 * @param int $parentSectionId ID родительского раздела (0 для корневого уровня).
 * @return void
 * @throws \Exception Если не удалось создать раздел или элемент.
 */
function createSectionsAndElements(array $items, int $iblockId, int $parentSectionId = 0): void
{
    $bs = new \CIBlockSection();
    $el = new \CIBlockElement();

    foreach ($items as $sectionData) {
        $sectionCode = trim((string)($sectionData['CODE'] ?? ''));
        if ($sectionCode === '') {
            $sectionCode = qwelpSettingsTransliterateString($sectionData['NAME']);
        }

        $sectionFields = [
            'ACTIVE' => 'Y',
            'IBLOCK_ID' => $iblockId,
            'NAME' => $sectionData['NAME'],
            'CODE' => $sectionCode,
            'SORT' => $sectionData['SORT'] ?? 500,
            'IBLOCK_SECTION_ID' => $parentSectionId,
        ];

        // Добавляем пользовательские поля из UF_FIELDS, если они есть
        if (!empty($sectionData['UF_FIELDS']) && is_array($sectionData['UF_FIELDS'])) {
            $sectionFields = array_merge($sectionFields, $sectionData['UF_FIELDS']);
        }

        $newSectionId = (int)$bs->Add($sectionFields);
        if ($newSectionId <= 0) {
            throw new \Exception(Loc::getMessage('QWELP_SITE_SETTINGS_SECTION_ADD_ERROR') . ' ' . $bs->LAST_ERROR);
        }

        // Создаем элементы в только что созданном разделе
        if (!empty($sectionData['ELEMENTS']) && is_array($sectionData['ELEMENTS'])) {
            foreach ($sectionData['ELEMENTS'] as $elementData) {
                $elementFields = [
                    'IBLOCK_ID' => $iblockId,
                    'NAME' => $elementData['NAME'],
                    'CODE' => $elementData['CODE'],
                    'ACTIVE' => 'Y',
                    'SORT' => $elementData['SORT'] ?? 500,
                    'IBLOCK_SECTION_ID' => $newSectionId,
                ];

                $elementId = (int)$el->Add($elementFields, false, true, true);
                if ($elementId <= 0) {
                    throw new \Exception(Loc::getMessage('QWELP_SITE_SETTINGS_ELEMENT_ADD_ERROR') . ' ' . $el->LAST_ERROR);
                }

                if (!empty($elementData['PROPERTIES'])) {
                    \CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, $elementData['PROPERTIES']);
                }
            }
        }

        // Рекурсивный вызов для дочерних разделов
        if (!empty($sectionData['SUBSECTIONS']) && is_array($sectionData['SUBSECTIONS'])) {
            createSectionsAndElements($sectionData['SUBSECTIONS'], $iblockId, $newSectionId);
        }
    }
}

/**
 * Создает тип инфоблока, инфоблок, свойства и наполняет их данными.
 *
 * @return bool
 */
function InstallDB(): bool
{
    global $APPLICATION;

    $iblockType = 'site_settings';
    $typeResult = TypeTable::getList(['filter' => ['=ID' => $iblockType]]);

    if (!$typeResult->fetch()) {
        $typeFields = [
            'ID' => $iblockType,
            'SECTIONS' => 'Y',
            'IN_RSS' => 'N',
            'SORT' => 500,
            'LANG' => [
                'ru' => ['NAME' => 'Настройки сайта', 'SECTION_NAME' => 'Разделы', 'ELEMENT_NAME' => 'Настройки'],
                'en' => ['NAME' => 'Site Settings', 'SECTION_NAME' => 'Sections', 'ELEMENT_NAME' => 'Settings'],
            ],
        ];
        $obIblockType = new \CIBlockType();
        if (!$obIblockType->Add($typeFields)) {
            $APPLICATION->ThrowException($obIblockType->LAST_ERROR);
            return false;
        }
    }

    $iblockCode = 'site_settings';
    $iblockXmlId = 'site_settings';

    $dbIblock = \CIBlock::GetList([], ['CODE' => $iblockCode, 'TYPE' => $iblockType, 'CHECK_PERMISSIONS' => 'N']);

    if (!$dbIblock->Fetch()) {
        $sites = [];
        $rsSites = \CSite::GetList('sort', 'asc');
        while ($arSite = $rsSites->Fetch()) {
            $sites[] = $arSite['LID'];
        }

        $iblockFieldsMain = [
            'ACTIVE' => 'Y',
            'NAME' => 'Настройки сайта',
            'CODE' => $iblockCode,
            'XML_ID' => $iblockXmlId,
            'IBLOCK_TYPE_ID' => $iblockType,
            'SITE_ID' => $sites,
            'SORT' => 100,
            'GROUP_ID' => ['2' => 'R'],
            'VERSION' => 2,
            'INDEX_ELEMENT' => 'N',
            'INDEX_SECTION' => 'N',
        ];

        $iblock = new \CIBlock();
        $iblockId = (int)$iblock->Add($iblockFieldsMain);
        if ($iblockId <= 0) {
            $APPLICATION->ThrowException($iblock->LAST_ERROR);
            return false;
        }

        $iblockFieldsSettings = \CIBlock::GetFields($iblockId);
        $translitSettings = [
            "UNIQUE" => "Y",
            "TRANSLITERATION" => "Y",
            "TRANS_LEN" => 100,
            "TRANS_CASE" => "L",
            "TRANS_SPACE" => "-",
            "TRANS_OTHER" => "-",
            "TRANS_EAT" => "Y",
            "USE_GOOGLE" => "N",
        ];
        $iblockFieldsSettings["SECTION_CODE"]["IS_REQUIRED"] = "Y";
        $iblockFieldsSettings["SECTION_CODE"]["DEFAULT_VALUE"] = $translitSettings;
        $iblockFieldsSettings["CODE"]["IS_REQUIRED"] = "Y";
        $iblockFieldsSettings["CODE"]["DEFAULT_VALUE"] = $translitSettings;
        \CIBlock::SetFields($iblockId, $iblockFieldsSettings);

        // === Создание пользовательских полей для разделов ===
        $oUserTypeEntity = new \CUserTypeEntity();
        $ufFields = [
            [
                'FIELD_NAME' => 'UF_ENABLE_DRAG_AND_DROP',
                'XML_ID' => 'UF_ENABLE_DRAG_AND_DROP',
                'SORT' => 500,
                'HELP_MESSAGE' => ['ru' => 'Активирует функционал drag and drop для раздела.', 'en' => 'Activates drag and drop functionality.'],
                'EDIT_FORM_LABEL' => ['ru' => 'Включить drag and drop', 'en' => 'Enable drag and drop'],
            ],
            [
                'FIELD_NAME' => 'UF_DETAIL_PROPERTY',
                'XML_ID' => 'UF_DETAIL_PROPERTY',
                'SORT' => 510,
                'HELP_MESSAGE' => ['ru' => 'Дополнительная информация для раздела.', 'en' => 'Additional information for the section.'],
                'EDIT_FORM_LABEL' => ['ru' => 'Детальное свойство', 'en' => 'Detail Property'],
            ],
            [
                'FIELD_NAME' => 'UF_COMMON_PROPERTY',
                'XML_ID' => 'UF_COMMON_PROPERTY',
                'SORT' => 520,
                'HELP_MESSAGE' => ['ru' => 'Единое свойство для всех разделов.', 'en' => 'A common property for all sections.'],
                'EDIT_FORM_LABEL' => ['ru' => 'Единое свойство', 'en' => 'Common Property'],
            ],
            [
                'FIELD_NAME' => 'UF_COLLAPSED_BLOCK',
                'XML_ID' => 'UF_COLLAPSED_BLOCK',
                'SORT' => 530,
                'HELP_MESSAGE' => ['ru' => 'Позволяет скрывать содержимое раздела по умолчанию.', 'en' => 'Allows to hide the section content by default.'],
                'EDIT_FORM_LABEL' => ['ru' => 'Свернутый блок', 'en' => 'Collapsed Block'],
            ],
            [
                'FIELD_NAME' => 'UF_HIDDEN_CHECKBOX',
                'XML_ID' => 'UF_HIDDEN_CHECKBOX',
                'SORT' => 535,
                'HELP_MESSAGE' => ['ru' => 'Скрытое свойство с чекбоксом.', 'en' => 'Hidden property with a checkbox.'],
                'EDIT_FORM_LABEL' => ['ru' => 'Скрытый чекбокс', 'en' => 'Hidden Checkbox'],
            ]
        ];
        foreach ($ufFields as $uf) {
            $ufData = [
                'ENTITY_ID' => 'IBLOCK_' . $iblockId . '_SECTION',
                'USER_TYPE_ID' => 'boolean',
                'MULTIPLE' => 'N',
                'MANDATORY' => 'N',
                'SHOW_FILTER' => 'N',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'N',
                'SETTINGS' => ['DEFAULT_VALUE' => 0],
                'LIST_COLUMN_LABEL' => $uf['EDIT_FORM_LABEL'],
                'LIST_FILTER_LABEL' => $uf['EDIT_FORM_LABEL'],
            ];
            if (!$oUserTypeEntity->Add(array_merge($uf, $ufData))) {
                if ($ex = $APPLICATION->GetException()) {
                    $APPLICATION->ThrowException(Loc::getMessage('QWELP_SITE_SETTINGS_UF_ADD_ERROR') . ' ' . $ex->GetString());
                }
                return false;
            }
        }

        // Строковые пользовательские поля
        $stringUfFields = [
            [
                'FIELD_NAME' => 'UF_HIDDEN_ELEMENTS_TITLE',
                'XML_ID' => 'UF_HIDDEN_ELEMENTS_TITLE',
                'SORT' => 540,
                'HELP_MESSAGE' => ['ru' => 'Заголовок для группы скрытых элементов.', 'en' => 'Title for hidden elements group.'],
                'EDIT_FORM_LABEL' => ['ru' => 'Название для спрятанных элементов', 'en' => 'Title for hidden elements'],
            ],
            [
                'FIELD_NAME' => 'UF_SECTION_TOOLTIP',
                'XML_ID' => 'UF_SECTION_TOOLTIP',
                'SORT' => 550,
                'HELP_MESSAGE' => ['ru' => 'Текст подсказки, отображаемый рядом с заголовком раздела.', 'en' => 'Tooltip text displayed next to the section title.'],
                'EDIT_FORM_LABEL' => ['ru' => 'Подсказка раздела', 'en' => 'Section Tooltip'],
            ]
        ];
        foreach ($stringUfFields as $uf) {
            $ufData = [
                'ENTITY_ID' => 'IBLOCK_' . $iblockId . '_SECTION',
                'USER_TYPE_ID' => 'string',
                'MULTIPLE' => 'N',
                'MANDATORY' => 'N',
                'SHOW_FILTER' => 'N',
                'SHOW_IN_LIST' => 'Y',
                'EDIT_IN_LIST' => 'Y',
                'IS_SEARCHABLE' => 'N',
                'SETTINGS' => ['DEFAULT_VALUE' => '', 'SIZE' => 50, 'ROWS' => 1],
                'LIST_COLUMN_LABEL' => $uf['EDIT_FORM_LABEL'],
                'LIST_FILTER_LABEL' => $uf['EDIT_FORM_LABEL'],
            ];
            if (!$oUserTypeEntity->Add(array_merge($uf, $ufData))) {
                if ($ex = $APPLICATION->GetException()) {
                    $APPLICATION->ThrowException(Loc::getMessage('QWELP_SITE_SETTINGS_UF_ADD_ERROR') . ' ' . $ex->GetString());
                }
                return false;
            }
        }

        // Добавляем кастомное поле HtmlBlockType для разделов
        $htmlBlockField = [
            'ENTITY_ID'         => 'IBLOCK_' . $iblockId . '_SECTION',
            'FIELD_NAME'        => 'UF_HTML_BLOCK',
            'USER_TYPE_ID'      => 'qwelp_html_block', // ID нашего кастомного типа
            'XML_ID'            => 'UF_HTML_BLOCK',
            'SORT'              => 600,
            'MULTIPLE'          => 'N', // [FIXED] Поле должно быть одиночным
            'MANDATORY'         => 'N',
            'SHOW_FILTER'       => 'N',
            'SHOW_IN_LIST'      => 'Y',
            'EDIT_IN_LIST'      => 'Y',
            'IS_SEARCHABLE'     => 'N',
            'SETTINGS'          => ['height' => 300],
            'EDIT_FORM_LABEL'   => [
                'ru' => 'HTML блок',
                'en' => 'HTML block',
            ],
            'LIST_COLUMN_LABEL' => [ // [FIXED] Обновлена метка на единственное число
                'ru' => 'HTML блок',
                'en' => 'HTML block',
            ],
            'LIST_FILTER_LABEL' => [
                'ru' => 'HTML блок',
                'en' => 'HTML block',
            ],
            'HELP_MESSAGE'      => [
                'ru' => 'Позволяет добавить произвольный HTML блок с заголовком в раздел.',
                'en' => 'Allows adding a custom HTML block with a title to the section.',
            ],
        ];

        if (!$oUserTypeEntity->Add($htmlBlockField)) {
            if ($ex = $APPLICATION->GetException()) {
                $APPLICATION->ThrowException(Loc::getMessage('QWELP_SITE_SETTINGS_UF_ADD_ERROR') . ' ' . $ex->GetString());
            }
            return false;
        }


        // === Свойства инфоблока ===
        $ibp = new \CIBlockProperty();
        $enum = new \CIBlockPropertyEnum();

        $properties = [
            ['NAME' => 'Варианты значений', 'CODE' => 'VALUES', 'PROPERTY_TYPE' => 'S', 'USER_TYPE' => 'QwelpSettingsValues', 'HINT' => 'JSON-массив вариантов'],
            ['NAME' => 'Показать заголовок', 'CODE' => 'SHOW_TITLE', 'PROPERTY_TYPE' => 'L', 'LIST_TYPE' => 'C', 'HINT' => 'Отображать заголовок на странице'],
            ['NAME' => 'Отображать в заголовке для свернутого блока', 'CODE' => 'HEADER_TITLE', 'PROPERTY_TYPE' => 'L', 'LIST_TYPE' => 'C', 'HINT' => 'Показывать в общем заголовке свернутого блока'],
            ['NAME' => 'Скрытое свойство с открытием checkbox', 'CODE' => 'HIDDEN_CHECKBOX', 'PROPERTY_TYPE' => 'L', 'LIST_TYPE' => 'C', 'HINT' => 'Скрытое свойство'],
            ['NAME' => 'Детальное свойство', 'CODE' => 'DETAIL_PROPERTY', 'PROPERTY_TYPE' => 'L', 'LIST_TYPE' => 'C', 'HINT' => 'Отображать детальное свойство на странице'],
            ['NAME' => 'Текст подсказки', 'CODE' => 'HELP_TEXT', 'PROPERTY_TYPE' => 'S', 'HINT' => 'Текст, показываемый при клике на ?'],
            ['NAME' => 'Изображение подсказки', 'CODE' => 'HELP_IMAGE', 'PROPERTY_TYPE' => 'F', 'HINT' => 'Изображение, показываемое в подсказке'],
            ['NAME' => 'Процент', 'CODE' => 'PERCENT', 'PROPERTY_TYPE' => 'S', 'HINT' => 'Введите значение в процентах'],
            // [NEW] Добавляем экземпляр нашего нового свойства "Ключ-Значение"
            [
                'NAME' => 'Технические данные',
                'CODE' => 'TECH_DATA',
                'PROPERTY_TYPE' => 'S',
                'USER_TYPE' => \Qwelp\SiteSettings\Property\KeyValuePropertyType::USER_TYPE,
                'MULTIPLE' => 'N',
                'IS_REQUIRED' => 'N',
                'HINT' => 'Сериализованный массив пар ключ-значение'
            ],
        ];

        foreach ($properties as $prop) {
            $prop['IBLOCK_ID'] = $iblockId;
            $propId = $ibp->Add($prop);
            if (!$propId) {
                $APPLICATION->ThrowException($ibp->LAST_ERROR);
                return false;
            }
            if ($prop['PROPERTY_TYPE'] === 'L') {
                $enum->Add(['PROPERTY_ID' => $propId, 'VALUE' => 'Да', 'DEF' => ($prop['CODE'] === 'SHOW_TITLE' ? 'Y' : 'N'), 'SORT' => 100]);
            }
        }

        // === Создание структуры и наполнение данными ===
        try {
            $demodata = include(__DIR__ . '/demodata.php');
            createSectionsAndElements($demodata, $iblockId, 0);
        } catch (\Exception $e) {
            $APPLICATION->ThrowException($e->getMessage());
            return false;
        }
    }

    return true;
}

/**
 * Удаляет инфоблок и все связанные сущности при удалении модуля.
 *
 * @return bool
 */
function UnInstallDB(): bool
{
    global $APPLICATION;

    $iblockCode = 'site_settings';
    $iblockType = 'site_settings';

    $dbIblock = \CIBlock::GetList([], ['CODE' => $iblockCode, 'TYPE' => $iblockType, 'CHECK_PERMISSIONS' => 'N']);

    if ($arIblock = $dbIblock->Fetch()) {
        $entity = new \CUserTypeEntity();
        $fieldsToDelete = [
            'UF_ENABLE_DRAG_AND_DROP',
            'UF_DETAIL_PROPERTY',
            'UF_COMMON_PROPERTY',
            'UF_COLLAPSED_BLOCK',
            'UF_HIDDEN_CHECKBOX',
            'UF_HIDDEN_ELEMENTS_TITLE',
            'UF_SECTION_TOOLTIP',
            'UF_HTML_BLOCK',
        ];

        foreach ($fieldsToDelete as $fieldName) {
            $rsData = $entity->GetList([], [
                'ENTITY_ID' => 'IBLOCK_' . $arIblock['ID'] . '_SECTION',
                'FIELD_NAME' => $fieldName
            ]);
            if ($arField = $rsData->Fetch()) {
                $entity->Delete($arField['ID']);
            }
        }

        if (!\CIBlock::Delete($arIblock['ID'])) {
            $APPLICATION->ThrowException(Loc::getMessage('QWELP_SITE_SETTINGS_IBLOCK_DELETE_ERROR'));
            return false;
        }
    }

    $dbIblocks = \CIBlock::GetList([], ['TYPE' => $iblockType, 'CHECK_PERMISSIONS' => 'N']);
    if (!$dbIblocks->Fetch()) {
        \CIBlockType::Delete($iblockType);
    }

    return true;
}