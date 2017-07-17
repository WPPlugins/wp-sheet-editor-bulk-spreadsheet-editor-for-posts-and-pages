<?php
/**
 * Template used for the spreadsheet editor page in all post types.
 */
$nonce = wp_create_nonce('bep-nonce');

if(empty($current_post_type)){
$current_post_type = VGSE()->helpers->get_post_type_from_query_string();
}
?>
<div class="remodal-bg highlightCurrentRow" id="vgse-wrapper" data-nonce="<?php echo $nonce; ?>">
	<div class="">
		<?php if( apply_filters( 'vg_sheet_editor/editor_page/allow_display_logo', true, $current_post_type )){ ?>
		<div class="">
			<h2 class="hidden"><?php _e('Sheet Editor', VGSE()->textname); ?></h2>
			<a href="<?php echo VGSE()->buy_link; ?>" target="_blank"><img src="<?php echo VGSE()->plugin_url; ?>assets/imgs/logo-248x102.png" class="vg-logo"></a>
			<?php do_action('vg_sheet_editor/editor_page/after_logo', $current_post_type); ?>
		</div>
		<?php } ?>
		<div>
			<!--Secondary toolbar-->
			<div class="vg-toolbar vg-secondary-toolbar">
				<div class="vg-header-toolbar-inner">

					<?php
					echo VGSE()->toolbar->get_rendered_post_type_items($current_post_type, 'secondary');
					do_action('vg_sheet_editor/toolbar/after_buttons', $current_post_type, 'secondary');
					?>

				</div>
			</div>

			<!--Primary toolbar placeholder, used to keep its height when the toolbar is fixed when scrolling-->
			<div id="vg-header-toolbar-placeholder" class="vg-toolbar-placeholder"></div>
			<!--Primary toolbar-->
			<div id="vg-header-toolbar" class="vg-toolbar">
				<div class="vg-header-toolbar-inner">

					<?php 
					echo VGSE()->toolbar->get_rendered_post_type_items($current_post_type, 'primary');
					do_action('vg_sheet_editor/toolbar/after_buttons', $current_post_type, 'primary');
					?>

				</div>
			</div>
			<div id="responseConsole" class="console"><?php _e('Click on "Save" to save all the changes', VGSE()->textname); ?></div>

		<?php do_action('vg_sheet_editor/editor_page/before_spreadsheet', $current_post_type); ?>
			
			<!--Spreadsheet container-->
			<div id="post-data" data-post-type="<?php echo $current_post_type; ?>" class="be-spreadsheet-wrapper"></div>

			<div id="mas-data"></div>

			<!--Footer toolbar-->
			<div id="vg-footer-toolbar" class="vg-toolbar">
				<button name="mas" class="button"><?php _e('Load More Posts', VGSE()->textname); ?></button>  <button id="go-top" class="button"><i class="fa fa-chevron-up"></i> <?php _e('Go to the top', VGSE()->textname); ?></button>						
			</div>
		</div>

		<!--Image cells modal-->
		<div class="remodal" data-remodal-id="image" data-remodal-options="closeOnOutsideClick: false">

			<div class="modal-content">

			</div>
			<br>
			<button data-remodal-action="confirm" class="remodal-confirm"><?php _e('OK', VGSE()->textname); ?></button>
		</div>

<!--handsontable cells modal-->
		<div class="remodal remodal8982 modal-handsontable" data-remodal-id="modal-handsontable" data-remodal-options="closeOnOutsideClick: false, hashTracking: false" style="max-width: 825px;">

				<div class="modal-content">
					<p class="custom-attributes-edit">
					<h3 class="modal-general-title"></h3> 
					<p class="modal-description"></p>
					<a href="#" class="remodal-confirm save-changes-handsontable"><?php _e('Save changes', VGSE()->textname); ?></a>
					<button data-remodal-action="confirm" class="remodal-cancel"><?php _e('Close', VGSE()->textname); ?></button>
					<div class="handsontable-in-modal" id="handsontable-in-modal"></div>

					<input type="hidden" value="<?php echo $nonce; ?>" name="nonce">
					<input type="hidden" value="" name="handsontable_modal_action">
					<input type="hidden" value="<?php echo $current_post_type; ?>" name="post_type">
				</div>
			</div>
			<br>

			</div>

		<!--Tinymce editor modal-->
		<div class="remodal remodal2 modal-tinymce-editor" data-remodal-id="editor" data-remodal-options="hashTracking: false, closeOnOutsideClick: false">

			<div class="modal-content">
				<h3 class="post-title-modal"><?php _e('Editing:', VGSE()->textname); ?> <span class="post-title"></span></h3>
				<?php
				$editor_id = 'editpost';
				wp_editor('', $editor_id);
				?>
			</div>
			<br>
			<?php do_action('vg_sheet_editor/editor_page/tinymce/before_action_buttons'); ?>
			<button class="remodal-mover anterior remodal-secundario guardar-popup-tinymce"><i class="fa fa-chevron-left"></i>&nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-save"></i></button><a href="#" class="tipso" data-tipso="<?php _e('Save changes and go to the previous post editor', VGSE()->textname); ?>">( ? )</a>
			<a href="#" class="remodal-confirm guardar-popup-tinymce"><i class="fa fa-save"></i></a><a href="#" class="tipso" data-tipso="<?php _e('Just save changes', VGSE()->textname); ?>">( ? )</a>
			<?php do_action('vg_sheet_editor/editor_page/tinymce/between_action_buttons'); ?>
			<button data-remodal-action="confirm" class="remodal-cancel"><i class="fa fa-close"></i></button><a href="#" class="tipso" data-tipso="<?php _e('Cancel the changes and close popup', VGSE()->textname); ?>">( ? )</a>
			<button class="siguiente remodal-secundario guardar-popup-tinymce"><i class="fa fa-save"></i>&nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-chevron-right"></i></button><a href="#" class="tipso" data-tipso="<?php _e('Save changes and go to the next post editor', VGSE()->textname); ?>">( ? )</a>
			<?php do_action('vg_sheet_editor/editor_page/tinymce/after_action_buttons'); ?>
		</div>

		<!--Save changes modal-->
		<div class="remodal remodal5 bulk-save" data-remodal-id="bulk-save" data-remodal-options="closeOnOutsideClick: false, hashTracking: false">

			<div class="modal-content">
				<h2><?php _e('Save changes', VGSE()->textname); ?></h2>

				<!--Warning state-->
				<div class="be-saving-warning">
					<p><?php _e('The changes about to be made are not reversible. You should backup your database before proceding.', VGSE()->textname); ?></p>
					<a href="#" class="be-start-saving remodal-confirm primary"><?php _e('I understand, continue', VGSE()->textname); ?></a> <a href="#" class="remodal-cancel"><?php _e('Close', VGSE()->textname); ?></a>
				</div>

				<!--Start saving state-->
				<div class="bulk-saving-screen">
					<p><?php _e('We are saving now. Don´t close this window until the process has finished.', VGSE()->textname); ?></p>
					<div id="be-nanobar-container"></div>

					<div class="response"></div>

					<!--Loading animation-->
					<div class="be-loading-anim">
						<div class="fountainG_1 fountainG"></div>
						<div class="fountainG_2 fountainG"></div>
						<div class="fountainG_3 fountainG"></div>
						<div class="fountainG_4 fountainG"></div>
						<div class="fountainG_5 fountainG"></div>
						<div class="fountainG_6 fountainG"></div>
						<div class="fountainG_7 fountainG"></div>
						<div class="fountainG_8 fountainG"></div>
					</div>
					<a href="#"  class="remodal-cancel hidden"><?php _e('Close', VGSE()->textname); ?></a>
				</div>


			</div>
			<br>
		</div>

		<?php do_action('vg_sheet_editor/editor_page/after_content', $current_post_type); ?>
	</div>
			<?php
		