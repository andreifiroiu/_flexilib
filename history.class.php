<?php

/** security check */
defined("_NETI_APP_") or die("Direct access is deniend");

/**
 * History Class, FlexiStore Framework
 * 
 * Navigation history support
 *
 * @author Andrei Firoiu <andrei.firoiu@neti.ro>
 * @link http://netinteraction.biz/dev/_flexilib
 * @version 1.0
 * @date 18.10.2008
 * @update 09.11.2008
 */
class History 
{
	static $stackSize = 10;
	static $siteLabel = "history";
	static $adminLabel = "admin_history";
	
	
	static function push()
	{
		if (!isset($_SESSION[_APP_ID_][self::getHistoryKey()]))
			$_SESSION[_APP_ID_][self::getHistoryKey()] = array();
		
		if (count($_SESSION[_APP_ID_][self::getHistoryKey()]) > 0)
			$lastHistory = $_SESSION[_APP_ID_][self::getHistoryKey()][count($_SESSION[_APP_ID_][self::getHistoryKey()]) - 1];
		
		if (!isset($lastHistory) || $lastHistory != $_SERVER["REQUEST_URI"])
		{
			$cnt = array_push($_SESSION[_APP_ID_][self::getHistoryKey()], $_SERVER["REQUEST_URI"]);
			
			if ($cnt > self::$stackSize)
				array_shift($_SESSION[_APP_ID_][self::getHistoryKey()]);
		}
		
		return true;
	}
	
	
	static function replace($position)
	{
		if (!isset($_SESSION[_APP_ID_][self::getHistoryKey()]))
			return false;
		
		$_SESSION[_APP_ID_][self::getHistoryKey()][$position] = $_SERVER["REQUEST_URI"];
		
		return true;
	}
	
	
	static function pop($returnUrl = false)
	{
		$url = array_pop($_SESSION[_APP_ID_][self::getHistoryKey()]);
		$url = ($url == NULL ? "index.php" : $url);
		
		if (!$returnUrl)
		{
			echo "<script type='text/javascript'>window.location='$url';</script>";
			exit;
		}
		
		return $url;
	}
	
	
	static function jumpto($url)
	{
		echo "<script type='text/javascript'>window.location='". (strstr($url, "http://") === FALSE && strstr($url, "https://") === FALSE ? "/". Settings::get("app.document_root") : ""). "$url';</script>";
		exit;
	}
	
	
	static function jump($position, $returnUrl = false)
	{
		if (!isset($_SESSION[_APP_ID_][self::getHistoryKey()]) || count($_SESSION[_APP_ID_][self::getHistoryKey()]) < 2)
			return null;
		
		if ($position < 0)
			$position += count($_SESSION[_APP_ID_][self::getHistoryKey()]);
		
		$url = (isset($_SESSION[_APP_ID_][self::getHistoryKey()][$position]) ? $_SESSION[_APP_ID_][self::getHistoryKey()][$position] : "index.php");
		$url = ($url == NULL ? "index.php" : $url);
		
		if (!$returnUrl)
		{
			array_splice($_SESSION[_APP_ID_][self::getHistoryKey()], $position + 1);
			
			echo "<script type='text/javascript'>window.location='$url';</script>";
			exit;
		}
		
		return $url;
	}
	
	
	static function getHistoryKey()
	{
		return (defined("_APP_ADMIN_") ? self::$adminLabel : self::$siteLabel);
	}
}


?>