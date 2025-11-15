<?php
class TPL {
	## pages
	public static function PageIndex($b){
		return <<<TPL
<h1>Under construction</h1>
<p>Please come back later to see this page contents</p>
TPL;
	}
	public static function PageUserCP($b,$xrole,$subline,$rows){
		#Here you can browse open RFQs, submit quotes, manage your offers, and monitor buyer activity — all in one place
		#From here, you can manage your sourcing requests, track supplier quotes, and control your procurement activity in one place
		return <<<TPL
					<!-- dashboard -->
					<div id="dashboard" class="common_section">
						<h3 class="w-100 text-center">$xrole Dashboard</h3>
						<p>Welcome to your Vergabo dashboard</p>
						<p>$subline</p>
						<br>
						<p>Your Tools</p>
						<div class="container sparse">
							$rows
						</div>
					</div>
					<!-- /dashboard -->
TPL;
	}
	## forms
	public static function FormChangePassword($b,$guard,$msg){
		$msg = self::FormWarningWrap($msg);
		return <<<TPL
					<!-- form:change_password -->
					<script defer type="text/javascript" src="$b/content/password-score.js"></script>
					<script defer type="text/javascript" src="$b/content/password-score-options.js"></script>
					<script defer type="text/javascript" src="$b/content/bootstrap-strength-meter.js"></script>
					<div id="change_password" class="common_section">
						<form action="{$b}/usercp/change_password" method="post" class="needs-validation" novalidate>
							<input type="hidden" name="token" value="$guard">
							<h3>Change password</h3>
							$msg
							<div class="form-group">
								<label for="apwd">Password</label>
								<div class="input-group">
									<input type="password" class="form-control" id="apwd" name="apwd" placeholder="Password" required minlength="8">
									<div class="input-group-append"><i class="input-group-text bi bi-eye-slash" id="show_apwd"></i></div>
									<div class="invalid-feedback">Password doesn't meets criteria</div>
								</div>
							</div>
							<div class="form-group">
								<div class="pw-strength-bar"></div>
								<h6>Minimum 8 characters, 1 capital letter, 1 number</h6>
							</div>
							<div class="form-group input-group">
								<label for="apwy"><strong>Current</strong> password</label>
								<div class="input-group">
									<input type="password" class="form-control" id="apwy" name="apwy" placeholder="Current password" required>
									<div class="input-group-append"><i class="input-group-text bi bi-eye-slash" id="show_apwy"></i></div>
									<div class="invalid-feedback">Password doesn't meets criteria</div>
								</div>
							</div>
							<div class="form-group">
								<button type="submit" class="btn btn-primary btn-lg w-100">Change</button>
							</div>
						</form>
					</div>
					<!-- /form:change_password -->
TPL;
	}
	public static function FormLoginFull($b,$guard,$msg,$aid){
		$msg = self::FormWarningWrap($msg);
		return <<<TPL
					<!-- form:login -->
					<div id="login" class="common_section">
						<form action="{$b}/login" method="post">
							<input type="hidden" name="token" value="$guard">
							<h3>Sign In</h3>
							$msg
							<small>Don't have an account? <a href="{$b}/register">Sign Up</a></small>
							<div class="form-group">
								<label for="aid">Email</label>
								<input type="text" class="form-control" id="aid" name="aid" placeholder="Email" value="$aid" required>
							</div>
							<div class="form-group">
								<label for="apwd">Password</label>
								<div class="input-group">
									<input type="password" class="form-control" id="apwd" name="apwd" placeholder="Password" required>
									<div class="input-group-append"><i class="input-group-text bi bi-eye-slash" id="show_apwy"></i></div>
								</div>
							</div>
							<div class="form-group form-check clearfix">
								<input type="checkbox" class="form-check-input" id="autologin" name="autologin">
								<label class="form-check-label" for="autologin">Remember me</label>
								<small class="float-right"><a href="{$b}/recovery">Forgot your password?</a></small>
								<br>
							</div>
							<div class="form-group">
								<button type="submit" class="btn btn-primary btn-lg w-100">Sign In</button>
							</div>
						</form>
					</div>
					<!-- /form:login -->
TPL;
	}
	public static function FormRecoveryBegin($b,$guard,$msg,$aid){
		$msg = self::FormWarningWrap($msg);
		return <<<TPL
					<!-- form:recovery -->
					<div id="recovery" class="common_section">
						<form action="{$b}/recovery/begin" method="post">
							<input type="hidden" name="token" value="$guard">
							<h3>Recover lost password</h3>
							$msg
							<div class="form-group">
								<label for="aid">Email</label>
								<input type="text" class="form-control" id="aid" name="aid" placeholder="Email" value="$aid" required>
							</div>
							<div class="form-group">
								<button type="submit" class="btn btn-primary btn-lg w-100">Send verification code</button>
							</div>
						</form>
					</div>
					<!-- /form:recovery -->
TPL;
	}
	public static function FormRecoveryOTP($b,$guard,$msg,$aid){
		$msg = self::FormWarningWrap($msg);
		return <<<TPL
					<!-- form:recovery -->
					<div id="recovery" class="common_section">
						<form action="{$b}/recovery/otp" method="post">
							<input type="hidden" name="token" value="$guard">
							<input type="hidden" name="aid" value="$aid">
							<h3>Enter code</h3>
							<p>We've sent you the verification code. Please check your email and enter code here.</p>
							$msg
							<div class="form-group">
								<label for="otp">Email</label>
								<input type="text" class="form-control" id="otp" name="otp" placeholder="Code from email" required>
							</div>
							<div class="form-group">
								<button type="submit" class="btn btn-primary btn-lg w-100">Proceed</button>
							</div>
						</form>
					</div>
					<!-- /form:recovery -->
TPL;
	}
	public static function FormRecoveryFinalize($b,$guard,$msg,$aid,$otp){
		$msg = self::FormWarningWrap($msg);
		return <<<TPL
					<!-- form:recovery -->
					<script defer type="text/javascript" src="$b/content/password-score.js"></script>
					<script defer type="text/javascript" src="$b/content/password-score-options.js"></script>
					<script defer type="text/javascript" src="$b/content/bootstrap-strength-meter.js"></script>
					<div id="recovery" class="common_section">
						<form action="{$b}/recovery/finalize" method="post" class="needs-validation" novalidate>
							<input type="hidden" name="token" value="$guard">
							<input type="hidden" name="aid" value="$aid">
							<input type="hidden" name="otp" value="$otp">
							<h3>Enter code</h3>
							$msg
							<div class="form-group">
								<label for="apwd">Password</label>
								<div class="input-group">
									<input type="password" class="form-control" id="apwd" name="apwd" placeholder="New password" required>
									<div class="input-group-append"><i class="input-group-text bi bi-eye-slash" id="show_apwd"></i></div>
									<div class="invalid-feedback">Password doesn't meets criteria</div>
								</div>
							</div>
							<div class="form-group">
								<div class="pw-strength-bar"></div>
								<h6>Minimum 8 characters, 1 capital letter, 1 number</h6>
							</div>
							<div class="form-group">
								<button type="submit" class="btn btn-primary btn-lg w-100">Recover password</button>
							</div>
						</form>
					</div>
					<!-- /form:recovery -->
TPL;
	}
	public static function FormRegister($b,$guard,$msg,$v,$country_field){
		$role_buyer_label = 'buyer' == $v['role'] ? " active" : '';
		$role_buyer_input = 'buyer' == $v['role'] ? " checked" : '';
		$role_supplier_label = 'supplier' == $v['role'] ? " active" : '';
		$role_supplier_input = 'supplier' == $v['role'] ? " checked" : '';
		$msg = self::FormWarningWrap($msg);
		return <<<TPL
					<!-- form:register -->
					<script defer type="text/javascript" src="$b/content/password-score.js"></script>
					<script defer type="text/javascript" src="$b/content/password-score-options.js"></script>
					<script defer type="text/javascript" src="$b/content/bootstrap-strength-meter.js"></script>
					<div id="register" class="common_section">
						<h3 class="w-100 text-center vgblue">Create your free Vergabo account</h3>
						<h5 class="w-100 text-center vgblue">Get access to global industrial RFQs and verified buyers or suppliers</h5>
						<form action="{$b}/register" method="post" class="needs-validation" novalidate>
							<input type="hidden" name="token" value="$guard">
							$msg
							<p>I'm registering as:</p>
							<div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
								<label class="btn btn-secondary btn-vgreg btn-lg$role_buyer_label">
									<input type="radio" name="role" value="buyer" id="buyer" required$role_buyer_input> Buyer
								</label>
								<label class="btn btn-secondary btn-vgreg btn-lg$role_supplier_label">
									<input type="radio" name="role" value="supplier" id="supplier" required$role_supplier_input> Supplier
								</label>
							</div>
							<div class="form-row">
								<div class="col">
									<input type="text" class="form-control" name="first_name" value="{$v['first_name']}" placeholder="First name" required>
									<div class="invalid-feedback">First name cannot be blank</div>
								</div>
								<div class="col">
									<input type="text" class="form-control" name="last_name" value="{$v['last_name']}" placeholder="Last name" required>
									<div class="invalid-feedback">Last name cannot be blank</div>
								</div>
							</div>
							<div class="form-row">
								<div class="col">
									<input type="text" class="form-control" name="organization" value="{$v['organization']}" placeholder="Company / Organization Name" required>
									<div class="invalid-feedback">Organization cannot be blank</div>
								</div>
								<div class="col input-group">
									<input type="text" class="form-control" name="tax_id" value="{$v['tax_id']}" placeholder="Tax ID Number" required>
									<div class="input-group-append"><i class="input-group-text bi bi-question-circle" data-toggle="popover" title="What is a Tax ID Number (TIN)?" data-content="<p>Your company’s Tax Identification Number is used for business verification, invoicing, and compliance.</p><p>Examples:</p><ul><li>USA — EIN / TIN</li><li>EU — VAT ID</li><li>UK — Company Number</li><li>DE — USt-IdNr</li></ul>"></i></div>
									<div class="invalid-feedback">Tax ID number cannot be blank</div>
								</div>
							</div>
							<div class="form-row">
								<div class="col">
									$country_field
									<div class="invalid-feedback">You must select your country</div>
								</div>
								<div class="col">
									<input type="text" class="form-control" name="postal_code" value="{$v['postal_code']}" placeholder="Postal Code" required>
									<div class="invalid-feedback">Postal code cannot be blank</div>
								</div>
							</div>
							<div class="form-row">
								<div class="col">
									<input type="text" class="form-control" name="city" value="{$v['city']}" placeholder="City" required>
									<div class="invalid-feedback">City name cannot be blank</div>
								</div>
								<div class="col">
									<input type="text" class="form-control" name="endpoint" value="{$v['endpoint']}" placeholder="Street address or P.O. Box" required>
									<div class="invalid-feedback">Address cannot be blank</div>
								</div>
							</div>
							<div class="form-row">
								<div class="col">
									<input type="email" class="form-control" name="email" value="{$v['email']}" placeholder="Business email address" required>
									<div class="invalid-feedback">Invalid email specified</div>
								</div>
								<div class="col"></div>
							</div>
							<div class="form-row">
								<div class="col input-group">
									<input type="password" class="form-control" id="apwd" name="apwd" placeholder="Password" required minlength="8">
									<div class="input-group-append"><i class="input-group-text bi bi-eye-slash" id="show_apwd"></i></div>
									<div class="invalid-feedback">Password doesn't meets criteria</div>
								</div>
								<div class="col">
									<div class="pw-strength-bar"></div>
									<h6>Minimum 8 characters, 1 capital letter, 1 number</h6>
								</div>
							</div>
							<div class="form-row">
								<div class="col">
									<button type="submit" class="btn btn-primary btn-lg w-100" data-toggle="tooltip" data-placement="bottom" title="Let’s go! Your account will be created instantly.">Create Free Account</button>
								</div>
								<div class="col">
									<span class="vgblue">No credit card required&nbsp;&middot;&nbsp;Takes 1 minute</span>
								</div>
							</div>
						</form>
					</div>
					<!-- /form:register -->

TPL;
		# old reg form, inactive, just in case
		return <<<TPL
					<!-- form:register -->
					<div id="register" class="common_section">
						<form action="{$b}/register" method="post">
							<input type="hidden" name="token" value="$guard">
							$msg
							<div class="btn-group-vertical btn-group-toggle" data-toggle="buttons">
								<label class="btn btn-primary btn-lg$role_buyer_label">
									<input type="radio" name="role" value="buyer" id="buyer" required$role_buyer_input> Register as a Buyer
								</label>
								<h4>OR</h4>
								<label class="btn btn-primary btn-lg$role_supplier_label">
									<input type="radio" name="role" value="supplier" id="supplier" required$role_supplier_input> Register as a Supplier
								</label>
							</div>
							<div class="form-row">
								<div class="col">
									<input type="text" class="form-control" name="first_name" value="{$v['first_name']}" placeholder="First name" required>
								</div>
								<div class="col">
									<input type="text" class="form-control" name="last_name" value="{$v['last_name']}" placeholder="Last name" required>
								</div>
							</div>
							<div class="form-group">
								<input type="text" class="form-control" name="organization" value="{$v['organization']}" placeholder="Company / Organization Name">
							</div>
							<div class="form-group">
								<input type="text" class="form-control" name="tax_id" value="{$v['tax_id']}" placeholder="Tax ID Number (TIN), for business verification and invoicing purposes">
							</div>
							<div class="form-group">
								$country_field
							</div>
							<div class="form-group">
								<input type="text" class="form-control" name="endpoint" value="{$v['endpoint']}" placeholder="Street address or P.O. Box" required>
							</div>
							<div class="form-row">
								<div class="col">
									<input type="text" class="form-control" name="city" value="{$v['city']}" placeholder="City" required>
								</div>
								<div class="col">
									<input type="text" class="form-control" name="postal_code" value="{$v['postal_code']}" placeholder="Postal Code" required>
								</div>
							</div>
							<div class="form-row">
								<div class="col">
									<input type="email" class="form-control" name="email" value="{$v['email']}" placeholder="Business email address" required>
								</div>
								<div class="col">
									<input type="phone" class="form-control" name="phone" value="{$v['phone']}" placeholder="Phone number with country code" required>
								</div>
							</div>
							<div class="form-group">
								<input type="password" class="form-control" name="apwd" placeholder="Password" required>
							</div>
							<div class="form-group">
								<input type="password" class="form-control" name="apwx" placeholder="Repeat password" required>
							</div>
							<div class="form-group">
								<button type="submit" class="btn btn-primary btn-lg w-100">Create Account</button>
							</div>
						</form>
					</div>
					<!-- /form:register -->

TPL;
	}
	## elements
	public static function FormText($name,$v,$dis=false,$req=false,$ph=''){
		$req = $req ? ' required' : '';
		$dis = $dis ? ' disabled' : '';
		return <<<TPL
<input type="text" class="form-control" name="$name" value="$v" placeholder="$ph"$req$dis>
TPL;
	}
	public static function FormSelect($name,$rows,$dis=false,$req=false){
		$req = $req ? ' required' : '';
		$dis = $dis ? ' disabled' : '';
		return <<<TPL
<select class="form-control" name="$name"$req$dis>$rows</select>
TPL;
	}
	public static function FormSelectOption($v,$txt,$active=false,$dis=false,$data=null){
		$xdat = '';
		if($data and is_array($data)){
			$xdat = array();
			foreach($data as $k => $v)
				$xdat[] = ' data-'.$k.'="'.$v.'"';
			$xdat = implode('',$xdat);
		}
		$dis = $dis ? ' disabled' : '';
		$active = $active ? ' selected' : '';
		return <<<TPL
<option value="$v"$xdat$active$dis>$txt</option>
TPL;
	}
	public static function NavBarLink($url,$txt,$active=false){
		$active = $active ? ' active' : '';
		return <<<TPL
						<li class="nav-item">
							<a class="nav-link$active" href="$url">$txt</a>
						</li>
TPL;
	}
	public static function UserBarButton($url,$txt){
		return <<<TPL
						<a href="$url" class="w-30 btn btn-primary btn-lg">$txt</a>
TPL;
	}
	public static function UserCPButton($url,$name,$description){
		return <<<TPL
							<div class="row">
								<div class="col-4">
									<a href="$url" class="btn btn-primary btn-lg w-100">$name</a>
								</div>
								<div class="col-8">
									$description
								</div>
							</div>
TPL;
	}
	public static function FormWarningWrap($w){
		if(!$w)
			return '';
		return <<<TPL
<div class="alert alert-danger" role="alert"><small>$w</small></div>
TPL;
	}
}
