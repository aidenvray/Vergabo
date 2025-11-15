<?php
class TinyUncover
{
	public static function IsProxy()
	{
		$proxy = 0;
		$ip = ZWEI::srv('REMOTE_ADDR');
		$kd = array('HTTP_FORWARDED','HTTP_X_FORWARDED_FOR','HTTP_CLIENT_IP','HTTP_VIA','HTTP_XROXY_CONNECTION','HTTP_PROXY_CONNECTION','HTTP_USERAGENT_VIA','HTTP_CACHE_CONTROL','HTTP_CACHE_INFO','HTTP_X_REAL_IP');
		foreach($kd as $k)
			if(ZWEI::srv($k))
				$proxy = 1;
		if(!self::GetProxyIP())
			$proxy = 0;
		return $proxy;
	}
	public static function GetProxyIP()
	{
		$ka = array('HTTP_X_FORWARDED_FOR','HTTP_CLIENT_IP','HTTP_X_REAL_IP');
		$ip = ZWEI::srv('REMOTE_ADDR');
		foreach($ka as $k)
		{
			if(!ZWEI::srv($k))
				continue;
			if(false !== strpos(ZWEI::srv($k),$ip))
				break;
			$ip = ZWEI::srv($k);
		}
		if($ip == ZWEI::srv('REMOTE_ADDR'))
			$ip = null;
		return $ip;
	}
}
?>