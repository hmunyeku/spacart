<div class="head-line">
{if $languages}
<div class="language_select">
<div>
{foreach $languages as $c}
{if $current_language['id'] == $c['id'] || (!$current_language['id'] && $c['main'])}
<img src="/images/flags/{$c['code']}.png" alt="" /> {$c['name']}
{/if}
{/foreach}
<ul>
{foreach $languages as $c}
<li><a href="javascript: void(0);" data-id="{$c['id']}"><img src="/images/flags/{$c['code']}.png" alt="" /> {$c['name']}</a></li>
{/foreach}
</ul>
</div>
{*
<select>
{foreach $languages as $c}
<option value="{$c['id']}"{if $current_language['id'] == $c['id'] || (!$current_language['id'] && $c['main'])} selected{/if}>{$c['name']}</option>
{/foreach}
</select>
*}
</div>
{/if}

{if $currencies}
<div class="currency_select">
<div>{foreach $currencies as $c}{if $current_currency['id'] == $c['id'] || (!$current_currency['id'] && $c['main'])}{$c['code']}{/if}{/foreach}
<ul>
{foreach $currencies as $c}
<li><a href="javascript: void(0);" data-id="{$c['id']}">{$c['code']}</a></li>
{/foreach}
</ul>
</div>
{*
<select>
{foreach $currencies as $c}
<option value="{$c['id']}"{if $current_currency['id'] == $c['id'] || (!$current_currency['id'] && $c['main'])} selected{/if}>{$c['code']}</option>
{/foreach}
</select>
*}
</div>
{/if}

<div class="head-line-links">
{if $login}
<a href="/profile" onclick="javascript: return profile_popup(1);">{lng[Account]}</a>
<a href="/orders">{lng[Mes commandes]}</a>
<a href="/wishlist" class="wishlist-link">{lng[Wishlist]}</a>
<a href="/logout">{lng[Log out]}</a>
<a href="/gift_cards" class="ajax_link">{lng[Gift Cards]}</a>
{else}
<a href="/login" onclick="javascript: return login_popup();">{lng[Login]}</a>
<a href="/register" onclick="javascript: return register_popup();">{lng[Register]}</a>
<a href="/login" onclick="javascript: return login_popup();">{lng[Wishlist]}</a>
<a href="/login" onclick="javascript: return login_popup();">{lng[Gift Cards]}</a>
{/if}
<a class="parent-link ajax_link" href="{$current_location}/blog">{lng[Blog]}</a>
<a class="parent-link ajax_link" href="{$current_location}/news">{lng[News]}</a>
<a class="header-email ajax_link" class="parent-link" href="{if $config['Tickets']['use_tickets']}{$current_location}/support_desk{else}{$current_location}/help{/if}"><svg><use xlink:href="/images/sprite.svg#email"></use></svg>{*<img src="/images/icons/email.png" alt="{lng[Email us|escape]}" />*} {lng[Email us]}</a>
<a class="parent-link ajax_link" href="{$current_location}/page/a-propos">{lng[About]}</a>

<div class="top-line-brands">
<a class="parent-link ajax_link" href="{$current_location}/brands">{lng[Brands]}</a>
{if $brands_menu}
<ul>
 {foreach $brands_menu as $v}
 <li><a class="ajax_link" href="{$current_location}/brands/{if $v['cleanurl']}{$v['cleanurl']}{else}{$v['brandid']}{/if}">{$v['name']}</a></li>
 {/foreach}
</ul>
{/if}
</div>
</div>

</div>


<div class="header desktop_head">
{*<div class="top-line">
<div class="social-icons">
{if $config['General']['social_facebook']}<a href="{$config['General']['social_facebook']}" target="_blank" rel="noopener" title="Facebook"><svg><use xlink:href="/images/sprite.svg#facebook"></use></svg></a>{/if}
{if $config['General']['social_linkedin']}<a href="{$config['General']['social_linkedin']}" target="_blank" rel="noopener" title="LinkedIn"><svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg></a>{/if}
{if $config['General']['social_whatsapp']}<a href="{$config['General']['social_whatsapp']}" target="_blank" rel="noopener" title="WhatsApp"><svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg></a>{/if}
{if $config['General']['social_instagram']}<a href="{$config['General']['social_instagram']}" target="_blank" rel="noopener" title="Instagram"><svg><use xlink:href="/images/sprite.svg#instagram"></use></svg></a>{/if}
{if $config['General']['social_twitter']}<a href="{$config['General']['social_twitter']}" target="_blank" rel="noopener" title="X/Twitter"><svg><use xlink:href="/images/sprite.svg#twitter"></use></svg></a>{/if}
{if $config['General']['social_youtube']}<a href="{$config['General']['social_youtube']}" target="_blank" rel="noopener" title="YouTube"><svg><use xlink:href="/images/sprite.svg#youtube"></use></svg></a>{/if}
</div>
</div>*}

<a href="/" class="logo-link"><img src="{if $config['Company']['company_logo']}{$config['Company']['company_logo']}{else}/images/logo_new.png{/if}" alt="{$config['Company']['company_name']}" /></a>
{if $mobile_link}
<a class="mobile-version" href="{$mobile_link}">Mobile version</a>
{/if}
<div class="menu-container">
<ul id="menu">
{if $categories_top_menu}
 {foreach $categories_top_menu as $k=>$v}
 <li id="menu-{$v['categoryid']}" class="{if $v['subcategories']}with-drop-down {/if}{if $v['categoryid'] == $parentid} active{/if}"><a class="parent-link" href="{$current_location}/{if $v['cleanurl']}{$v['cleanurl']}{else}{$v['categoryid']}{/if}">{$v['title']}</a>
  {if $v['subcategories']}
{*<div class="submenu-fade"></div>*}
  <ul>
{*	<li class="top-part"></li>*}
   {foreach $v['subcategories'] as $s}
   <li><a href="{$current_location}/{if $s['cleanurl']}{$s['cleanurl']}{else}{$s['categoryid']}{/if}">{$s['title']}</a>
	{if $s['subcategories']}<div>
	 {foreach $s['subcategories'] as $s2}
<a href="{$current_location}/{if $s2['cleanurl']}{$s2['cleanurl']}{else}{$s2['categoryid']}{/if}">{$s2['title']}</a>
	 {/foreach}
	 </div>
	{/if}
   </li>
   {/foreach}
  </ul>
  {/if}
 </li>
 {/foreach}
{/if}
  <li id="menu-brands" class="{if $brands_menu}with-drop-down {/if}{if $get['0'] == 'brands'} active{/if}"><a class="parent-link" href="{$current_location}/brands">{lng[Brands]}</a>
{if $brands_menu}
<ul>
 {foreach $brands_menu as $v}
 <li><a href="{$current_location}/brands/{if $v['cleanurl']}{$v['cleanurl']}{else}{$v['brandid']}{/if}">{$v['name']}</a></li>
 {/foreach}
</ul>
{/if}
  </li>
{*
  <li id="menu-blog"{if $get['0'] == 'blog'} class="active"{/if}><a class="parent-link" href="{$current_location}/blog">{lng[Blog]}</a></li>
  <li id="menu-page"{if $get['0'] == 'page'} class="active"{/if}><a class="parent-link" href="{$current_location}/page/a-propos">{lng[About CMS]}</a>
<ul>
 <li><a href="{$current_location}/page/scripts-structure.html">Scripts structure</a></li>
 <li><a href="{$current_location}/page/templages-engine.html">Templates engine</a></li>
 <li><a href="{$current_location}/page/MySQL-standards.html">MySQL standards</a></li>
</ul>
  </li>
  <li id="menu-news"{if $get['0'] == 'news'} class="active"{/if}><a class="parent-link" href="{$current_location}/news">{lng[News]}</a>
*}
  <li class="search-dd"><svg><use xlink:href="/images/sprite.svg#search"></use></svg>
<form method="POST" action="/search" class="searchform searchform_desktop">
<div class="search">
<input type="text" name="substring" value="{if $substring}{php echo escape($substring, 2);}{/if}" placeholder="{lng[Search|escape]}" autocomplete="off" />
<button type="submit"><svg><use xlink:href="/images/sprite.svg#search"></use></svg></button>
<div class="instant-search"><div class='enter-3-chars'>{lng[Enter at least 2 characters]}</div></div>
</div>
</form>
  </li>
</ul>
</div>

<div class="menu_right_part">
<div class="mrp-row">
<div class="mrp-rounded-box"><svg><use xlink:href="/images/sprite.svg#phone"></use></svg></div>
<div class="mrp-title">{lng[Call Us]}</div>
<div class="mrp-subtitle">{$config['Company']['company_phone']}</div>
<div class="mrp-subtitle-2">{lng[Free call]}</div>
</div>

<div class="mrp-row mrp-row-cart">
<div class="mrp-rounded-box"><svg><use xlink:href="/images/sprite.svg#cart"></use></svg></div>
<div id="minicart">
{$minicart}
</div>
</div>

</div>
</div>

<div class="subheader">
<table>
<tr>
 <td>
<div class="sh-rounded-box">
<svg><use xlink:href="/images/sprite.svg#delivery"></use></svg>
</div>
<h4>{lng[Fast Delivery]}</h4>
<span>{lng[Fast delivery across DRC]}</span>
 </td>
 <td>
<div class="sh-rounded-box">
<svg><use xlink:href="/images/sprite.svg#money"></use></svg>
</div>
<h4>{lng[Secure Payment]}</h4>
<span>{lng[100% secure transactions]}</span>
 </td>
 <td>
<div class="sh-rounded-box">
<svg><use xlink:href="/images/sprite.svg#ring_phone"></use></svg>
</div>
<h4>{lng[Customer Support]}</h4>
<span>{$config['Company']['company_phone']}</span>
 </td>
</tr>
</table>
</div>

<div id="head_mobile">
{*<div class="header-phone">{lng[Call Us]} {$config['Company']['company_phone']}</div>*}
<div id="minicart">
{$minicart}
</div>

<div class="navigation-toggle"><div class="toggle-box"><div class="toggle-inner"></div></div></div>


<a href="/" class="logo-link"><img src="{if $config['Company']['company_logo']}{$config['Company']['company_logo']}{else}/images/logo_new.png{/if}" alt="{$config['Company']['company_name']}" /></a>

<form method="POST" action="/search" class="searchform searchform_mobile">
<div class="search">
<input type="text" name="substring" value="{if $substring}{php echo escape($substring, 2);}{/if}" placeholder="{lng[Search|escape]}" autocomplete="off" />
<button type="submit"><svg><use xlink:href="/images/sprite.svg#search"></use></svg></button>
<div class="instant-search"><div class='enter-3-chars'>{lng[Enter at least 2 characters]}</div></div>
</div>
</form>

</div>