{include="mail/header.php"}
{$email_header}
{lng[Hello]}
<br /><br />
<p>{lng[Message from]} {$email}</p>
<br />{php echo func_eol2br($message);}
<br /><br />
{$signature}
{include="mail/footer.php"}