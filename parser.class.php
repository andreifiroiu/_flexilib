<?php

/** security check */
defined("_NETI_APP_") or die("Direct access is deniend");

/**
 * Parser Class, FlexiStore Framework
 * 
 * @TODO - deprecate this?
 *
 * Parsare HTML
 * @author Andrei Firoiu <andrei@neti.ro>
 * @copyright  Copyright (c) 2008, Net Interaction, www.neti.ro
 * @link http://flexistore.neti.ro E-commerce Solutions Net Interaction
 * @version 1.0
 * @date 01.06.2009
 * @package util
 */
class Parser
{
	
	// FUNCTIA EXTRAGE UN STRING dintr-unul mai mare. Capetele trebuie sa fie lowercase
	static function extractString($str, $start, $end = "")
	{
		$str_low = $str;//strtolower($str);
		$pos_start = strpos($str_low, $start);
		
		if ($end != "")
			$pos_end = strpos($str_low, $end, ($pos_start + strlen($start)));
		else
			$pos_end = false;
		
		if ($pos_start !== false) {
			$pos1 = $pos_start + strlen($start);
			
			if ($pos_end !== false) {
				$pos2 = $pos_end - $pos1;
				return substr($str, $pos1, $pos2);
			}
			else
				return substr($str, $pos1);
		}
	}
	
	
	// curatare text*  by VISAN 27.06.2006 *
	static function cleanText($text)
	{
		$text = htmlentities($text);

		// pun totul pe un singur rand
		$text = preg_replace("/(&lt;script)(.*?)(script&gt;)/si", "", "$text");
		$text = preg_replace("/(\n)/","",$text);
		$text = preg_replace("/(\r)/","",$text);
		$text = preg_replace("/(\t)/","",$text);

		$text = preg_replace("/  /","",$text);
		
		return $text;
	}	


	// citeste o pagina si o transforma in string * by VISAN 27.06.2006 *
	static function page2string($link)			 
	{
		/*$pagina_curenta = file($link);
		$numar_linii = count($pagina_curenta);
		$string = "";
		
		for($i = 0; $i < $numar_linii; $i++)
			$string .= $pagina_curenta[$i];
		
		return $string;*/
		
		return get_site($link);
	}
	
	
	static function text2line($text)
	{
		// pun totul pe un singur rand
		$text = preg_replace("/(\n)/","",$text);
		$text = preg_replace("/(\r)/","",$text);
		$text = preg_replace("/(\t)/","",$text);

		$text = preg_replace("/  /","",$text);
		
		return $text;
	}
	
	
	static function parseURL($url) 
	{ 	
		print_r(parse_url($url));
	}
	
	
	static function LNtoBR($str) {
		$str = str_replace("\r\n", "\n", $str);
		$str = str_replace("\n", "\r", $str);
		$str = str_replace("\r", "<br />\r\n", $str);
		$str = str_replace("  ", "&nbsp;&nbsp;", $str);
		return $str;
	}


	static function da($array) {
		echo LNtoBR(print_r($array, 1));
	}
	
	
	static function save2draft($bank, $pagina)
	{
		$handler = @fopen("data/". date("dmY"). "_$bank.txt", "w+");
		@fwrite($handler, $pagina);
		@fclose($handler);
	}



	static function printRawLines($lines)
	{
		$liness = array();
		foreach ($lines as $l)
			$liness[] = trim(strip_tags($l));
		
		print_r($liness);
	}


	static function parseValue($rawVal)
	{
		$rawVal = trim($rawVal);
		$val = str_replace(",", ".", strip_tags($rawVal));
		$val = str_replace("&nbsp;", "", strip_tags($val));
		
		return floatval($val);
	}
}


?>