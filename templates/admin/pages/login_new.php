	<section>
		<div class="color"></div>
		<div class="color"></div>
		<div class="color"></div>
		<div class="box">
			<div class="square" style="--i:0"></div>
			<div class="square" style="--i:1"></div>
			<div class="square" style="--i:2"></div>
			<div class="square" style="--i:3"></div>
			<div class="square" style="--i:4"></div>
			<div class="container">
				<div class="form">
					<h2>Login to admin area</h2>
<form name="login" method="POST" action="/admin/login" class="noajax loginform" id="loginform">
<input type="hidden" name="token" value="{$token}" />
						<div class="inputBox">
<input placeholder="E-mail" class="login-enter def email<?php if ($_POST['email']) echo ' def'; ?>" type="text" name="email" value="<?php if (DEMO) { echo 'a@a.com'; }  elseif ($_POST['email']) { echo $_POST['email']; } ?>" />
						</div>
						<div class="inputBox">
                            <input placeholder="Password" type="password" class="login-enter def" name="password" value="<?php if (DEMO) echo '01230';?>" />
						</div>
						<div class="inputBox">
							<div class="btn" onclick="document.getElementById('loginform').submit();"><a href="javascript: void(0);">Login</a></div>
							<div class="btn" onclick="window.open('{$current_location}', '');"><a href="javascript: void(0);">Open site</a></div>
<div class="clear"></div>
						</div>
						<p class="forget">Forgot password ? <a href="javascript: void(0);" onclick="javascript: return restore_password();">Click Here</a></p>
					</form>
				</div>
			</div>
		</div>
	</section>