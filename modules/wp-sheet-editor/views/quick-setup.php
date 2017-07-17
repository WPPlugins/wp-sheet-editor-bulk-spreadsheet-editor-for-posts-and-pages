<?php
/**
 * Template used for the quick setup page.
 */
$nonce = wp_create_nonce('bep-nonce');

$current_post_type = VGSE()->helpers->get_post_type_from_query_string();
$quick_setup_disabled = get_option('vgse_disable_quick_setup', false);
?>
<div class="remodal-bg quick-setup-page-content" id="vgse-wrapper" data-nonce="<?php echo $nonce; ?>">
	<div class="">
		<div class="">
			<h2 class="hidden"><?php _e('Sheet Editor', VGSE()->textname); ?></h2>
			<a href="<?php echo VGSE()->buy_link; ?>" target="_blank"><img src="<?php echo VGSE()->plugin_url; ?>assets/imgs/logo-248x102.png" class="vg-logo"></a>
		</div>
		<h2><?php _e('Welcome to WP Sheet Editor', VGSE()->textname); ?></h2>
		<div class="setup-screen" style="<?php
		if ($quick_setup_disabled) {
			echo 'display: none;';
		}
		?>">
<?php do_action('vg_sheet_editor/quick_setup_page/quick_setup_screen/before_content'); ?>
			<p><?php _e('You can start using the spreadsheet editor in just 5 minutes. The editor is already set up and you just need to follow these steps.', VGSE()->textname); ?></p>

			<?php
			ob_start();
			require 'post-types-form.php';
			$post_types_form = ob_get_clean();
			$steps = array();

			$tgm = TGM_Plugin_Activation::get_instance();

			if ( !class_exists('ReduxFramework') && is_object($tgm) && !$tgm->is_plugin_active('redux-framework')) {
				$steps['install_dependencies'] = '<p>' . __(sprintf('Install the Redux Framework plugin. <a href="%s" target="_blank" class="button">Click here</a>.<br/>When you finish the installation please return to this page to continue.',  $tgm->get_tgmpa_url() ), VGSE()->textname) . '</p>';
			}
			$steps['enable_post_types'] = '<p>' . __('Select the post types that you want to edit with the spreadsheet editor.') . '</p>' . $post_types_form;

			$steps = apply_filters('vg_sheet_editor/quick_setup_page/setup_steps', $steps);

			if (!empty($steps)) {
				echo '<ol class="steps">';
				foreach ($steps as $key => $step_content) {
					?>
					<li><?php echo $step_content; ?></li>		
				<?php
				}

				echo '</ol>';
			}
			?>		


			<button class="button button-primary button-primario save-all-trigger"><?php _e('Save and continue', VGSE()->textname); ?></button>

			
	<?php if (!defined('VGSE_ANY_PREMIUM_ADDON')) { ?>
		<div class="clear"></div>
		<hr>
		<h2><?php _e('Go Premium', VGSE()->textname); ?></h2>
		<p><?php _e('Edit WooCommerce products, WooCommerce Variations and Attributes.<br/>Edit hundreds of posts at once using formulas, copy information between posts,<br/>Edit custom post types and custom fields.', VGSE()->textname); ?> </p>
		<a href="<?php echo VGSE()->buy_link; ?>" class="button-primary" style="margin-bottom: 20px; display: inline-block;"><?php _e('Buy extension now', VGSE()->textname); ?></a>
	<?php } ?>

<?php do_action('vg_sheet_editor/quick_setup_page/quick_setup_screen/after_content'); ?>
		</div>

		<div class="usage-screen" style="<?php
		if (!$quick_setup_disabled) {
			echo 'display: none;';
		}
		?>">
			<p><?php _e('The spreadsheet editor is ready for use. You just have to select the post type that you want to edit and start editing.', VGSE()->textname) ?></p>
			<p><b><?php _e('What post type do you want to edit?', VGSE()->textname); ?></b></p>
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
			<hr>
			<?php if( class_exists('ReduxFramework')){ ?>
			<p><a class="button settings-button" href="<?php echo add_query_arg( array('page' => VGSE()->options_key), admin_url('admin.php')); ?>"><i class="fa fa-cog"></i> <?php _e('Settings', VGSE()->textname); ?></a></p>
			<?php } ?>

			
	<?php if (!defined('VGSE_ANY_PREMIUM_ADDON')) { ?>
		<div class="clear"></div>
		<hr>
		<h2><?php _e('Edit your Posts Faster', VGSE()->textname); ?></h2>
		<p><?php _e('Edit WooCommerce products, Update hundreds of posts at once using formulas, copy information between posts, edit images and media gallery, add custom columns to the spreadsheet, and more...', VGSE()->textname); ?> </p>
		<a href="<?php echo VGSE()->buy_link; ?>" class=" remodal-confirm primario primary" style="margin-bottom: 20px; display: inline-block;"> <?php _e('Buy extension now', VGSE()->textname); ?> </a>
	<?php } ?>


		<?php do_action('vg_sheet_editor/quick_setup_page/usage_screen/after_content'); ?>
		</div>
<?php do_action('vg_sheet_editor/quick_setup_page/after_content'); ?>
	</div>
</div>
			<?php
		