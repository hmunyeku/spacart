{include="mail/header.php"}
{$email_header}
{lng[Hello]},
<br /><br />
<p>{lng[New testimonial is here]}</p>
<br />
<a href="{$http_location}/admin/testimonials">{$http_location}/admin/testimonials</a>
<br /><br />
{$signature};
{include="mail/footer.php"}