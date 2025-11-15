<?php
class TinyLibrary {
	# single point hashing/verification
	public static function Digest($input,$verify=null){
		$out = null;
		if(!$verify and function_exists('password_hash')) # the most costly one to bf, php_version >= 5.5, sodium
			$out = password_hash($input,PASSWORD_BCRYPT);
		elseif(function_exists('openssl_digest')) # php_version < 5.5, openssl
			$out = openssl_digest(openssl_digest($input,'SHA256'),'SHA256');
		elseif(function_exists('mhash')) # php_version < 5.5, no openssl, mhash
			$out = mhash(MHASH_SHA256,mhash(MHASH_SHA256,$input));
		else # nothing at all
			$out = substr(sha1(sha1(sha1(sha1(sha1($input))))).sha1(sha1(sha1(sha1(sha1($input))))),0,64);
		#
		if($verify and function_exists('password_verify'))
			$out = password_verify($input,$verify);
		elseif($verify)
			$out = ($out == $verify);
		return $out;
	}
	# single point random hash generation
	public static function DigestRandom(){
		$out = null;
		if(function_exists('openssl_random_pseudo_bytes'))
			list(,$out) = unpack('H*',openssl_random_pseudo_bytes(32));
		else
			$out = self::Digest(microtime().rand().microtime().rand().rand());
		return $out;
	}
	#
	public static function EntityConfiguration($entity){
		$cfg = array(
			'buyer'			=> array(
				'table'		=> 'accounts', # TODO: project this through!
				'id'		=> array('name' => 'id','type' => 'int','ordinal' => true),
				'filter'	=> array(array('role','buyer')),
				'search'	=> array('first_name','last_name','email','phone','organization','tax_id','country','city','postal_code','endpoint'),
				'fields'	=> array(
					'first_name'	=> array('label' => 'First Name','type' => 'text','rw' => true),
					'last_name'		=> array('label' => 'Last Name','type' => 'text','rw' => true),
					'organization'	=> array('label' => 'Organization','type' => 'text','rw' => true),
					'tax_id'		=> array('label' => 'Tax ID','type' => 'text','rw' => true),
					'country'		=> array('label' => 'Country','type' => 'text','rw' => true),
					'city'			=> array('label' => 'City','type' => 'text','rw' => true),
					'postal_code'	=> array('label' => 'Postal Code','type' => 'text','rw' => true),
					'endpoint'		=> array('label' => 'Address','type' => 'text','rw' => true),
					'role'			=> array('label' => 'Role','type' => 'select','rw' => true,'opts' => array('buyer' => 'Buyer', 'supplier' => 'Supplier'), 'opt_default' => 'buyer'),
					'email'			=> array('label' => 'Email','type' => 'text','rw' => true,'unique' => true),
					'phone'			=> array('label' => 'Phone','type' => 'text','rw' => true,'nullable' => true,'hidden' => true),
					'password'		=> array('label' => 'Password','type' => 'text','rw' => true,'sensitive' => true),
					'autologin'		=> array('label' => 'Autologin key','type' => 'text','rw' => true,'sensitive' => true),
					'ctime'			=> array('label' => 'Created','type' => 'timestamp','rw' => false),
					'mtime'			=> array('label' => 'Updated','type' => 'timestamp','rw' => false),
					'email_verification'	=> array('label' => 'Validation','type' => 'text','rw' => true,'nullable' => true),
					'phone_verification'	=> array('label' => 'Validation','type' => 'text','rw' => true,'nullable' => true, 'hidden' => true),
					'status'		=> array('label' => 'Status','type' => 'select','rw' => true,'opts' => array(0 => 'Normal',1 => 'Read-only',-1 => 'Blocked')),
					'email_ok'		=> array('label' => 'Email OK','type' => 'checkbox','rw' => true),
					'phone_ok'		=> array('label' => 'Phone OK','type' => 'checkbox','rw' => true, 'hidden' => true),
				)
			),
			'supplier'		=> array(
				'table'		=> 'accounts', # TODO: project this through!
				'id'		=> array('name' => 'id','type' => 'int','ordinal' => true),
				'filter'	=> array(array('role','supplier')),
				'search'	=> array('first_name','last_name','email','phone','organization','tax_id','country','city','postal_code','endpoint'),
				'fields'	=> array(
					'first_name'	=> array('label' => 'First Name','type' => 'text','rw' => true),
					'last_name'		=> array('label' => 'Last Name','type' => 'text','rw' => true),
					'organization'	=> array('label' => 'Organization','type' => 'text','rw' => true),
					'tax_id'		=> array('label' => 'Tax ID','type' => 'text','rw' => true),
					'country'		=> array('label' => 'Country','type' => 'text','rw' => true),
					'city'			=> array('label' => 'City','type' => 'text','rw' => true),
					'postal_code'	=> array('label' => 'Postal Code','type' => 'text','rw' => true),
					'endpoint'		=> array('label' => 'Address','type' => 'text','rw' => true),
					'role'			=> array('label' => 'Role','type' => 'select','rw' => true,'opts' => array('buyer' => 'Buyer', 'supplier' => 'Supplier'), 'opt_default' => 'buyer'),
					'email'			=> array('label' => 'Email','type' => 'text','rw' => true,'unique' => true),
					'phone'			=> array('label' => 'Phone','type' => 'text','rw' => true,'nullable' => true,'hidden' => true),
					'password'		=> array('label' => 'Password','type' => 'text','rw' => true,'sensitive' => true),
					'autologin'		=> array('label' => 'Autologin key','type' => 'text','rw' => true,'sensitive' => true),
					'ctime'			=> array('label' => 'Created','type' => 'timestamp','rw' => false),
					'mtime'			=> array('label' => 'Updated','type' => 'timestamp','rw' => false),
					'email_verification'	=> array('label' => 'Validation','type' => 'text','rw' => true,'nullable' => true),
					'phone_verification'	=> array('label' => 'Validation','type' => 'text','rw' => true,'nullable' => true, 'hidden' => true),
					'status'		=> array('label' => 'Status','type' => 'select','rw' => true,'opts' => array(0 => 'Normal',1 => 'Read-only',-1 => 'Blocked')),
					'email_ok'		=> array('label' => 'Email OK','type' => 'checkbox','rw' => true),
					'phone_ok'		=> array('label' => 'Phone OK','type' => 'checkbox','rw' => true, 'hidden' => true),
				)
			),
			'manufacturer'	=> array(
				'table'		=> 'manufacturers',
				'id'		=> array('name' => 'id','type' => 'int','ordinal' => true),
				'search'	=> array('name','description'),
				'fields'	=> array(
					'name'			=> array('label' => 'Name','type' => 'text','rw' => true),
					'description'	=> array('label' => 'Description','type' => 'text','rw' => true),
					'ratio'			=> array('label' => 'Ratio','type' => 'int','rw' => true),
					'active'		=> array('label' => 'Active','type' => 'checkbox','rw' => true),
					'ctime'			=> array('label' => 'Created','type' => 'timestamp','rw' => false),
					'mtime'			=> array('label' => 'Updated','type' => 'timestamp','rw' => false),
				)
			),
		);
		#
		if(isset($cfg[$entity]))
			return $cfg[$entity];
		return null;
	}
}
