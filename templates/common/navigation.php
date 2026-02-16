{if $total_pages > 2}
<div class="navigation">
<span class="navigation-title">{lng[Pages]}:</span>

{if $currentPage > 1}
<a class="navigation-larrow" href="{$navigation_script}&page={php echo $currentPage - 1;}"><img src="{$current_location}/images/spacer.gif" alt="{lng[Previous page|escape]}" /></a>
<span class="nav-sep"></span>
{/if}


{if $currentPage > $maxPagesToShow - 2}
<a class="nav-page" href="{$navigation_script}&page=1">1</a><span class="nav-sep"></span>
{if $startPage > 1 && $currentPage != 4}
<span class="nav-dots">...</span><span class="nav-sep"></span>
{/if}
{/if}

{for $i = $startPage; $i <= $endPage; $i++}
{if $i == $currentPage}
<span class="current-page">{$i}</span>
{else}
<a class="nav-page" href="{$navigation_script}&page={$i}">{$i}</a>
{/if}
{if $i != $total_pages - 1}
<span class="nav-sep"></span>
{/if}
{/for}

{if $currentPage < ($totalPages - 2) && $currentPage > 1}
{if $currentPage < ($totalPages - 1)}
<span class="nav-sep"></span><span class="nav-dots">...</span>
{/if}

<span class="nav-sep"></span><a class="nav-page" href="{$navigation_script}&page={$totalPages}">{$totalPages}</a>
{/if}

{if $currentPage < $totalPages}
<span class="nav-sep"></span>
<a class="navigation-rarrow" href="{$navigation_script}&page={php echo $currentPage + 1;}"><img src="{$current_location}/images/spacer.gif" alt="{lng[Next page|escape]}" /></a>
{/if}

</div>
{/if}