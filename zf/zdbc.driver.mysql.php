<?php
class ZDBCDriverMysql extends ZDBC
{
	protected
		$__drv_dclsname				= null;
	protected static
		$__drv_name					= 'MySQL',
		$__drv_family				= 'mysql',
		$__drv_classname			= __class__,
		$__drv_required_zdbc_ver	= array('2','>='),
		$__drv_configutaion			= array('host','user','pass','base','pers','init','chrx'),
		$__drv_required_set			= array('host','user','pass','base'),
		$__drv_required_notblank	= array('host','user','base'),
		$__drv_str_quote			= "'",
		$__drv_obj_quote			= '`';
	##
	public function __construct(&$x)
	{
		$this->__drv_dclsname = self::$__drv_classname;
		$this->__drv_caps = parent::c_persistent+parent::c_transactions;
		try{parent::__construct($x);}
		catch(Exception $e){throw $e;}
		if($this->is_dbh() and $this->check_config($x))
			$this->connect($x);
	}
	private function connect(&$cfg)
	{
		$this->dbh = (!empty($cfg['pers'])) ? @mysql_pconnect($cfg['host'],$cfg['user'],$cfg['pass']) : @mysql_connect($cfg['host'],$cfg['user'],$cfg['pass']);
		if(is_resource($this->dbh))
			$this->is_connected = true;
		else
			throw new Exception(mysql_error(),mysql_errno());
		if(!mysql_select_db($cfg['base'],$this->dbh))
			throw new Exception($this->_error_string(),$this->_error_code());
		if(!empty($cfg['chrx']) and !$this->__exec("SET NAMES {$cfg['chrx']}"))
			throw new Exception($this->_error_string(),$this->_error_code());
		if(!empty($cfg['init']) and !$this->__exec("{$cfg['init']}"))
			throw new Exception($this->_error_string(),$this->_error_code());
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
		$this->rsh = mysql_query($this->query,$this->dbh);
		return $this->rsh;
	}
	protected function _exec()
	{
		return ($this->_query()) ? true : false;
	}
	protected function _fetch($num=false)
	{
		return ($num) ? mysql_fetch_row($this->rsh) : mysql_fetch_assoc($this->rsh);
	}
	protected function _fetch_all($num=false)
	{
		## N/A
	}
	protected function _fetch_col($offset=0)
	{
		## N/A
	}
	protected function _num_rows()
	{
		return (is_resource($this->rsh)) ? mysql_num_rows($this->rsh) : false;
	}
	protected function _aff_rows()
	{
		return mysql_affected_rows($this->dbh);
	}
	protected function _last_id()
	{
		return mysql_insert_id($this->dbh);
	}
	protected function _prepare()
	{
		## N/A
	}
	protected function _execute(&$p)
	{
		## N/A
	}
	protected function _begin()
	{
		if(mysql_query('SET autocommit=0',$this->dbh))
			return mysql_query('BEGIN',$this->dbh);
		else
			return false;
	}
	protected function _rollback()
	{
		$r = mysql_query('ROLLBACK',$this->dbh);
		mysql_query('SET autocommit=1',$this->dbh);
		return $r;
	}
	protected function _commit()
	{
		$r = mysql_query('COMMIT',$this->dbh);
		mysql_query('SET autocommit=1',$this->dbh);
		return $r;
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
		return mysql_real_escape_string($s,$this->dbh);
	}
	protected function _wrap($p,$obj=false)
	{
		if($obj)
			return "`$p`";
		else
			return "'".$this->_escape($p)."'";
	}
	protected function _limit($q,$l,$o=0,$order=null)
	{
		return "$q LIMIT $o,$l";
	}
	protected function _reset_statement()
	{
		$this->query = $this->query_src;
		return true;
	}
	protected function _close_statement()
	{
		$this->sth = null;
		return true;
	}
	protected function _free_result()
	{
		if(is_resource($this->rsh))
			return mysql_free_result($this->rsh);
	}
	protected function _close()
	{
		mysql_close($this->dbh);
	}
	protected function _error_string()
	{
		return mysql_error($this->dbh);
	}
	protected function _error_code()
	{
		return mysql_errno($this->dbh);
	}
	protected static function _backend_available(){return function_exists('mysql_connect');}
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