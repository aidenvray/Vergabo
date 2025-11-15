<?php
class TinyApplication extends TinyScript {
	const
		USER_STATUS_NORMAL	= 0,
		USER_STATUS_BLOCKED	= 1;
	protected
		$bx			= null,
		$title		= null,
		$menu		= null,
		$jsinc		= null,
		$sid		= null,
		$login		= null,
		$level		= null,
		$vars		= null;
	protected function __construct(){
		# Add pre-construct hooks here
		parent::__construct();
		# Add post-construct hooks here
		require_once APP_ROOT.'tpl.admin.class.php';
		$this->_wrapper = APP_ROOT.'wrapper.admin.php';
		$this->vars = $GLOBALS['cfg']['app'];
		ZDBC::instance(null,$GLOBALS['cfg']['zdbc']);
		# Add any indep logic here
		$this->bx = $this->b.'/';
		#
		$this->Bootstrap();
		$this->Selector();
	}
	public static function run(){
		# Add pre-init hooks here
		self::initialize(__class__);
		# Add post-init hooks here
	}
	##
	private function Bootstrap(){
		$DB = ZDBC::instance();
		$DB->exec(array('delete'=>'management_sessions','where'=>array('mtime','<','?')),array(time()-7200)); # TODO: move session cutoff to vars
		$this->sid = ZWEI::req('s');
		if($this->sid){
			$S = $DB->fetchRow(array('select'=>'*','from'=>'management_sessions','where'=>array('id','=','?')),array($this->sid));
			if($S){
				$valid = ($S['ip'] == ZWEI::srv('REMOTE_ADDR') and $S['ua'] == ZWEI::srv('HTTP_USER_AGENT')); # NB: both checks
				# TODO: maybe proxy checks?
				if(!$valid){
					$DB->exec(array('delete'=>'management_sessions','where'=>array('id','=','?')),array($this->sid));
					$this->sid = $S = null;
				} else {
					if($S['account']){
						$U = $DB->fetchRow(array('select'=>'login,level','from'=>'management','where'=>array('id','=','?')),array($S['account']));
						if($U and 0 < $U['level']){
							$this->login = $U['login'];
							$this->level = $U['level'];
							$DB->exec(array('update'=>'management_sessions','set'=>array('mtime'=>'?'),'where'=>array('id','=','?')),array(time(),$this->sid));
						} else {
							$DB->exec(array('delete'=>'management_sessions','where'=>array('id','=','?')),array($this->sid));
							$this->sid = $S = null;
						}
					}
				}
			}
		}
		##
		$this->title = 'CRM System Login :: Dreamer Consulting, LLC';
	}
	private function BuildMenu($act){
		if($this->login and $this->level){
			$this->menu = TPL::MenuWrap(implode('',array(
				TPL::MenuLink('Home',$this->_makeURL('act=idx'),('idx' == $act)?1:0),
				TPL::MenuLink('Buyers',$this->_makeURL('act=list&what=buyer'),('list' == $act and 'buyer' == ZWEI::req('what'))?1:0),
				TPL::MenuLink('Suppliers',$this->_makeURL('act=list&what=supplier'),('list' == $act and 'supplier' == ZWEI::req('what'))?1:0),
				TPL::MenuLink('Manufacturers',$this->_makeURL('act=list&what=manufacturer'),('list' == $act and 'manufacturer' == ZWEI::req('what'))?1:0),
				TPL::MenuLink('Log Out',$this->_makeURL('act=logout'),0)
			)));
		} else
			$this->menu = TPL::MenuWrap(implode('',array(
				TPL::MenuLink('Home','',-1),
				TPL::MenuLink('Log Out','',-1)
			)));
	}
	private function Selector(){
		$act = trim(ZWEI::req('act'));
		##
		if($this->sid and $this->login and 0 < $this->level){
			if('login' == $act)
				$this->redirect("?s={$this->sid}"); # TODO!
			#
			switch($act){
				case 'logout':
					$this->BuildMenu(''); # MADV_DONTNEED
					$this->ActLogout();
				break;
				case 'list':
					$this->BuildMenu('list'); # called from this::ActList()
					$this->ActList();
				break;
				case 'edit':
					$this->BuildMenu('');
					$this->ActEdit();
				break;
				case 'delete':
					$this->BuildMenu('');
					$this->ActDelete();
				break;
				case 'import':
					$this->BuildMenu('');
					$this->ActImport();
				break;
				default:
					$this->BuildMenu('idx');
					$this->ActIdx();
				break;
			}
		} elseif('login' != $act)
			$this->redirect('?act=login');
		$this->ActLogin();
	}
	##
	private function LoginLog($id,$success,$acp=false){
		$tbl = $acp ? 'management_logins' : 'logins';
		$DB = ZDBC::instance();
		$DB->exec(array('insert'=>$tbl,'lazy'=>1,'values'=>array(
			'account'	=> $id,
			'time'		=> time(),
			'success'	=> intval($success),
			'ip'		=> ZWEI::srv('REMOTE_ADDR'),
			'ipex'		=> TinyUncover::GetProxyIP(),
			'ua'		=> ZWEI::srv('HTTP_USER_AGENT'),
			'rf'		=> ZWEI::srv('HTTP_REFERER')
		)));
	}
	private function _makeURL($url){
		if($this->sid)
			return "{$this->bx}?s={$this->sid}&$url";
		else
			return "{$this->bx}?$url";
	}
	private function _makeWhere(&$Q,$filter){
		$P = null;
		if(is_array($filter) and count($filter)){
			$w = $v = array();
			foreach($filter as $x){
				if(is_array($x) and 1 < count($x)){
					if(2 == count($x)){
						$w[] = array($x[0],'=','?');
						$v[] = $x[1];
					} elseif(3 == count($x)){
						$w[] = array($x[0],$x[1],'?');
						$v[] = $x[2];
					}
				} elseif(is_string($x) and ($x = strtolower($x)) and in_array($x,array('and','or')))
					$w[count($w)-1][] = $x;
			}
			$Q['where'] = $w;
			$P = $v;
		}
		return $P;
	}
	private function EntityEnum($name,$tbl,$filter=null){
		$out = array();
		if(!$name or !$tbl)
			return $out;
		#
		$DB = ZDBC::instance();
		#
		$Q = array('select'=>'count(*)','from'=>$tbl);
		$P = $this->_makeWhere($Q,$filter);
		return $DB->fetchOne($Q,$P);
	}
	private function EntityList($name,$tbl,$limit=100,$offset=0,$filter=null){
		$out = array();
		if(!$name or !$tbl or !$limit or 0 > $limit)
			return $out;
		#
		$DB = ZDBC::instance();
		#
		$Q = array('select'=>'*','from'=>$tbl);
		$P = $this->_makeWhere($Q,$filter);
		if($limit)
			$Q['limit'] = $limit;
		if($offset)
			$Q['offset'] = $offset;
		return $DB->fetchTable($Q,$P);
	}
	private function EntityRead($name,$tbl,$filter){
		$out = array();
		if(!$name or !$tbl or !is_array($filter) or !count($filter))
			return $out;
		#
		$DB = ZDBC::instance();
		#
		$Q = array('select'=>'*','from'=>$tbl);
		$P = $this->_makeWhere($Q,$filter);
		return $DB->fetchRow($Q,$P);
	}
	private function EntityCreate($name,$tbl,$params){
		if(!$name or !$tbl or !is_array($params) or !count($params))
			return $out;
		#
		$DB = ZDBC::instance();
		if($DB->exec(array('insert'=>$tbl,'lazy'=>1,'values'=>$params))){
			$lid = $DB->last;
			if($lid)
				return $lid;
			else
				return true;
		} else
			return false;
	}
	private function EntityUpdate($name,$tbl,$filter,$params){
		if(!$name or !$tbl or !is_array($filter) or !count($filter) or !is_array($params) or !count($params))
			return $out;
		#
		$DB = ZDBC::instance();
		$set = $vz = array();
		foreach($params as $k => $v){
			$set[$k] = '?';
			$vz[] = $v;
		}
		$Q = array('update'=>$tbl,'set'=>$set);
		$P = $this->_makeWhere($Q,$filter);
		foreach($P as $v)
			$vz[] = $v;
		return $DB->exec($Q,$vz);
	}
	private function EntityDelete($name,$tbl,$filter){
		$out = array();
		if(!$name or !$tbl or !is_array($filter) or !count($filter))
			return $out;
		#
		$DB = ZDBC::instance();
		#
		$Q = array('delete'=>$tbl);
		$P = $this->_makeWhere($Q,$filter);
		return $DB->exec($Q,$P);
	}
	##
	private function ActError($msg){
		$this->title = 'Error! :: Dreamer Consulting, LLC';
		$this->send_body(TPL::PageError($msg));
	}
	private function ActIdx(){
		$this->title = 'WIP :: Dreamer Consulting, LLC';
		$content = TPL::PageIndex($this->login);
		# TODO?
		$this->send_body($content);
	}
	private function ActImport(){
		$what = ZWEI::req('what');
		if(!$what)
			return $this->ActError("Incorrect usage: no entity specified");
		#
		$what = strtolower($what);
		$cfg = TinyLibrary::EntityConfiguration($what);
		if(!$cfg)
			return $this->ActError("Incorrect usage: no such entity in existence");
		#
		$msg = 'Under construction: no import file format currently defined';
		#
		if(isset($_FILES['imported'])){
			if($_FILES['imported']['error'])
				$msg = "Upload error #{$_FILES['imported']['error']}";
			elseif(!$_FILES['imported']['size'])
				$msg = "Uploaded file is empty";
			else {
				$b = @file_get_contents($_FILES['imported']['tmp_name']);
				if(!$b)
					$msg = "Uploaded file is either empty or was not uploaded";
				else {
					# TODO: determine format
					$msg = "Under construction: no import file format currently defined<br><strong>File was uploaded successfully but we don't know how to import it</strong>";
				}
			}
		}
		#
		$this->send_body(TPL::PageImport($this->_makeURL("act=import&what=$what"),$msg));
	}
	private function ActDelete(){
		$what = ZWEI::req('what');
		$id = ZWEI::req('id');
		if(!$what or !$id)
			return $this->ActError("Incorrect usage: no entity specified");
		$what = strtolower($what);
		$cfg = TinyLibrary::EntityConfiguration($what);
		if(!$cfg)
			return $this->ActError("Incorrect usage: no such entity in existence");
		#
		$confirm = (false !== ZWEI::req('confirm'));
		#
		if($confirm){
			$this->EntityDelete($what,$cfg['table'],array(array($cfg['id']['name'],$id)));
			$this->redirect("?s={$this->sid}&act=list&what=$what");
		}
		# TODO: load some fancy name maybe?
		$this->send_body(TPL::PageConfirmDelete($this->_makeURL("act=delete&what=$what&id=$id&confirm"),$this->_makeURL("act=list&what=$what"),$what,$id));
	}
	private function ActEdit(){
		$what = ZWEI::req('what');
		if(!$what)
			return $this->ActError("Incorrect usage: no entity specified");
		#
		$what = strtolower($what);
		$cfg = TinyLibrary::EntityConfiguration($what);
		if(!$cfg)
			return $this->ActError("Incorrect usage: no such entity in existence");
		#
		$id = ZWEI::req('id');
		$e = null;
		$msg = '';
		#
		if($id){
			$e = $this->EntityRead($what,$cfg['table'],array(array($cfg['id']['name'],$id)));
			if(!$e)
				return $this->ActError("Incorrect usage: no such entity in existence");
		}
		#
		$commit = true; # TODO: what to do with this?
		$values = array();
		#
		foreach($cfg['fields'] as $f => $fc){
			if(!empty($fc['hidden']) or empty($fc['rw']))
				continue;
			#
			$type = !empty($fc['type']) ? $fc['type'] : 'text';
			#$unique = !empty($fc['unique']);
			$nullable = !empty($fc['nullable']);
			$sensitive = !empty($fc['sensitive']);
			#
			$v = ZWEI::req($f);
			if('checkbox' == $type)
				$v = $v ? 1 : 0;
			if(false === $v or ($sensitive and '[REDACTED]' == $v)){
				$commit = false;
				continue;
			}
			#
			if($nullable and false !== ZWEI::req("null_$f"))
				$v = null;
			#
			if($e and (!isset($e[$f]) or $e[$f] == $v)) # edit mode: no such field at all or same value so not altered
				continue;
			#
			if('password' == $f)
				$v = TinyLibrary::Digest($v);
			#
			$values[$f] = $v;
		}
		if(isset($cfg['fields']['mtime']))
			$values['mtime'] = time();
		if(!$e and isset($cfg['fields']['ctime']))
			$values['ctime'] = time();
		#
		$commit = false !== (ZWEI::req('commit'));
		#
		if($commit and count($values)){
			$DB = ZDBC::instance();
			$r = $e ? $this->EntityUpdate($what,$cfg['table'],array(array($cfg['id']['name'],$id)),$values) : $this->EntityCreate($what,$cfg['table'],$values);
			if($r)
				$this->redirect("?s={$this->sid}&act=list&what=$what");
			$msg = 'Database error: '.$DB->error;
		}
		#
		$form = array();
		#
		foreach($cfg['fields'] as $f => $fc){
			if(!empty($fc['hidden']))
				continue;
			#
			$v = isset($values[$f]) ? $values[$f] : (($e and isset($e[$f])) ? $e[$f] : null);
			#
			$type = !empty($fc['type']) ? $fc['type'] : 'text';
			#$unique = !empty($fc['unique']);
			$writable = !empty($fc['rw']);
			$nullable = !empty($fc['nullable']);
			$sensitive = !empty($fc['sensitive']);
			#
			if('timestamp' == $type and !$e)
				continue;
			if('timestamp' == $type and $v)
				$v = date('Y-m-d H:i:s',$v);
			if($sensitive)
				$v = '[REDACTED]';
			#
			if('text' == $type or 'int' == $type or 'timestamp' == $type)
				$ctl = TPL::FormText($f,$v,!$writable,!$writable);
			elseif('checkbox' == $type)
				$ctl = TPL::FormFlag($f,$v,!$writable);
			elseif('radio' == $type or 'select' == $type){
				$opts = array();
				foreach($fc['opts'] as $kk => $vv)
					$opts[] = TPL::FormSelectOption($kk,$vv,($v === $kk or (!empty($fc['opt_default']) and $fc['opt_default'] == $kk)));
				$ctl = TPL::FormSelect($f,implode('',$opts),!$writable,!$writable);
			}
			#
			if($nullable)
				$ctl .= TPL::FormFlagNull("null_$f",(null === $v));
			#
			$form[] = TPL::FormRow($f,$ctl,!empty($fc['label'])?$fc['label']:$f);
		}
		#
		$url = "act=edit&what=$what";
		if($e)
			$url .= "&id=$id";
		$label_top = $e ? "Editing: $what ID $id" : "New $what";
		$label_commit = $e ? 'Edit' : 'Create';
		$this->send_body(TPL::FormEdit($this->_makeURL($url),$label_top,$label_commit,implode('',$form),$msg));
	}
	private function ActList(){
		$what = ZWEI::req('what');
		if(!$what)
			return $this->ActError("Incorrect usage: no entity specified");
		#
		$what = strtolower($what);
		$cfg = TinyLibrary::EntityConfiguration($what);
		if(!$cfg)
			return $this->ActError("Incorrect usage: no such entity in existence");
		#
		$page = ZWEI::req('page');
		if($page)
			$page = intval($page);
		if(1 > $page)
			$page = 1;
		#
		if($filter = ZWEI::req('filter') and !empty($cfg['search'])){
			$search = array();
			foreach($cfg['search'] as $k){
				$search[] = array($k,'like','%'.$filter.'%');
				$search[] = 'or';
			}
		} else
			$filter = null;
		#
		if(!empty($cfg['filter'])){
			if(!$filter)
				$filter = array();
			foreach($cfg['filter'] as $ff)
				$filter[] = $ff;
		}
		#
		$rows = $this->EntityEnum($what,$cfg['table'],$filter);
		#
		$P = new ZPage($rows,$this->vars['mgmt_list_per_page']);
		if($page > $P->pages)
			$page = $P->pages;
		#
		$pagination = 1 < $P->pages ? $P->Nav($page,"{$this->bx}?act=list&what=$what&page=%ST%") : '';
		#
		$lst = $this->EntityList($what,$cfg['table'],$this->vars['mgmt_list_per_page'],$P->Offset($page),$filter);
		#
		$rows = array();
		#
		$row = array();
		$row[] = TPL::TableCell('#','col');
		foreach($cfg['fields'] as $f => $fc){
			if(!empty($fc['hidden']))
				continue;
			$row[] = TPL::TableCell(!empty($fc['label'])?$fc['label']:$f,'col');
		}
		$rows[] = TPL::TableRow(implode('',$row));
		#
		foreach($lst as $e){
			$row = array();
			#
			if(!isset($e[$cfg['id']['name']]))
				$this->ActError("Invalid configuration for entity [$what]: no ID field");
			$id = $e[$cfg['id']['name']];
			#
			$row[] = TPL::TableCell($id,'row');
			#
			foreach($cfg['fields'] as $f => $fc){
				if(!isset($e[$f]) and empty($fc['nullable']))
					$this->ActError("Invalid configuration for entity [$what]: no [$f] field");
				#
				if(!empty($fc['hidden']))
					continue;
				#
				$v = $e[$f];
				#
				if(!empty($fc['sensitive']))
					$v = '[REDACTED]';
				#
				$type = !empty($fc['type']) ? $fc['type'] : 'text';
				if('timestamp' == $type)
					$v = date('Y-M-D H:i:s',$v);
				elseif('checkbox' == $type)
					$v = $v ? 'Yes' : 'No';
				elseif('select' == $type or 'radio' == $type)
					$v = isset($fc['opts'][$v]) ? $fc['opts'][$v] : "???$v";
				#
				$row[] = TPL::TableCell($v);
			}
			#
			$row[] = TPL::TableCell(TPL::TableBtn('Edit',$this->_makeURL("act=edit&what=$what&id=$id"),'warning').TPL::TableBtn('Delete',$this->_makeURL("act=delete&what=$what&id=$id"),'danger'),'row');
			$rows[] = TPL::TableRow(implode('',$row));
		}
		#
		if(!$lst)
			$rows[] = TPL::TableRow(TPL::TableCell('<h5 class="w-100"><i>No data</i></h5>',null,count($cfg['fields'])+1));
		#
		$rows[] = TPL::TableRow(TPL::TableCell('',null,count($cfg['fields'])-1).TPL::TableCell(TPL::TableBtn('New',$this->_makeURL("act=edit&what=$what"),'primary btn-block')).TPL::TableCell(TPL::TableBtn('Import',$this->_makeURL("act=import&what=$what"),'secondary btn-block')));
		#
		$tbl = TPL::TableWrap(implode('',$rows));
		#
		$this->title = "List: $what";
		$this->send_body(TPL::PageList($what,$tbl,$pagination));
	}
	private function ActLogin(){
		$msg = '';
		#
		$DB = ZDBC::instance();
		$regmode = false;
		if(!$DB->fetchOne(array('select'=>'count(*)','from'=>'management'))){ # no admins? register mode!
			$msg = 'No admins found, register new admin via the form below';
			$regmode = true;
		}
		#
		$aid = ZWEI::req('aid');
		$apwd = ZWEI::post('apwd');
		#
		if(false !== $aid){ # form sent
			if(!$aid)
				$msg = 'Login cannot be empty';
			elseif(!$apwd)
				$msg = 'Password cannot be empty';
			else {
				$valid = false;
				if($regmode){
					if(!$DB->exec(array('insert'=>'management','lazy'=>1,'values'=>array(
						'ctime'		=> time(),
						'mtime'		=> time(),
						'level'		=> 5,
						'login'		=> $aid,
						'password'	=> TinyLibrary::Digest($apwd)
					))))
						$msg = "Database operation failed: {$DB->error}";
					else
						$valid = true;
				} else {
					$apwx = $DB->fetchOne(array('select'=>'password','from'=>'management','where'=>array('login','=','?')),array($aid));
					# NB: we SHOULD NOT give different messages to prevent information leaking
					$valid = ($apwx and TinyLibrary::Digest($apwd,$apwx));
					if(!$valid)
						$this->LoginLog(0,0,1);
					$apwd = $apwx = null; # preventing leaks
				}
				if(!$valid)
					$msg = $msg ? $msg : 'Incorrect login and/or password input';
				else {
					$U = $DB->fetchRow(array('select'=>'id,login,level','from'=>'management','where'=>array('login','=','?')),array($aid));
					if($U and $U['id'] and 0 < $U['level']){
						$this->LoginLog($U['id'],1,1);
						$this->login = $U['login'];
						$this->level = $U['level'];
						#
						$this->sid = TinyLibrary::DigestRandom();
						$DB->exec(array('insert'=>'management_sessions','lazy'=>1,'values'=>array(
							'id'		=> $this->sid,
							'mtime'		=> time(),
							'account'	=> $U['id'],
							'ip'		=> ZWEI::srv('REMOTE_ADDR'),
							'ua'		=> ZWEI::srv('HTTP_USER_AGENT'),
							'rf'		=> ZWEI::srv('HTTP_REFERER')
						)));
						#
						$this->redirect("?s={$this->sid}");
					} else {
						$this->LoginLog($U['id'],0,1);
						$msg = 'This account is not allowed to log in';
					}
				}
			}
		}
		# TODO
		$content = TPL::FormLogin($this->_makeURL('act=login'),$msg,$aid);
		$this->send_body($content);
	}
	private function ActLogout(){
		if($this->sid and $this->level){
			$DB = ZDBC::instance();
			$DB->exec(array('delete'=>'management_sessions','where'=>array('id','=','?')),array($this->sid));
			$this->sid = null;
		}
		$this->redirect();
	}
}
