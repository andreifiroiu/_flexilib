<?php

/** security check */
defined("_NETI_APP_") or die("Direct access is deniend");


/**
 * Page Class
 * 
 * some simple URL manipulation helpers
 *
 * @author Andrei Firoiu <andrei.firoiu@neti.ro>
 * @link http://netinteraction.biz/dev/_flexilib
 * @version 1.0
 * @date 20.10.2009
 */
class URL
{	
	static function slugify($term, $urlencode = true)
	{
		return self::encodeUrlTerm($term, $urlencode);
	}


	static function encodeUrlTerm($term, $urlencode = true)
	{
		$term = Util::stripEntities($term, "-");
		
		$term = str_replace(array("é", "ē", "è", "É", "Ē", "È", "é", "É"), "e", $term);
		$term = str_replace(array("ü", "Ü", "ú", "Ú", "ű", "Ű"), "u", $term);
		$term = str_replace(array("ó", "ö", "Ó", "Ö", "ő", "Ő"), "o", $term);
		$term = str_replace(array("í", "Í"), "i", $term);
		$term = str_replace(array("á", "ä", "Á", "Ä"), "a", $term);
		
		$term = str_replace(array(" ", "!", "?", "&", "\$", "\"", "'", "(", ")", "%", ":", ".", ",", "\\". "|", "+", "#", "@", ";", "/"), "-", $term);
		//$term = preg_replace("/[^A-Za-z0-9 -]/","-", $term);
		$term = preg_replace('/\W+/', '-', $term);
		$term = str_replace(array("---", "--"), "-", $term);
		
		$term = strtolower(trim($term, "-"));
		
		$specialChars = array("%E3%BB", "%E3%A7", "%E2%80%A0", "%E3%A4", "%2A", "%E2%80%99", "%E3%B4", "%EE%B1");
		
		if ($urlencode)
			$term = str_replace($specialChars, "-", urlencode($term));
		
		return $term;
	}
	
	
	static function decodeUrlTerm($term, $isExact = false, $urldecode = false)
	{
		$term = str_replace("-", "%", $term). (!$isExact ? "%" : "");
		$term = str_replace("%%", "%", $term);
		
		return ($urldecode ? urldecode($term) : $term);
	}
	
	
	static function decodeUrlTermForText($term)
	{
		$term = str_replace(array("-", "%"), " ", $term);
		
		return ucwords($term);
	}
}


?>