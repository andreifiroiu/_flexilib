<?php

/** security check */
defined("_NETI_APP_") or die("Direct access is deniend");


/**
 * Cache Class, _FlexiLib Framework
 * 
 * Lightweight cache support
 *
 * @author Andrei Firoiu <andrei.firoiu@neti.ro>
 * @link http://netinteraction.biz/dev/_flexilib
 * @version 1.0
 * @date 03.01.2010
 */
class Cache
{
	public static $sqlTable = "cache";
	
	
	static function getCache($key)
	{
		//return null; // debug mode
		
		$cache = SQL::getSelf()->getLine("select * from ". self::$sqlTable. " where cache_key='$key'", false);
		
		if ($cache == null)
			return null;
		
		if ($cache["validity"] != 0 && strtotime($cache["expires"]) < time())
		{
			SQL::getSelf()->query("delete from ". self::$sqlTable. " where cache_key='$key'");
			@unlink("cache/$key.dat");
			
			return null;
		}
		
		$prepath = "";
		
		if (!file_exists("cache/"))
			$prepath = "../";
		
		if (!file_exists($prepath. "cache/$key.dat"))
			return null;
		
		//return unserialize($cache["cache_data"]);
		return unserialize(file_get_contents($prepath. "cache/$key.dat"));
	}
	
	
	static function setCache($key, $data, $validity = null)
	{
		//return;
		
		$cache = SQL::getSelf()->getLine("select * from ". self::$sqlTable. " where cache_key='$key'", false);
		
		$validity = ($validity != null ? $validity : ($cache != null ? $cache["validity"] : $GLOBALS["DEFAULT_CACHE_VALIDITY"]));
		
		//cache_data='". serialize($data). "',
		SQL::getSelf()->query(($cache != null ? "update" : "insert into"). " ". self::$sqlTable. " set 
						cache_key='$key',
						cache_data='',
						validity=$validity,
						expires='". date("Y-m-d H:i:s", time() + $validity). "'".
                
		                ($cache != null ? " where cache_key='$key'" : ""));
		
		
		$prepath = "";
		
		if (!file_exists("cache/"))
			$prepath = "../";
		
		file_put_contents($prepath. "cache/$key.dat", serialize($data));
	}
	
	/*
	
	static function refreshCache()
	{
		$items = SQL::getSelf()->getSimpleArray(self::$sqlTable, "cache_key", "expires<CURDATE()");
		
		SQL::getSelf()->query("delete from ". self::$sqlTable. " where expires<CURDATE()");
		
		foreach ($items as $key)
			@unlink("cache/$key.dat");
	}
	
	
	static function getCache($key)
	{
		return (file_exists("cache/$key.dat") ? unserialize(file_get_contents("cache/$key.dat")) : null);
	}
	
	
	static function setCache($key, $data, $validity = null)
	{
		//return;
		
		$cache = SQL::getSelf()->getLine("select * from ". self::$sqlTable. " where cache_key='$key'");
		
		$validity = ($validity != null ? $validity : ($cache != null ? $cache["validity"] : $GLOBALS["DEFAULT_CACHE_VALIDITY"]));
		
		//cache_data='". serialize($data). "',
		SQL::getSelf()->query(($cache != null ? "update" : "insert into"). " ". self::$sqlTable. " set 
						cache_key='$key',
						cache_data='',
						validity=$validity,
						expires='". date("Y-m-d H:i:s", time() + $validity). "'".
                
		                ($cache != null ? " where cache_key='$key'" : ""));
		
		file_put_contents("cache/$key.dat", serialize($data));
	}
	*/
}

?>