<style>

	.vg-sheet-editor-usage-stats {
		text-align: center;
		overflow: auto;

	}
	.vg-sheet-editor-usage-stats p {
		font-size: 15px;
	}
	.vg-sheet-editor-usage-stats li {
		float: left;
	}
	.vg-sheet-editor-usage-stats .stats-list li {
		width: 100%;
		max-width: 165px;
		font-size: 16px;
		margin-bottom: 20px;
	}
	.vg-sheet-editor-usage-stats .count {
		font-size: 40px;
		color: green;
		line-height: 40px;
	}
	.vg-logo {
		display: block;
		margin: 0 auto;
	}
	.vg-sheet-editor-usage-stats hr {
		margin: 20px auto 0;
	}
	.vg-sheet-editor-usage-stats .post-types-enabled {
		margin-top: 10px;
	}
</style>
<div class="vg-sheet-editor-usage-stats">
			<a href="<?php echo VGSE()->buy_link; ?>" target="_blank"><img src="<?php echo VGSE()->plugin_url; ?>assets/imgs/logo-248x102.png" class="vg-logo"></a>
			
	<p><?php _e('Thank you for using our spreadsheet editor', VGSE()->textname); ?></p>

	<?php 
	
	$editions_count = (int) get_option('vgse_editions_counter', 0);
	$processed_count = (int) get_option('vgse_processed_counter', 0);
	
	if( $processed_count > 0 ){
	?>
	<h2><?php _e('Usage stats', VGSE()->textname); ?></h2>

	<?php

	$minutes_saved = ($editions_count * 4) / 3;
	$stats = array(
		'total_editions' => array(
			'label' => __('Modified posts', VGSE()->textname),
			'count' => $processed_count,
		),
		'time_saved' => array(
			'label' => __('Time saved <br/>(estimated)', VGSE()->textname),
			'count' => ( $minutes_saved > 60 ) ? intval($minutes_saved / 60) . ' hr' : intval($minutes_saved) . ' mins.',
		),
		'clicks_avoided' => array(
			'label' => __('Clicks avoided <br/>(estimated)', VGSE()->textname),
			'count' => $editions_count * 5,
		),
	);

	$stats = apply_filters('vg_sheet_editor/usage_stats/stats', $stats, $editions_count, $processed_count);

	if (!empty($stats)) {
		?>
		<ul class="stats-list">
			<?php foreach ($stats as $key => $stat) { ?>
				<li><div class="count"><?php echo $stat['count']; ?></div><div class="label"><?php echo $stat['label']; ?></div></li>
			<?php } ?>
		</ul>
	<?php } 
	} ?>


	<?php if (!defined('VGSE_ANY_PREMIUM_ADDON')) { ?>
		<div class="clear"></div>
		<hr>
		<h2><?php _e('Go Premium', VGSE()->textname); ?></h2>
		<p><?php _e('Edit WooCommerce products, WooCommerce Variations and Attributes.<br/>Edit hundreds of posts at once using formulas, copy information between posts,<br/>Edit custom post types and custom fields.', VGSE()->textname); ?> </p>
		<a href="<?php echo VGSE()->buy_link; ?>" class="button button-primary" style="margin-bottom: 20px; display: inline-block;"><?php _e('Buy extension now', VGSE()->textname); ?></a>
	<?php } ?>
	<div class="clear"></div>
	<hr>
	<h2><?php _e('Open the Spreadsheet Editor', VGSE()->textname); ?></h2>
<div class="post-types-enabled">
				<?php
				$post_types = VGSE()->post_type;

				if (!empty($post_types)) {
					foreach ($post_types as $key => $post_type_name) {
						if (is_numeric($key)) {
							$key = $post_type_name;
						}
						?>
						<a class="button post-type-<?php echo $key; ?>" href="<?php
						   if ($key === 'attachment') {
							   $url_part = 'upload.php?page=bulk-edit-' . $key;
						   } elseif ($key === 'post') {
							   $url_part = 'edit.php?page=bulk-edit-' . $key;
						   } else {
							   $url_part = 'edit.php?post_type=' . $key . '&page=bulk-edit-' . $key;
						   }
						   echo admin_url($url_part);
						   ?>"><?php _e('Edit ' . $post_type_name . 's', VGSE()->textname); ?></a>		
					   <?php
					   }
				   }
				   ?>
			</div>
	<div class="clear"></div>
	<hr>
	<h2><?php _e('Help', VGSE()->textname); ?></h2>
	<?php
	$support_links = VGSE()->support_links;

	if (!empty($support_links)) {
		echo '<ul>';
		foreach ($support_links as $support_link) {
			?>
			<li><a class="button button-secondary button-secundario" target="_blank" href="<?php echo $support_link['url']; ?>"><?php echo $support_link['label']; ?></a></li> 
		<?php }
		echo '</ul>';
		?>
<?php } ?>


	<div class="clear"></div>

</div>

<script>
	if (typeof jQuery === 'function') {
		jQuery(document).ready(function () {
			// Equalize stats items height
			var $statsItem = jQuery('.stats-list li');
			var tallest = 0;
			$statsItem.each(function () {
				if (jQuery(this).height() > tallest) {
					tallest = jQuery(this).height();
				}
			});

			$statsItem.height(tallest);
		});
	}
</script>