{include="mail/header.php"}
{$email_header}
{lng[Dear]} {$user['firstname']},
<br>
<br>
{lng[To set new password]}, <a href="{$http_location}/password/{$user['id']}/{$key}">{lng[click here]}</a>
<br /><br />
{$signature};
{include="mail/footer.php"}