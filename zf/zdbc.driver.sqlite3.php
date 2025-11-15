<?php
class ZDBCDriverSqlite3 extends ZDBC
{
	protected
		$__drv_dclsname				= null;
	protected static
		$__drv_name					= 'SQLite 3',
		$__drv_family				= 'sqlite',
		$__drv_classname			= __class__,
		$__drv_required_zdbc_ver	= array('2','>='),
		$__drv_configutaion			= array('base','ifnx','init','readonly'),
		$__drv_required_set			= array('base'),
		$__drv_required_notblank	= array('base'),
		$__drv_str_quote			= "'",
		$__drv_obj_quote			= '`';
	private
		$_rshFilled					= false;
	##
	public function __construct(&$x)
	{
		$this->__drv_dclsname = self::$__drv_classname;
		$this->__drv_caps = parent::c_prepared+parent::c_named;
		try{parent::__construct($x);}
		catch(Exception $e){throw $e;}
		if($this->is_dbh() and $this->check_config($x))
			$this->connect($x);
	}
	private function connect(&$cfg)
	{
		$f = (!empty($cfg['readonly'])) ? SQLITE3_OPEN_READONLY : SQLITE3_OPEN_READWRITE;
		if(!isset($cfg['ifnx']) or $cfg['ifnx'])
			$f += SQLITE3_OPEN_CREATE;
		try{$this->dbh = new SQLite3($cfg['base'],$f);$this->is_connected = true;}
		catch(Exception $x){throw $x;}
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
		$this->rsh = $this->dbh->query($this->query);
		$this->_rshFilled = ($this->rsh instanceof SQLite3Result and $this->rsh->numColumns());
		return $this->rsh;
	}
	protected function _exec()
	{
		$this->_rshFilled = false;
		return @$this->dbh->exec($this->query);
	}
	protected function _fetch($num=false)
	{
		if($this->rsh)
			return $this->rsh->fetchArray(($num)?SQLITE3_NUM:SQLITE3_ASSOC);
		else
			return false;
	}
	protected function _fetch_all($num=false)
	{
		$out = array();
		while($r = $this->_fetch($num))
			$out[] = $r;
		return $out;
	}
	protected function _fetch_col($offset=0)
	{
		## N/A
	}
	protected function _num_rows()
	{
		if($this->rsh instanceof SQLite3Result and $this->rsh->numColumns()){
			$rows = 0;
			while($this->rsh->fetchArray(SQLITE3_NUM))
				$rows++;
			$this->rsh->reset();
			return $rows;
		}
		else
			return 0;
	}
	protected function _aff_rows()
	{
		return $this->dbh->changes();
	}
	protected function _last_id()
	{
		return $this->dbh->lastInsertRowID();
	}
	protected function _prepare()
	{
		return $this->dbh->prepare($this->query);
	}
	protected function _execute(&$p)
	{
		foreach($p as $k => $v)
		{
			if(!$this->is_named)
				$k = intval($k)+1;
			if(is_null($v))
				$t = SQLITE3_NULL;
			elseif(is_int($v))
				$t = SQLITE3_INTEGER;
			elseif(is_float($v))
				$t = SQLITE3_FLOAT;
			else
				$t = SQLITE3_TEXT;
			$this->sth->bindValue($k,$v,$t);
		}
		## TODO: BIND!
		$this->rsh = $this->sth->execute();
		$this->_rshFilled = ($this->rsh instanceof SQLite3Result and $this->rsh->numColumns());
		if(!$this->rsh)
		{
			$this->rsh = null;
			return false;
		}
		return true;
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
		if($this->rsh instanceof SQLite3Result and $this->rsh->numColumns())
			return true;
		else
			return false;
	}
	protected function _escape($s)
	{
		return $this->dbh->escapeString($s);
	}
	protected function _wrap($p,$obj=false)
	{
		if($obj)
			return '`'.$p.'`';
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
		return $this->sth->reset();
	}
	protected function _close_statement()
	{
		if($this->sth instanceof SQLite3Stmt)
			$this->sth = null; #return @$this->sth->close();
		return true;
	}
	protected function _free_result()
	{
		if($this->rsh instanceof SQLite3Result and $this->_rshFilled)
			$this->rsh->finalize();
	}
	protected function _close()
	{
		if($this->dbh)
			$this->dbh->close();
	}
	protected function _error_string()
	{
		if($this->dbh)
			return $this->dbh->lastErrorMsg();
		else
			return 'Unknown error, no database opened.';
	}
	protected function _error_code()
	{
		if($this->dbh)
			return $this->dbh->lastErrorCode();
		else
			return -13;
	}
	protected static function _backend_available(){return class_exists('SQLite3');}
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