<?php

namespace Qwelp\SiteSettings;

use Bitrix\Main\Data\Cache;
use Bitrix\Main\Application;

class SiteSettingsHelper
{
	private static $cache = null;
	private static $settings = null;
	private static $fullStructure = null;

	/**
	 * Получает закешированные настройки сайта
	 */
	public static function getAllCached(int $cacheTime = 3600): array
	{
		if (self::$settings !== null) {
			return self::$settings;
		}

		if (!self::$cache) {
			self::$cache = Cache::createInstance();
		}

		$cacheId = "site_settings_all_" . SITE_ID;
		$cacheDir = "/qwelp_site_settings/";

		if (self::$cache->initCache($cacheTime, $cacheId, $cacheDir)) {
			self::$settings = self::$cache->getVars();
		} elseif (self::$cache->startDataCache()) {
			self::$settings = OptionsManager::getAll();
			self::$cache->endDataCache(self::$settings);
		}

		return self::$settings ?? [];
	}

	/**
	 * Получает полную структуру настроек с разделами
	 */
	public static function getFullStructure(int $cacheTime = 3600): array
	{
		if (self::$fullStructure !== null) {
			return self::$fullStructure;
		}

		if (!self::$cache) {
			self::$cache = Cache::createInstance();
		}

		$cacheId = "site_settings_structure_" . SITE_ID;
		$cacheDir = "/qwelp_site_settings/";

		if (self::$cache->initCache($cacheTime, $cacheId, $cacheDir)) {
			self::$fullStructure = self::$cache->getVars();
		} elseif (self::$cache->startDataCache()) {
			$settingsStructure = SettingsManager::getSettings();
			$savedOptions = OptionsManager::getAll();

			if (!empty($settingsStructure['sections']) && !empty($savedOptions)) {
				self::mergeSettingsWithOptions($settingsStructure['sections'], $savedOptions);
			}

			self::$fullStructure = $settingsStructure;
			self::$cache->endDataCache(self::$fullStructure);
		}

		return self::$fullStructure ?? [];
	}

	/**
	 * Находит раздел по его коду в структуре
	 */
	public static function findSectionByCode(string $sectionCode, array $sections = null): ?array
	{
		if ($sections === null) {
			$structure = self::getFullStructure();
			$sections = $structure['sections'] ?? [];
		}

		foreach ($sections as $section) {
			if ($section['id'] === $sectionCode) {
				return $section;
			}

			if (!empty($section['SUBSECTIONS'])) {
				$found = self::findSectionByCode($sectionCode, $section['SUBSECTIONS']);
				if ($found) {
					return $found;
				}
			}
		}

		return null;
	}

	/**
	 * Преобразует код подраздела в имя шаблона
	 */
	private static function getTemplateNameFromCode(string $code): string
	{
		return str_replace('-', '_', $code);
	}

	/**
	 * Получает настройки для конкретного компонента с данными только активного подраздела
	 */
	public static function getComponentSettings(string $sectionCode, string $defaultTemplate = '.default', int $cacheTime = 3600): ?array
	{
		$section = self::findSectionByCode($sectionCode);

		if (!$section) {
			return null;
		}

		$activeSubSection = null;
		$template = $defaultTemplate;
		$activeSubSectionCode = null;

		if (!empty($section['SUBSECTIONS'])) {
			$savedOptions = self::getAllCached($cacheTime);
			$savedActiveTemplate = $savedOptions[$sectionCode] ?? null;

			if ($savedActiveTemplate) {
				foreach ($section['SUBSECTIONS'] as $subSection) {
					if ($subSection['id'] === $savedActiveTemplate) {
						$activeSubSection = $subSection;
						$activeSubSectionCode = $savedActiveTemplate;
						$template = self::getTemplateNameFromCode($savedActiveTemplate);
						break;
					}
				}
			}

			if (!$activeSubSection && !empty($section['SUBSECTIONS'])) {
				$activeSubSection = reset($section['SUBSECTIONS']);
				if ($activeSubSection) {
					$activeSubSectionCode = $activeSubSection['id'];
					$template = self::getTemplateNameFromCode($activeSubSection['id']);
				}
			}
		}

		return [
			'template' => $template,
			'active_subsection' => $activeSubSection,
			'section_code' => $sectionCode,
			'active_subsection_code' => $activeSubSectionCode
		];
	}

	/**
	 * Получает конкретную настройку из активного подраздела
	 */
	public static function getSubsectionSetting(string $sectionCode, string $settingCode, $defaultValue = null): mixed
	{
		$componentData = self::getComponentSettings($sectionCode);

		if (!$componentData || empty($componentData['active_subsection']['settings'])) {
			return $defaultValue;
		}

		foreach ($componentData['active_subsection']['settings'] as $setting) {
			if ($setting['code'] === $settingCode) {
				return $setting['value'] ?? $defaultValue;
			}
		}

		return $defaultValue;
	}

	/**
	 * Объединяет настройки с сохраненными значениями
	 */
	private static function mergeSettingsWithOptions(array &$sections, array $options): void
	{
		foreach ($sections as &$section) {
			if (!empty($section['settings'])) {
				foreach ($section['settings'] as &$setting) {
					if (isset($options[$setting['code']])) {
						$setting['value'] = $options[$setting['code']];
					}
				}
				unset($setting);
			}

			if (!empty($section['SUBSECTIONS'])) {
				self::mergeSettingsWithOptions($section['SUBSECTIONS'], $options);
			}
		}
		unset($section);
	}

	/**
	 * Очистить кеш настроек
	 */
	public static function clearCache(): void
	{
		$cache = Cache::createInstance();
		$cache->clean("site_settings_all_" . SITE_ID, "/qwelp_site_settings/");
		$cache->clean("site_settings_structure_" . SITE_ID, "/qwelp_site_settings/");
		self::$settings = null;
		self::$fullStructure = null;
	}
}