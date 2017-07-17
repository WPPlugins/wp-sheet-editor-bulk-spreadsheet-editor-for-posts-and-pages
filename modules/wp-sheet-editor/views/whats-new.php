<?php
/**
 * Template used for notifying the user about the new features
 */
$nonce = wp_create_nonce('bep-nonce');
?>
<div class="remodal-bg quick-setup-page-content" id="vgse-wrapper" data-nonce="<?php echo $nonce; ?>">
	<div class="">
		<div class="">
			<h2 class="hidden"><?php _e('Sheet Editor', VGSE()->textname); ?></h2>
			<img src="<?php echo VGSE()->plugin_url; ?>assets/imgs/logo-248x102.png" class="vg-logo"> 
		</div>
		<h2><?php _e('What´s new on WP Sheet Editor', VGSE()->textname); ?></h2>
		<div class="setup-screen whats-new-content">
			<p><?php _e('Thank you for updating to the new version of the plugin.', VGSE()->textname); ?></p>

			<?php 
			ob_start();

			include VGSE_DIR . '/views/whats-new/' . VGSE()->version . '.php';
			$items = apply_filters('vg_sheet_editor/whats_new_page/items', $items);

			if (!empty($items)) {
				echo '<ol class="steps">';
				foreach ($items as $key => $step_content) {
					?>
					<li><?php echo $step_content; ?></li>		
				<?php
				}

				echo '</ol>';
			}
			
			echo ob_get_clean();
			?>		
<?php do_action('vg_sheet_editor/whats_new_page/quick_setup_screen/after_content'); ?>
			
		</div>
<?php do_action('vg_sheet_editor/whats_new_page/after_content'); ?>
	</div>
</div>
			<?php
		