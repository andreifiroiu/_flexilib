<?php

/** security check */
defined("_NETI_APP_") or die("Direct access is deniend");

/**
 * DbItem Class, _FlexiLib Framework
 * 
 * Generic data object working as a layer on top of the databse
 *
 * @author Andrei Firoiu <andrei.firoiu@neti.ro>
 * @link http://netinteraction.biz/dev/_flexilib
 * @version 1.0
 * @date 20.10.2008
 */
class DbItem
{
	public static $_table; // abstract
	public static $_softDelete = false;
	
	protected $id = 0;
	protected $isEmpty = true;
	
	
	
	function DbItem($id = 0)
	{
		if ($id == 0)
			return;
		
		if (!is_array($id))
			self::load($id);
		else
			self::setAll($id);
	}
	
	
	static function find($filter = "", $order = "", $fields = "", $dbName = null)
	{
		$className = get_called_class();
		
		if ($dbName == null)
			$dbName = Settings::get("system.default_db");
		
		$items = array();
		
		$data = SQL::getSelf($dbName)->get("select ". ($fields != "" ? $fields : "*"). " from ". $className::$_table. ($filter != "" ? (strstr($filter, " join ") === FALSE ? " where " : ""). $filter : ""). 
						($order != "" ? " order by $order" : (!empty($className::$_defaultOrderField) ? " order by ". $className::$_defaultOrderField : "")));
		
		foreach ($data as $item)
			$items[] = new $className($item);
		
		return $items;
	}
	

	static function find_ids($filter = "", $order = "", $dbName = null)
	{
		$className = get_called_class();
		
		if ($dbName == null)
			$dbName = Settings::get("system.default_db");
		
		$items = array();
		
		$items = SQL::getSelf($dbName)->getSimpleArray("select id from ". $className::$_table. ($filter != "" ? " where $filter" : ""). 
						($order != "" ? " order by $order" : (!empty($className::$_defaultOrderField) ? " order by ". $className::$_defaultOrderField : "")));
		
		return $items;
	}
	
	
	static function count($filter = "", $fields = "*", $dbName = null)
	{
		$className = get_called_class();
		
		if ($dbName == null)
			$dbName = Settings::get("system.default_db");
		
		return SQL::getSelf($dbName)->getValue("select count($fields) from ". $className::$_table. ($filter != "" ? " where $filter" : ""));
	}
	
	
	static function find_one($id = 0)
	{
		$className = get_called_class();
		
		if (is_numeric($id) && $id != 0)
			$data = SQL::getSelf()->getLineForId($className::$_table, $id);
		else
		//if (is_array($id))
			$data = SQL::getSelf()->getLine("select * from ". $className::$_table. " where $id");
		
		if ($data != null)
			$item = new $className($data);
		else
			$item = null;
		
		return $item;
	}
	
	
	function load($id)
	{
		if ($id != 0)
		{
			$className = get_class($this);
			
			$data = SQL::getSelf()->getLineForId($className::$_table, $id);
			$fields = get_object_vars($this);
			
			if ($data != null)
				$this->isEmpty = false;
			
			foreach ($fields as $field => $val)
				if (isset($data[$field]))
					$this->$field = stripslashes($data[$field]);
		}
	}
	
	
	function save()
	{
		$className = get_class($this);
		
		if (!empty($this->id))
			SQL::getSelf()->save($className::$_table, get_object_vars($this), "id=". $this->id);
		else
		{
			SQL::getSelf()->save($className::$_table, get_object_vars($this));
			$this->id = SQL::getSelf()->getLastId($className::$_table, get_object_vars($this));
		}
	}
	
	
	function delete($soft = true)
	{
		$className = get_class($this);
		
		if ($className::$_softDelete && $soft)
			SQL::getSelf()->save($className::$_table, array("deleted" => 1), "id=". $this->id);
		else
			SQL::getSelf()->delete($className::$_table, "id=". $this->id);
		
		$props = get_object_vars($this);
		
		foreach ($props as $name => $val)
			$this->$name = null;
	}


	function url($params = null)
	{
		$className = get_class($this);

		return Util::slugify($className). "/". Util::slugify($this->name). "-". $this->id. ($params != null ? "?". $params : "");
	}
	
	
	function isEmpty()
	{
		return $this->isEmpty;
	}
	
	
	function setProperty($property, $value)
	{
		$this->$property = $value;
	}
	
	
	function getProperty($property)
	{
		return (isset($this->$property) ? $this->$property : null);
	}
	
	
	function getAllProperties()
	{
		return get_object_vars($this);
	}
	
	
	function getId()
	{
		return $this->id;
	}
	
	
	function getName()
	{
		return (isset($this->name) ? $this->name : "item #". $this->id);
	}
	
	
	function setAll($item)
	{
		if ($item == null || !is_array($item))
			return;
		
		foreach ($item as $property => $value)
			self::setProperty($property, stripslashes($value));
	}
	
	
	function getContext()
	{
		$context = get_object_vars($this);
		$context["self"] = $this;
		
		return $context;
	}
	
	
	static function table()
	{
		return self::$_table;
	}
}

?>