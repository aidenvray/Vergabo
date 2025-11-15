<?php
class ZDBCDriverSqlsrv extends ZDBC
{
	protected
		$__drv_dclsname				= null;
	protected static
		$__drv_name					= 'M$ SQLSRV',
		$__drv_family				= 'mssql',
		$__drv_classname			= __class__,
		$__drv_required_zdbc_ver	= array('2','>='),
		$__drv_configutaion			= array('host','user','pass','base','init','chrx'),
		$__drv_required_set			= array('base'),
		$__drv_required_notblank	= array('base'),
		$__drv_str_quote			= '',
		$__drv_obj_quote			= '[]';
	private
		$_sth_bounds				= null,
		$_sth_ready					= false,
		$_aff_rows					= -17;
	##
	public function __construct(&$x)
	{
		$this->__drv_dclsname = self::$__drv_classname;
		$this->__drv_caps = parent::c_prepared+parent::c_transactions;
		try{parent::__construct($x);}
		catch(Exception $e){throw $e;}
		if($this->is_dbh() and $this->check_config($x))
			$this->connect($x);
	}
	private function connect(&$cfg)
	{
		$srv = (!empty($cfg['host'])) ? $cfg['host'] : '(local)';
		$ci = array('Database'=>$cfg['base']);
		if(!empty($cfg['user']))
			$ci['UID'] = $cfg['user'];
		if(!empty($cfg['pass']))
			$ci['PWD'] = $cfg['pass'];
		if(!empty($cfg['chrx']))
			$ci['CharacterSet'] = $cfg['chrx'];
		$this->dbh = @sqlsrv_connect($srv,$ci);
		if(is_resource($this->dbh))
			$this->is_connected = true;
		else
			throw new Exception($this->_error_string().', code[s] '.$this->_error_code(),2);
		if(!empty($cfg['init']) and !$this->__exec("{$cfg['init']}"))
			throw new Exception($this->_error_string().', code[s] '.$this->_error_code(),4);
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
		$this->rsh = @sqlsrv_query($this->dbh,$this->query);
		return $this->rsh;
	}
	protected function _exec()
	{
		$this->_aff_rows = -17;
		if($this->_query())
			$this->_aff_rows = sqlsrv_rows_affected($this->rsh);
		return ($this->rsh) ? true : false;
	}
	protected function _fetch($num=false)
	{
		if(is_resource($this->rsh))
			return sqlsrv_fetch_array($this->rsh,($num)?SQLSRV_FETCH_NUMERIC:SQLSRV_FETCH_ASSOC);
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
		if(is_resource($this->sth))
			return sqlsrv_num_rows($this->sth);
		elseif(is_resource($this->rsh))
			return sqlsrv_num_rows($this->rsh);
		else
			return 0;
	}
	protected function _aff_rows()
	{
		return $this->_aff_rows;
	}
	protected function _last_id()
	{
		list($x) = sqlsrv_fetch_array(sqlsrv_query($this->dbh,'SELECT SCOPE_IDENTITY()'),SQLSRV_FETCH_NUMERIC);
		return $x;
	}
	protected function _prepare()
	{
		## TODO: DEFER IN DEFER LAL!
		$this->_sth_bounds = null;
		$this->_sth_ready = false;
		if($stx = sqlsrv_prepare($this->dbh,$this->query))
		{
			sqlsrv_free_stmt($stx);
			return true;
		}
		return false;
	}
	protected function _execute(&$p)
	{
		if(!is_array($this->_sth_bounds) or !count($this->_sth_bounds))
			$this->_sth_bounds = array();
		foreach($p as $k => &$v)
			$this->_sth_bounds[$k] = &$v;
		if(!$this->_sth_ready)
		{
			$this->sth = sqlsrv_prepare($this->dbh,$this->query,$this->_sth_bounds,array('Scrollable'=>SQLSRV_CURSOR_STATIC));
			$this->_sth_ready = true;
		}
		$this->rsh = sqlsrv_execute($this->sth);
		if(is_resource($this->rsh))
			$this->_aff_rows = sqlsrv_rows_affected($this->rsh);
		if(1 > $this->_aff_rows)
			$this->_aff_rows = -17;
		return ($this->rsh) ? true : false;
		#if($x = sqlsrv_execute($this->sth))
			#return $x;
		# TODO: check this out!
		#debug_zval_dump(sqlsrv_errors()[0]['message']);
		#var_dump(sqlsrv_errors(),$this->query);
		#exit;
	}
	protected function _begin()
	{
		return sqlsrv_begin_transaction($this->dbh);
	}
	protected function _rollback()
	{
		return sqlsrv_rollback($this->dbh);
	}
	protected function _commit()
	{
		return sqlsrv_commit($this->dbh);
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
		$h = ($this->rsh) ? $this->rsh : ($this->sth ? $this->sth : null);
		return ($h) ? (bool)sqlsrv_num_fields($h) : false;
	}
	protected function _escape($s)
	{
		list($x) = unpack('H*0',$s);
		return '0x'.strtoupper($x);
	}
	protected function _wrap($p,$obj=false)
	{
		$p = trim($p);
		if($obj)
		{
			if(strpos($p,'[') or strpos($p,']'))
				$p = preg_replace('/[\[\]]/','',$p);
			if(strpos($p,'[') === false)
				$p = '['.$p;
			if(strpos($p,']') === false)
				$p .= ']';
		}
		else
		{
			if(!preg_match('/^\s*(\'.+?\'|".+?")\s*$/',$p)) ## if not 'param' or "param"
			{
				if(preg_match('/^\s*[^\'"]+[\'"]+[^\'"]+\s*$/',$p)) # ' or " in middle-string
					$p = $this->_escape($p);
				elseif(false !== ($x = strpos($p,"'"))) # '
				{
					if($x == strlen($p)-1)
						$p = "'$p";
					else
						$p = "$p'";
				}
				elseif(false !== ($x = strpos($p,'"'))) # "
				{
					if($x == strlen($p)-1)
						$p = "\"$p";
					else
						$p = "$p\"";
				}
				elseif(false === strpos($p,"'"))
					$p = "'$p'";
				elseif(false === strpos($p,'"'))
					$p = "\"$p\"";
			}
		}
		return $p;
	}
	protected function _limit($q,$l,$o=0,$order=null)
	{
		throw new Exception('ZDBCDriver::_limit(): not implemented due to large amount of work required.',14);
	}
	protected function _reset_statement()
	{
		if(is_resource($this->sth))
			return sqlsrv_cancel($this->sth);
		else
			return false;
	}
	protected function _close_statement()
	{
		if(is_resource($this->sth))
			return sqlsrv_free_stmt($this->sth);
		else
			return false;
	}
	protected function _free_result()
	{
		if(is_resource($this->rsh))
			return sqlsrv_free_stmt($this->rsh);
		else
			return false;
	}
	protected function _close()
	{
		if($this->dbh)
			sqlsrv_close($this->dbh);
	}
	protected function _error_string()
	{
		$e = sqlsrv_errors();
		if(!is_array($e))
			return '';
		$ex = array();
		foreach($e as $x)
			$ex[] = $x['message'];
		return implode("\n",$ex);
	}
	protected function _error_code()
	{
		$e = sqlsrv_errors();
		if(!is_array($e))
			return 0;
		$ex = array();
		foreach($e as $x)
			$ex[] = $x['code'];
		return implode(",",$ex);
	}
	protected static function _backend_available(){return function_exists('sqlsrv_connect');}
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