<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:g="http://base.google.com/ns/1.0" xmlns:og="http://ogp.me/ns#" xmlns:fb="http://ogp.me/ns/fb#" class="area-c">
<head>
<meta charset="utf-8" />
<meta name="keywords" content="<?php if ($get['0'] == 'home' && lng('Homepage meta keywords')) echo lng('Homepage meta keywords'); elseif ($product['meta_keywords']) echo $product['meta_keywords']; elseif ($category['meta_keywords']) echo $category['meta_keywords']; elseif ($static_page['meta_keywords']) echo $static_page['meta_keywords']; elseif ($blog['meta_keywords']) echo $blog['meta_keywords']; elseif ($brand['meta_keywords']) echo $brand['meta_keywords']; else echo "";?>">
<meta name="description" content="<?php if ($get['0'] == 'home' && lng('Homepage meta description')) echo lng('Homepage meta description'); elseif ($product['meta_description']) echo $product['meta_description']; elseif ($category['meta_description']) echo $category['meta_description']; elseif ($static_page['meta_description']) echo $static_page['meta_description']; elseif ($blog['meta_description']) echo $blog['meta_description']; elseif ($brand['meta_description']) echo $brand['meta_description']; else echo "";?>">
<meta name="robots" content="ALL">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<link rel="shortcut icon" href="/favicon.png" type="image/vnd.microsoft.icon" />
<title>{$page_title}</title>
<link href="https://fonts.googleapis.com/css?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style type="text/css" media="all">
<?php
include SITE_ROOT.'/includes/css.php';
?>
</style>
{if $config['theme_color']}
<style id="custom_style">
:root {
	--theme-color: #{$config['theme_color']};
	--theme-color-2: #{$config['theme_color_2']};
}
</style>
{/if}
{*<script src="https://checkout.stripe.com/checkout.js"></script>*}
<script type="text/javascript">
var current_area = 'C',
	page = '{$get['0']}',
	parentid = '{$parentid}',
	pageid = '{$brand['brandid']}',
	current_location = '{$current_location}',
{php}
if (defined('INTERNAL_WEB_DIR') && INTERNAL_WEB_DIR !== $web_dir) {
	// Use JS concatenation to prevent output buffer URL rewriting from replacing this value
	$_iwd_parts = explode('/', ltrim(INTERNAL_WEB_DIR, '/'));
	echo "	internal_web_dir = '/" . implode("' + '/", $_iwd_parts) . "',
";
} else {
	echo "	internal_web_dir = '" . $web_dir . "',
";
}
{/php}	stripe_key = '{$stripe_pkey}',
	ajax_delimiter = '{$ajax_delimiter}',
	currency_symbol = '{$config['General']['currency_symbol']}',
	weight_symbol = '{$config['General']['weight_symbol']}',
	payment_currency = '{$payment_currency}',
	is_ajax_page = {php echo $is_ajax_page;},
	facebook_api = '{$current_protocol}://connect.facebook.net/en-en/all.js',
	twitter_api = '{$current_protocol}://platform.twitter.com/widgets.js',
	w_prices = [],
	qadd = '',
	oid = 0,
	variants = [],
	groups = [],
	options = [],
	exceptions = [],
	w_prices = [],
	product_base,
	product_price,
	product_weight,
	product_price_ql,
	product_weight_ql,
	default_images,
	default_images_ql,
	product_avail = [];

variants[0] = [];
variants[1] = [];
groups[0] = [];
groups[1] = [];
options[0] = [];
options[1] = [];
exceptions[0] = [];
exceptions[1] = [];
w_prices[0] = [];
w_prices[1] = [];

{if !$login}
var need_login = {if $_GET['mode'] == 'login'}1{else}0{/if};
{/if}
</script>

<script>
var states = {ldelim}{rdelim};
	user_state = "{php echo escape($userinfo['state'], 2);}";

{foreach $countries as $v}
 {if $v['states']}
states.{$v['code']} = {states: []};
  {foreach $v['states'] as $k=>$s}
states.{$v['code']}.states[{$k}] = {code: "{php echo escape($s['code'], 2);}", state: "{php echo escape($s['state'], 2)}"};
  {/foreach}
 {/if}
{/foreach}
</script>

{*
<script type="text/javascript" src="{$current_protocol}://connect.facebook.net/en-en/all.js"></script>
<script>
$(function() {
	FB.init({
		xfbml: true
	});
});
</script>
<script type="text/javascript" src="{$current_protocol}://platform.twitter.com/widgets.js"></script>
*}

  <script src="https://js.braintreegateway.com/web/3.54.2/js/client.min.js"></script>
  <script src='https://js.braintreegateway.com/web/3.54.2/js/three-d-secure.js'></script>
  <script src="https://js.braintreegateway.com/web/3.54.2/js/hosted-fields.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<style>
/* Swiper Hero Slider */
.ef-hero-slider { width: 100%; height: 500px; overflow: hidden; }
.ef-hero-slider .swiper-slide {
  background-size: cover; background-position: center; position: relative;
  display: flex; align-items: center; justify-content: center;
}
.ef-slide-overlay { position: absolute; inset: 0; z-index: 1; }
.ef-slide-content { position: relative; z-index: 2; text-align: center; padding: 40px; max-width: 800px; }
.ef-slide-content.ef-text-left { text-align: left; margin-right: auto; margin-left: 5%; }
.ef-slide-content.ef-text-right { text-align: right; margin-left: auto; margin-right: 5%; }
.ef-slide-title {
  font-size: 42px; font-weight: 700; margin-bottom: 16px; line-height: 1.2;
  opacity: 0; transform: translateY(30px); transition: all 0.6s ease;
}
.ef-slide-subtitle {
  font-size: 20px; margin-bottom: 24px; line-height: 1.5;
  opacity: 0; transform: translateY(30px); transition: all 0.6s ease 0.1s;
}
.ef-slide-btn {
  display: inline-block; padding: 14px 36px; border: 2px solid #fff;
  color: #fff; text-decoration: none; font-size: 16px; font-weight: 600;
  border-radius: 4px;
  opacity: 0; transform: translateY(30px); transition: all 0.6s ease 0.2s;
}
.ef-slide-btn:hover { background: #fff; color: #333; }
.ef-animate-in { opacity: 1 !important; transform: translateY(0) !important; }
.ef-hero-slider .swiper-button-prev, .ef-hero-slider .swiper-button-next { color: #fff; }
.ef-hero-slider .swiper-pagination-bullet { background: #fff; opacity: 0.6; }
.ef-hero-slider .swiper-pagination-bullet-active { opacity: 1; }
@media (max-width: 850px) {
  .ef-hero-slider { height: 300px; }
  .ef-slide-title { font-size: 26px; }
  .ef-slide-subtitle { font-size: 15px; }
  .ef-slide-btn { padding: 10px 24px; font-size: 14px; }
  .ef-slide-content { padding: 20px; }
}
@media (max-width: 480px) {
  .ef-hero-slider { height: 250px; }
  .ef-slide-title { font-size: 20px; }
  .ef-slide-subtitle { font-size: 13px; margin-bottom: 14px; }
  .ef-slide-btn { padding: 8px 18px; font-size: 12px; }
}
</style></head>
<body class="{if $get['0'] == 'category' || $get['0'] == 'search' || ($get['0'] == 'brands' && $get['1'])} withfilter{/if}{if $classes} {$classes}{/if}" id="body-{$get['0']}">

<div class="mobile-left_menu">
{if $config['General']['shop_closed'] != 'Y'}
{include="left_menu.php"}
{/if}
</div>
<div class="mobile-menu-fade"></div>
<div class="loading-header">
<div aria-busy="true" aria-label="Loading, please wait." role="progressbar"></div>
</div>

{*<div class="body-loading"><div></div></div>*}

{if $config['General']['shop_closed'] != 'Y'}
<div id="head">
{$head}
</div>
{/if}

<div class="ajax_container">
{include="ajax_container.php"}
</div>

{*
<div class="page-container">
{if $alerts}
 <div class="alerts"><span onclick="javascript: $('.alerts').slideUp();"><b>X</b></span>
 {foreach $alerts as $v}
  {if $v['type'] == 'e'}<div class="error">Error: {$v['content']}</div>{else}{$v['content']}<br>{/if}<br>
 {/foreach}
 </div>
{/if}

<div id="bread_crumbs_container">{$bread_crumbs_html}</div>

<div class="content" align="left">
{if false && !$no_left_menu}
{$left_menu;}
{/if}

	<div id="center"{if true || $no_left_menu == 'Y'} class="no_left_menu"'{/if}>
{$page}
	</div>
</div>
<div class="clear"></div>
</div>
*}
<div class="loading">
{*
<img src="{$current_location}/images/spacer.gif" alt="..."/>
*}
<div class="cssload-container"><div class="cssload-speeding-wheel"></div></div>
</div>
{if $config['General']['shop_closed'] != 'Y'}
<div id="foot-subscribe">
<div class="foot-subscribe">
<form method="POST" action="/subscribe" id="subsform">
<div id="subscribe">
<input placeholder="{lng[Subscribe to our news here...]}" type="text" id="sub-email" name="email" />
<button>{lng[Subscribe]}</button>
</div>
</form>
</div>
</div>

<div id="foot">
{$foot}
</div>
{/if}

<script src="//code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="//code.jquery.com/ui/1.14.1/jquery-ui.min.js" integrity="sha256-AlTido85uXPlSyyaZNsjJXeCs07eSv3r43kyCVc8ChI=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.14.1/themes/base/jquery-ui.css" type="text/css" />

<?php
include SITE_ROOT.'/includes/js.php';
?>
{*
<script src="http://connect.facebook.net/en-en/all.js"></script>
*}

<img src="/images/scrolltop.png" alt="" id="scrolltop" />

{if $config['General']['tawk_to_site_id']}
<!--Start of Tawk.to Script-->
<script type="text/javascript">
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='https://embed.tawk.to/{$config['General']['tawk_to_site_id']}/default';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();
Tawk_API.onLoad = function(){
    $('iframe').removeAttr('title');
};
</script>
<!--End of Tawk.to Script-->
{/if}

{if $design_mode}
<?php
include SITE_ROOT.'/theme.php';
?>
{/if}

{if $translate_mode}
{include="common/translate.php"}
{/if}

<script src="/images/kickout-ads.min.js"></script>
{if $autotranslate_enabled}
<!-- SpaCart Auto-Translate - Google Translate via PHP proxy -->
<script>
(function() {
    var langMap = {
        'fr': 'fr', 'en': 'en', 'de': 'de', 'ru': 'ru',
        'es': 'es', 'pt': 'pt', 'it': 'it', 'ar': 'ar',
        'zh': 'zh', 'ja': 'ja', 'ko': 'ko', 'nl': 'nl'
    };

    var sourceLangCode = '{$autotranslate_source_lang}'.substring(0, 2).toLowerCase();
    if (sourceLangCode === 'fr') sourceLangCode = 'fr';
    else if (sourceLangCode === 'en') sourceLangCode = 'en';
    else if (sourceLangCode === 'ge') sourceLangCode = 'de';
    else if (sourceLangCode === 'ru') sourceLangCode = 'ru';

    var currentLangCode = '{$current_language['code']}' || sourceLangCode;
    var excludeSelectors = '{$autotranslate_exclude}'.split(',').map(function(s){return s.trim();}).filter(Boolean);
    var apiUrl = '{$web_dir}/api/translate.php';
    var translating = false;

    function isExcluded(el) {
        if (el.classList && el.classList.contains('notranslate')) return true;
        for (var i = 0; i < excludeSelectors.length; i++) {
            try {
                if (el.closest && el.closest(excludeSelectors[i])) return true;
            } catch(e) {}
        }
        return false;
    }

    function getTextNodes(root) {
        var nodes = [];
        var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
            acceptNode: function(node) {
                if (!node.textContent.trim()) return NodeFilter.FILTER_REJECT;
                if (node.parentElement && (node.parentElement.tagName === 'SCRIPT' || node.parentElement.tagName === 'STYLE' || node.parentElement.tagName === 'NOSCRIPT')) return NodeFilter.FILTER_REJECT;
                if (isExcluded(node.parentElement)) return NodeFilter.FILTER_REJECT;
                return NodeFilter.FILTER_ACCEPT;
            }
        });
        while (walker.nextNode()) nodes.push(walker.currentNode);
        return nodes;
    }

    function translatePage(targetCode) {
        if (targetCode === sourceLangCode || translating) return;
        translating = true;

        var container = document.getElementById('ajax_container') || document.body;
        var textNodes = getTextNodes(container);
        if (!textNodes.length) { translating = false; return; }

        // Deduplicate texts and batch
        var uniqueTexts = {};
        var nodeMap = {};
        textNodes.forEach(function(node) {
            var txt = node.textContent.trim();
            if (txt.length < 2 || txt.length > 500) return;
            if (/^[\d\s\.\,\+\-\€\$\£\%\(\)\/\:]+$/.test(txt)) return; // Skip numbers/prices
            if (!uniqueTexts[txt]) {
                uniqueTexts[txt] = true;
                nodeMap[txt] = [];
            }
            nodeMap[txt].push(node);
        });

        var textsArr = Object.keys(uniqueTexts);
        if (!textsArr.length) { translating = false; return; }

        console.log('[Translate] ' + sourceLangCode + ' -> ' + targetCode + ' (' + textsArr.length + ' texts)');

        // Batch in chunks of 30
        var batchSize = 30;
        var batches = [];
        for (var i = 0; i < textsArr.length; i += batchSize) {
            batches.push(textsArr.slice(i, i + batchSize));
        }

        var completed = 0;
        batches.forEach(function(batch) {
            fetch(apiUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({texts: batch, source: sourceLangCode, target: targetCode})
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.translations) {
                    batch.forEach(function(origText, idx) {
                        var translated = data.translations[idx];
                        if (translated && translated !== origText && nodeMap[origText]) {
                            nodeMap[origText].forEach(function(node) {
                                // Preserve leading/trailing whitespace
                                var orig = node.textContent;
                                var leadingSpace = orig.match(/^\s*/)[0];
                                var trailingSpace = orig.match(/\s*$/)[0];
                                node.textContent = leadingSpace + translated + trailingSpace;
                            });
                        }
                    });
                }
                completed++;
                if (completed >= batches.length) {
                    translating = false;
                    console.log('[Translate] Done');
                }
            })
            .catch(function(err) {
                console.error('[Translate] Error:', err);
                completed++;
                if (completed >= batches.length) translating = false;
            });
        });
    }

    // Expose globally
    window.SpaCartAutoTranslate = {
        enabled: true,
        sourceLangCode: sourceLangCode,
        translate: translatePage,
        init: function() {
            if (currentLangCode && currentLangCode !== sourceLangCode) {
                setTimeout(function() { translatePage(currentLangCode); }, 300);
            }
        }
    };

    // Auto-init
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() { window.SpaCartAutoTranslate.init(); });
    } else {
        window.SpaCartAutoTranslate.init();
    }
})();
</script>
{/if}

<input type="hidden" id="csrf_token" value="{php echo spacart_csrf_token();}" /><script>$.ajaxSetup({beforeSend:function(x,s){if(s.type==="POST"&&s.data){var t=document.getElementById("csrf_token");if(t&&t.value){s.data+=(s.data?"&":"")+"csrf_token="+encodeURIComponent(t.value)}}}});$(document).ajaxComplete(function(e,x){var t=x.getResponseHeader("X-CSRF-Token");if(t){var el=document.getElementById("csrf_token");if(el)el.value=t;var hf=document.querySelector("input[name=csrf_token]");if(hf)hf.value=t}});</script>
</body>
</html>