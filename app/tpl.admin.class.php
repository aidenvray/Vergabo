<?php
class TPL {
	## pages
	public static function PageIndex($name){
		return <<<TPL
		<div class="container">
			<div class="row">
				<div class="col-2"></div>
				<div class="col-8">
					<h1>Welcome, <b>$name!</b></h1>
					<p>Here you can manage current system deployment.</p>
				</div>
				<div class="col-2"></div>
			</div>
		</div>
TPL;
	}
	public static function PageError($msg){
		return <<<TPL
		<div class="container">
			<div class="row">
				<div class="col-2"></div>
				<div class="col-8">
					<h1>ERROR</h1>
					<p>$msg</p>
				</div>
				<div class="col-2"></div>
			</div>
		</div>
TPL;
	}
	public static function PageList($what,$table,$pagination){
		return <<<TPL
<h1>List: $what</h1>
$pagination
$table
$pagination
TPL;
	}
	public static function PageConfirmDelete($url_ok,$url_no,$what,$id){
		return <<<TPL
		<div class="container">
			<div class="row">
				<div class="col-2"></div>
				<div class="col-8">
					<h3>Delete: $what ID $id</h3>
					<h4>Are you sure?</h4>
					<div class="form-row">
						<div class="col">
							<a href="$url_ok" class="btn btn-lg btn-block btn-danger" role="button">Yes</a>
						</div>
						<div class="col">
							<a href="$url_no" class="btn btn-lg btn-block btn-secondary" role="button">No</a>
						</div>
					</div>
				</div>
				<div class="col-2"></div>
			</div>
		</div>
TPL;
	}
	public static function PageImport($url,$msg){
		$msg = self::FormWarningWrap($msg);
		return <<<TPL
		<div class="container">
			<div class="row">
				<div class="col-2"></div>
				<div class="col-8">
					<h3>Import data from file</h3>
					$msg
					<form action="$url" method="post" enctype="multipart/form-data">
						<div class="form-group">
							<label for="aid">File:</label>
							<input type="file" class="form-control" id="imported" name="imported" required>
						</div>
						<div class="form-group">
							<button type="submit" class="btn btn-primary btn-lg w-100">Upload</button>
						</div>
					</form>
				</div>
				<div class="col-2"></div>
			</div>
		</div>
TPL;
	}
	#
	public static function TableWrap($rows){
		return <<<TPL
<table class="table">
	$rows
</table>
TPL;
	}
	public static function TableRow($cols,$span=1){
		$span = (1 < $span) ? ' rowspan="'.$span.'"' : '';
		return <<<TPL
<tr$span>$cols</tr>
TPL;
	}
	public static function TableCell($txt,$header='',$span=1){
		$span = (1 < $span) ? ' colspan="'.$span.'"' : '';
		$tag = ('col' == $header or 'row' == $header) ? 'th' : 'td';
		$scope = ('col' == $header or 'row' == $header) ? ' scope="'.$header.'"' : '';
		return <<<TPL
<$tag$scope$span>$txt</$tag>
TPL;
	}
	public static function TableBtn($txt,$url,$style='primary'){
		return <<<TPL
<a href="$url" class="btn btn-sm btn-$style" role="button">$txt</a>
TPL;
	}
	#
	public static function FormLogin($url,$msg,$aid){
		$msg = self::FormWarningWrap($msg);
		return <<<TPL
		<div class="container">
			<div class="row">
				<div class="col-2"></div>
				<div class="col-8">
					<!-- form:login -->
					<div id="login" class="common_section">
						<form action="$url" method="post">
							<h3>Log In</h3>
							$msg
							<div class="form-group">
								<label for="aid">Login</label>
								<input type="text" class="form-control" id="aid" name="aid" placeholder="Login" value="$aid" required>
							</div>
							<div class="form-group">
								<label for="apwd">Password</label>
								<div class="input-group">
									<input type="password" class="form-control" id="apwd" name="apwd" placeholder="Password" required>
									<div class="input-group-append"><i class="input-group-text bi bi-eye-slash" id="show_apwy"></i></div>
								</div>
							</div>
							<div class="form-group">
								<button type="submit" class="btn btn-primary btn-lg w-100">Sign In</button>
							</div>
						</form>
					</div>
					<!-- /form:login -->
				</div>
				<div class="col-2"></div>
			</div>
		</div>
TPL;
	}
	public static function FormEdit($url,$label_top,$label_commit,$fields,$msg){
		$msg = self::FormWarningWrap($msg);
		return <<<TPL
		<div class="container">
			<div class="row">
				<div class="col-2"></div>
				<div class="col-8">
					<form action="$url" method="post">
						<h3>$label_top</h3>
						$msg
						$fields
						<div class="form-row">
							<div class="col">
								<button name="commit" class="btn btn-lg btn-block btn-danger" role="submit">$label_commit</button>
							</div>
							<div class="col">
								<button name="cancel" class="btn btn-lg btn-block btn-secondary" role="submit">Cancel</button>
							</div>
						</div>
					</form>
				</div>
				<div class="col-2"></div>
			</div>
		</div>
TPL;
	}
	public static function FormRow($name,$ctl,$label){
		return <<<TPL
						<div class="form-row">
							<div class="col text-right">
								<label for="$name">$label</label>
							</div>
							<div class="col">
								<div class="input-group">
									$ctl
								</div>
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
	#
	public static function MenuWrap($menu){
		return <<<TPL
<ul class="nav">
	$menu
</ul>
TPL;
	}
	public static function MenuLink($txt,$url='',$status=0){
		$status = (1 === $status) ? ' active' : (-1 === $status ? ' disabled' : '');
		return <<<TPL
<li class="nav-item">
	<a href="$url" class="nav-link$status">$txt</a>
</li>
TPL;
	}
	#
	public static function FormText($name,$v,$dis=false,$req=false,$ph=''){
		$req = $req ? ' required' : '';
		$dis = $dis ? ' disabled' : '';
		return <<<TPL
<input type="text" class="form-control" name="$name" value="$v" placeholder="$ph"$req$dis>
TPL;
	}
	public static function FormFlag($name,$v=false,$dis=false,$req=false){
		$v = $v ? ' checked' : '';
		$req = $req ? ' required' : '';
		$dis = $dis ? ' disabled' : '';
		return <<<TPL
<input type="checkbox" class="form-control" name="$name"$v$req$dis>
TPL;
	}
	public static function FormFlagNull($name,$active=false){
		$active = $active ? ' checked' : '';
		return <<<TPL
<label for="$name"><input type="checkbox" class="form-control" name="$name"$active> Null?</label>
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
}
