<div id="left_menu">
<div class="mobile-menu-links">
{if $languages}
<div class="language_select mobile_languages_select">
{lng[Language]}:
<select>
{foreach $languages as $c}
<option value="{$c['id']}"{if $current_language['id'] == $c['id'] || (!$current_language['id'] && $c['main'])} selected{/if}>{$c['name']}</option>
{/foreach}
</select>
</div>
{/if}

{if $currencies}
<div class="currency_select mobile_currency_select">
{lng[Currency]}:
<select>
{foreach $currencies as $c}
<option value="{$c['id']}"{if $current_currency['id'] == $c['id'] || (!$current_currency['id'] && $c['main'])} selected{/if}>{$c['code']}</option>
{/foreach}
</select>
</div>
{/if}

{if $currencies || $languages}
<div class="clear"></div>
<hr />
{/if}


{if $login}
<a href="/profile" class="ajax_link">{lng[Account]}</a>
<a href="/orders" class="ajax_link">{lng[Orders history]}</a>
<a href="/wishlist" class="ajax_link">{lng[Wishlist]}</a>
<a href="/logout">{lng[Logout]}</a>
<a href="/gift_cards" class="ajax_link">{lng[Gift Cards]}</a>
{else}
<a href="/login" class="ajax_link">{lng[Login]}</a>
<a href="/register" class="ajax_link">{lng[Register]}</a>
<a href="/login" class="ajax_link">{lng[Wishlist]}</a>
<a href="/login" class="ajax_link">{lng[Gift Cards]}</a>
{/if}
<hr />
<?php
$categories_menu = $categories_top_menu;
?>
{foreach $categories_menu as $k=>$v}
<?php
		echo '<a class="ajax_link" href="/'.($v['cleanurl'] ? $v['cleanurl'] : $v['categoryid']).'" class="root-link">'.$v['title'].'</a>';
?>
{/foreach}
<hr />
<a class="ajax_link" href="{$current_location}/brands">{lng[Brands]}</a>
<a class="ajax_link" href="{$current_location}/blog">{lng[Blog]}</a>
<a class="ajax_link" href="{$current_location}/page/a-propos">{lng[About]}</a>
<a class="ajax_link" href="{if $config['Tickets']['use_tickets']}{$current_location}/support_desk{else}{$current_location}/help{/if}">{lng[Contact]}</a>
</div>
</div>