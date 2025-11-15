<?php
class ZDBCDriverMssql extends ZDBC
{
	protected
		$__drv_dclsname				= null;
	protected static
		$__drv_name					= 'MSSQL',
		$__drv_family				= 'mssql',
		$__drv_classname			= __class__,
		$__drv_required_zdbc_ver	= array('2','>='),
		$__drv_configutaion			= array('host','user','pass','base','pers','init'),
		$__drv_required_set			= array('base'),
		$__drv_required_notblank	= array('base'),
		$__drv_str_quote			= '',
		$__drv_obj_quote			= '[]';
	##
	public function __construct(&$x)
	{
		$this->__drv_dclsname = self::$__drv_classname;
		$this->__drv_caps = parent::c_persistent+parent::c_storedprocs;
		try{parent::__construct($x);}
		catch(Exception $e){throw $e;}
		if($this->is_dbh() and $this->check_config($x))
			$this->connect($x);
	}
	private function connect(&$cfg)
	{
		mssql_min_message_severity(17);
		if(empty($cfg['host']))
			$cfg['host'] = '(local)';
		if(!empty($cfg['user']) and isset($cfg['pass']))
			$this->dbh = (!empty($cfg['pers'])) ? @mssql_pconnect($cfg['host'],$cfg['user'],$cfg['pass']) : @mssql_connect($cfg['host'],$cfg['user'],$cfg['pass']);
		else
			$this->dbh = (!empty($cfg['pers'])) ? @mssql_pconnect($cfg['host']) : @mssql_connect($cfg['host']);
		if(is_resource($this->dbh))
			$this->is_connected = true;
		else
			throw new Exception($this->_error_string(),101);
		if(!mssql_select_db($cfg['base'],$this->dbh))
			throw new Exception($this->_error_string(),102);
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
		$this->rsh = mssql_query($this->query,$this->dbh);
		return $this->rsh;
	}
	protected function _exec()
	{
		return (@$this->_query()) ? true : false;
	}
	protected function _fetch($num=false)
	{
		return ($num) ? @mssql_fetch_row($this->rsh) : @mssql_fetch_assoc($this->rsh);
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
		return ($this->rsh) ? @mssql_num_rows($this->rsh) : false;
	}
	protected function _aff_rows()
	{
		return @mssql_rows_affected($this->dbh);
	}
	protected function _last_id()
	{
		return intval(mssql_result(mssql_query('SELECT SCOPE_IDENTITY()',$this->dbh),0,0));
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
		if(!$this->sph = mssql_init($name))
			return false;
	}
	protected function _sp_exec(&$in,&$out)
	{
		foreach($in as $k => $v)
		{
			$nt = (is_null($v)) ? true : false;
			if(is_null($v))
				$t = 0;
			elseif(is_int($v))
			{
				if($v > 0xFFFF)
					$t = SQLINT4;
				elseif($v > 0xFF)
					$t = SQLINT2;
				else
					$t = SQLINT1;
			}
			elseif(is_float($v))
				$t = SQLFLT4;
			elseif(is_bool($v))
			{
				$v = ($v) ? 1 : 0;
				$t = SQLBIT;
			}
			else
				$t = SQLTEXT;
			if(!mssql_bind($this->sph,"@$k",$v,$t,false,$nt))
				throw new Exception('ZDBCDriver::_sp_exec(): can\'t bind stored procedure param!',14);
		}
		foreach($out as $k => $v)
		{
			$ts = array(
			'int' => SQLINT4,
			'float' => SQLFLT4,
			'string' => SQLTEXT
			);
			if(!mssql_bind($this->sph,"@$k",$v[0],$ts[$v[1]],true))
				throw new Exception('ZDBCDriver::_sp_exec(): can\'t bind stored procedure param!',14);
		}
		return mssql_execute($this->sph);
	}
	protected function _is_result()
	{
		return !is_bool($this->rsh);
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
		/*$type = substr($q,0,6);
		if($type != 'select')
		{
			throw new Exception('ZDBCDriver::_limit(): limit clause emulation for non-select query is not implemented due to large amount of work required.',14);
			return false;
		}*/
		# TODO
		#$new = 'SELECT * FROM ( SELECT ROW_NUMBER() OVER (ORDER BY [ikeyid] ASC ) AS [ZDBCLIMIT],'.substr($q,6).' [ZdbcSqlsrvLimit] WHERE [ZDBCLIMIT] <= '.$l;
		#$_q = 'SELECT * FROM ( SELECT ROW_NUMBER() OVER (ORDER BY '.$q['order'].') AS '.$zl.', '.$_q.') '.$rs.' WHERE '.$zl.' <= '.$q['limit'];
		throw new Exception('ZDBCDriver::_limit(): not implemented due to large amount of work required.',14);
	}
	protected function _reset_statement()
	{
		## N/A
	}
	protected function _close_statement()
	{
		## N/A
	}
	protected function _free_result()
	{
		if($this->rsh and is_resource($this->rsh))
			return mssql_free_result($this->rsh);
		else
			return true;
	}
	protected function _close()
	{
		if(!$this->is_persistent and mssql_close($this->dbh))
			$this->is_connected = false;
	}
	protected function _error_string()
	{
		return mssql_get_last_message();
	}
	protected function _error_code()
	{
		return 0;
	}
	protected static function _backend_available(){return function_exists('mssql_connect');}
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