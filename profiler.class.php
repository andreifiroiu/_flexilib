<?php


/** security check */
defined("_NETI_APP_") or die("Direct access is deniend");

//define("_LOGGING_APP_", "lifecarenew");

/**
 * Log Class, FlexiStore Framework
 *
 * @TODO - deprecate this?
 * 
 * Clasa contine functii de log si debug
 * @author Andrei Firoiu <andrei@neti.ro>
 * @copyright  Copyright (c) 2008, Net Interaction, www.neti.ro
 * @link http://flexistore.neti.ro E-commerce Solutions Net Interaction
 * @version 1.0
 * @date 18.10.2008
 * @package util
 */
class Profiler 
{
	
	static function label($key = "general", $label, $timestamp = false)
	{
		//$nkey = $key. ($timestamp ? "-". time(). "-".  : "");
		//$nkey = uniqid($key);
		$nkey = md5($label);
		
		self::$labels[$nkey] = $label;
		
		return $nkey;
	}
	
	
	static function timer($key = "general", $logType = "", $reset = false)
	{
		if (!defined("_LOGGING_APP_"))
			return;
		
		if (!isset(self::$profiler[$key]) || $reset)
		{
			self::$profiler[$key] = microtime(true);
			return self::$profiler[$key];
		}
		
		$time = (microtime(true) - self::$profiler[$key]) * 1000;
		
		self::$stats[$key] = $time;
		
		$logTypeArr = explode(" ", $logType);
		
		if (in_array("debug", $logTypeArr))
			self::debug("Profiler: <strong>$key</strong> ---- $time");
		
		if (in_array("comment", $logTypeArr))
			echo "\n<!-- Profiler: $key ---- $time -->\n\n";
		
		if (in_array("db", $logTypeArr))
			self::saveTimer($key);
		
		return $time;
	}
	
	
	static function getTimerStats()
	{
		return self::$stats;
	}
	
	
	
	static function displayTimerStats()
	{
		if (!defined("_LOGGING_APP_"))
			return;
		
		//echo "<div style='position: absolute; left: 10px; top: 10px; background-color: #fff; border: 1px solid #ddd; color: #000; width: 800px; height: auto; padding: 10px; z-index: 5000;'>\n";
		echo "<div style='background-color: #fff; border: 1px solid #ddd; color: #000; padding: 10px; z-index: 5000;'>\n";
		
		//self::displayTimerInfo(self::$stats);
		
		arsort(self::$stats);
		
		self::displayTimerInfo(self::$stats);
		
		echo "</div>\n";
	}
	
	
	static function displayTimerInfo($arr)
	{
		if (!defined("_LOGGING_APP_"))
			return;
		
		echo "
		<table align='center' cellspacing='0' cellpadding='5' border='1' width='700' style='width: 700px ! important;word-wrap:break-word;'>
		<thead style='font-weight: bold;'>
			<th style='padding: 2px 4px;'>key</td>
			<th style='padding: 2px 4px;'>label</td>
			<th align='right' style='padding: 2px 4px;'>time (ms)</td>
		</thead>
		<tbody>\n";
		
		foreach ($arr as $key => $time)
			echo "<tr>
					<td align='left' style='padding: 2px 4px;'>$key</td>
					<td align='left' style='padding: 2px 4px;word-wrap:break-word;'>". (isset(self::$labels[$key]) ? self::$labels[$key] : "-"). "</td>
					<td align='right' style='padding: 2px 4px;'>". number_format($time, 6). "</td>
				</tr>\n";
		
		echo "</tbody>
		</table>
		<br/><br/>\n";
	}
	
	
	
	static function saveAllTimers()
	{
		if (!defined("_LOGGING_APP_"))
			return;
		
		self::$dblink = mysql_connect(Settings::get("db_site.host"), Settings::get("db_site.user"), Settings::get("db_site.password"));
		mysql_select_db(Settings::get("db_site.name"));
		
		mysql_query("SET NAMES utf8");
		
		foreach (self::$stats as $key => $time)
			self::saveTimer($key);
	}
	
	
	static function saveTimer($key)
	{
		if (!defined("_LOGGING_APP_"))
			return;
		
		$time = self::$stats[$key];
		
		if (isset(self::$labels[$key]))
			$query = self::$labels[$key];
		else
			$query = "";
		
		try
		{
			throw new Exception('Just timing');
		}
		catch (Exception $e)
		{
			//$trace = self::formatTrace($e->getTrace());
			$trace = serialize($e->getTrace());
			
			$result = mysql_query("select * from queries where locationkey='$key'");
			
			if (mysql_errno() != 0)
				echo (mysql_errno() . ": " . mysql_error(). "<br>query=1");
			
			if (!mysql_fetch_assoc($result))
			{
				mysql_query("insert into queries set 
										appname='". _LOGGING_APP_ ."', 
										locationkey='$key',
										trace='". mysql_real_escape_string($trace). "',
										query='". mysql_real_escape_string($query). "',
										total_time_ever=0, 
										total_count_ever=0, 
										time_since_reset=0, 
										count_since_reset=0");
				
				if (mysql_errno() != 0)
					echo(mysql_errno() . ": " . mysql_error(). "<br>query=2");
			}
			
			mysql_query("update queries set 
								total_time_ever=total_time_ever+$time, 
								total_count_ever=total_count_ever+1, 
								time_since_reset=time_since_reset+$time, 
								count_since_reset=count_since_reset+1 
								
							where locationkey='$key'");
			
			if (mysql_errno() != 0)
				echo(mysql_errno() . ": " . mysql_error(). "<br>query=3");
		}
	}
	
	
	private static function formatTrace($strace)
	{
		$trace = "";
		
		foreach ($strace as $line)
		{
			$path = substr($line["file"], strpos($line["file"], Settings::get("app.document_root")));
			
			$trace .= $path. ":". $line["line"]. "\n";
		}
		
		$trace = mysql_real_escape_string($trace);
		
		return $trace;
	}
}