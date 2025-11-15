<?php
class ZDBCDriverOdbc extends ZDBC
{
	protected
		$__drv_dclsname				= null;
	protected static
		$__drv_name					= null,#'MSSQL',
		$__drv_family				= null,#'mssql',
		$__drv_classname			= __class__,
		$__drv_required_zdbc_ver	= array('2','>='),
		$__drv_configutaion			= array('fmly','dsn','user','pass','init'),
		$__drv_required_set			= array('fmly','dsn'),
		$__drv_required_notblank	= array('fmly','dsn'),
		$__drv_str_quote			= null,#'',
		$__drv_obj_quote			= null;#'[]';
	##
	public function __construct(&$x)
	{
		$this->__drv_dclsname = self::$__drv_classname;
		$this->__drv_caps = parent::c_persistent+parent::c_prepared+parent::c_transactions;
		try{parent::__construct($x);}
		catch(Exception $e){throw $e;}
		##
		if($this->is_dbh() and $this->check_config($x)){
			$GLOBALS['__zdbc_odbc_drvname']		= self::$__drv_name			= 'ODBC::'.$x['fmly'];
			$GLOBALS['__zdbc_odbc_drvfamily']	= self::$__drv_family		= $x['fmly'];
			$GLOBALS['__zdbc_odbc_drvstrquote']	= self::$__drv_str_quote	= "'";
			#
			if('mssql' == $x['fmly'])
				self::$__drv_obj_quote	= '[]';
			elseif('mysql' == $x['fmly'])
				self::$__drv_obj_quote	= '`';
			elseif('pgsql' == $x['fmly'])
				self::$__drv_obj_quote	= "'";
			else
				self::$__drv_obj_quote	= '';
			$GLOBALS['__zdbc_odbc_drvobjquote'] = self::$__drv_obj_quote;
			#
			$this->connect($x);
		}
		elseif(!$this->is_dbh()){
			self::$__drv_name		= $GLOBALS['__zdbc_odbc_drvname'];
			self::$__drv_family		= $GLOBALS['__zdbc_odbc_drvfamily'];
			self::$__drv_str_quote	= $GLOBALS['__zdbc_odbc_drvstrquote'];
			self::$__drv_obj_quote	= $GLOBALS['__zdbc_odbc_drvobjquote'];
		}
	}
	private function connect(&$cfg)
	{
		if(!empty($cfg['user']) and isset($cfg['pass']))
			$this->dbh = (!empty($cfg['pers'])) ? @odbc_pconnect($cfg['dsn'],$cfg['user'],$cfg['pass']) : @odbc_connect($cfg['dsn'],$cfg['user'],$cfg['pass']);
		else
			$this->dbh = (!empty($cfg['pers'])) ? @odbc_pconnect($cfg['dsn']) : @odbc_connect($cfg['dsn']);
		if(is_resource($this->dbh))
			$this->is_connected = true;
		else
			throw new Exception($this->_error_string(),101);
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
		$this->rsh = odbc_exec($this->dbh,$this->query);
		return $this->rsh;
	}
	protected function _exec()
	{
		return (@$this->_query()) ? true : false;
	}
	protected function _fetch($num=false)
	{
		$r = null;
		if(is_resource($this->sth))
			$r = @odbc_fetch_array($this->sth);
		if(!$r and is_resource($this->rsh))
			$r = @odbc_fetch_array($this->rsh);
		if($r and $num)
			return array_values($r);
		return $r;
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
		if($this->sth)
			return @odbc_num_rows($this->sth);
		elseif($this->rsh)
			return @odbc_num_rows($this->rsh);
		else
			return false;
	}
	protected function _aff_rows()
	{
		return $this->_num_rows();
	}
	protected function _last_id()
	{
		if('mssql' == self::$__drv_family)
			return intval(odbc_result(odbc_exec('SELECT SCOPE_IDENTITY()',$this->dbh),0));
		elseif('mysql' == self::$__drv_family)
			return intval(odbc_result(odbc_exec('SELECT LAST_INSERT_ID()',$this->dbh),0));
		else
			return -1;
	}
	protected function _prepare()
	{
		return @odbc_prepare($this->dbh,$this->query);
	}
	protected function _execute(&$p)
	{
		return ($this->sth and is_resource($this->sth)) ? @odbc_execute($this->sth,$p) : false;
	}
	protected function _begin()
	{
		return $this->__exec('BEGIN TRANSACTION') or $this->__exec('BEGIN');
	}
	protected function _rollback()
	{
		return @odbc_rollback($this->dbh);
	}
	protected function _commit()
	{
		return @odbc_commit($this->dbh);
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
		return !is_bool($this->rsh);
	}
	protected function _escape($s)
	{
		if(!$s)
			return '';
		if(is_numeric($s))
			return $s;
		##
		$safe = array(0x07,0x0A,0x0D,0x20,0x5F);
		for($c=0x30;$c<0x3A;$c++)
			$safe[] = $c;
		for($c=0x41;$c<0x5B;$c++)
			$safe[] = $c;
		for($c=0x61;$c<0x7B;$c++)
			$safe[] = $c;
		##
		for($c=0;$c<0x80;$c++)
			if(!in_array($c,$safe))
				$s = str_replace(chr($c),sprintf('\\x%08X',$c),$s);
		##
		return $s;
	}
	protected function _wrap($p,$obj=false)
	{
		if($obj){
			$obj_wrap_l = substr(self::$__drv_obj_quote,0,1);
			$obj_wrap_r = (1 < strlen(self::$__drv_obj_quote)) ? substr(self::$__drv_obj_quote,1,1) : self::$__drv_obj_quote;
			$p = str_replace($obj_wrap_l,'',$p);
			$p = str_replace($obj_wrap_r,'',$p);
			$p = $obj_wrap_l.$p.$obj_wrap_r;
		}
		else{
			$str_wrap_l = substr(self::$__drv_str_quote,0,1);
			$str_wrap_r = (1 < strlen(self::$__drv_str_quote)) ? substr(self::$__drv_str_quote,1,1) : self::$__drv_str_quote;
			$p = $str_wrap_l.$this->_escape($p).$str_wrap_r;
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
		if('mysql' == self::$__drv_family)
			return "$q LIMIT $o,$l";
		if('sqlite' == self::$__drv_family){
			$o = ($o) ? ' OFFSET '.$o : '';
			return "$q LIMIT $l$o";
		}
		else
			throw new Exception('ZDBCDriver::_limit(): not implemented due to large amount of work required.',14);
	}
	protected function _reset_statement()
	{
		if($this->sth and is_resource($this->sth))
			return odbc_free_result($this->sth);
		return true;
	}
	protected function _close_statement()
	{
		if($this->sth and is_resource($this->sth))
			return odbc_free_result($this->sth);
		return true;
	}
	protected function _free_result()
	{
		if($this->sth and is_resource($this->sth))
			return odbc_free_result($this->sth);
		elseif($this->rsh and is_resource($this->rsh))
			return odbc_free_result($this->rsh);
		else
			return true;
	}
	protected function _close()
	{
		if(!$this->is_persistent and odbc_close($this->dbh))
			$this->is_connected = false;
	}
	protected function _error_string()
	{
		return odbc_errormsg($this->dbh);
	}
	protected function _error_code()
	{
		return odbc_error($this->dbh);
	}
	protected static function _backend_available(){return function_exists('odbc_connect');}
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