<?php

/** security check */
defined("_NETI_APP_") or die("Direct access is deniend");

/**
 * Util Class, FlexiStore Framework
 * 
 * All kind of utilitarian functions
 *
 * @TODO: see what can be moved in some more specialized classes
 *
 * @author Andrei Firoiu <andrei.firoiu@neti.ro>
 * @link http://netinteraction.biz/dev/_flexilib
 * @version 1.0
 * @date 20.10.2008
 */
class Util
{	
	/*
	 * Realizarea salvarea unui fisier incarcat de pe un calculator local pe server, in calea specificata
	 * @param string numele fisierului incarcat
	 * @param string calea unde trebuie salvat fisierul
	 * @param string noul nume al fisierului (optional)
	 * @param boolean daca se pastreaza si numele vechi al fisierului (optional)
	 * @param array extensiile acceptate pentru fisierul incarcat (optional)
	 *
	 * @return array diverse informatii despre statusul operatiei (err - true, daca a intervenit o eroare; message - mesajul de eroare; filename - numele fisierului salvat, fara cale; size - dimensiunea fisierului in bytes; original - numele orginal al fisierului)
	 */
	static function uploadFile($fieldName, $path, $filename = NULL, $original = false, $extensions = null)
	{
		$err_vec = array(
				UPLOAD_ERR_OK => "upload reusit", 
				UPLOAD_ERR_INI_SIZE => "fisierul depaseste marimea admisa de server. Fisireul nu a fost uploadat.",
				UPLOAD_ERR_FORM_SIZE => "fisierul depaseste marimea admisa. Fisireul nu a fost uploadat.",
				UPLOAD_ERR_PARTIAL => "fisierul a fost uploadat partial. Fisireul nu a fost uploadat.",
				UPLOAD_ERR_NO_FILE => "fisierul nu poate fi citit. Fisierul nu a fost uploadat.",
				UPLOAD_ERR_NO_TMP_DIR => "fisierul nu poate fi scris in directorul temporar. Fisierul nu a fost uploadat.",
				UPLOAD_ERR_CANT_WRITE => "fisierul nu poate fi scris pe server. Fisierul nu a fost uploadat.",
		);
		
		$err = false;
		
		if (is_uploaded_file($_FILES[$fieldName]["tmp_name"]))
			$msg = $_FILES[$fieldName]["name"]. ": upload reusit";
		else
		{
			$msg = $_FILES[$fieldName]["name"]. ": ". $err_vec[$_FILES[$fieldName]["error"]];
			$err = true;
		}
		
		if ($_FILES[$fieldName]["error"] == 0)
		{
			$type = strtolower(substr(strrchr($_FILES[$fieldName]["name"], "."), 1));
			
			if ($extensions != null && count($extensions) > 0 && !in_array(strtolower($type), $extensions))
			{
				$msg = $_FILES[$fieldName]["name"]. " tipul fisierului nu este permis. Fisierul nu a fost uploadat.";
				$err = true;
			}
			
			if (!$err)
			{
				//$filename = ($original ? $_FILES[$fieldName]["name"] : ""). md5(uniqid(rand(), true)). ".". $type;
				$filename = ($original ? $_FILES[$fieldName]["name"]. ($filename != null ? "-$filename" : "") : ($filename ? $filename : md5(uniqid(rand(), true))). ".". $type);
				
				$name = str_replace(".". $type, "", $filename);
				$filename = strtolower(URL::encodeUrlTerm($name, true). ".". $type);
				
				copy($_FILES[$fieldName]["tmp_name"], $path. $filename);
			}
		}
		
		return array("err" => $err, "message" => $msg, "filename" => $filename, "type" => (isset($type) ? $type : ""), 
					"size" => $_FILES[$fieldName]["size"], "original" => $_FILES[$fieldName]["name"]);
	}
	
	
	/*
	 * Redimensioneaza o imagine incarcata pe server
	 *
	 * @param array contine informatii despre imagine (filename - numele imaginii, fara cale)
	 * @param indica tipul de marime (thumb, medium, original)
	 * @param string calea catre imagine
	 * @param int latime in pixeli (optional)
	 * @param int inaltime in pixeli (optional)
	 */
	function resizeImage($newFile, $sizeType, $path, $customWidth = 0, $customHeight = 0)
	{
		$SMALL_WIDTH = 78;
		$SMALL_HEIGHT = 78;
		
		$THUMB_WIDTH = 200;
		$THUMB_HEIGHT = 200;
		
		$MEDIUM_WIDTH = 350;
		$MEDIUM_HEIGHT = 350;
		
		$ORIGINAL_WIDTH = 1200;
		$ORIGINAL_HEIGHT = 1200;
		
		$IMAGE_SIZE_SMALL = "small";
		$IMAGE_SIZE_THUMB = "thumb";
		$IMAGE_SIZE_MEDIUM = "medium";
		$IMAGE_SIZE_NORMAL = "normal";
		$IMAGE_SIZE_CUSTOM = "custom";
		$IMAGE_SIZE_ORIGINAL = "original";
		
		
		$IMG_GIF = 1;
		$IMG_JPG = 2;
		$IMG_PNG = 3;
		$IMG_SWF = 4;
		$IMG_PSD = 5;
		$IMG_BMP = 6;
		$IMG_TFF = 7;

		$imageName = $path. $newFile["filename"];
		
		$size = getimagesize($imageName);
		
		switch ($size[2])
		{
			case $IMG_JPG: $im = imagecreatefromjpeg($imageName); break;
			case $IMG_PNG: $im = imagecreatefrompng($imageName); break;
			case $IMG_GIF: $im = imagecreatefromgif($imageName); break;
			case $IMG_BMP: $im = imagecreatefromwbmp($imageName); break;
		}
		
		switch ($sizeType)
		{
			case $IMAGE_SIZE_SMALL:
			{
				$thumbWidth = $SMALL_WIDTH;
				$thumbHeight = $SMALL_HEIGHT;
				$prefix = "small";
				break;
			}
			
			case $IMAGE_SIZE_THUMB:
			{
				$thumbWidth = $THUMB_WIDTH;
				$thumbHeight = $THUMB_HEIGHT;
				$prefix = "thumb";
				break;
			}
			
			case $IMAGE_SIZE_MEDIUM:
			{
				$thumbWidth = $MEDIUM_WIDTH;
				$thumbHeight = $MEDIUM_HEIGHT;
				$prefix = "medium";
				break;
			}
			
			case $IMAGE_SIZE_CUSTOM:
			{
				$thumbWidth = $customWidth;
				$thumbHeight = $customHeight;
				$prefix = "custom";
				break;
			}
			
			case $IMAGE_SIZE_ORIGINAL:
			{
				$thumbWidth = $ORIGINAL_WIDTH;
				$thumbHeight = $ORIGINAL_HEIGHT;
				$prefix = "";
				break;
			}
		}
		
		$thumbName = $path. ($prefix != "" ? $prefix. "-" : ""). $newFile["filename"];
		
		if ($thumbWidth > $size[0] && $thumbHeight > $size[1])
		{
			//if (!file_exists($thumbName))
				copy($path. $newFile["filename"], $thumbName);
			
			return;
		}
		
		$imageRatio = $size[0] / $size[1];
		$thumbRatio = $thumbWidth / $thumbHeight;
			
		if ($thumbRatio < $imageRatio)
			$thumbHeight = round($thumbWidth / $imageRatio);
		else
			$thumbWidth = round($thumbHeight * $imageRatio);
		
		$thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
		imagecopyresampled($thumb, $im, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $size[0], $size[1]);
		
		switch ($size[2])
		{
			case $IMG_JPG: imagejpeg($thumb, $thumbName, 60); break;
			case $IMG_PNG: imagepng($thumb, $thumbName); break;
			case $IMG_GIF: imagegif($thumb, $thumbName); break;
			case $IMG_BMP: imagewbmp($thumb, $thumbName); break;
		}
		
		imagedestroy($im);
		imagedestroy($thumb);
	}
	
	
	static function stripEntities($content, $replacingChar = "")
	{
		return preg_replace("/&#?[a-z0-9]{2,8};/i", $replacingChar, $content);
	}
	
	
	static function slugify($term)
	{
		$term = preg_replace('/\W+/', '-', $term);
		$term = strtolower($term);

		return $term;
	}


	static function getUrlInfo($url, $file)
	{
		$file = new GetWebObject($url, 80, $file);
		$info = $file->get_header();
		
		return $info;
	}
	
	
	static function getUrlContent($url, $file, $port = 80)
	{
		$file = new GetWebObject($url, $port, $file);
		$fp = $file->get_content();
		
		return $fp;
	}
	
	
	static function cleanUpHTML($text) 
	{
		$text = stripslashes($text);
		
		$newtext = strstr($text, "<body"); 
		
		if ($newtext != FALSE)
			$text = $newtext;
		
		// strip tags, still leaving attributes, second variable is allowable tags
		$text = strip_tags($text, "<p><b><i><strong><a><h1><h2><h3><h4><h5><h6><strong><em><table><tr><td><th><thead>
								<tbody><ul><ol><li><br><hr><img><br><br/><font><span><blockquote></blockquote>
									<sup><sub><embed><param><object><div><form><input><select><label>");
		
		// removes the attributes for allowed tags, use separate replace for heading tags since a
		// heading tag is two characters
		//$text = ereg_replace("<([b|i|u])[^>]*>", "", $text);
		//$text = ereg_replace("<([h1|h2|h3|h4|h5|h6][1-6])[^>]*>", "", $text);
		
		//$text = ereg_replace("width=[^>]", "", $text);
		$text = ereg_replace("font\-family:[^>]*;", "", $text);
		//$text = ereg_replace("face=[^>]*(?=[\s|>])", "", $text);
		
		//$text = stripeentag($text, "table", array("border", "cellspacing", "cellpadding"));
		/*$text = stripeentag($text, "tr", array("valign", "align"));
		$text = stripeentag($text, "td", array("valign", "align"));
		$text = stripeentag($text, "p", array("align"));*/
		
		return addslashes($text);
	}
	
	
	static function truncateText($text, $len = 200, $words = true, $moreStr = "...")
	{
		$text = trim(strip_tags($text));
		
		if (strlen($text) > $len)
		{
			if (!$words)
				return substr($text, 0, $len). " ". $moreStr;
			else
			{
				$substr = substr($text, 0, $len + 1);
				return substr($substr, 0, strrpos($substr, " ")). " ". $moreStr;
			}
		}
			
		return $text;
	}
	
	
	static function filterStringParam($param)
	{
		$param = str_replace(array("concat_ws", "union", "select", "password", "limit", "users", "%", "$", "!="), " *** ", urldecode($param));
		$param = addslashes($param);
		
		return $param;
	}
	
	
	static function filterNumParam($param)
	{
		if (!is_numeric($param))
			return 0;
		
		if (is_int($param))
			$param = intval($param);
		else
			$param = floatval($param);
		
		return $param;
	}


	static function sanitizeParams($what = "_POST", $exceptFields = "")
	{
		if ($exceptFields != "")
			$fields = explode(",", $exceptFields);
		
		foreach ($_POST as $key => $val)
		{
			if (in_array($key, $fields))
				continue;
			
			if (is_array($val))
			{
				foreach ($val as $skey => $sval)
					$_POST[$key][$skey] = Util::filterStringParam(strip_tags($sval));
			}
			else
				$_POST[$key] = Util::filterStringParam(strip_tags($val));
		}
	}


	/*
	static function sanitizeParams($what = "_POST", $exceptFields = "")
	{
		if ($exceptFields != "")
			$fields = explode(",", $exceptFields);

		foreach ($$what as $key => $val)
		{
			if (in_array($key, $fields))
				continue;
			
			if (is_array($val))
			{
				foreach ($val as $skey => $sval)
					$$what[$key][$skey] = Util::filterStringParam(strip_tags($sval));
			}
			else
				$$what[$key] = Util::filterStringParam(strip_tags($val));
		}
	}
	*/

	
	static function formatDate($dateStr = null, $format = "d/m/Y")
	{
		if ($dateStr == null)
			return "-";
		
		if ($dateStr == "now")
			$dateStr = date($format);
		
		if ($format == "ro")
			return date("d", strtotime($dateStr)). " ".  Language::getMonth(date("n", strtotime($dateStr)) - 1). " ". date("Y", strtotime($dateStr));
		
		return date($format, strtotime($dateStr));
	}
	
	
	static function formatDateTime($dateStr = null, $format = "d/m/Y H:i:s")
	{
		return self::formatDate($dateStr, $format);
	}
	
	
	static function formatPrice($price, $displayCurrency = true, $decimals = 2, $varDecimals = false)
	{
		return self::formatNumber($price, $decimals, $varDecimals). ($displayCurrency ? " ". Language::getCurrentCurrency(true) : "");
	}
	
	
	static function formatNumber($number, $decimals = 2, $varDecimals = false)
	{
		$regionalFormat = ",.";//Constants::get("regional_number_format.". Language::getCurrentLanguage());
		
		return number_format($number, ($varDecimals && $number == round($number) ? 0 : $decimals), substr($regionalFormat, 0, 1), substr($regionalFormat, 1, 1));
	}
	
	
	static function formatFileSize($bytes)
	{
		$return = "";
		
		if ($bytes >= 1099511627776)
		{
			$return = round($bytes / 1024 / 1024 / 1024 / 1024, 2);
			$suffix = "TB";
		} 
		elseif ($bytes >= 1073741824)
		{
			$return = round($bytes / 1024 / 1024 / 1024, 2);
			$suffix = "GB";
		} 
		elseif ($bytes >= 1048576)
		{
			$return = round($bytes / 1024 / 1024, 2);
			$suffix = "MB";
		}
		elseif ($bytes >= 1024)
		{
			$return = round($bytes / 1024, 2);
			$suffix = "KB";
		}
		else
		{
			$return = $bytes;
			$suffix = "Byte";
		}
		
		if ($return == 1)
			$return .= " " . $suffix;
		else
			$return .= " " . $suffix . "s";
		
		return $return;
	}
	
	
	static function getFileSize($url, $timeout = 2)
	{
		$url = parse_url($url);
		
		if ($fp = fsockopen($url['host'], (isset($url['port']) ? $url['port'] : 80), $errno, $errstr, $timeout))
		{
			fwrite($fp, 'HEAD '. $url['path']. " HTTP/1.0\r\nHost: ". $url['host']. "\r\n\r\n");
			stream_set_timeout($fp, $timeout);
			
			while (!feof($fp))
			{
				$size = fgets($fp, 4096);
				
				if (stristr($size, 'Content-Length') !== false) // PHP5: stripos
				{
					$size = trim(substr($size, 16));
					break;
				}
			}
			
			fclose ($fp);
		}
		
		return is_numeric($size) ? intval($size) : false;
	}
	
	
	static function randomNumber($min = null, $max = null)
	{
		static $seeded;
		
		if (!isset($seeded))
		{
			mt_srand((double)microtime() * 1000000);
			$seeded = true;
		}
		
		if (isset($min) && isset($max))
		{
			if ($min >= $max)
				return $min;
			else
				return mt_rand($min, $max);
		}
		
		return mt_rand();
	}
	
	
	static function paginate($recordsCount, $url, $displayPageLimit = false, $displayFirstLast = false, $realCount = 0)
	{
		$pageLimit = (isset($_GET["page_limit"]) ? $_GET["page_limit"] : Settings::get("general.page_limit"));
		
		echo "Afisare <strong>". ($realCount != 0 ? $realCount : $pageLimit). "</strong> 
					inregistrari din <strong>$recordsCount</strong>.<br/>\n";
		
		if ($recordsCount <= $pageLimit)
			return $recordsCount;
		
		if (!isset($_GET["page_num"]))
			$_GET["page_num"] = 0;
		
		if ($_GET["page_num"] != 0)
			echo ($displayFirstLast ? "<a href='$url&page_num=0". (isset($_GET["page_limit"]) ? "&page_limit=". $_GET["page_limit"] : ""). "'>&laquo; Prima pagina</a> | " : ""). "
				<a href='$url&page_num=". ($_GET["page_num"] - 1). (isset($_GET["page_limit"]) ? "&page_limit=". $_GET["page_limit"] : ""). "'>&laquo; Pagina anterioara</a> | \n";
		
		if (floor(($recordsCount / $pageLimit)) > Settings::get("general.page_display_cnt") - 1)
		{
			if ($_GET["page_num"] + Settings::get("general.page_display_cnt") > floor($recordsCount / $pageLimit))
			{
				$panala = floor($recordsCount / $pageLimit) - ($recordsCount % $pageLimit != 0 ? 0 : 1);
				$tempdiv = floor(($recordsCount - $recordsCount % $pageLimit) / $pageLimit - ($recordsCount % $pageLimit != 0 ? 0 : 1));
				$dela = floor($_GET["page_num"] - (Settings::get("general.page_display_cnt") - ($tempdiv - $_GET["page_num"])));
			}
			else
			if ($_GET["page_num"] > floor(Settings::get("general.page_display_cnt") / 2))
			{
				$panala = floor($_GET["page_num"] + Settings::get("general.page_display_cnt") / 2); 
				$dela = floor($_GET["page_num"] - Settings::get("general.page_display_cnt") / 2);
			}
			else
			{
				$panala = floor($_GET["page_num"] + (Settings::get("general.page_display_cnt") - $_GET["page_num"])); 
				$dela = 0;
			}
		}
		else
		{
			$dela = 0; 
			$panala = floor($recordsCount / $pageLimit);
		}
		
		$totalPages = floor(($recordsCount - $recordsCount % $pageLimit) / $pageLimit) + ($recordsCount % $pageLimit != 0 ? 1 : 0);
		
		echo "Pagina: \n";
		for ($i = $dela; $i <= $panala; $i++)
		{
			if ($i == $_GET["page_num"])
				echo "<strong style='font-size: 130%;'>". ($_GET["page_num"] + 1). "</strong> ";
			else 
				echo " <a href='$url&page_num=$i". (isset($_GET["page_limit"]) ? "&page_limit=". $_GET["page_limit"] : ""). "'>". ($i + 1). "</a> ";
				
			echo ($i != $panala ? "&nbsp;|&nbsp; " : "");
		}
		
		echo "&nbsp; din $totalPages | \n";
		
		if ($_GET["page_num"] != floor(($recordsCount - $recordsCount % $pageLimit) / $pageLimit))
			echo "
				<a href='$url&page_num=". ($_GET["page_num"] + 1). (isset($_GET["page_limit"]) ? "&page_limit=". $_GET["page_limit"] : ""). "'>Pagina urmatoare &raquo;</a>
				". ($displayFirstLast ? " | <a href='$url&page_num=". (($recordsCount - $recordsCount % $pageLimit) / $pageLimit - ($recordsCount % $pageLimit != 0 ? 0 : 1)). (isset($_GET["page_limit"]) ? "&page_limit=". $_GET["page_limit"] : ""). "'>Ultima pagina &raquo;</a>" : ""). "\n";
		
		if ($displayPageLimit)
			echo " &nbsp;&nbsp;&nbsp;&nbsp; Inregistrari pe pagina: 
				<select name='page_limit' onchange=\"window.location='$url&page_num=". $_GET["page_num"]. "&page_limit=' + this.options[this.selectedIndex].value;\">
					<option value='10'". (isset($_GET["page_limit"]) && $_GET["page_limit"] == 10 ? " selected='selected'" : ""). ">10</option>
					<option value='15'". (isset($_GET["page_limit"]) && $_GET["page_limit"] == 15 ? " selected='selected'" : ""). ">15</option>
					<option value='20'". (isset($_GET["page_limit"]) && $_GET["page_limit"] == 20 ? " selected='selected'" : ""). ">20</option>
					<option value='30'". ((isset($_GET["page_limit"]) && $_GET["page_limit"] == 25) || !isset($_GET["page_limit"]) ? " selected='selected'" : ""). ">25</option>
					<option value='25'". (isset($_GET["page_limit"]) && $_GET["page_limit"] == 30 ? " selected='selected'" : ""). ">30</option>
					<option value='40'". (isset($_GET["page_limit"]) && $_GET["page_limit"] == 40 ? " selected='selected'" : ""). ">40</option>
					<option value='50'". (isset($_GET["page_limit"]) && $_GET["page_limit"] == 50 ? " selected='selected'" : ""). ">50</option>
					<option value='100'". (isset($_GET["page_limit"]) && $_GET["page_limit"] == 100 ? " selected='selected'" : ""). ">100</option>
				</select>\n";
		
		return $recordsCount;
	}
	
	
	/**
	* Genereaza un cod de confirmare pentru un anumit identificator unic dintr-o anumita sectiune
	* @see SQL lifecarenew.access_codes
	* @param string - sectiune sau tabel SQL
	* @param integer - identificator unic sau id in tabel SQL
	* @param boolean - daca codul respectiv expira automat intr-un anumit interval de timp
	* @param - data expirarii, in cazul in care $expire = true
	* @return string - codul unic de confirmare (hash md5)
	*/
	static function generateCode($section = "", $sectionId = 0, $expire = false, $expireDate = null, $dbName = "site")
	{
		$code = md5(uniqid(""));
		
		SQL::getSelf($dbName)->query("delete from access_codes where section='$section' and section_id='$sectionId'");
		
		SQL::getSelf($dbName)->query("insert into access_codes set
									code='$code',
									expires='". ($expire ? 1 : 0). "',
									expire_date='". ($expireDate != null ? $expireDate : date("Y-m-d", time() + 60 * 60 * 24 * 7)). "',
									section='$section',
									section_id='$sectionId'");
		
		return $code;
	}
	
	
	/**
	* Verifica un cod de confirmare pentru diverse operatii
	* @see SQL lifecarenew.access_codes
	* @param string - hash-ul codului (md5)
	* @param boolean - daca este true, codul este sters din db dupa verificare
	* @return array - linia din baza de date pentru codul respectiv, sau null daca codul este invalid
	*/
	static function verifyCode($code, $delete = false)
	{
		$codeDb = SQL::getSelfInstance()->getLine("select * from access_codes where code='$code'");
		
		if ($codeDb == null)
			return null;
		
		if ($delete)
			SQL::getSelfInstance()->query("delete from access_codes where code='$code'");
		
		return $codeDb;
	}
	
	
	/*
	 * Converteste un numar din cifre in litere
	 * @param float numarul de convertit
	 * @return string numarul scris in litere
	 */
	static function numberToLetters($x)
	{
		$numeCifre = array("unu", "doi", "trei", "patru", "cinci", "sase", "sapte", "opt", "noua", "zece", "unsprezece", "doisprezece", 
							"treisprezece", "paisprezece", "cincisprezece", "saisprezece", "saptisprezece", "optisprezece",
								"nouasprezece");
		
		$numeCifreSute = array("una", "doua", "trei", "patru", "cinci", "sase", "sapte", "opt", "noua", "unsprezece", "doisprezece",
							"treisprezece", "paisprezece", "cincisprezece", "saisprezece", "saptisprezece", "optisprezece",
								"nouasprezece");
		
		if (substr($x, 0, 1) == "-") {
			$minus = true;
			$x = substr($x, 1);
		}
		else
			$minus = false;
			
		$general = "";
		$zeci = "";
		$sute = "";
		$s = "";
		$bani = "";
		
		$y = $x;
		$t = $y % 1000;
		$y = round($y / 1000);
		
		$x = $x * 100;
		$w = $x;
		$w = $w % 100;
		
		if ($w > 19) {
			if ($w % 10 != 0)
				$bani = $numeCifreSute[$w / 10 - 1]. "zecisi". $numeCifre[$w % 10 - 1];
			else
				$bani = $numeCifreSute[$w / 10 - 1]. "zeci";
		}
		elseif ($w > 0)
			$bani = $numeCifre[$w - 1];
		
		$m = 0;
		do {
			$sute = "";
			if ($t > 99) {
				if ($t / 100 - 1 == 0)
					$s = "suta";
				else
					$s = "sute";
				
				$sute = $numeCifreSute[$t / 100 - 1]. $s;
				$t = $t % 100;
			}
			
			$zeci = "";
			if ($t > 19) {
				if ($t % 10 != 0)
					$zeci = $numeCifreSute[$t / 10 - 1]. "zecisi". $numeCifre[$t % 10 - 1];
				else
					$zeci = $numeCifreSute[$t / 10 - 1]. "zeci";
			}
			elseif ($t > 0) {
				if ($m == 1)
					$zeci = $numeCifreSute[$t - 1];
				else
					$zeci = $numeCifre[$t - 1];
			}
			
			if ($m == 1)
				$general = $sute. $zeci. "mii". $general;
			else
				$general = $sute. $zeci. $general;
			
			$m = $m + 1;
			$t = $y % 1000;
			$y = round($y / 1000);
		}
		while ($t != 0 || $y != 0);
		
		$general = ($minus ? "minus" : ""). ($general == "" ? "" : $general. "lei"). 
					($bani == "" || $general == "" ? "" : "si"). 
						($bani == "" ? "" : $bani. "bani");
		
		return $general;
	}
	
	
	static function getRegionName($code, $table = "judete", $dbkey = "")
	{
		return SQL::getSelf($dbkey)->getValue("select nume from $table where cod='$code'");
	}
	
	
	/**
	* Verifica daca o adresa de email este valida.
	* @param string adresa de email
	* @return boolean (true daca adresa este valida)
	*/
	static function isValidEmail($email)
	{
		return preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i", $email);
	}
	
	
	/**
	* Verifica daca un numar de telefon este valid
	* @param string numarul de telefon
	* @return boolean (true daca numarul este valid)
	*/
	static function isValidPhone($phone)
	{
		if ($phone == "")
			return false;
		
		$digits = "10123456789";
		
		$phoneNumberDelimiters = "()- "; // non-digit characters which are allowed in phone numbers
		$validWorldPhoneChars = $phoneNumberDelimiters ."+";  // characters which are allowed in international phone numbers (a leading + is OK)
		$minDigitsInIPhoneNumber = 10; // Minimum no of digits in an international phone no.
		
		// Search through string's characters one by one.
		// If character is not in bag, append to returnString.
		$str = "";
		
		for ($i = 0; $i < strlen($phone); $i++)
		{
			// Check that current character isn't whitespace.
			$c = $phone[$i];
			
			if (strchr($validWorldPhoneChars, $phone[$i]) === FALSE)
				$str .= $phone[$i];
		}
		
		if (strlen($str) < 10 || strlen($str) > 13)
			return false;
		
		if (intval($str) <= 0)
			return false;
		
		return true;
	}
	
	
	/**
	* Verifica daca sectiunea specificata este suspendata sau nu. Suspendarile se realizeaza din admin - Configurare / Suspendare sectiuni. Lista de sectiuni se gasesc in lista predefinita suspended_sections
	* @param string $section
	* @return array | null (returneaza sectiunea daca exista, sau null in caz contrar)
	*/
	static function checkDowntime($section)
	{
		$now = date("Y-m-d H:i:s");
		
		$section = SQL::getSelf()->getLine("SELECT * FROM `suspended_sections` where site_section='$section' and start_date<'$now' and end_date>='$now' order by end_date limit 1");
		
		return $section;
	}
	
	
	static function obfuscateEmails($content)
	{
		preg_match_all("/(<a ([\w]+)[^>]*>)/", $content, $matches);
		$emails = array();
		
		foreach ($matches[0] as $m)
		{
			if (strchr($m, "@") === FALSE)
				continue;
			
			$tag = str_replace("\"", "'", $m);
			
			$hrefPos = strpos($tag, "href='");
			$emails[] = trim(str_replace("mailto:", "", substr($tag, $hrefPos + strlen("href='"), strpos($tag, "'", $hrefPos + strlen("href='")) - $hrefPos - strlen("href='"))));
		}
		
		foreach ($emails as $e)
		{
			$link = $e;
			$obfuscatedLink = "";
			
			for ($i = 0; $i < strlen($link); $i++)
				$obfuscatedLink .= "&#". ord($link[$i]). ";";
			
			$content = str_replace($e, $obfuscatedLink, $content);
		}
		
		return $content;
	}
	
	
	static function arrayMerge($arr1, $arr2)
	{
		foreach ($arr2 as $key => $value)
			$arr1[$key] = $value;
			
		return $arr1;
	}
	
	
	static function arraySort($array, $key, $desc = false)
	{
		$gap = count($array) - 1; //initialize gap size
		$swaps = 1;
		
		while ($gap > 1 || $swaps > 0)
		{
			//update the gap value for a next comb
			if ($gap > 1)
			{
				$gap /= 1.3;
			
				if ($gap == 10 || $gap == 9)
					$gap = 11;
			}
			
			$i = 0;
			$swaps = 0; //see bubblesort for an explanation
			//a single "comb" over the input list
			while ($i + $gap < count($array))
			{
				if ((!$desc && $array[$i][$key] > $array[$i + $gap][$key]) || ($desc && $array[$i][$key] < $array[$i + $gap][$key]))
				{
					$aux = $array[$i];
					$array[$i] = $array[$i + $gap];
					$array[$i + $gap] = $aux;
					
					$swaps++;
				}
				
				$i++;
			}
		}
		
		return $array;
	}
	
	
	function objectSort($array, $key, $desc = false)
	{
		$gap = count($array) - 1; //initialize gap size
		$swaps = 1;
		
		while ($gap > 1 || $swaps > 0)
		{
			if ($gap > 1)
			{
				$gap /= 1.3;
				
				if ($gap == 10 || $gap == 9)
					$gap = 11;
			}
			
			$i = 0;
			$swaps = 0;
			
			while ($i + $gap < count($array))
			{
				if ((!$desc && $array[$i]->$key > $array[$i + $gap]->$key) || ($desc && $array[$i]->$key < $array[$i + $gap]->$key))
				{
					$aux = $array[$i];
					$array[$i] = $array[$i + $gap];
					$array[$i + $gap] = $aux;
					
					$swaps++;
				}
				
				$i++;
			}
		}
		
		return $array;
	}
	
}



class GetWebObject
{
	var $host = "";
	var $port = "";
	var $path = "";
	var $header = array();
	var $content = "";
	
	function GetWebObject($host, $port, $path)
	{
		$this->host = $host;
		$this->port = $port;
		$this->path = $path;
		$this->fetch();
	}
	
	function fetch()
	{
		$fp = fsockopen ($this->host, $this->port);
		//$fp = @fsockopen($this->host, $this->port, $errno, $errstr, 5);
		
		if(!$fp)
			die("Could not connect to host.");
		
		$header_done=false;
		
		$request = "GET ".$this->path." HTTP/1.0\r\n";
		$request .= "User-Agent: Mozilla/4.0 (compatible; MSIE 5.5; Windows 98)\r\n";
		$request .= "Host: ".$this->host."\r\n";
		$request .= "Connection: Close\r\n\r\n";
		$return = '';
		
		fputs ($fp, $request);
		
		$line = fgets ($fp, 128);
		$this->header["status"] = $line;
		
		while (!feof($fp)) {
			$line = fgets ( $fp, 128 );
			if($header_done)
				$this->content .= $line;
			else {
				if($line == "\r\n")
					$header_done=true;
				else {
					$data = explode(": ",$line);
					$this->header[$data[0]] = $data[1];
				}
			}
		}
		
		fclose ($fp);
	}
	
	
	function get_header()
	{
		return($this->header);
	}
	
	
	function get_content()
	{ 
		return($this->content);
	}
}

?>