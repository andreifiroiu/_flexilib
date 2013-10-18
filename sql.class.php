<?php

/** security check */
defined("_NETI_APP_") or die("Direct access is deniend");


/**
 * SQL Class, _FlexiLib Framework
 * 
 * SQL interface class / factory
 *
 * @author Andrei Firoiu <andrei.firoiu@neti.ro>
 * @link http://netinteraction.biz/dev/_flexilib
 * @version 1.0
 * @date 18.10.2008
 */
class SQL
{
	protected $name;
	protected $host;
	protected $user;
	protected $password;
    
    	protected $dbkey;
	
	protected static $selfInstance = null;
	
	////////////////////////////////////////////////////////
	
	function SQL($dbkey = "")
	{
		$this->dbkey = ($dbkey == "" ? Settings::get("system.default_db") : $dbkey);
        
		$this->host = Settings::get("db_". $this->dbkey. ".host");
		$this->user = Settings::get("db_". $this->dbkey. ".user");
		$this->password = Settings::get("db_". $this->dbkey. ".password");
		
		$this->name = Settings::get("db_". $this->dbkey. ".name");
	}
	
	
	/**
		* Returneaza un obiect SQL cu baza de date selectata $dbname
		* @param String $dbkey baza de date accesata (default value "" -> dbkey va fi preluata din config)
		* @return SQL
	*/
	static function getSelfInstance($dbkey = "")
	{
		return self::getSelf($dbkey);
	}
	
	
	/**
		* Shortcut wrapper pt getSelfInstance()
		* @see SQL::getSelfInstance()
		* @param String $dbkey baza de date accesata (default value "" -> dbkey va fi preluata din config)
		* @return SQL
	*/
	static function getSelf($dbkey = "")
	{
		if ($dbkey == "")
			$dbkey = Settings::get("system.default_db");
		
		if (self::$selfInstance == null)
			self::$selfInstance = array();
		
		unset(self::$selfInstance[$dbkey]);
		
		if (!isset(self::$selfInstance[$dbkey]))
		{
			switch (Settings::get("db_$dbkey.type"))
			{
				case "mysql" : self::$selfInstance[$dbkey] = new MySQL($dbkey); break;
				case "mysqli" : self::$selfInstance[$dbkey] = new MySQLii($dbkey); break;
				
				default: die(Settings::get("db_$dbkey.type"). " layer not implemented");
			}
		}
		
		return self::$selfInstance[$dbkey];
	}
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	
	function get($query)
	{
		//self::getSelfInstance()->sqlGet($table, $properties);
		// abstract method, overridden by particular db layer class
	}
	
	
	function getLine($query)
	{
		// abstract method, overridden by particular db layer class
	}
	
	
	function getLineForId($table, $id)
	{
		// abstract method, overridden by particular db layer class
	}
	
	
	function getValue($query)
	{
		// abstract method, overridden by particular db layer class
	}
	
	
	function getValueForId($table, $field, $id)
	{
		// abstract method, overridden by particular db layer class
	}

	
	
	function getLastId($table, $fields)
	{
		// abstract method, overridden by particular db layer class
	}
	
	
	function getArray($table, $field = "name", $where = "", $order = "")
	{
		// abstract method, overridden by particular db layer class
	}
	
	function getSimpleArray($table, $field = "id", $where = "", $order = "")
	{
		// abstract method, overridden by particular db layer class
	}
	
	function getMap($table, $keyField, $valueField, $where = "")
	{
		// abstract method, overridden by particular db layer class
	}
	
	
	function getArrayFromQuery($query, $field)
	{
		// abstract method, overridden by particular db layer class
	}
	
	
	function getSimpleArrayFromQuery($query, $field)
	{
		// abstract method, overridden by particular db layer class
	}
	
	
	function getMapFromQuery($query, $keyField, $valueField)
	{
		// abstract method, overridden by particular db layer class
	}
	
	function save($table, $fields, $where = null)
	{
		// abstract method, overridden by particular db layer class
	}
	
	
	function delete($table, $where = null)
	{
		// abstract method, overridden by particular db layer class
	}
	
	
	function query($query)
	{
		// abstract method, overridden by particular db layer class
	}
}


?>
