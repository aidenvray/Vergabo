<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
		<title><?php echo $this->title;?></title>
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
		<link rel="stylesheet" type="text/css" href="<?php echo $this->lc('main.css');?>">
	</head>
	<body>
		<div class="container">
			<div class="row" id="header">
				<div class="col-2" id="header_logo_wrap">
					<a href="<?php echo $this->b;?>/"><img src="<?php echo $this->lc('graphics/logo.png');?>" alt="Vergabo Logo" id="header_logo"></a>
					<img src="<?php echo $this->lc('graphics/separator.svg');?>" alt="|" id="header_separator">
				</div>
				<div class="col-5" id="header_clock">
					<div><?php echo date('H:i');?> (CET)</div>
					<div><?php echo date('D., j. F Y');?></div>
				</div>
				<div class="col-5" id="header_userctl">
					<?php echo $this->userbar;?>
				</div>
			</div>
			<div class="row" id="content">
				<div class="col">
					<ul class="nav nav-tabs">
						<?php echo $this->navbar;?>
					</ul>
					<?php echo $content;?>
				</div>
			</div>
			<div class="row" id="prefooter">
				<div class="col-3">
					<h5>General</h5>
					<div><a href="<?php echo $this->b;?>/about">About</a></div>
					<div><a href="<?php echo $this->b;?>/contact">Contact</a></div>
					<div><a href="<?php echo $this->b;?>/legal">Legal</a></div>
				</div>
				<div class="col-3">
					<h5>Support</h5>
					<div><a href="<?php echo $this->b;?>/payment">Payment Information</a></div>
					<div><a href="<?php echo $this->b;?>/help">Help Center / FAQ</a></div>
					<div><a href="<?php echo $this->b;?>/pricing">Pricing</a></div>
				</div>
				<div class="col-4"></div>
				<div class="col-2 text-right">
					<div class="pinned-bottom">
						<a href="#"><img src="<?php echo $this->lc('graphics/linkedin.svg');?>"></a>
						<a href="#"><img src="<?php echo $this->lc('graphics/u19.svg');?>"></a>
					</div>
				</div>
			</div>
			<div class="row" id="footer">
				<div class="col-1"><a href="<?php echo $this->b;?>/contacts">Contact</a></div>
				<div class="col-1"><a href="<?php echo $this->b;?>/privacy">Privacy</a></div>
				<div class="col-1"><a href="<?php echo $this->b;?>/terms">Terms</a></div>
				<div class="col-2"><a href="<?php echo $this->b;?>/cookies">Cookie Notice</a></div>
				<div class="col-4"></div>
				<div class="col-3">&copy; 2024 Ecom Systems GmbH</div>
			</div>
		</div>
		<!-- login -->
		<div class="modal" tabindex="-1">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body">
						<h5>Sign In</h5>
						<form action="<?php echo $this->b;?>/login" method="post">
							<input type="hidden" name="token" value="">
							<div class="form-group">
								<input type="text" class="form-control" name="aid" placeholder="Email">
							</div>
							<div class="form-group">
								<input type="text" class="form-control" name="apwd" placeholder="Password">
							</div>
						</form>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-primary">Sign In</button>
					</div>
				</div>
			</div>
		</div>
		<!-- /login -->
		<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous"></script>
		<script src="<?php echo $this->lc('main.js');?>"></script>
		<?php echo $this->jsinc;?>
	</body>
</html>