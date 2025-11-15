<?php
##============================================================================##
##              ZPage: ZCT Media Research Labs web paginator                  ##
##============================================================================##

# $ Rev.5 $

class ZPage
{
	private
		$_total		= null,
		$_pages		= null,
		$_per_page	= null;
	##
	public function __construct($entries=0,$per_page=30)
	{
		$this->_total = $entries;
		$this->_per_page = $per_page;
		$this->_pages = ($entries > $per_page) ? intval(ceil($entries/$per_page)) : 1;
	}
	public function __get($k)
	{
		if(in_array($k,array('pages','total_pages','totalpages')))
			return $this->_pages;
		elseif(in_array($k,array('entries','total_entries')))
			return $this->_total;
	}
	public function Offset($page=1)
	{
		if($this->_total < $this->_per_page or $page < 2)
			return 0;
		##
		if($page > $this->_pages)
			$page = $this->_pages;
		##
		return ($page-1)*$this->_per_page;
	}
	public function Nav($page=1,$url='',$pattern='%ST%',$simple=false)
	{
		$nav = array();
		if($simple)
		{
			for($c=1;$c<$this->_pages;$c++)
				$nav[] = $this->_wrapPageLink($c,($c == $page ? null : str_replace($pattern,$c,$url)));
		}
		else
		{
			$nav[] = $this->_wrapPageLink('&#171;',($page > 2 ? str_replace($pattern,1,$url) : null));
			$nav[] = $this->_wrapPageLink('&lt;',($page > 1 ? str_replace($pattern,$page-1,$url) : null));
			##
			$x = ($page > 2) ? $page-1 : $page;
			$x = ($page > 1 and $x == $page) ? $page-1 : $x;
			$y = ($page > 1) ? $page+2 : $page+3;
			if($y > $this->_pages)
				$y = $this->_pages+1;
			if(($x+3) > $y)
				$x = $y-(($y-$x)+1);
			if(1 > $x)
				$x = 1;
			for($c=$x;$c<$y;$c++)
				$nav[] = $this->_wrapPageLink($c,($c == $page ? null : str_replace($pattern,$c,$url)));
			##
			$nav[] = $this->_wrapPageLink('&gt;',($page < $this->_pages ? str_replace($pattern,$page+1,$url) : null));
			$nav[] = $this->_wrapPageLink('&#187;',($page+1 < $this->_pages ? str_replace($pattern,$this->_pages,$url) : null));
		}
		return implode('&nbsp;',$nav);
	}
	##
	private function _wrapPageLink($txt,$href=null,$braces=true)
	{
		$out = ($href) ? '<a class="zpl" href="'.$href.'">'.$txt.'</a>' : '<span class="zpl">'.$txt.'</span>';
		if($braces)
			$out = "[$out]";
		return $out;
	}
	## TODO: JavaScript wrappers from DS CMS project
}
?>