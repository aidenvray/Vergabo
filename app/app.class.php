<?php
define('COOKIE_SESSION','mix_id');
define('COOKIE_AUTOLOGIN','mix_hash');

class TinyApplication extends TinyScript {
	const
		USER_STATUS_NORMAL	= 0,
		USER_STATUS_BLOCKED	= 1;
	protected
		$title		= null,
		$guard		= null,
		$navbar		= null,
		$userbar	= null,
		$jsinc		= null,
		$user		= null,
		$org		= null,
		$vars		= null;
	protected function __construct(){
		# Add pre-construct hooks here
		parent::__construct();
		# Add post-construct hooks here
		require_once APP_ROOT.'tpl.class.php';
		$this->_wrapper = APP_ROOT.'wrapper.php';
		$this->vars = $GLOBALS['cfg']['app'];
		ZDBC::instance(null,$GLOBALS['cfg']['zdbc']);
		# Add any indep logic here
		$this->SelfTest();
		$this->Bootstrap();
		$this->Selector();
	}
	public static function run(){
		# Add pre-init hooks here
		self::initialize(__class__);
		# Add post-init hooks here
	}
	##
	private function CreateSession(){
		$DB = ZDBC::instance();
		$sid = TinyLibrary::DigestRandom();
		$this->guard = TinyLibrary::DigestRandom();
		$DB->exec(array('insert'=>'sessions','lazy'=>1,'values'=>array(
			'id'		=> $sid,
			'mtime'		=> time(),
			'account'	=> $this->user ? $this->user['id'] : 0,
			'ip'		=> ZWEI::srv('REMOTE_ADDR'),
			'ua'		=> ZWEI::srv('HTTP_USER_AGENT'),
			'rf'		=> ZWEI::srv('HTTP_REFERER'),
			'guard'		=> $this->guard
		)));
		@setcookie(COOKIE_SESSION,$sid,0);
	}
	#
	private function CheckGuard(){
		if(!$this->guard)
			return false;
		$guard = ZWEI::req('token');
		$sid = ZWEI::cookie(COOKIE_SESSION);
		if(!$sid)
			return false; # FIXME: ensure we're always have a session!
		$valid = ($guard === $this->guard);
		# mandatory refresh!
		$DB = ZDBC::instance();
		$this->guard = TinyLibrary::DigestRandom();
		$DB->exec(array('update'=>'sessions','set'=>array('guard'=>'?'),'where'=>array('id','=','?')),array($this->guard,$sid));
		return $valid;
	}
	##
	private function SelfTest(){
		# TODO
		/*$DB = ZDBC::instance();
		if(!$DB->exec(array('select'=>'*','from'=>'msg')))
			if(!$DB->exec("CREATE TABLE msg (
							msg_author varchar(128) not null,
							msg_time integer not null,
							msg_ip varchar(48) not null,
							msg_ua varchar(255) not null,
							msg_data text not null
						   )"))
				$this->fatal("Can't create table!");
		*/
	}
	private function Bootstrap(){
		$DB = ZDBC::instance();
		# SESSION
		$DB->exec(array('delete'=>'sessions','where'=>array('mtime','<','?')),array(time()-7200)); # TODO: move session cutoff to vars
		if($sid = ZWEI::cookie(COOKIE_SESSION)){
			$S = $DB->fetchRow(array('select'=>'*','from'=>'sessions','where'=>array('id','=','?')),array($sid));
			if($S){
				# TODO: toggle checks in vars
				$valid = ($S['ip'] == ZWEI::srv('REMOTE_ADDR') and $S['ua'] == ZWEI::srv('HTTP_USER_AGENT'));
				if(!$valid){
					$DB->exec(array('delete'=>'sessions','where'=>array('id','=','?')),array($sid));
					$sid = $S = null;
				} else {
					$this->guard = $S['guard'];
					if($S['account']){
						$U = $DB->fetchRow(array('select'=>'*','from'=>'accounts','where'=>array('id','=','?')),array($S['account']));
						if($U){
							unset($U['password']); # preventing leaks
							$this->user = $U;
						}
					}
					$DB->exec(array('update'=>'sessions','set'=>array('mtime'=>'?'),'where'=>array('id','=','?')),array(time(),$sid));
				}
			}
		}
		# AUTOLOGIN
		if(!$this->user and $alk = ZWEI::cookie(COOKIE_AUTOLOGIN)){
			$U = $DB->fetchRow(array('select'=>'*','from'=>'accounts','where'=>array('autologin','=','?')),array($alk));
			if(!$U)
				@setcookie('autologin','',-1);
			else {
				unset($U['password']); # preventing leaks
				$this->user = $U;
				$this->CreateSession();
			}
		}
		# SESSION REPRISE
		if(!$sid)
			$this->CreateSession();
		# BAR
		if($this->user)
			$this->userbar = TPL::UserBarButton($this->b.'/usercp','Dashboard').TPL::UserBarButton($this->b.'/logout','Log Out'); # TODO: some sort of crc check for logout!
		else
			$this->userbar = TPL::UserBarButton($this->b.'/register','Register').TPL::UserBarButton($this->b.'/login','Log In');
		#
	}
	private function BuildNavigation($act){
		$navlinks = array(
			'rfq'		=> 'For buyers',
			'suppliers'	=> 'For suppliers',
			'requests'	=> 'Requests',
			'help'		=> 'FAQ &amp; Help',
			'about'		=> 'About Us'
		);
		$rows = array();
		foreach($navlinks as $url => $txt){
			$active = ($url == $act);
			$rows[] = TPL::NavBarLink($this->b.'/'.$url,$txt,$active);
		}
		$this->navbar = implode('',$rows);
	}
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
	private function Selector(){
		$req = self::route();
		$act = !empty($req[0]) ? $req[0] : 'suppliers';
		#
		$this->BuildNavigation($act);
		#
		switch($act){
			case 'countries.json':
				$this->ActAJAX($req);
			break;
			case 'privacy':
			case 'pricing':
			case 'payment':
			case 'cookies':
			case 'terms':
			case 'tos':
			case 'legal':
			case 'about':
			case 'help':
			case 'faq':
			case 'contact':
			case 'contacts':
				$this->ActStatic($req);
			break;
			case 'login':
				$this->ActLogin($req);
			break;
			case 'logout':
				$this->ActLogout($req);
			break;
			case 'register':
				$this->ActRegister($req);
			break;
			case 'recovery':
				$this->ActRecovery($req);
			break;
			case 'usercp':
				$this->ActUserCP($req);
			break;
			case 'suppliers':
				$this->ActIdx($req);
			break;
			default:
				$this->ActIdx($req);
			break;
		}
	}
	##
	private function ActLogin($req){
		if($this->user)
			$this->redirect();
		$msg = '';
		#
		$aid = ZWEI::req('aid');
		$apwd = ZWEI::post('apwd');
		#
		if(false !== $aid){ # form sent
			if(!$aid)
				$msg = 'Email cannot be empty';
			elseif(!$apwd)
				$msg = 'Password cannot be empty';
			elseif(!$this->CheckGuard())
				$msg = 'Integrity validation failed';
			else {
				$DB = ZDBC::instance();
				$apwx = $DB->fetchOne(array('select'=>'password','from'=>'accounts','where'=>array(array('email','=','?','or'),array('phone','=','?'))),array($aid,$aid));
				# NB: we SHOULD NOT give different messages to prevent information leaking
				$valid = ($apwx and TinyLibrary::Digest($apwd,$apwx));
				$apwd = $apwx = null; # preventing leaks
				if(!$valid){
					$msg = 'Incorrect email and/or password input';
					$this->LoginLog(0,0);
				}
				else {
					$U = $DB->fetchRow(array('select'=>'*','from'=>'accounts','where'=>array(array('email','=','?','or'),array('phone','=','?'))),array($aid,$aid));
					unset($U['password']); # preventing leaks
					$this->LoginLog($U['id'],1);
					$this->user = $U;
					$this->CreateSession();
					if(!ZWEI::req('autologin'))
						$DB->exec(array('update'=>'accounts','set'=>array('autologin'=>''),'where'=>array('id','=','?')),array($U['id']));
					else {
						if(!empty($this->vars->reset_autologin)){
							$U['autologin'] = TinyLibrary::DigestRandom();
							$DB->exec(array('update'=>'accounts','set'=>array('autologin'=>'?'),'where'=>array('id','=','?')),array($U['autologin'],$U['id']));
						}
						@setcookie(COOKIE_AUTOLOGIN,$U['autologin'],60*60*24*30);
					}
					$this->redirect('/usercp');
				}
			}
		}
		# TODO
		$this->title = 'Vergabo // Login';
		$content = TPL::FormLoginFull($this->b,$this->guard,$msg,$aid);
		$this->send_body($content);
	}
	private function ActLogout($req){
		if($this->user){
			$DB = ZDBC::instance();
			if(ZWEI::cookie(COOKIE_AUTOLOGIN)){
				$DB->exec(array('update'=>'accounts','set'=>array('autologin'=>''),'where'=>array('id','=','?')),array($this->user['id']));
				@setcookie(COOKIE_AUTOLOGIN,'',-1);
			}
			$DB->exec(array('delete'=>'sessions','where'=>array('account','=','?')),array($this->user['id']));
			@setcookie(COOKIE_SESSION,'',-1);
		}
		$this->redirect();
	}
	private function ActRegister($req){
		if($this->user)
			$this->redirect();
		$msg = '';
		#
		$roles = array(
			'buyer',
			'supplier'
		);
		#
		$keys = array(
			'first_name',
			'last_name',
			'organization',
			'tax_id',
			'country',
			'endpoint',
			'city',
			'postal_code',
			'email',
			'phone',
		);
		$optional = array(
			'phone',
			'organization',
			'tax_id'
		);
		$values = array(
			'ctime'		=> time(),
			'mtime'		=> time(),
			'autologin'	=> '',
			'role'		=> '',
			
			'email_verification'	=> '',
			'phone_verification'	=> '',
			'status'	=> self::USER_STATUS_NORMAL,
			'email_ok'	=> 0,
			'phone_ok'	=> 0
		);
		foreach($keys as $k)
			$values[$k] = ZWEI::req($k);
		#
		$countries = array();
		#
		if(file_exists(APP_ROOT.'countries.lst')){
			$rows = array(
				TPL::FormSelectOption('','Select your country',true,true)
			);
			foreach(file(APP_ROOT.'countries.lst',FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $l){
				list($c,$n) = explode(':',$l);
				$countries[$c] = $n;
				$rows[] = TPL::FormSelectOption($c,$n,($c == $values['country']),false);
			}
			$rows = implode('',$rows);
			$country_field = TPL::FormSelect('country',$rows,false,true);
		} else
			$country_field = TPL::FormText('country',$values['country'],false,true,'Country');
		#
		$role = ZWEI::req('role');
		#
		if(false !== $role){ # form sent
			$values['role'] = $role;
			#
			$apwd = ZWEI::post('apwd');
			#$apwx = ZWEI::post('apwx');
			# TODO
			if(!$apwd)
				$msg = 'Password required';
			/*elseif(!$apwx)
				$msg = 'Password repeat required';
			elseif($apwd != $apwx)
				$msg = "Password repeat doesn't match";*/
			elseif(!in_array($role,$roles))
				$msg = 'Unknown user role selected';
			else {
				$values['password'] = TinyLibrary::Digest($apwd);
				foreach($keys as $k){
					$v = ZWEI::req($k);
					if(!$v and !in_array($k,$optional)){
						$msg = "$k cannot be blank";
						break;
					}
					#
					if('email' == $k and !preg_match('/^[-._a-z0-9]+@(?:[a-z0-9][-a-z0-9]+?)+\.[a-z]{2,63}$/i',$v)){
						$msg = 'Wrong email address';
						break;
					}
					#
					if('phone' == $k and !$v)
						$v = substr(sha1($values['email']),0,8);
					/*if('phone' == $k and !preg_match('/^\+?[\d\s]{7,}$/',$v)){
						$msg = 'Wrong phone number';
						break;
					}*/
					#
					if('country' == $k and $countries and !isset($countries[$v])){
						$msg = 'Unknown country specified';
						break;
					}
					#
					$values[$k] = $v;
				}
				if(!$msg){
					$DB = ZDBC::instance();
					if($DB->fetchOne(array('select'=>'count(*)','from'=>'accounts','where'=>array(array('role','=','?','and'),array('email','=','?','or'),array('phone','=','?'))),array($role,$values['email'],$values['phone'])))
						$msg = "Account already exists";
					elseif(!$DB->exec(array('insert'=>'accounts','lazy'=>1,'values'=>$values)))
						$msg = "Database operation failed: {$DB->error}";
					else {
						$this->user = $DB->fetchRow(array('select'=>'*','from'=>'accounts','where'=>array('email','=','?')),array($values['email']));
						if(!$this->user)
							$msg = 'Account registration failed, reason unknown';
						else {
							unset($this->user['password']); # security reasons!
							$this->CreateSession();
							$this->redirect('/usercp');
						}
					}
				}
			}
		}
		if(!$role)
			$values['role'] = 'buyer';
		# TODO
		$this->title = 'Vergabo // Register';
		$content = TPL::FormRegister($this->b,$this->guard,$msg,$values,$country_field);
		$this->send_body($content);
	}
	private function ActRecovery($req){
		if($this->user)
			$this->redirect();
		#
		$sect = 'begin';
		#$sect = !empty($req[1]) ? $req[1] : 'begin';
		#
		$aid = ZWEI::req('aid');
		$otp = ZWEI::req('otp');
		$apwd = ZWEI::post('apwd');
		#$apwx = ZWEI::post('apwx');
		#
		$this->title = 'Vergabo // Recover password';
		#
		if(false !== $aid){
			if(!$aid)
				$msg = 'Email is required';
			elseif(!preg_match('/^[-._a-z0-9]+@(?:[a-z0-9][-a-z0-9]+?)+\.[a-z]{2,63}$/i',$aid))
				$msg = 'Email is invalid';
			elseif(false !== $otp){
				$sect = 'otp';
				if(!$otp)
					$msg = 'Verification code is required';
				elseif(false !== $apwd/* or false !== $apwx*/){
					if(!$apwd)
						$msg = 'Password is required';
					/*elseif(!$apwx)
						$msg = 'Password confirmation is required';
					elseif($apwd != $apwx)
						$msg = 'Password confirmation not matches password';*/
					else {
						$DB = ZDBC::instance();
						$id = $DB->fetchOne(array('select'=>'id','from'=>'accounts','where'=>array(array('email_verification','=','?','and'),array('email','=','?','or'),array('phone','=','?'))),array($otp,$aid,$aid));
						if(!$id)
							$msg = 'Invalid verification code entered';
						else {
							$password = TinyLibrary::Digest($apwd);
							$DB->exec(array('update'=>'accounts','set'=>array('password'=>'?','email_verification'=>'','mtime'=>time()),'where'=>array('id','=','?')),array($password,$id));
							$this->redirect('/login');
							# TODO: show message?
						}
					}
				} else {
					$DB = ZDBC::instance();
					$id = $DB->fetchOne(array('select'=>'id','from'=>'accounts','where'=>array(array('email_verification','=','?','and'),array('email','=','?','or'),array('phone','=','?'))),array($otp,$aid,$aid));
					if(!$id)
						$msg = 'Invalid verification code entered';
					else
						$sect = 'finalize';
				}
			} else {
				# NB: do NOT tell user whether email exists or not, only that code is sent to it!
				$DB = ZDBC::instance();
				if($id = $DB->fetchOne(array('select'=>'id','from'=>'accounts','where'=>array(array('email','=','?','or'),array('phone','=','?'))),array($aid,$aid))){
					$otp = rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9);
					$DB->exec(array('update'=>'accounts','set'=>array('email_verification'=>'?','mtime'=>time()),'where'=>array('id','=','?')),array($otp,$id));
					#
					$emailtxt = <<<TPL
Greetings!

Somebody has requested password recovery on Vergabo.

If it was not you, ignore this message.

If you indeed have requested password recovery, please enter following code in recovery form:

$otp

--
Vergabo

TPL;
					#
					@mail($aid,'Vergabo: recover password',$emailtxt,"From: {$this->vars['admin_email']}\r\nContent-Type: text/plain; charset=UTF-8");
				}
				$sect = 'otp';
			}
		}
		#
		if('finalize' == $sect)
			$content = TPL::FormRecoveryFinalize($this->b,$this->guard,$msg,$aid,$otp);
		elseif('otp' == $sect)
			$content = TPL::FormRecoveryOTP($this->b,$this->guard,$msg,$aid);
		else
			$content = TPL::FormRecoveryBegin($this->b,$this->guard,$msg,$aid);
		$this->send_body($content);
	}
	private function ActUserCP($req){
		if(!$this->user)
			$this->redirect('/login');
		if(!empty($req[1]) and 'change_password' == $req[1]){
			$apwd = ZWEI::post('apwd');
			$apwx = ZWEI::post('apwx');
			$apwy = ZWEI::post('apwy');
			#
			$msg = '';
			#
			if(false !== $apwd or false !== $apwx or false !== $apwy){
				if(!$apwd)
					$msg = 'New password cannot be blank';
				/*elseif(!$apwx)
					$msg = 'New password confirmation cannot be blank';*/
				elseif(!$apwy)
					$msg = 'Current password cannot be blank';
				/*elseif($apwd != $apwx)
					$msg = "Password confirmation don't match password";*/
				else {
					$DB = ZDBC::instance();
					$apwz = $DB->fetchOne(array('select'=>'password','from'=>'accounts','where'=>array('id','=','?')),array($this->user['id']));
					$valid = ($apwz and TinyLibrary::Digest($apwy,$apwz));
					$apwz = null; # preventing leaks
					if(!$valid)
						$msg = 'Current password incorrect';
					else {
						$apwd = TinyLibrary::Digest($apwd);
						if(!$DB->exec(array('update'=>'accounts','set'=>array('password'=>'?','mtime'=>time()),'where'=>array('id','=','?')),array($apwd,$this->user['id'])))
							$msg = "DB error: {$DB->error}";
						else
							$this->redirect('/usercp');
					}
				}
			}
			$apwd = $apwx = $apwy = null; # preventing leaks
			$this->title = 'Vergabo // User CP';
			$content = TPL::FormChangePassword($this->b,$this->guard,$msg);
			$this->send_body($content);
		}
		$xrole = '';
		$subline = '';
		if('buyer' == $this->user['role']){
			$xrole = 'Buyer';
			$subline = 'From here, you can manage your sourcing requests, track supplier quotes, and control your procurement activity in one place';
			$btncfg = array(
				array('#','Create New Request','Start a new RFQ for parts, components, or assemblies'),
				array('#','View Active Requests','Monitor ongoing sourcing rounds and supplier responses in real time'),
				array('#','Archived Requests','Access closed or past RFQs for reference or reordering'),
				array('#','Company Profile','Manage your contact information, team members, and Tax ID'),
				array($this->b.'/usercp/change_password','Change Password','Change current account password')
			);
		} elseif('supplier' == $this->user['role']){
			$xrole = 'Supplier';
			$subline = 'Here you can browse open RFQs, submit quotes, manage your offers, and monitor buyer activity — all in one place';
			$btncfg = array(
				array('#','View Requests','Browse open RFQs submitted by verified buyers'),
				array('#','My Quotes','Track and manage all your submitted quotations'),
				array('#','Favorites','Saved RFQs you’ve marked as favorites'),
				array('#','Company Profile','Update your contact details, team members, Tax ID, and subscription settings'),
				array($this->b.'/usercp/change_password','Change Password','Change current account password')
			);
		}
		#
		$btns = array();
		foreach($btncfg as $btn)
			$btns[] = TPL::UserCPButton($btn[0],$btn[1],$btn[2]);
		$rows = implode('',$btns);
		# TODO
		$this->title = 'Vergabo // User CP';
		$content = TPL::PageUserCP($this->b,$this->guard,$xrole,$subline,$rows);
		$this->send_body($content);
	}
	private function ActStatic($req){
		$page = implode('_',$req); # TODO: change on demand
		# TODO
		$this->title = 'Vergabo // Under construction';
		if(file_exists(TINY_ROOT.'static/'.$page.'.php'))
			require_once TINY_ROOT.'static/'.$page.'.php';
		else
			$content = TPL::PageIndex($this->b);
		$this->send_body($content);
	}
	private function ActAJAX($req){
		if(!empty($req[0])){
			if('countries.json' == $req[0]){
				$list = array();
				foreach(file(APP_ROOT.'countries.lst',FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $l){
					list($c,$n) = explode(':',$l);
					$list[$c] = $n;
				}
				$this->send_headers(true,true,'UTF-8','application/json');
				$this->send_body(json_encode($list),true);
			}
		}
	}
	private function ActIdx($req){
		# TODO
		$this->title = 'Vergabo // Under construction';
		$content = TPL::PageIndex($this->b);
		$this->send_body($content);
		/*
		if($en){
			$PL = new ZPage($en,$this->vars['per_page']);
			if($PL->pages < $page)
				$page = $PL->pages;
			$nav = TPL::NavWrap($PL->Nav($page,$this->_base.'/page/%ST%'));
			$DB->query(array('select'=>'*','from'=>'msg','offset'=>$PL->Offset($page),'limit'=>$this->vars['per_page']));
			$out = array();
			while($msg = $DB->fetch())
				$out[] = TPL::MsgWrap($msg);
			$out = implode(TPL::Sep(),$out);
			$out = TPL::MsgListWrap($nav.$out.$nav);
		}
		else
			$out = TPL::NoMsgWrap($this->lang['no_posts']);
		## implicit invocation of default send_headers
		$this->send_body($out);
		*/
	}
}
