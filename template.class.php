<?php

/** security check */
defined("_NETI_APP_") or die("Direct access is deniend");

/**
 * Template Class, FlexiStore Framework
 * 
 * Simple template engine based on php code
 *
 * @author Andrei Firoiu <andrei.firoiu@neti.ro>
 * @link http://netinteraction.biz/dev/_flexilib
 * @version 1.0
 * @date 20.10.2008
 */
class Template
{
	public static $MASTER_TEMPLATE = "layout";
	
	private static $PAGE_CONTENT;
	
	
	public static function render($templateName = null, $context = null)
	{
		self::$PAGE_CONTENT = (!empty($templateName) ? self::view($templateName, $context) : "");
		
		echo self::view(null, $context);
	}
	
	
	/**
		* Returneaza continutul unui templateName, specificat prin nume.
		* @param $$templateName Calea spre templateName. Aceasta poate fi furnizata in 2 feluri: cale relativa  (templateNames/elements/login.tpl) sau notatie obiectuala (elements.login).
		* In cazul celei de a 2a metode, functia va construi automat calea catre templateName-ul respectiv, in functie de directorul curent din care e apelata.
		* @param array $context Contine contextul de date pentru evaluarea templateName-ului. Daca acesta nu este furnizat (null), atunci se foloseste contextul $GLOBALS. Valoarea default: null
		* @param boolean $verbose Daca este true, continutul templateName-ului va fi afisat la stdout, pe langa faptul ca este returnat de functie. Valoarea default: false
		* @returns $Returneaza continutul templateName-ului, dupa ce a fost evaluat
	*/
	public static function view($templateName = null, $context = null, $verbose = false)
	{
		if  (empty($templateName))
		{
			$templateName = "_". self::$MASTER_TEMPLATE;
			$context["PAGE_CONTENT"] = self::$PAGE_CONTENT;
		}
		
		//log::timer($templateName);
		
		if (strstr($templateName, ".tpl.php") === FALSE)
		{
			$elems = explode(".", $templateName);
			
			$templateName = "views/". implode("/", $elems). ".tpl.php"; // !!! posibil sa fie si in alte directoare !!!!
		}
		
		if (!file_exists($templateName))
		{
			Log::error("Template not found: $templateName");
			return "";
		}
		
		if ($context == null)
			$context = array();
		
		if (is_array($context))
			foreach ($context as $var => $value)
				$$var = $value;
		
		//$context = file_get_contents($templateName);
		
		//preg_match_all("/(@[^@]*@)/", $content, $matches);
		/*preg_match_all("/@([^@]*)@([^@]*)@[^@]*@/", $content, $matches);
		
		if ($matches != null && count($matches) > 0)
		{
			$cnt = 0;
			
			foreach ($matches[0] as $m)
			{
				$var = $matches[1][$cnt];
				
				if (!isset($$var))
					preg_replace("/(@[^@]*@$var@[^@]*@)/", "", $content);
				else
				if (is_array($$var))
				{
					$data = "";
					
					foreach ($$var as $key => $item)
						$data .= "\$$key = \"$item\";";
					
					$data .= "\$data = \"". $matches[2][$cnt]. "\";";
					eval($eval);
					
					preg_replace("/(@[^@]*@$var@[^@]*@)/", $data, $content);
				}
				
				$cnt++;
			}
		} */
		
		ob_start();
		include $templateName;
		$content = ($verbose ? ob_get_flush() : ob_get_clean());
		
		
		//log::timer($templateName);
		
		return $content;
	}
	
	
	function paginate($total, $url = "")
	{
		$pagination = array();
		
		$pagination["current_page"] = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1);
		$pagination["rows_per_page"] = (isset($_REQUEST["rows"]) ? $_REQUEST["rows"] : Settings::get("paginate.rows_per_page"));
		
		$pagination["total"] = $total;
		$pagination["total_pages"] = ceil($pagination["total"] / $pagination["rows_per_page"]);
		
		$pagination["start"] = ($pagination["current_page"] - 1) * $pagination["rows_per_page"] + 1;
		$pagination["end"] = $pagination["current_page"] * $pagination["rows_per_page"];
		
		if ($pagination["end"] > $pagination["total"])
			$pagination["end"] = $pagination["total"];
		
		if ($pagination["total_pages"] <= 5)
		{
			$pagination["start_page"] = 1;
			$pagination["stop_page"] = $pagination["total_pages"];
		}
		else
		if ($pagination["current_page"] <= 3)
		{
			$pagination["start_page"] = 1;
			$pagination["stop_page"] = 5;
		}
		else
		if ($pagination["current_page"] > $pagination["total_pages"] - 2)
		{
			$pagination["start_page"] = $pagination["total_pages"] - 5;
			$pagination["stop_page"] = $pagination["total_pages"];
		}
		else
		{
			$pagination["start_page"] = $pagination["current_page"] - 2;
			$pagination["stop_page"] = $pagination["current_page"] + 2;
		}
		
		$pagination["url"] = ($url != "" ? $url : CONTROLLER);
		
		return $pagination;
	}
	
	
	function sortable($field, $pagination)
	{
		return " class=\"sorting". ($pagination["order"] == $field ? "_". $pagination["dir"] : ""). "\" onclick=\"location.href='". $pagination["url"]. "page=". $pagination["current_page"]. (isset($_GET["rows"]) ? "&rows=". $_GET["rows"] : ""). "&order=$field&dir=". ($pagination["order"] == $field && $pagination["dir"] == "asc" ? "desc" : "asc"). "';\"";
	}
}

?>