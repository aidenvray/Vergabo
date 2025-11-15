<?php
class ZDBCDriverMysqli extends ZDBC
{
	protected
		$__drv_dclsname				= null,
		$__drv_caps					= null;
	protected static
		$__drv_name					= 'MySQLi',
		$__drv_family				= 'mysql',
		$__drv_classname			= __class__,
		$__drv_required_zdbc_ver	= array('2','>='),
		$__drv_configutaion			= array('host','user','pass','base','init','chrx'),
		$__drv_required_set			= array('host','user','pass','base'),
		$__drv_required_notblank	= array('host','user','base'),
		$__drv_str_quote			= "'",
		$__drv_obj_quote			= '`';
	## PRIVATE ##
	private
		$legacy_mode				= true,
		$stored						= false,
		$bind_key					= '',
		$bind_vars					= array(),
		$bind_data					= array();
	##
	public function __construct(&$x)
	{
		$this->legacy_mode = !(version_compare(PHP_VERSION,'5.3.0','>=') and extension_loaded('mysqlnd'));
		$this->__drv_dclsname = self::$__drv_classname;
		$this->__drv_caps = ($this->legacy_mode) ? parent::c_prepared+parent::c_transactions : parent::c_mfetch+parent::c_prepared+parent::c_transactions;
		try{parent::__construct($x);}
		catch(Exception $e){throw $e;}
		if($this->is_dbh() and $this->check_config($x)) {
			try{$this->connect($x);}
			catch(Exception $e){throw $e;}
		}

	}
	private function connect(&$cfg)
	{
		$this->dbh = @new mysqli($cfg['host'],$cfg['user'],$cfg['pass'],$cfg['base']);
		$es = ($this->legacy_mode or !$this->dbh) ? mysqli_connect_error() : $this->dbh->connect_error;
		$ec = ($this->legacy_mode or !$this->dbh) ? mysqli_connect_errno() : $this->dbh->connect_errno;
		if($ec)
			throw new Exception($es,$ec);
		if(!empty($cfg['chrx'])){
			if(method_exists($this->dbh,'set_charset') and !$this->dbh->set_charset($cfg['chrx']))
				throw new Exception($this->dbh->error,$this->dbh->errno);
			elseif(!$this->__exec('SET NAMES '.$cfg['chrx']))
				throw new Exception($this->dbh->error,$this->dbh->errno);
		}
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
		$this->stored = false;
		try {
			$this->rsh = $this->dbh->query($this->query);
		} catch(Exception $x){
			$this->rsh = false;
		}
		return $this->rsh;
	}
	protected function _exec()
	{
		$this->stored = false;
		try {
			$res = $this->dbh->real_query($this->query);
		} catch(Exception $x){
			$res = false;
		}
		$xr = @$this->dbh->use_result();
		if($xr instanceof mysqli_result)
			$xr->free();
		return $res;
	}
	protected function _fetch($num=false)
	{
		$this->__myprefetch();
		if($this->legacy_mode and $this->sth instanceof mysqli_stmt)
		{
			## statement
			if(!$this->stored)
				return false;
			##
			$bk = crc32($this->query.'::'.$this->query_src.'::'.$num);
			if($this->bind_key != $bk)
			{
				## Public contribution by nieprzeklinaj@gmail.com, 10x 2 him --M3956
				$this->bind_vars = $this->bind_data = array();
				$meta = $this->sth->result_metadata();
				$banal_iterator = 0;
				while($field = $meta->fetch_field())
				{
					$this->bind_vars[] =& $this->bind_data[(!$num)?$field->name:$banal_iterator];
					$banal_iterator++;
				}
				$meta->free();
				call_user_func_array(array($this->sth,'bind_result'),$this->bind_vars);
				$this->bind_key == $bk;
			}
			$state = $this->sth->fetch();
			if($state !== true) # false[error]/null[no_more_rows]
				return false;
			/*elseif($state === false)
				throw new Exception('When trying to fetch result from statement.',15);*/
			$result = array();
			foreach($this->bind_data as $k => $v)
				$result[$k] = $v;
			return $result;
		}
		elseif($this->rsh instanceof mysqli_result)
		{
			if(@!isset($this->rsh->field_count) or @!empty($this->rsh->field_count))
			{
				$out = ($num) ? $this->rsh->fetch_row() : $this->rsh->fetch_assoc();
				if(null === $out)
					return false;
				return $out;
			}
			else
			{
				$this->free();
				return false;
			}
		}
		else
			return $this->rsh;
	}
	protected function _fetch_all($num=false)
	{
		if(!$this->legacy_mode){
			$this->__myprefetch();
			if($this->rsh instanceof mysqli_result)
				return $this->rsh->fetch_all(($num)?MYSQLI_NUM:MYSQLI_ASSOC);
			else
				return $this->rsh;
		}

	}
	protected function _fetch_col($offset=0)
	{
		## N/A
	}
	protected function _num_rows()
	{
		if($this->rsh instanceof mysqli_result)
			return $this->rsh->num_rows;
		elseif($this->sth instanceof mysqli_stmt){
			if(!$this->stored)
				$this->__myprefetch();
			return ($this->legacy_mode) ? $this->sth->num_rows : $this->rsh->num_rows;
		}
		else
			return false;
	}
	protected function _aff_rows()
	{
		if($this->sth instanceof mysqli_stmt)
			return ($this->sth->affected_rows) ? $this->sth->affected_rows : $this->dbh->affected_rows;
		else
			return $this->dbh->affected_rows;
	}
	protected function _last_id()
	{
		return ($this->sth instanceof mysqli_stmt) ? $this->sth->insert_id : $this->dbh->insert_id;
	}
	protected function _prepare()
	{
		$this->stored = false;
		return $this->dbh->prepare($this->query);
	}
	protected function _execute(&$p)
	{
		$this->stored = false;
		$types = '';
		## Public contribution by nieprzeklinaj@gmail.com, 10x 2 him --m3956
		foreach($p as $param)
		{
			## TODO!
			if(is_int($param))
				$types .= 'i';
			elseif(is_float($param))
				$types .= 'd';
			else $types .= 's';
		}
		#if($this->multi_ph)
			#$params = array(substr($types,0,1));
		#else
			$params = array($types);
		foreach($p as $idx => $param)
			$params[] = &$p[$idx];
		unset($types);
		#
		/*$xp = array();
		foreach($p as $px)
			$xp[] = (is_numeric($px)) ? $px : "'$px'";
		$xp = implode(',',$xp);*/
		#
		if(call_user_func_array(array($this->sth,'bind_param'),$params)){
			try {
				return $this->sth->execute();
			} catch(Exception $x){
				return false;
			}
		} else
			return false;
	}
	protected function _begin()
	{
		if($this->dbh->autocommit(false))
		{
			if(version_compare(PHP_VERSION,'5.5.0','>='))
				return $this->dbh->begin_transaction();
			else
				return $this->dbh->query('BEGIN');
		}
		else
			return false;
	}
	protected function _rollback()
	{
		$r = $this->dbh->rollback();
		$this->dbh->autocommit(true);
		return $r;
	}
	protected function _commit()
	{
		$r = $this->dbh->commit();
		$this->dbh->autocommit(true);
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
		if(!$this->legacy_mode and $this->derive and $this->is_sth)
			return ($this->sth->field_count) ? true : false;
		else
			return ($this->dbh->field_count) ? true : false;
	}
	protected function _escape($s)
	{
		return $this->dbh->real_escape_string($s);
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
		if($this->sth instanceof mysqli_stmt)
			return $this->sth->reset();
		else
			return false;
	}
	protected function _close_statement()
	{
		if($this->sth instanceof mysqli_stmt)
		{
			if(@$this->sth->close())
			{
				$this->sth = null;
				return true;
			}
			else
				return false;
		}
		else
			return false;
	}
	protected function _free_result()
	{
		if($this->sth instanceof mysqli_stmt and $this->sth->field_count)
			$this->sth->free_result();
		if($this->rsh instanceof mysqli_result and $this->rsh->field_count)
		{
			$this->rsh->free();
			$this->rsh = null;
		}
		return true;
	}
	protected function _close()
	{
		$this->_free_result();
		$this->_close_statement();
		return @$this->dbh->close();
	}
	protected function _error_string()
	{
		if(mysqli_connect_errno())
			return mysqli_connect_error();
		elseif($this->sth instanceof mysqli_stmt and $this->sth->error)
			return $this->sth->error;
		else
			return $this->dbh->error;
	}
	protected function _error_code()
	{
		if(mysqli_connect_errno())
			return mysqli_connect_errno();
		elseif($this->sth instanceof mysqli_stmt and $this->sth->errno)
			return $this->sth->errno;
		else
			return $this->dbh->errno;
	}
	protected static function _backend_available(){return class_exists('mysqli');}
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
	## PRIVATE ##
	private function __myprefetch()
	{
		if($this->stored)
			return;
		if($this->legacy_mode){
			if(!$this->_is_result())
				return;
			if($this->sth instanceof mysqli_stmt)
				if($this->sth->store_result())
					$this->stored = true;
			elseif($this->rsh = $this->dbh->store_result())
				$this->stored = ($this->rsh instanceof mysqli_result) ? true : false;
		}
		elseif($this->sth instanceof mysqli_stmt){
			if(null !== $this->sth->result_metadata())
				$this->rsh = $this->sth->get_result();
			else
				$this->rsh = true;
			$this->stored = true;
		}
	}
}
?>