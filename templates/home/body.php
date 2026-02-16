{if $banners}
{* Page container assign *}
</div>
</div>
<div class="clear"></div>
</div>
</div></div>

<div class="swiper ef-hero-slider">
  <div class="swiper-wrapper">
    {foreach $banners as $k=>$v}
    <div class="swiper-slide" style="background-image: url('{$v['image_url']}');">
      <div class="ef-slide-overlay" style="background: {php echo $v['overlay_color'] ? $v['overlay_color'] : 'rgba(0,0,0,0.3)';}"></div>
      <div class="ef-slide-content ef-text-{php echo $v['text_position'] ? $v['text_position'] : 'center';}">
        {if $v['title']}
        <h2 class="ef-slide-title" style="color: {php echo $v['text_color'] ? $v['text_color'] : '#fff';}">{$v['title']}</h2>
        {/if}
        {if $v['subtitle']}
        <p class="ef-slide-subtitle" style="color: {php echo $v['text_color'] ? $v['text_color'] : '#fff';}">{$v['subtitle']}</p>
        {/if}
        {if $v['button_text']}
        <a href="{php echo $v['button_url'] ? $v['button_url'] : '#';}" class="ef-slide-btn">{$v['button_text']}</a>
        {/if}
      </div>
    </div>
    {/foreach}
  </div>
  <div class="swiper-pagination"></div>
  <div class="swiper-button-prev"></div>
  <div class="swiper-button-next"></div>
<script>
(function() {
  function initHeroSwiper() {
    if (typeof Swiper === 'undefined') return;
    var slider = document.querySelector('.ef-hero-slider');
    if (!slider) return;
    if (slider.swiper) slider.swiper.destroy(true, true);
    var sw = new Swiper('.ef-hero-slider', {
      loop: true,
      autoplay: { delay: 5000, disableOnInteraction: false },
      effect: 'fade',
      fadeEffect: { crossFade: true },
      speed: 800,
      pagination: { el: '.swiper-pagination', clickable: true },
      navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
      on: {
        slideChangeTransitionStart: function() {
          var a = this.slides[this.activeIndex];
          if (!a) return;
          var els = a.querySelectorAll('.ef-slide-title, .ef-slide-subtitle, .ef-slide-btn');
          for (var i = 0; i < els.length; i++) els[i].classList.remove('ef-animate-in');
        },
        slideChangeTransitionEnd: function() {
          var a = this.slides[this.activeIndex];
          if (!a) return;
          var els = a.querySelectorAll('.ef-slide-title, .ef-slide-subtitle, .ef-slide-btn');
          for (var i = 0; i < els.length; i++) {
            (function(el, d) { setTimeout(function() { el.classList.add('ef-animate-in'); }, d); })(els[i], i * 200);
          }
        }
      }
    });
    setTimeout(function() {
      var fs = document.querySelector('.ef-hero-slider .swiper-slide-active');
      if (fs) {
        var els = fs.querySelectorAll('.ef-slide-title, .ef-slide-subtitle, .ef-slide-btn');
        for (var i = 0; i < els.length; i++) {
          (function(el, d) { setTimeout(function() { el.classList.add('ef-animate-in'); }, d); })(els[i], 300 + i * 200);
        }
      }
    }, 100);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initHeroSwiper);
  } else {
    initHeroSwiper();
  }
  // Also init on AJAX page load (SPA navigation)
  if (typeof jQuery !== 'undefined') {
    jQuery(document).ajaxComplete(function() {
      setTimeout(initHeroSwiper, 200);
    });
  }
})();
</script></div>

<div class="boxes-homepage-line">
<div class="boxes-homepage">
 <div><h2><svg viewBox="0 0 24 24" width="28" height="28" style="vertical-align:middle;margin-right:8px"><path fill="currentColor" d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>{lng[Livraison rapide]}</h2><p>{lng[Livraison sécurisée dans toute la RDC et en Afrique centrale]}</p></div>
 <div><h2><svg viewBox="0 0 24 24" width="28" height="28" style="vertical-align:middle;margin-right:8px"><path fill="currentColor" d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>{lng[Paiement sécurisé]}</h2><p>{lng[Transactions protégées et moyens de paiement adaptés]}</p></div>
 <div><h2><svg viewBox="0 0 24 24" width="28" height="28" style="vertical-align:middle;margin-right:8px"><path fill="currentColor" d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-7 12h-2v-2h2v2zm0-4h-2V6h2v4z"/></svg>{lng[Support client]}</h2><p>{lng[Notre équipe est disponible pour vous accompagner]}</p></div>
 <div><h2><svg viewBox="0 0 24 24" width="28" height="28" style="vertical-align:middle;margin-right:8px"><path fill="currentColor" d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>{lng[Prix compétitifs]}</h2><p>{lng[Les meilleurs prix pour des produits et services de qualité]}</p></div>
</div>
</div>
<div class="clear"></div>

{if lng('Site title') || lng('Site description')}
<div class="page-container page-container-afterbanner">
<div class="content">
	<div id="center" class="no_left_menu">
{/if}
{/if}
<div id="dcart"><img src="{$current_location}/images/dcart.png" alt="" /><br />{lng[Move product here]}</div>
<?php
if (lng('Site title'))
	echo "<br /><h1>".lng('Site title')."</h1>";

if (lng('Site description'))
	echo "<br /><p>".lng('Site description')."</p>";
?>

{* Page container assign *}
{if lng('Site title') || lng('Site description')}
</div>
</div>
<div class="clear"></div>
</div>
{/if}

<div class="page-container page-container-testimonials">
<div class="content">
	<div id="center" class="no_left_menu">

<div class="testimonial">
<img src="/images/testimonial.jpg" class="test-image" alt="{lng[Our testimonials|escape]}" />
<h2>{lng[Testimonials]}</h2>
<div class="message message-box">{$testimonial['message']}</div>
<div class="name">{$testimonial['name']}</div>
{if $testimonial['url']}
<div class="url"><a rel="nofollow" href="{$testimonial['url']}" target="_blank">{$testimonial['url']}</a></div>
{/if}
<div class="test-links">
<a class="all-link link-button" href="/testimonials">{lng[All testimonials]}</a>
<a class="leave-link link-button" href="/testimonials/new">{lng[Write testimonial]}</a>
</div>
<div class="clear"></div>
</div>
{* End page container *}
</div>
</div>
<div class="clear"></div>
</div>

<div id="home-tabs">
<ul class="home-tabs">
 <li class="tab-1 active" data-tab="1">{lng[Featured products]}</li>
</ul>
</div>

{* Start page container *}
<div class="page-container page-container-2">
<div class="content">
	<div id="center" class="no_left_menu">

{if $featured_products}
 {php $tag_id = "featured_products"; $products = $featured_products; $per_row = 4;}
<div class="tab-content" id="tab-1">
<div class="carousel-pr" id="carousel-0">
  <div class="controls">
    <div class="button-left">
      <div class="icon">
        <span></span>
      </div>
    </div>
    <div class="button-right">
      <div class="icon">
        <span></span>
      </div>
    </div>
  </div>
  <div class="carousel-wrapper">
    <div class="content-pr">
 {include="common/products.php"}
     </div>
  </div>
</div>

</div>
{/if}

{if $bestsellers}
{* End page container *}
</div>
</div>
<div class="clear"></div>
</div>

<div id="home-tabs">
<ul class="home-tabs">
 <li class="tab-1 active" data-tab="1">{lng[Bestsellers]}</li>
</ul>
</div>

{* Start page container *}
<div class="page-container page-container-2">
<div class="content">
	<div id="center" class="no_left_menu">

 {php $tag_id = "bestsellers_products"; $products = $bestsellers; $per_row = 4;}
<div class="tab-content" id="tab-2">
<div class="carousel-pr" id="carousel-1">
  <div class="controls">
    <div class="button-left">
      <div class="icon">
        <span></span>
      </div>
    </div>
    <div class="button-right">
      <div class="icon">
        <span></span>
      </div>
    </div>
  </div>
  <div class="carousel-wrapper">
    <div class="content-pr">
 {include="common/products.php"}
     </div>
  </div>
</div>
</div>
{/if}

{if $most_viewed}
{* End page container *}
</div>
</div>
<div class="clear"></div>
</div>

<div id="home-tabs">
<ul class="home-tabs">
 <li class="tab-1 active" data-tab="1">{lng[Most viewed]}</li>
</ul>
</div>

{* Start page container *}
<div class="page-container page-container-2">
<div class="content">
	<div id="center" class="no_left_menu">

 {php $tag_id = "most_viewed"; $products = $most_viewed; $per_row = 4;}
<div class="tab-content" id="tab-3">
<div class="carousel-pr" id="carousel-2">
  <div class="controls">
    <div class="button-left">
      <div class="icon">
        <span></span>
      </div>
    </div>
    <div class="button-right">
      <div class="icon">
        <span></span>
      </div>
    </div>
  </div>
  <div class="carousel-wrapper">
    <div class="content-pr">
 {include="common/products.php"}
     </div>
  </div>
</div>
</div>
{/if}

{if $new_arrivals}
{* End page container *}
</div>
</div>
<div class="clear"></div>
</div>

<div id="home-tabs">
<ul class="home-tabs">
 <li class="tab-1 active" data-tab="1">{lng[New arrivals]}</li>
</ul>
</div>

{* Start page container *}
<div class="page-container page-container-2">
<div class="content">
	<div id="center" class="no_left_menu">

 {php $tag_id = "new_arrivals"; $products = $new_arrivals; $per_row = 4;}
<div class="tab-content" id="tab-4">
<div class="carousel-pr" id="carousel-3">
  <div class="controls">
    <div class="button-left">
      <div class="icon">
        <span></span>
      </div>
    </div>
    <div class="button-right">
      <div class="icon">
        <span></span>
      </div>
    </div>
  </div>
  <div class="carousel-wrapper">
    <div class="content-pr">
 {include="common/products.php"}
     </div>
  </div>
</div>
</div>
{/if}

{if $last_news}
{* Page container assign *}
</div>
</div>
<div class="clear"></div>
</div>

<div class="page-container page-container-news">
<div class="content">
	<div id="center" class="no_left_menu">

<div class="news-list">
<h2>{lng[Browse our news]}</h2>
{foreach $last_news as $b}
<div class="news-item">
{php $url = $current_location.'/news/'.($b['cleanurl'] ? $b['cleanurl'].'.html' : $b['newsid']);}
<?php
	if ($b['imageid']) {
		echo '<a class="ajax_link" href="'.$url.'">';
		$image = $b;
		$image['new_width'] = 250;
		$image['new_height'] = 200;
		include SITE_ROOT . '/includes/news_image.php';
		echo '</a>';
	}
?>
<a class="ajax_link" href="{$url}"><h5>{$b['title']} (<span class="date"><?php echo date($date_format, $b['date']); ?></span>)</h5></a>
<div class="short-descr message-box">{$b['descr']}</div>
<br />
<a class="news-more ajax_link link-button" href="{$url}">{lng[See full article]}</a>
</div>
<div class="clear"></div>
{/foreach}
<a href="{$current_location}/news" class="ajax_link link-button">{lng[All news]}</a>
<br /><br />
</div>
{/if}

{if $last_blog}
{* Page container assign *}
</div>
</div>
<div class="clear"></div>
</div>

<div class="page-container page-container-blog">
<div class="content">
	<div id="center" class="no_left_menu">

<div class="news-list">
<h2>{lng[Browse our blog]}</h2>
<div class="news-item">
{php $url = $current_location.'/blog/'.($last_blog['cleanurl'] ? $last_blog['cleanurl'].'.html' : $last_blog['blogid']);}
<?php
	if ($last_blog['imageid']) {
		echo '<a class="ajax_link" href="'.$url.'">';
		$image = $last_blog;
	$image['new_width'] = 250;
	$image['new_height'] = 200;
	include SITE_ROOT . '/includes/blog_image.php';
		echo '</a>';
	}
?>
<a class="ajax_link" href="{$url}"><h5>{$last_blog['title']} (<span class="date"><?php echo date($date_format, $last_blog['date']); ?></span>)</h5></a>
<div class="short-descr message-box">{$last_blog['descr']}</div>
<br />
<a class="news-more ajax_link link-button" href="{$url}">{lng[See full blog]}</a>
</div>
<div class="clear"></div>

<a href="/blog" class="ajax_link link-button">{lng[All blogs]}</a>
<br /><br />
</div>
{/if}
