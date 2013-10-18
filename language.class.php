<?php

/** security check */
defined("_NETI_APP_") or die("Direct access is deniend");

// deprecated
class Language
{
	protected static $languages = null;
	protected static $sessionKey = null;
	
	
	static function init()
	{
		if (!isset($_SESSION[_APP_ID_]["language"]))
			$_SESSION[_APP_ID_]["language"] = Settings::get("system.default_language");
	}
	
	
	static function getLanguages()
	{
		if (Language::$languages == null)
		{
			// @TODO to implement language detection
		}
		
		return Language::$languages;
	}
	
	
	static function getCurrentLanguage()
	{
		if (!isset($_SESSION[_APP_ID_]["language"]))
			self::setCurrentLanguage(Settings::get("system.default_language"));
		
		return $_SESSION[_APP_ID_]["language"];
	}
	
	
	static function setCurrentLanguage($language)
	{
		$_SESSION[_APP_ID_]["language"] = $language;
		self::$sessionKey = "language_". $language;
		//self::setCurrentCurrency(Constants::get("language_currency.". $language));
		//self::load();
	}
	
	
	static function displayLanguages()
	{
		$languages = SQL::getSelfInstance()->get("select * from languages");
		
		$init = true;
		foreach ($languages as $lang)
			if ($lang["code1"] == Language::getCurrentLanguage())
			{
				$lang0 = $lang;
				break;
			}
		
		Util::getTemplate("elements.language", $lang0, true);
		
		foreach ($languages as $lang)
		{
			if ($lang["code"] == $lang0["code"])
				continue;
			
			Util::getTemplate("elements.language", $lang, true);
		}
	}
	
	
	// sintaxa autosave:
	//getMessage("category.label", array("ro" => "Mesaj in ro", "en" => "Mesaj in en"));
	static function get()
	{
		global $LANGUAGE;
		
		if (func_num_args() == 0)
			return (Settings::get("log.debug_mode") ? "<em>(no_message)</em>" : "");
		
		$message = (isset($LANGUAGE["messages"][func_get_arg(0)]) ? $LANGUAGE["messages"][func_get_arg(0)] : func_get_arg(0));
		
		if (func_num_args() > 1)
		{
			for ($i = 1; $i < func_num_args(); $i++)
			{
				$value = func_get_arg($i);
				
				$message = str_replace("%$i", $value, $message);
			}
		}
		
		return $message;
	}
	
	
	static function getSpecial()
	{
		global $LANGUAGE;
		
		$list = func_get_arg(0);
		$label = func_get_arg(1);
		
		$message = (isset($LANGUAGE[$list][$label]) ? $LANGUAGE[$list][$label] : $label);
		
		if (func_num_args() > 2)
		{
			for ($i = 2; $i < func_num_args(); $i++)
			{
				$value = func_get_arg($i);
				
				$message = str_replace("%$i", $value, $message);
			}
		}
		
		return $message;
	}
	
	
	static function getList($list)
	{
		global $LANGUAGE;
		
		return (isset($LANGUAGE[$list]) ? $LANGUAGE[$list] : array());
	}
	
	
	static function getWeekDay($day)
	{
		return self::get("date.week_days_$day");
	}
	
	
	static function getMonth($month)
	{
		return self::get("date.months_$month");
	}
	
	
	static function save()
	{
		foreach ($_SESSION[_APP_ID_][Language::$sessionKey] as $category => $values)
		{
			if ($category == "db")
				continue;
			
			foreach ($values as $name => $value)
				SQL::getSelfInstance()->save(
						Language::$sqlTable, 
						array(Language::$categoryField => $category, Language::$nameField => $name, Language::$valueField => $value, "language_code" => Language::getCurrentLanguage()), 
						array(Language::$categoryField => $category, Language::$nameField => $name, "language_code" => Language::getCurrentLanguage()));
		}
	}
	
	
	
	static function set($propertyName, $propertyValue, $isPermanent = false)
	{
		
	}
	
}

?>