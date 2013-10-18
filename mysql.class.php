<?php

/** security check */
defined("_NETI_APP_") or die("Direct access is denied");


/**
 * MySQL Class, _FlexiLib Framework
 * 
 * MySQL wrapper
 * DEPRECATED - see MysqlIi class
 *
 * @author Andrei Firoiu <andrei.firoiu@neti.ro>
 * @link http://netinteraction.biz/dev/_flexilib
 * @version 1.0
 * @date 03.01.2010
 */
class MySQL extends SQL
{
	protected $link;
	protected $result;
	
	
	function MySQL($dbkey = "")
	{
		parent::SQL($dbkey);
		$this->connect();
	}
	
	/////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	// returneaza un array cu datele din result set-ul queryului primit ca prametru
	function get($query)
	{
		//global $timer;
		//$time = microtime(true);
		
		if (is_array($query))
			$query = $this->constructQuery($query);
		
		//log::debug($query);
		$rs = $this->query($query);
		
		$rsArr = array();
		$cnt = 0;
		
		if ($rs == FALSE)
			return $rsArr;
		
		while (($line = $this->get_line($rs)) != null)
			$rsArr[$cnt++] = $line;
		
		mysql_free_result($rs);
		
		//$timer += microtime(true) - $time;
		//$this->logTimer($timer, $query);
    
		return $rsArr;
	}
	
  
	function logTimer($time, $query)
	{
		if (!defined("_LOGGING_APP_"))
			return;
		
		try
		{
			throw new Exception('Just timing');
		}
		catch (Exception $e)
		{
			$key = $this->makeTimerKey($e->getTrace());
			
			$key = str_replace("D:\\htdocs\\", "", $key);
			$key = str_replace("D:\\Program Files\\Apache Software Foundation\\", "", $key);
			$key = mysql_real_escape_string($key);
			
			$result = mysql_query("select * from timer_logs.queries where locationkey='$key'");
			
			if (!mysql_fetch_assoc($result))
				mysql_query("insert into timer_logs.queries set appname='"._LOGGING_APP_."', locationkey='$key'");
			
			mysql_query("update timer_logs.queries set total_time_ever=total_time_ever+$time, total_count_ever=total_count_ever+1, 
				    time_since_reset=time_since_reset+$time, count_since_reset=count_since_reset+1, 
				    query='".mysql_real_escape_string($query)."'
				    where locationkey='$key'");
		}
	}


	function makeTimerKey($strace)
	{
		$key = "";
		
		foreach ($strace as $level)
			$key = $key.$level["file"].":".$level["line"]."\n";
		
		return $key;
	}
	
	
	function getLine($query)
	{
		$rsArr = $this->get($query);
		
		return ($rsArr != null && isset($rsArr[0]) ? $rsArr[0] : null);
	}
	
	
	function getLineForId($table, $id)
	{
		//return $this->getLine(array("table" => $table, "where" => array("id" => $id)));
		return $this->getLine("select * from $table where id=$id");
	}
	
	
	function getValue($query)
	{
		$rsArr = $this->get($query);
		
		return ($rsArr != null && isset($rsArr[0]) ? current($rsArr[0]) : null);
	}

	
	
	function getValueForId($table, $field, $id)
	{
		//return $this->getValue($table, array("fields" => $field, "where" => array("id" => $id)));
		return $this->getValue("select $field from $table where id=$id");
	}
	
	
	function getArray($table, $field = "name", $where = "", $order = "")
	{
		return $this->getArrayFromQuery("select id,$field from $table". ($where != "" ? " where $where" : ""). 
									($order != "" ? " order by $order" : ""), $field);
	}
	
	
	function getSimpleArray($table, $field = "id", $where = "", $order = "")
	{
		return $this->getSimpleArrayFromQuery("select $field from $table". ($where != "" ? " where $where" : ""). 
									($order != "" ? " order by $order" : ""), $field);
	}
	
	
	function getMap($table, $keyField, $valueField, $where = "")
	{
		return $this->getMapFromQuery("select distinct $keyField, $valueField from $table". ($where != "" ? " where $where" : ""), $keyField, $valueField);
	}
	
	
	function getArrayFromQuery($query, $field)
	{
		//if (($arr = $this->get($table, array("fields" => "id,$field", "where" => array("id" => $id)))) == null)
		if (($arr = $this->get($query)) == null)
			return null;
		
		$newArr = array();
		
		foreach ($arr as $a)
			$newArr[$a["id"]] = $a[$field];
		
		return $newArr;
	}
	
	
	function getSimpleArrayFromQuery($query, $field)
	{
		if (($arr = $this->get($query)) == null)
			return array();
		
		$newArr = array();
		
		foreach ($arr as $a)
			$newArr[] = $a[$field];
		
		return $newArr;
	}
	
	
	function getMapFromQuery($query, $keyField, $valueField)
	{
		if (($arr = $this->get($query)) == null)
			return null;
		
		$newArr = array();
		
		foreach ($arr as $a)
			$newArr[$a[$keyField]] = $a[$valueField];
		
		return $newArr;
	}
	
	
	function getLastId($table, $fields)
	{
		//return $this->getValue("select LAST_INSERT_ID() last_id". ($table != null ? " from $table" : ""), "last_id");
		
		$result = $this->query("select * from $table limit 1");
		$header = $this->get_head($result);
		
		$query = "";
		
		foreach ($header as $field)
			if (isset($fields[$field]) && $field != "id")
				$query .= ($query != "" ? " and " : " where "). "$field='". addslashes($fields[$field]). "'";
		
		return $this->getValue("select id from $table $query order by id desc limit 1");
	}
	
	
	
	function save($table, $fields, $where = null)
	{
		$result = $this->query("select * from $table limit 1");
		$header = $this->get_head($result);
		
		$query = "";
		
		if (!is_array($where))
			$whereQuery = ($where != "" ? " where " : ""). $where;
		else
			$whereQuery = $this->constructQuery($where);
		
		foreach ($header as $field)
		{
			if ($where != null && is_array($where) && isset($where[$field]))
				$whereQuery .= ($whereQuery != "" ? " and " : ""). "$field='". addslashes($where[$field]). "'";
			
			if (isset($fields[$field]))
				$query .= ($query != "" ? " , " : ""). "$field='". addslashes($fields[$field]). "'";
		}
		
		if ($query != "")
		{
			$this->query(($where == "" ? "insert into" : "update"). " $table set $query$whereQuery");
			
			return true; // really???
		}
		
		return false;
	}
	
	
	function delete($table, $where = null)
	{
		if (!is_array($where))
			$whereQuery = ($where != "" ? " where " : ""). $where;
		else
			$whereQuery = $this->constructQuery($where);
		
		$this->query("delete from $table $whereQuery");
		
		return false;
	}
	
	
	protected function constructQuery($queryArr)
	{
		if (empty($queryArr))
			return "";
		
		if (isset($queryArr["where"]) && is_array($queryArr["where"]))
			$whereArr = $queryArr["where"];
		else
			$whereArr = $queryArr;
		
		$where = "";
		foreach ($whereArr as $key => $val)
			$where .= ($where == "" ? " where " : " and "). "$key='$val'";
		
		$order = (isset($queryArr["order"]) && $queryArr["order"] != "" ? " order by ". $queryArr["order"] : "");
		$group = (isset($queryArr["group"]) && $queryArr["group"] != "" ? " group by ". $queryArr["group"] : "");
		$limit = (isset($queryArr["limit"]) && $queryArr["limit"] != "" ? " limit ". $queryArr["limit"] : "");
		
		return $where. $group. $order. $limit;
	}
	
	
	/////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	protected function connect()
	{
		/* Connecting, selecting database */
		//$this->link = mysql_connect($this->host, $this->user, $this->password) 
		$this->link = mysql_pconnect($this->host, $this->user, $this->password) 
			or die("Could not connect: ". $this->name);
		
		if ($this->name != null)
		{
			mysql_select_db($this->name)
				or die("Could not select database: ". $this->name);
			
			mysql_query("SET NAMES utf8");
		}
		
		if (mysql_errno() != 0)
			Log::error(mysql_errno() . ": " . mysql_error());
	}
	
	
	function query($query)
	{
		if (strtolower(substr($query, 0, 6)) != "select" && strtolower(substr($query, 0, 7)) != "(select" && file_exists("logs/queries.log"))
		{
			$yesterday = date("Y-m-d", mktime(12, 0, 0, date("m"), date("d") - 1, date("Y")));
			
			if (!file_exists("logs/queries-$yesterday.log") && file_exists("logs/queries.log"))
				rename("logs/queries.log", "logs/queries-$yesterday.log");
			
			$f = fopen("logs/queries.log", "a");
			fwrite($f, "[". date("Y-m-d H:i:s"). "] [". (isset($_SESSION["script"]) ? $_SESSION["script"] : $_SERVER["PHP_SELF"]). "] [". 
					(isset($_SESSION[_APP_ID_]["login"]) ? "". $_SESSION[_APP_ID_]["login"]["username"] : 
								(isset($_SESSION[_APP_ID_]["giftcard"]) ? "giftcard ". $_SESSION[_APP_ID_]["giftcard"] : "neautentificat")). "] $query\n\n");
			fclose($f);
		}
		
		//$key = log::label("sql", $query, true);
		//log::timer($key);
		
		/* Performing SQL query */
		$this->result = mysql_query($query);
		//	or die("Query failed");
		
		if (mysql_errno() != 0)
		{
			//Log::debug(mysql_errno() . ": " . mysql_error(). "<br>query=$query");
			Log::error(mysql_errno() . ": " . mysql_error(). "<br>query=$query");
		}
		//log::timer($key);
		//////////////////////////////
		
		/*
		$yesterday = date("Y-m-d", mktime(12, 0, 0, date("m"), date("d") - 1, date("Y")));
		
		if (!file_exists("logs/tqueries-$yesterday.log"))
			rename("logs/tqueries.log", "logs/tqueries-$yesterday.log");
		
		$f = fopen("logs/tqueries.log", "a");
		fwrite($f, "[". date("Y-m-d H:i:s"). "] [". (isset($_SESSION["script"]) ? $_SESSION["script"] : $_SERVER["PHP_SELF"]). "] [". 
				(isset($_SESSION["client"]) ? "client ". $_SESSION["client"]["user"] : (isset($_SESSION["new"]) ? "new ". $_SESSION["new"]["user"] : 
							(isset($_SESSION["giftcard"]) ? "giftcard ". $_SESSION["giftcard"]["CodClient"] : "neautentificat"))). "] $query\nTime: $time\n\n");
		fclose($f);
		//////////////////////////////
		*/
		
		return $this->result;
	}


	function clear()
	{
		/* Free resultset */
		mysql_free_result($this->result);
	}
	
	
	function close()
	{
		$this->clear();
		
		/* Closing connection */
		mysql_close();
	}


	protected function get_row($result)
	{
		$line = mysql_fetch_row($result);
		return $line;
	}


	// returneaza o linie din $result ca vector simplu
	protected function get_line($result)
	{
		$line = mysql_fetch_array($result, MYSQL_ASSOC);
		return $line;
	}


	protected function get_object($result)
	{
		$line = mysql_fetch_object($result);
		
		return $line;
	}
	
	
	// returneaza o linie din $result ca vector cu asociatii
	protected function get_assoc_line($result)
	{
		$line = mysql_fetch_assoc($result, MYSQL_ASSOC);
		return $line;
	}


	// returneaza numele coloanelor
	protected function get_head($result)
	{
		$num = mysql_num_fields($result);
		for ($i = 0; $i < $num; $i++)
			$names[$i] = mysql_field_name($result, $i);

		return $names;
	}
	
	
	function get_dbs()
	{
		$db_list = mysql_list_dbs();
		return $db_list;
	}
	
	
	function get_tables($dbname)
	{
		$table_list = mysql_list_tables($dbname);
		return $table_list;
	}
	
	
	function get_fields_type($table)
	{
		$query = "SELECT * FROM ". $table;
		$result = mysql_query($query);
		$fields = mysql_num_fields($result);
		
		for ($i=0; $i < $fields; $i++) {
			$vec[$i]["name"] = mysql_field_name($result, $i);
			$vec[$i]["type"]  = mysql_field_type($result, $i);
		}
		
		mysql_free_result($result);
		
		return $vec;
	}
	
	
	function get_fields($table)
	{
		$query = "SELECT * FROM ". $table;
		$result = mysql_query($query);
		$fields = mysql_num_fields($result);
		
		for ($i=0; $i < $fields; $i++) {
			$vec[$i]["name"]  = mysql_field_name($result, $i);
			$vec[$i]["type"]  = mysql_field_type($result, $i);
			$vec[$i]["len"]   = mysql_field_len($result, $i);
			$vec[$i]["flags"] = mysql_field_flags($result, $i);
		}
		
		mysql_free_result($result);
		
		return $vec;
	}

}


?>
