<?php

/** security check */
defined("_NETI_APP_") or die("Direct access is deniend");

//define("_LOGGING_APP_", "lifecarenew");

/**
 * Log Class, FlexiStore Framework
 * 
 * Debugging and error log support
 *
 * @author Andrei Firoiu <andrei.firoiu@neti.ro>
 * @link http://netinteraction.biz/dev/_flexilib
 * @version 1.0
 * @date 18.10.2008
 */
class Log 
{
	/**
		* String delimitator pentru campurile scrise in fisierul de log
		* @private string $delimiter
	*/
	static private $delimiter = "=====";
	
	static private $profiler = array();
	static private $stats = array();
	static private $labels = array();
	
	static private $dblink = null;
	
	//////////////////////////////////////////////////////////
	
	/**
		* Salveaza mesaje de eroare/notificari pt afisarea ulterioara pe site (intre refreshuri)
		* @param string $msg Textul mesajului
		* @param string $type Tipul mesajului (err=eroare, msg=notificare)
		* @param string $pageFilter Pagini pe care ar trebui sa apara mesajul respectiv. Daca nu este specificat, se va afisa la primul apel. TODO: de implementat
		* @return boolean - true daca a adaugat mesajul in lista, false daca mesajul exista deja
	*/
	static function addSiteError($msg, $type = "err", $pageFilter = null)
	{
		if (!isset($_SESSION[_APP_ID_]["err"]) || !is_array($_SESSION[_APP_ID_]["err"]))
			$_SESSION[_APP_ID_]["err"] = array();
		
		foreach ($_SESSION[_APP_ID_]["err"] as $item)
			if ($item["msg"] == $msg && $item["type"] == $type && $item["filters"] == $pageFilter)
				return false;
		
		$item = array();
		$item["msg"] = $msg;
		$item["type"] = $type;
		$item["filters"] = $pageFilter;
		
		$_SESSION[_APP_ID_]["err"][] = $item;
		
		return true;
	}
	
	
	/**
	  * Afiseaza mesajele de eroare/notificare pe site
	**/
	static function displaySiteErrors($pageFilter = null, $formated = true)
	{
		if (!isset($_SESSION[_APP_ID_]["err"]))
			return;
		
		if (!is_array($_SESSION[_APP_ID_]["err"]))
			$_SESSION[_APP_ID_]["err"] = array();
		
		$cnt = 0;
		$str = "";
		
		while ($cnt < count($_SESSION[_APP_ID_]["err"]) && count($_SESSION[_APP_ID_]["err"]) > 0)
		{
			if (($pageFilter == null && $_SESSION[_APP_ID_]["err"][count($_SESSION[_APP_ID_]["err"]) - 1]["filters"] == null) || 
					($pageFilter != null && $_SESSION[_APP_ID_]["err"][count($_SESSION[_APP_ID_]["err"]) - 1]["filters"] == $pageFilter))
			{
				$item = array_pop($_SESSION[_APP_ID_]["err"]);
				
				if (!in_array($item["type"], array("err", "msg", "info")))
					continue;
				
				if ($formated)
					Util::getTemplate("elements.". $item["type"], array($item["type"] => $item["msg"]), true);
				else
					$str .= $item["msg"]. "<br/>\n";
			}
			
			$cnt++;
		}
		
		if (!$formated)
			return $str;
	}
	
	
	/**
	 * Returneaza mesajele de eroare/notificare pe site
	 **/
	static function getSiteErrors($pageFilter = null)
	{
		if (!isset($_SESSION[_APP_ID_]["err"]))
			return;
		
		$cnt = 0;
		$messages = array();
		
		while ($cnt < count($_SESSION[_APP_ID_]["err"]) && count($_SESSION[_APP_ID_]["err"]) > 0)
		{
			if (($pageFilter == null && $_SESSION[_APP_ID_]["err"][count($_SESSION[_APP_ID_]["err"]) - 1]["filters"] == null) || 
					($pageFilter != null && $_SESSION[_APP_ID_]["err"][count($_SESSION[_APP_ID_]["err"]) - 1]["filters"] == $pageFilter))
			{
				$item = array_pop($_SESSION[_APP_ID_]["err"]);
				
				if (!in_array($item["type"], array("err", "msg", "info")))
					continue;
				
				$mitem = array();
				$mitem["type"] = $item["type"];
				$mitem["msg"] = $item["msg"];
				
				$messages[] = $mitem;
			}
			
			$cnt++;
		}
		
		return $messages;
	}
	
	
	/**
		* Testeaza daca exista mesaje de eroare/notificare de afisat
	*/
	static function hasSiteErrors($pageFilter = null)
	{
		if (!isset($_SESSION[_APP_ID_]["err"]))
			return false;
		
		foreach ($_SESSION[_APP_ID_]["err"] as $err)
			if (($pageFilter == null && $err["filters"] == null) || ($pageFilter != null && $err["filters"] != null && $err["filters"] == $pageFilter))
				return true;
		
		return false;
	}
	
	
	/**
		* Afiseaza continutul unei variabilei sau un mesaj de debug. Face diferentierea intre array-uri si variabile simple
		* @param mixed $var Variabila sau mesajul de debug de afisat
		* @param boolean $exit Setata pe true, opreste executia scriptului dupa afisarea mesajului de debug (default value: false)
	*/
	static function debug($var, $exit = false, $forceLog = false)
	{
		//if (!$forceLog && !in_array(self::getUserIP(), $GLOBALS["DEBUG_IP_ADDRESSES"]) && !isset($_SESSION[_APP_ID_]["debug"]))
		//	return;
		
		echo "\n\n<hr/>\n";
		
		if (is_array($var))
		{
			echo "LOG ARRAY:<br/>\n";
			self::my_print_r($var);
		}
		else
		if (is_object($var))
		{
			echo "LOG OBJECT:<br/>\n";
			self::my_print_r($var);
		}
		else
			echo "LOG STRING:<br/>\n$var\n";
		
		echo "<hr/>\n\n";
		
		if ($exit)
			die("Debugger stop");
	}
	
	/**
		* Apeleaza functia debug, dupa care opreste executia scriptului
		* @param mixed $var Variabila sau mesajul de debug de afisat. Daca variabila nu e specificata, functia opreste pur si simplu scriptul (default value: null).
		* @uses debug() pentru a afisa continutul variabilei sau mesaju de debug
	*/
	static function debugAndStop($var = null)
	{
		if ($var != null)
			debug($var, true);
		
		die("Debugger stop");
	}
	
	
	/**
		* Afiseaza continutul unui array in mod preformatat, pentru o cititre mai usoara.
		* @param array $array Variabila sau mesajul de debug de afisat
	*/
	static function my_print_r($var)
	{
		echo "<pre>";
		print_r($var);
		echo "</pre>";
	}
	
	
	/**
		* In functie de setarile generale de log, afiseaza stringul de eroare, il salveaza in fisierul de loguri de eroare sau nu afiseaza nimic
		* @param string $str Mesajul de eroare de afisat
		* @uses Settings::get() Verifica statusul pentru tratarea logurilor si acceseaza calea catre fisierul de loguri
	*/
	static function error($str, $setting = null)
	{
		if ($setting == null)
			$setting = Settings::get("log.status");
		
		$ops = explode("|", $setting);
		
		if (in_array("display", $ops))
			self::debug("ERROR". self::$delimiter. $str);
			
		if (in_array("write", $ops))
		{
			$errMsg = date("d/m/y H:i:s"). self::$delimiter. 
					"IP=". self::getUserIP(). self::$delimiter. 
					"REFFERER=". (isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : "-"). self::$delimiter.
					"URL=". (isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : "cron"). self::$delimiter. 
					"ERROR=$str\n";
			
			/*$f = fopen(Settings::get("log.error_file"), "a");
			fwrite($f, $errMsg);
			fclose($f);*/
			
			$logPath = Settings::get("log.error_file");
			
			if (!file_exists($logPath))
			{
				$logPath = "../". $logPath;
					
				if (!file_exists($logPath))
				{
					$logPath = "../". $logPath;
					
					if (!file_exists($logPath))
						return false;
				}
			}
			
			error_log($errMsg, 3, $logPath);
		}
		
		return true;
	}
	
	
	/**
		* In functie de setarile generale de log, afiseaza stringul un mesaj sistem, il salveaza in fisierul de mesaje sistem sau nu afiseaza nimic
		* @param string $str Mesajul de eroare de afisat
		* @uses Settings::get() Verifica statusul pentru tratarea logurilor si acceseaza calea catre fisierul de loguri
	*/
	static function message($str, $setting = null)
	{
		if ($setting == null)
			$setting = Settings::get("log.status");
		
		$ops = explode("|", $setting);
		
		if (in_array("display", $ops))
			self::debug("MESSAGE". self::$delimiter. $str);
			
		if (in_array("write", $ops))
		{
			$errMsg = "\n". self::$delimiter. self::$delimiter. self::$delimiter. "\n". date("d/m/y H:i:s"). self::$delimiter. "$str\n";
			
			/*
			$f = fopen(Settings::get("log.message_file"), "a");
			fwrite($f, $errMsg);
			fclose($f);
			*/
			
			error_log($errMsg, 3, Settings::get("log.message_file"));
		}
		else
		if (in_array("off", $ops) || in_array("none", $ops))
		{
			// no action taken
		}
	}
	
	
	
	static function getUserIP()
	{
		if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
			$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		
		if (isset($_SERVER["REMOTE_ADDR"]))
			$ip = $_SERVER["REMOTE_ADDR"];
		else
			$ip = "cron";
		
		return $ip;
	}
	
	
	
	static function access($user, $message)
	{
		if (!Settings::get("log.access"))
			return false;
		
		SQL::getSelf()->save("access_log", array("date" => date("Y-m-d H:i:s"), "ip" => self::getUserIP(), "user" => $user, "message" => $message));
		
		// clean up
		$logStamp = date("Y-m", mktime(0, 0, 0, date("n") - 2, 1, date("Y")));
		
		if (!file_exists("logs/access/log-$logStamp.log"))
		{
			$logs = AccessLog::find("YEAR(date)=". date("Y"). " and MONTH(date)=". (date("n") - 2));
			
			$f = fopen("logs/access/log-$logStamp.log", "a");
			$str = "";
			
			foreach ($logs as $log)
				$str .= "[". $log->date. "] ip=". $log->ip. " | user=". $log->user. " | message=". $log->message. "\n";
			
		
			fwrite($f, $str);
			fclose($f);
			
			self::message("access log cleanup - $logStamp");
			
			SQL::getSelf()->delete(AccessLog::$_table, "YEAR(date)=". date("Y"). " and MONTH(date)=". (date("n") - 2));
		}
	}
	
	
	static function operation($table, $operation, $recId, $query = "")
	{
		if (!Settings::get("log.operations"))
			return;
		
		
		SQL::getSelf()->save("access_log", array("date" => date("Y-m-d H:i:s"), "ip" => self::getUserIP(), "user" => Auth::getUserId(), "tbl_name" => $table, "rec_id" => $recId, "operation" => $operation, "query" => addslashes($query)));
		
		// clean up
		$logStamp = date("Y-m", mktime(0, 0, 0, date("n") - 2, 1, date("Y")));
		
		if (!file_exists("logs/operations/log-$logStamp.log"))
		{
			$logs = OperationLog::find("YEAR(date)=". date("Y"). " and MONTH(date)=". (date("n") - 2));
			
			$f = fopen("logs/operations/log-$logStamp.log", "a");
			$str = "";
			
			foreach ($logs as $log)
				$str .= "[". date("Y-m-d H:i:s"). "] ip=". $log->ip. " | user=". $log->user. " | tbl_name=". $log->tbl_name. " | rec_id=". $log->rec_id. " | operation=". $log->operation. " | query=". $log->query. "\n";
			
			fwrite($f, $str);
			fclose($f);
			
			self::message("operation log cleanup - $logStamp");
			
			SQL::getSelf()->delete(OperationLog::$_table, "YEAR(date)=". date("Y"). " and MONTH(date)=". (date("n") - 2));
		}
	}
	
	
	// shortcuts
	
	static function edit($table)
	{
		self::logOperation($table, "edit", $_REQUEST["id"], var_export($_POST, true));
	}
	
	
	static function delete($table)
	{
		self::logOperation($table, "delete", $_REQUEST["id"]);
	}
}

?>