<?php
class ZDBCDriverPgsql extends ZDBC
{
	protected
		$__drv_dclsname				= null;
	protected static
		$__drv_name					= 'PostgreSQL',
		$__drv_family				= 'pgsql',
		$__drv_classname			= __class__,
		$__drv_required_zdbc_ver	= array('2','>='),
		$__drv_configutaion			= array('host','user','pass','base','pers','init','chrx'),
		$__drv_required_set			= array('host','pass','base'),
		$__drv_required_notblank	= array('host','base'),
		$__drv_str_quote			= "'",
		$__drv_obj_quote			= "'";
	private
		$_aff_rows					= -17;
	##
	public function __construct(&$x)
	{
		$this->__drv_dclsname = self::$__drv_classname;
		$this->__drv_caps = (version_compare(PHP_VERSION,'5.1.0','<')) ? parent::c_mfetch+parent::c_cfetch+parent::c_persistent : parent::c_mfetch+parent::c_cfetch+parent::c_persistent+parent::c_prepared;
		try{parent::__construct($x);}
		catch(Exception $e){throw $e;}
		if($this->is_dbh() and $this->check_config($x))
			$this->connect($x);
	}
	private function connect(&$cfg)
	{
		$dsn = "host='{$cfg['host']}' ";
		if(!empty($cfg['port']))
			$dsn .= "port='{$cfg['port']}' ";
		if(!empty($cfg['user']) and $cfg['user'] != $cfg['base'])
			$dsn .= "user='{$cfg['user']}' ";
		if(!empty($cfg['pass']))
			$dsn .= "password='{$cfg['pass']}' ";
		$dsn .= "dbname='{$cfg['base']}' ";
		if(!empty($cfg['chrx']))
			$dsn .= "options='--client_encoding={$cfg['chrx']}' ";
		##
		$this->dbh = (!empty($cfg['pers'])) ? @pg_pconnect($dsn) : @pg_connect($dsn);
		if(is_resource($this->dbh))
			$this->is_connected = true;
		else
			throw new Exception(pg_errormessage(),101);
		##
		if(!empty($cfg['init']) and !$this->__exec("{$cfg['init']}"))
			throw new Exception($this->_error_string(),103);
	}
	private function __exec($q)
	{
		$this->query = $q;
		$r = @$this->_query();
		$this->query = '';
		$this->rsh = null;
		return ($r) ? true : false;
	}
	protected function _query()
	{
		$this->_aff_rows = -17;
		$this->rsh = @pg_query($this->dbh,$this->query);
		return $this->rsh;
	}
	protected function _exec()
	{
		$this->_aff_rows = -17;
		$this->_query();
		if(is_resource($this->rsh))
			$this->_aff_rows = pg_affected_rows($this->rsh);
		return (false !== $this->rsh) ? true : false;
	}
	protected function _fetch($num=false)
	{
		return ($num) ? pg_fetch_row($this->rsh) : pg_fetch_assoc($this->rsh);
	}
	protected function _fetch_all($num=false)
	{
		if($num)
		{
			$out = array();
			while($r = $this->_fetch(true))
				$out[] = $r;
			return $out;
		}
		else
			return pg_fetch_all($this->rsh);
	}
	protected function _fetch_col($offset=0)
	{
		return pg_fetch_all_columns($this->rsh,$offset);
	}
	protected function _num_rows()
	{
		return (is_resource($this->rsh)) ? pg_num_rows($this->rsh) : -1;
	}
	protected function _aff_rows()
	{
		return $this->_aff_rows;
	}
	protected function _last_id()
	{
		return pg_last_oid($this->rsh);
	}
	protected function _prepare()
	{
		$k = 'zdbcprpstmt'.dechex(crc32($this->query));
		if(!isset($GLOBALS['__zdbcprpstmtz']))
			$GLOBALS['__zdbcprpstmtz'] = array();
		if(!isset($GLOBALS['__zdbcprpstmtz'][$k])){
			$q = explode('?',$this->query);
			for($c=1;$c<count($q);$c++)
				$q[$c-1] .= '$'.$c;
			$GLOBALS['__zdbcprpstmtz'][$k] = implode('',$q);
			$this->sth = pg_prepare($this->dbh,$k,$GLOBALS['__zdbcprpstmtz'][$k]);
		}
		else
			$this->_reset_statement();
		return ($this->sth) ? true : false;
	}
	protected function _execute(&$p)
	{
		$this->_aff_rows = -17;
		if($this->rsh = pg_execute($this->dbh,'zdbcprpstmt'.dechex(crc32($this->query)),$p)){
			$this->_aff_rows = pg_affected_rows($this->rsh);
			return true;
		}
		else
			return $this->rsh;
	}
	protected function _begin()
	{
		## N/A
	}
	protected function _rollback()
	{
		## N/A
	}
	protected function _commit()
	{
		## N/A
	}
	protected function _sp_init($name)
	{
		## N/A
	}
	protected function _sp_exec(&$in,&$out)
	{
		## N/A
	}
	protected function _is_result()
	{
		return is_resource($this->rsh);
	}
	protected function _escape($s)
	{
		return pg_escape_string($s);
	}
	protected function _wrap($p,$obj=false)
	{
		if(version_compare(PHP_VERSION,'5.4.4','>='))
			return ($obj) ? pg_escape_identifier($this->dbh,$p) : pg_escape_literal($this->dbh,$p);
		elseif($obj)
			return '"'.str_replace('"','""',$p).'"';
		else
			return "'".$this->_escape($p)."'";
	}
	protected function _limit($q,$l,$o=0,$order=null)
	{
		$o = ($o) ? ' OFFSET '.$o : '';
		return "$q LIMIT $l$o";
	}
	protected function _reset_statement()
	{
		return $this->_free_result();
	}
	protected function _close_statement()
	{
		$this->sth = null;
		if($this->__drv_caps & self::c_prepared){
			$ds = @pg_query($this->dbh,'DEALLOCATE zdbcprpstmt'.dechex(crc32($this->query)));
			if($ds and isset($GLOBALS['__zdbcprpstmtz']['zdbcprpstmt'.dechex(crc32($this->query))]))
				unset($GLOBALS['__zdbcprpstmtz']['zdbcprpstmt'.dechex(crc32($this->query))]);
			return $ds;
		}
		else
			return true;
	}
	protected function _free_result()
	{
		return (is_resource($this->rsh)) ? pg_free_result($this->rsh) : false;
	}
	protected function _close()
	{
		if(is_resource($this->dbh))
			@pg_close($this->dbh);
	}
	protected function _error_string()
	{
		if($this->_is_result())
			return pg_result_error($this->rsh);
		else
			return pg_last_error($this->dbh);
	}
	protected function _error_code()
	{
		return 0;
	}
	protected static function _backend_available(){return function_exists('pg_connect');}
	protected static function _query_driver_info()
	{
		return array(
			self::$__drv_name, # backend subsystem name
			self::$__drv_family, # database family
			self::_backend_available(), # is backend available on server
			self::$__drv_required_zdbc_ver, # array(zdbc_version_reference,condition to compare)/equals to version_compare's 2nd and 3rd param
			self::$__drv_configutaion, # configuration fields AVAILABLE list
			self::$__drv_required_set, # configuration fields REQUIRED list
			self::$__drv_required_notblank, # configuration fields THAT MUST NOT BE BLANK list
			self::$__drv_str_quote # strings quotation
		);
	}
}
?>