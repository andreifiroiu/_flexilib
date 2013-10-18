<?php


/**
 * WebFile Class, _FlexiLib Framework
 * 
 * Get some difficult remote pages that don't cooperate with a get_file_content
 *
 * @TODO: deprecate it and use curl?
 *
 * @author Andrei Firoiu <andrei.firoiu@neti.ro>
 * @link http://netinteraction.biz/dev/_flexilib
 * @version 1.0
 * @date 03.01.2010
 */
class WebFile
{
	var $host = "";
	var $port = "";
	var $path = "";
	var $protocol = "HTTP";
	var $header = array();
	var $content = "";
	
	
	static function getFileInfo($url, $file)
	{
		$file = new WebFile($url, 80, $file);
		$info = $file->get_header();
		
		return $info;
	}


	static function getFileContent($url, $file, $port = 80)
	{
		$file = new WebFile($url, $port, $file);
		$fp = $file->get_content();
		
		return $fp;
	}
	
	
	function WebFile($host, $port, $path, $protocol = "HTTP")
	{
		$this->host = $host;
		$this->port = $port;
		$this->path = $path;
		$this->protocol = $protocol;
		$this->fetch();
	}
	
	
	function fetch()
	{
		$fp = fsockopen ($this->host, $this->port);
		//$fp = @fsockopen($this->host, $this->port, $errno, $errstr, 5);
		
		if (!$fp)
			die("Could not connect to host.");
		
		$header_done=false;
		
		$request = "GET ". $this->path. " ". $this->protocol. "/1.0\r\n";
		$request .= "User-Agent: Mozilla/4.0 (compatible; MSIE 5.5; Windows 98)\r\n";
		$request .= "Host: ". $this->host. "\r\n";
		$request .= "Connection: Close\r\n\r\n";
		$return = '';
		
		fputs ($fp, $request);
		
		$line = fgets ($fp, 128);
		$this->header["status"] = $line;
		
		while (!feof($fp))
		{
			$line = fgets($fp, 128);
			if ($header_done)
				$this->content .= $line;
			else
			{
				if ($line == "\r\n")
					$header_done = true;
				else
				{
					$data = explode(": ", $line);
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