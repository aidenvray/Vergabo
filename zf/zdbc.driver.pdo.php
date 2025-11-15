<?php
class ZDBCDriverPdo extends ZDBC
{
	protected
		$__drv_dclsname				= null;
	protected static
		$__drv_name					= null,#'MSSQL',
		$__drv_family				= null,#'mssql',
		$__drv_classname			= __class__,
		$__drv_required_zdbc_ver	= array('2','>='),
		$__drv_configutaion			= array('fmly','dsn','user','pass','pers','init'),
		$__drv_required_set			= array('fmly','dsn'),
		$__drv_required_notblank	= array('fmly','dsn'),
		$__drv_str_quote			= null,#'',
		$__drv_obj_quote			= null;#'[]';
	##
	public function __construct(&$x)
	{
		$this->__drv_dclsname = self::$__drv_classname;
		$this->__drv_caps = parent::c_persistent+parent::c_prepared+parent::c_named+parent::c_transactions+parent::c_mfetch+parent::c_cfetch;
		try{parent::__construct($x);}
		catch(Exception $e){throw $e;}
		##
		if($this->is_dbh() and $this->check_config($x)){
			$GLOBALS['__zdbc_pdo_drvname']		= self::$__drv_name			= 'PDO::'.$x['fmly'];
			$GLOBALS['__zdbc_pdo_drvfamily']	= self::$__drv_family		= $x['fmly'];
			$GLOBALS['__zdbc_pdo_drvstrquote']	= self::$__drv_str_quote	= "'";
			#
			if('mssql' == $x['fmly'])
				self::$__drv_obj_quote	= '[]';
			elseif('mysql' == $x['fmly'] or 'sqlite:' == substr($x['dsn'],0,7))
				self::$__drv_obj_quote	= '`';
			elseif('pgsql' == $x['fmly'])
				self::$__drv_obj_quote	= "'";
			else
				self::$__drv_obj_quote	= '';
			$GLOBALS['__zdbc_pdo_drvobjquote'] = self::$__drv_obj_quote;
			#
			if('mysql' == $x['fmly'])
				self::$__drv_configutaion[] = 'chrx';
			#
			$this->connect($x);
		}
		elseif(!$this->is_dbh()){
			self::$__drv_name		= $GLOBALS['__zdbc_pdo_drvname'];
			self::$__drv_family		= $GLOBALS['__zdbc_pdo_drvfamily'];
			self::$__drv_str_quote	= $GLOBALS['__zdbc_pdo_drvstrquote'];
			self::$__drv_obj_quote	= $GLOBALS['__zdbc_pdo_drvobjquote'];
		}
	}
	private function connect(&$cfg)
	{
		$user = $pass = null;
		$optz = null;
		if(!empty($cfg['pers']))
			$optz = array(PDO::ATTR_PERSISTENT=>true);
		if(!empty($cfg['user']) and isset($cfg['pass'])){
			$user = $cfg['user'];
			$pass = $cfg['pass'];
		}
		##
		if('mysql' == $cfg['fmly'] and !empty($cfg['chrx']) and false === stripos($cfg['dsn'],'charset'))
			$cfg['dsn'] .= ';charset='.$cfg['chrx'];
		##
		try{
			if($optz)
				$this->dbh = new PDO($cfg['dsn'],$user,$pass,$optz);
			elseif($user)
				$this->dbh = new PDO($cfg['dsn'],$user,$pass);
			else
				$this->dbh = new PDO($cfg['dsn']);
			$this->is_connected = true;
		}
		catch(PDOException $x){
			throw new Exception($x->getMessage(),$x->getCode(),$x->getPrevious());
		}
		##
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
		return @$this->dbh->exec($this->query);
	}
	protected function _fetch($num=false)
	{
		if($this->sth instanceof PDOStatement)
			return $this->sth->fetch(($num)?PDO::FETCH_NUM:PDO::FETCH_ASSOC);
		elseif($this->rsh instanceof PDOStatement)
			return $this->rsh->fetch(($num)?PDO::FETCH_NUM:PDO::FETCH_ASSOC);
		return false;
	}
	protected function _fetch_all($num=false)
	{
		if($this->sth instanceof PDOStatement)
			return $this->sth->fetchAll(($num)?PDO::FETCH_NUM:PDO::FETCH_ASSOC);
		elseif($this->rsh instanceof PDOStatement)
			return $this->rsh->fetchAll(($num)?PDO::FETCH_NUM:PDO::FETCH_ASSOC);
		return false;
	}
	protected function _fetch_col($offset=0)
	{
		if($this->sth instanceof PDOStatement)
			return $this->sth->fetchAll(PDO::FETCH_COLUMN,$offset);
		elseif($this->rsh instanceof PDOStatement)
			return $this->rsh->fetchAll(PDO::FETCH_COLUMN,$offset);
		return false;
	}
	protected function _num_rows()
	{
		if($this->sth instanceof PDOStatement)
			return $this->sth->rowCount();
		elseif($this->rsh instanceof PDOStatement)
			return $this->rsh->rowCount();
		else
			return false;
	}
	protected function _aff_rows()
	{
		return $this->_num_rows();
	}
	protected function _last_id()
	{
		return $this->dbh->lastInsertId();
	}
	protected function _prepare()
	{
		return @$this->dbh->prepare($this->query);
	}
	protected function _execute(&$p)
	{
		return ($this->sth instanceof PDOStatement) ? @$this->sth->execute($p) : false;
	}
	protected function _begin()
	{
		return @$this->dbh->beginTransaction();
	}
	protected function _rollback()
	{
		return @$this->dbh->rollBack();
	}
	protected function _commit()
	{
		return @$this->dbh->commit();
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
		return ($this->rsh instanceof PDOStatement) ? true : false;
	}
	protected function _escape($s)
	{
		return $this->dbh->quote($s);
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
		if($this->sth instanceof PDOStatement)
			return @$this->sth->closeCursor();
		return true;
	}
	protected function _close_statement()
	{
		if($this->sth instanceof PDOStatement)
			return @$this->sth->closeCursor();
		return true;
	}
	protected function _free_result()
	{
		if($this->rsh instanceof PDOStatement)
			return @$this->rsh->closeCursor();
		return true;
	}
	protected function _close()
	{
		if(!$this->is_persistent){
			$this->dbh = null;
			$this->is_connected = false;
		}
	}
	protected function _error_string()
	{
		$info = $this->dbh->errorInfo();
		return $info[2];
	}
	protected function _error_code()
	{
		$info = $this->dbh->errorInfo();
		return $info[1];
	}
	protected static function _backend_available(){return class_exists('PDO');}
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