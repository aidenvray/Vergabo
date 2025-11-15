<?php
class ZDBCDriverSqlite extends ZDBC
{
	protected
		$__drv_dclsname				= null;
	protected static
		$__drv_name					= 'SQLite',
		$__drv_family				= 'sqlite',
		$__drv_classname			= __class__,
		$__drv_required_zdbc_ver	= array('2','>='),
		$__drv_configutaion			= array('base','prms','init'),
		$__drv_required_set			= array('base'),
		$__drv_required_notblank	= array('base'),
		$__drv_str_quote			= "'",
		$__drv_obj_quote			= '';
	##
	public function __construct(&$x)
	{
		$this->__drv_dclsname = self::$__drv_classname;
		$this->__drv_caps = parent::c_mfetch+parent::c_cfetch;
		try{parent::__construct($x);}
		catch(Exception $e){throw $e;}
		if($this->is_dbh() and $this->check_config($x))
			$this->connect($x);
	}
	private function connect(&$cfg)
	{
		$perms = (!empty($cfg['prms'])) ? $cfg['prms'] : 0666;
		$error_msg = '';
		$this->dbh = new SQLiteDatabase($cfg['base'],$perms,$error_msg);
		if(!$error_msg)
			$this->is_connected = true;
		else
			throw new Exception($error_msg,101);
		if(!empty($cfg['init']) and !$this->__exec("{$cfg['init']}"))
			throw new Exception($this->_error_string(),103);
	}
	private function __exec($q)
	{
		$this->query = $q;
		$r = @$this->_exec();
		$this->query = '';
		$this->rsh = null;
		return ($r) ? true : false;
	}
	protected function _query()
	{
		$this->rsh = @$this->dbh->query($this->query);
		return $this->rsh;
	}
	protected function _exec()
	{
		return @$this->dbh->queryExec($this->query);
	}
	protected function _fetch($num=false)
	{
		if($this->rsh)
			return $this->rsh->fetch(($num)?SQLITE_NUM:SQLITE_ASSOC);
		else
			return false;
	}
	protected function _fetch_all($num=false)
	{
		if($this->rsh)
			return $this->rsh->fetchAll(($num)?SQLITE_NUM:SQLITE_ASSOC);
		else
			return false;
	}
	protected function _fetch_col($offset=0)
	{
		if($this->rsh)
			return $this->rsh->column($offset);
		else
			return false;
	}
	protected function _num_rows()
	{
		return ($this->rsh) ? $this->rsh->numRows() : false;
	}
	protected function _aff_rows()
	{
		return $this->dbh->changes();
	}
	protected function _last_id()
	{
		return $this->dbh->lastInsertRowid();
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
		return (!is_bool($this->rsh)) ? $this->rsh->valid() : false;
	}
	protected function _escape($s)
	{
		return sqlite_escape_string($s);
	}
	protected function _wrap($p,$obj=false)
	{
		if($obj)
			return $p;
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
		$this->query->sth_end();
		return true;
	}
	protected function _close_statement()
	{
		$this->sth = null;
		return true;
	}
	protected function _free_result()
	{
		$this->rsh = null;
		return true;
	}
	protected function _close()
	{
		$this->dbh = null;
	}
	protected function _error_string()
	{
		return sqlite_error_string($this->dbh->lastError());
	}
	protected function _error_code()
	{
		return $this->dbh->lastError();
	}
	protected static function _backend_available(){return class_exists('SQLiteDatabase');}
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