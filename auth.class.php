<?php

/** security check */
defined("_NETI_APP_") or die("Direct access is deniend");

/**
 * Auth Class, _FlexiLib Framework
 * 
 * User authentication support
 *
 * @author Andrei Firoiu <andrei.firoiu@neti.ro>
 * @link http://netinteraction.biz/dev/_flexilib
 * @version 1.0
 * @date 03.01.2010
 */
class Auth
{	
	
	static function testLogin($verbose = true)
	{
		if ((isset($_REQUEST["op"]) && $_REQUEST["op"] == "login") || isset($_REQUEST["login"]))
        	{
			if (self::login())
				return;
		}
		else
		if ((isset($_GET["operation"]) && $_GET["operation"] == "logout") || isset($_REQUEST["logout"]))
			self::logout();
		
		if ($verbose)
			self::loginForm();
	}
	
	
	static function login($autologin = null)
	{
		if (isset($_REQUEST["username"]) && isset($_REQUEST["password"]))
        	{
			if ($_REQUEST["username"] == "")
				$err = "Invalid account (1)";
			else
            		{
				$user = str_replace(array("'", "\"", "\\", ":", ",", "?", "%", "&", "|"), "", $_REQUEST["username"]);
				
				if ($user != $_REQUEST["username"])
					$err = "Invalid account (2)"; 
				else
				{
					$password = $_REQUEST["password"];
					
					$dbUser = SQL::getSelf()->getLine("select * from users where user='$user'");
					
					if ($dbUser == null || empty($password))
                			{
						$err = "Invalid username or password";
						Log::error("Login error: ". $_REQUEST["username"]);
					}
					else
                			if (!Auth::validatePassword($password, $dbUser["password"], $dbUser["user"]))
						$err = "Invalid username or password 1";
				}
			}
		}
		else
		if (isset($_COOKIE["autologin"]))
			$dbUser = SQL::getSelf()->getLine("select * from users where md5='". $_COOKIE["autologin"]. "'");
		else
		if (!empty($autologin))
			$dbUser = SQL::getSelf()->getLine("select * from users where md5='$autologin'");

		if (!isset($err))
		{
			if (!isset($dbUser))
            		{
				Log::error("Login: unknown operation", true);
				die("Login: unknown operation");
			}

        		return Auth::doLogin($dbUser);
        	}

        	if (isset($err))
			$GLOBALS["login_err"] = $err;
		else
			unset($GLOBALS["login_err"]);
	}


	static private function doLogin($dbUser)
	{
		if ($dbUser["active"] == 0)
			$err = "Suspended account";
		else
		{
			$_SESSION[_APP_ID_]["login"] = array();
			$_SESSION[_APP_ID_]["login"]["id"] = $dbUser["id"];
			$_SESSION[_APP_ID_]["login"]["first_name"] = $dbUser["first_name"];
			$_SESSION[_APP_ID_]["login"]["last_name"] = $dbUser["last_name"];
			$_SESSION[_APP_ID_]["login"]["username"] = $dbUser["user"];
			$_SESSION[_APP_ID_]["login"]["email"] = $dbUser["email"];
			$_SESSION[_APP_ID_]["login"]["type"] = $dbUser["type"];
			
			if (isset($dbUser["type"]) && $dbUser["type"] == "admin")
				$_SESSION[_APP_ID_]["login"]["access"] = 100;
			
			//$sections = array();//SQL::getSelfInstance()->getMap("access_level_sections", "section", "section", "level='". $dbUser["access"]. "'");
			//$_SESSION[_APP_ID_]["login"]["sectionTree"] = Auth::makeSectionTree($sections);
			
			//SQL::getSelfInstance()->saveRecords("users", array("session_id" => ""), "adddate(login_date, INTERVAL 1 DAY)<Curdate()");
			SQL::getSelfInstance()->save("users", array("last_login" => date("Y-m-d H:i:s")), "id=". $dbUser["id"]);
			SQL::getSelfInstance()->save("log_users", array("ip" => getenv("REMOTE_ADDR"), "date" => date("Y-m-d H:i:s"), "user_id" => $dbUser["id"]));
			
			if (isset($_REQUEST["autologin"]))// && $dbUser["type"] != "admin")
			{
				if (!headers_sent())
				{
					$key = md5($dbUser["id"]. "-". date("Y-m-d-H-i-s"));
					SQL::getSelfInstance()->saveRecords("users", array("md5" => $key));
					setcookie("autologin", $key, time() + 60 * 60 * 24 * 634, "/");
				}
				else
				{
					$err = "Autologin cannot be set. Headers already sent";
					Log::error("Autologin error: ". $_REQUEST["username"]);
				}
			}
			
			//Log::access($dbUser["user"], "login-success");
			
			return true;
		}
		
		if (isset($err))
			$GLOBALS["login_err"] = $err;
		else
			unset($GLOBALS["login_err"]);
		
		return false;
	}
	

	static function loginWithFacebook($fbuser, $fbprofile)
	{
		$dbUser = SQL::getSelf()->getLine("select * from users where facebook_id='$fbuser'");

		if ($dbUser == null && !empty($fbprofile["email"]))
		{
			$dbUser = SQL::getSelf()->getLine("select * from users where email='". $fbprofile["email"]. "'");

			if ($dbUser != null)
			{
				$item = new User($dbUser["id"]);

				$item->facebook_id = $fbuser;
				
				if (isset($fbprofile["location"]["name"]))
					$item->location = $fbprofile["location"]["name"];

				if (empty($item->user))
					$item->user = $fbprofile["email"];

				if (empty($item->picture))
					$item->picture = $item->getFacebookPicture();

				$item->save();
			}
		}
		
		
		if ($dbUser == null)
		{
			$item = new User();
			
			$item->first_name = $fbprofile["first_name"];
			$item->last_name = $fbprofile["last_name"];

			$item->email = $fbprofile["email"];
			$item->facebook_id = $fbuser;
			$item->facebook_name = $fbprofile["username"];

			
			if (isset($fbprofile["location"]["name"]))
				$item->location = $fbprofile["location"]["name"];

			if (isset($fbprofile["bio"]))
				$item->bio = $fbprofile["bio"];
			$item->user = $fbprofile["email"];
			$item->active = 1;
			$item->confirmed = 1;

			$item->password = ""; //Auth::encryptPassword($_POST["password"], $_POST["email"]);
			
			$item->type = User::$TYPE_CLIENT;
			$item->add_date = date("Y-m-d H:i:s");
			$item->md5 = md5($dbUser["email"]. "-". date("Y-m-d-H-i-s"));

			$item->picture = $item->getFacebookPicture();
			
			$item->save();

			$dbUser = SQL::getSelf()->getLine("select * from users where facebook_id='$fbuser'");
		}
		else
		{
			$user = User::find_one($dbUser["id"]);
			$user->picture = $user->getFacebookPicture();
			$user->save();
		}


		return Auth::doLogin($dbUser);

		//$GLOBALS["login_err"] = $err;
	}


	static function loginWithTwitter($status)
	{
		$dbUser = SQL::getSelf()->getLine("select * from users where twitter_id='". $status->id. "'");

		if ($dbUser == null)
		{
			$item = new User();
			$names = explode(" ", $status->name);
			$fname = array_splice($names, -1, 1);
			
			$item->last_name = $fname[0];
			$item->first_name = implode(" ", $names);
			
			//$item->email = $status->email;
			$item->twitter_id = $status->id;
			$item->twitter_name = $status->screen_name;

			$item->location = $status->location;

			$item->user = $status->screen_name;
			$item->bio = $status->description;
			
			$item->active = 1;
			$item->confirmed = 1;

			$item->password = ""; //Auth::encryptPassword($_POST["password"], $_POST["email"]);
			
			$item->type = User::$TYPE_CLIENT;
			$item->add_date = date("Y-m-d H:i:s");
			$item->md5 = md5($dbUser["email"]. "-". date("Y-m-d-H-i-s"));

			if ($status->profile_image_url != "")
				$item->picture = $item->getTwitterPicture($status->profile_image_url);
			
			$item->save();

			$dbUser = SQL::getSelf()->getLine("select * from users where twitter_id='". $status->id. "'");
		}
		else
		{
			$user = User::find_one($dbUser["id"]);

			if ($status->location != "" && $user->location == "")
				$user->location = $status->location;

			if ($status->description != "" && $user->bio == "")
				$user->bio = $status->description;

			if ($status->profile_image_url != "" && $user->picture == "")
				$user->picture = $user->getTwitterPicture($status->profile_image_url);

			$user->save();
		}


		return Auth::doLogin($dbUser);

		//$GLOBALS["login_err"] = $err;
	}


	static function loginWithLinkedin($status)
	{
		$dbUser = SQL::getSelf()->getLine("select * from users where linkedin_id='". $status->id. "'");

		if ($dbUser == null)
		{
			$item = new User();
			
			$item->last_name = $status->lastName;
			$item->first_name = $status->firstName;
			
			$item->email = $status->emailAddress;
			$item->linkedin_id = $status->id;
			$item->linkedin_name = $status->publicProfileUrl;

			
			$item->user = $status->emailAddress;
			$item->bio = $status->summary;
			
			$item->active = 1;
			$item->confirmed = 1;

			$item->password = ""; //Auth::encryptPassword($_POST["password"], $_POST["email"]);
			
			$item->type = User::$TYPE_CLIENT;
			$item->add_date = date("Y-m-d H:i:s");
			$item->md5 = md5($dbUser["email"]. "-". date("Y-m-d-H-i-s"));

			if ($status->pictureUrl != "")
				$item->picture = $item->getLinkedinPicture($status->pictureUrl);
			
			$item->save();

			$dbUser = SQL::getSelf()->getLine("select * from users where twitter_id='". $status->id. "'");
		}
		else
		{
			$user = User::find_one($dbUser["id"]);

			if ($status->summary != "" && $user->bio == "")
				$user->bio = $status->summary;

			if ($status->pictureUrl != "" && $user->picture == "")
				$user->picture = $user->getTwitterPicture($status->pictureUrl);

			$user->save();
		}


		return Auth::doLogin($dbUser);

		//$GLOBALS["login_err"] = $err;
	}

	
	static function logout($fbLogoutUrl)
	{
		//log_access(getenv("REMOTE_ADDR"), $_SESSION[_APP_ID_]["login"]["user"], $GLOBALS["USER"], $GLOBALS["MSG_USER_LOGOUT"]);
		
		if (isset($_COOKIE["autologin"]))
			if (!headers_sent())
				setcookie("autologin", $_SESSION[_APP_ID_]["login"]["id"], time(), "/");
			else
			{
				$err = "Autologin cannot be set. Headers already sent";
				Log::error("Autologin error (logout): ". $_REQUEST["username"]);
			}
		
		unset($_SESSION[_APP_ID_]["login"]);
		unset($_SESSION[_APP_ID_]["history"]);
		
		//session_destroy();
		//self::loginForm();
		
		History::jumpTo(!empty($fbLogoutUrl) ? $fbLogoutUrl : URL::getURL());
		//History::jumpTo(URL::getURL());
	}
	
	
	static function getLoginInfo()
	{
		if (self::isLogged())
			return Util::getTemplate("login_info");
		
		return "";
	}
	
	
	static function loginForm()
	{
		Util::getTemplate("login", null, true);
		
		if (isset($GLOBALS["login_err"]))
			unset($GLOBALS["login_err"]);
	}
	
	
	static function checkAccess($section, $verbose = false)
	{
		$sectionDb = SQL::getSelfInstance()->getLine("select * from sections where section='$section'");
		
		if ($sectionDb == null) {
			//SQL::getSelfInstance()->saveRecords("sections", array("section" => $section));
			
			return true;
		}
		
		if ($_SESSION[_APP_ID_]["login"]["type"] == "admin") // superuser - has all the rights in the world :D
			return true;
		
		if (isset($_REQUEST["autologin"]) && Auth::verifyAutologinCode() !== false)
				return true;
		
		$sectionArr = explode("/", $section);
		if (count($sectionArr) == 0)
			return true;
		
		$arrayStr = "\$_SESSION[_APP_ID_][\"login\"][\"sectionTree\"]";
		$accessDenied = false;
		
		foreach ($sectionArr as $s)
		{
			$arrayStr .= "[\"$s\"]";
			
			eval("if (!isset($arrayStr)) \$accessDenied = true;");
			
			if ($accessDenied)
			{
				if ($verbose)
				{
					echo "<p class='error'>Access denied</p>\n";
					exit;
				}
				
				return false;
			}
		}
		
		/*$access = SQL::getSelfInstance()->getLine("select * from user_rights where section='$section' and user=". $_SESSION[_APP_ID_]["login"]["id"]);
		
		if ($access == null) {
			if ($verbose) {
				echo "<p class='error'>Access denied</p>\n";
				exit;
			}
			
			return false;
		}*/
		
		return true;
	}
	

	static function isLogged()
	{
		if (isset($_SESSION[_APP_ID_]["login"]) && isset($_SESSION[_APP_ID_]["login"]["type"]))
			return true;
		
		return false;
	}
	
	
	static function isAdmin()
	{
		return (isset($_SESSION[_APP_ID_]["login"]["access"]) && $_SESSION[_APP_ID_]["login"]["type"] == "admin" ? true : false);
	}
	
	
	static function getUserId()
	{
		if (isset($_SESSION[_APP_ID_]["login"]) && isset($_SESSION[_APP_ID_]["login"]["type"]))
			return $_SESSION[_APP_ID_]["login"]["id"];
		
		return 0;
	}
	
	
	static function getAccess()
	{
		if (isset($_SESSION[_APP_ID_]["login"]) && isset($_SESSION[_APP_ID_]["login"]["type"]))
			return $_SESSION[_APP_ID_]["login"]["access"];
		
		return "none";
	}
	
	
	static function getUsername()
	{
		if (isset($_SESSION[_APP_ID_]["login"]) && isset($_SESSION[_APP_ID_]["login"]["type"]))
			return $_SESSION[_APP_ID_]["login"]["username"];
		
		return null;
	}
	
	
	static function getUser()
	{
		if (isset($_SESSION[_APP_ID_]["login"]) && isset($_SESSION[_APP_ID_]["login"]["type"]))
			return new User($_SESSION[_APP_ID_]["login"]["id"]);
		
		return null;
	}
	
	static function getName()
	{
		if (isset($_SESSION[_APP_ID_]["login"]) && isset($_SESSION[_APP_ID_]["login"]["type"]))
			return $_SESSION[_APP_ID_]["login"]["first_name"]. " ". $_SESSION[_APP_ID_]["login"]["last_name"];
		
		return null;
	}
	
	
	static function makeSectionTree($sections)
	{
		$sectionTree = array();
		
		if (count($sections) == 0)
			return array();
		
		foreach ($sections as $s => $t)
		{
			$els = explode("/", $s);
			$arrayStr = "\$sectionTree";
			
			foreach ($els as $e)
			{
				$arrayStr .= "[\"$e\"]";
				
				//log::debug("if (!isset($arrayStr) $arrayStr = array();");
				eval("if (!isset($arrayStr)) $arrayStr = array();");
			}
		}
		
		return $sectionTree;
	}
	
	
	static function verifyAutologinCode()
	{
		if (!isset($_REQUEST["code"]))
			return false;
		
		$code = SQL::getSelfInstance()->getLine("select * from access_codes where code='". $_REQUEST["code"]. "' and (expires<2 or expire_date>=CURDATE())");
		
		if ($code != null && $code["expires"] == 1)
			SQL::getSelfInstance()->deleteRecords("access_codes", "id=". $code["id"]);
		
		return ($code != null ? true: false);
	}


	static function validatePassword($password, $dbPassword, $key = "")
	{
		if ($key == "")
			$key = $password;
		
		if (!empty($password) && !empty($dbPassword))
		{
			if (self::encryptPassword($password, $key) == $dbPassword)
				return true;
		}
		
		return false;
	}
	
	
	static function encryptPassword($password, $key = "")
	{
		if ($key == "")
			$key = $password;
		
		return crypt(md5($password), md5($key));
	}
	
	
	static function generatePassword($length = 8)
	{
		$password = "";
		$possible = "0123456789bcdfghjkmnpqrstvwxyz"; 
		$i = 0;
		
		while ($i < $length)
		{
			$char = substr($possible, mt_rand(0, strlen($possible) - 1), 1);
			
			//if (!strstr($password, $char)) { 
				$password .= $char;
				$i++;
			//}
		}
		
		return $password;
	}
}