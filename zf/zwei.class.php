<?php
##============================================================================##
##            ZWEI: ZCT Media Research Labs Web Input processor               ##
##============================================================================##

# $ Rev.16 $

/**
* ZWEI: Web Input Parser
*/
class ZWEI
{
	const
		max_recur_depth = 17;
	/**
	* Default input processor
	*
	* @param string key Name of incoming param
	* @param boolean raw Do not filter param value
	* @param boolean force_order_gp Prefer _GET parameter over _POST
	* @return mixed String value of param or boolean FALSE if no such param
	*/
	public static function req($key='',$raw=false,$force_order_gp=false)
	{
		$out = false;
		if($force_order_gp)
		{
			if(isset($_GET[$key]))
				$out = $_GET[$key];
			elseif(isset($_POST[$key]))
				$out = $_POST[$key];
		}
		else
		{
			if(isset($_POST[$key]))
				$out = $_POST[$key];
			elseif(isset($_GET[$key]))
				$out = $_GET[$key];
		}
		if(!$raw and $out)
			$out = (is_array($out)) ? self::parse($out) : self::clear_value($out);
		return $out;
	}
	/**
	* Raw _GET grabber
	*
	* @param string key Name of incoming param
	* @return mixed String value of param or boolean FALSE if no such param
	*/
	public static function get($key='')
	{
		if(isset($_GET[$key]))
			return $_GET[$key];
		else return false;
	}
	/**
	* Raw _POST grabber
	*
	* @param string key Name of incoming param
	* @return mixed String value of param or boolean FALSE if no such param
	*/
	public static function post($key='')
	{
		if(isset($_POST[$key]))
			return $_POST[$key];
		else return false;
	}
	/**
	* Raw _GOOKIE grabber
	*
	* @param string key Name of cookie
	* @return mixed String value of cookie or boolean FALSE if no cookie with such name
	*/
	public static function cookie($key='')
	{
		if(isset($_COOKIE[$key]))
			return $_COOKIE[$key];
		else return false;
	}
	/**
	* Gets or sets value for session variable of _SESSION superglobal. Doesn't work if no session started.
	*
	* @param string key Name of session variable
	* @param mixed value Optional. Value to set
	* @return mixed Boolean FALSE on any error. Otherwise, returns boolean TRUE on set and mixed value of session variable on get.
	*/
	public static function s()
	{
		if(!isset($_SESSION) or 1 > func_num_args())
			return false;
		$key = func_get_arg(0);
		if(1 < func_num_args()){
			$value = func_get_arg(1);
			if(2 < func_num_args() and func_get_arg(2) and isset($_SESSION[$key]))
				unset($_SESSION[$key]);
			else
				$_SESSION[$key] = $value;
			return true;
		}
		if(isset($_SESSION[$key]))
			return $_SESSION[$key];
		else
			return false;
	}
	/**
	* _SERVER grabber
	*
	* @param string key Name of server/env variable
	* @param boolean raw Do not filter param value
	* @return mixed String value of server/env variable or boolean FALSE on any error
	*/
	public static function srv($key,$raw=false)
	{
		if(!empty($_SERVER[$key]))
			$value = $_SERVER[$key];
		else
			$value = @getenv($key);
		return ($raw) ? $value : self::clear_value($value);
	}
	/**
	* _ENV grabber for use in some strange cases.
	*
	* @param string key Name of environment variable
	* @param boolean raw Do not filter param value
	* @return mixed String value of environment variable or boolean FALSE on any error
	*/
	public static function env($key,$raw=false)
	{
		if(!empty($_ENV[$key]))
			$value = $_ENV[$key];
		else
			$value = @getenv($key);
		return ($raw) ? $value : self::clear_value($value);
	}
	############################################################################
	/**
	* Creates array of filtered pairs from supplied arrays. Can be used to create filtered _REQUEST-like variable.
	*
	* @param array i Input array
	* @param array o Optional cleaned array for merging
	* @param int c Iterator for depth limiting
	* @return array Output array
	*/
	public static function parse(&$i,$o=array(),$c=0)
	{
		if($c > self::max_recur_depth)
			return $o;
		if(is_array($i) and count($i))
		{
			foreach($i as $k => $v)
			{
				if(is_array($v))
					$o[$k] = self::parse($i[$k],array(),$c+1);
				else
					$o[self::clear_key($k)] = self::clear_value($v);
			}
		}
		return $o;
	}
	/**
	* Cleanes up array from nasty characters such as null bytes and dir traversals
	*
	* @param array p Input array reference
	* @param int i Iterator for depth limiting
	*/
	public static function clear(&$p,$i=0)
	{
		if($i > self::max_recur_depth)
			return $p;
		if(is_array($p))
			foreach($p as $k => $v)
				self::clear($p[$k],$i+1);
		elseif($p)
		{
			self::ss($p);
			$p = preg_replace('/\\\0/','&#92;&#48;',$p);
			$p = preg_replace('/\\x00/','&#92;x&#48;&#48;',$p);
			$p = str_replace('%00','%&#48;&#48;',$p);
			$p = str_replace('../','&#46;&#46;/',$p);
		}
	}
	/**
	* Cleanes up request key name
	*
	* @param string k Key name
	* @param string Cleaned up key name
	*/
	public static function clear_key($k)
	{
		if(!$k)
			return '';
    	$k = htmlspecialchars(urldecode($k));
    	$k = str_replace('..','',$k);
    	$k = preg_replace('/\_\_(.+?)\_\_/','',$k);
    	$k = preg_replace('/^([\w\.\-\_]+)$/','$1',$k);
    	return $k;
	}
	/**
	* Cleanes up request value
	*
	* @param string v Value
	* @param string Cleaned up value
	*/
	public static function clear_value($v)
	{
		if(!$v)
			return '';
		self::nl($v);
		$v = str_replace('&#032;',' ',$v);
		$v = str_replace('&#8238;','',$v);
		$v = str_replace('&','&amp;',$v);
		$v = str_replace('<!--','&#60;&#33;--',$v);
		$v = str_replace('-->','--&#62;',$v);
		$v = preg_replace('/<script/i','&#60;script',$v);
		$v = str_replace('>','&gt;',$v);
		$v = str_replace('<','&lt;',$v);
		$v = str_replace('"','&quot;',$v);
		$v = str_replace("\n",'<br />',$v);
		$v = str_replace('$','&#036;',$v);
		$v = str_replace('!','&#33;',$v);
		$v = str_replace("'",'&#39;',$v);
		##
		$v = preg_replace('/&amp;#([0-9]+);/s','&#\\1;',$v);
		$v = preg_replace('/&#(\d+?)([^\d;])/i','&#\\1;\\2',$v);
		##
		return $v;
	}
	/**
	* Internal magic quotes unwinder
	*
	* @param string s String with slashes reference
	* @param string Free string
	*/
	private static function ss(&$s)
	{
		# Damned magic quotes workaround! IMMA AINT N00B!! IMMA FIRIN MAH LAZOR!!1
		if(version_compare(PHP_VERSION,'5.3','<') and @get_magic_quotes_gpc())
			$s = preg_replace('/\\\(?!&amp;#|\?#)/','&#092;',stripslashes($s));
	}
	/**
	* Internal newlines unifyer
	*
	* @param string s Incoming string reference
	* @param string String with unified newlines
	*/
	private static function nl(&$s)
	{
		if(!$s)
			return;
		if(strpos($s,"\r\n") !== false)
		{
			## M$
			$s = str_replace("\r\n","\n",$s);
			if(strpos($s,"\r") !== false)
				$s = str_replace("\r",'',$s);
		}
		if(strpos($s,"\n\r") !== false)
		{
			## Strangeness
			$s = str_replace("\n\r","\n",$s);
			if(strpos($s,"\r") !== false)
				$s = str_replace("\r",'',$s);
		}
		if(strpos($s,"\r") !== false)
			## Yabble
			$s = str_replace("\r","\n",$s);
	}
}
?>