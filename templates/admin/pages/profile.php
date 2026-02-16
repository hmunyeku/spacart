<center>
<form name="register" method="POST">
<div class="regsec">
<input class="name firstname<?php if ($user['firstname']) echo ' def'; ?>" maxlength="32" type="text" name="firstname" value="<?php if ($user['firstname']) echo $user['firstname']; else {?>{lng[First name|escape]}<?php} ?>" />
<input class="name<?php if ($user['lastname']) echo ' def'; ?>" maxlength="32" type="text" name="lastname" value="<?php if ($user['lastname']) echo $user['lastname']; else {?>{lng[Last name|escape]}<?php} ?>" />
<br />
<input type="text"<?php if ($user['email']) echo ' class="def"'; ?> name="email" value="<?php if ($user['email']) echo $user['email']; else {?>{lng[Email|escape]}<?php} ?>" />
<br />
<input type="<?php if ($get['0'] == 'register') echo 'text'; else echo 'password';?>" name="password" placeholder="{lng[Password|escape]}" autocomplete="off" />
<br />
<input type="text"<?php if ($user['address']) echo ' class="def"'; ?> name="address" value="<?php if ($user['address']) echo $user['address']; else {?>{lng[Address|escape]}<?php} ?>" />
<br />
<input type="text"<?php if ($user['city']) echo ' class="def"'; ?> name="city" value="<?php if ($user['city']) echo $user['city']; else {?>{lng[City|escape]}<?php} ?>" />
<br />
<input type="text"<?php if ($user['zipcode']) echo ' class="def"'; ?> name="zipcode" value="<?php if ($user['zipcode']) echo $user['zipcode']; else {?>{lng[Zip/Postal code|escape]}<?php} ?>" />
<br />
<input type="text"<?php if ($user['phone']) echo ' class="def"'; ?> name="phone" value="<?php if ($user['phone']) echo $user['phone']; else {?>{lng[Phone|escape]}<?php} ?>" />
<br /><br />
<button type="button" class="big">{lng[Save]}</button>
</div>
</form>