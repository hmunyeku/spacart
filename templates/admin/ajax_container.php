<div class="admin-location">
{if $login && $userinfo['usertype'] == 'A'}
{$location}
{/if}
</div>
<hr />
  <?php
	if ($alerts) {
		echo '<div class="alerts"><span class="close-alerts"><b>X</b> Close</span>';
		foreach ($alerts as $v) {
			if ($v['type'] == 'e') {
?>
<div class="error">{lng[Error]}:
<?php
				echo ' '.$v['content'].'</div>';
			} else {
				echo ' '.$v['content'].'<br>';
			}

			echo '<br>';
		}

		echo '</div>';
	}
  ?>

<br />
{$page}
<div class="clear"></div>