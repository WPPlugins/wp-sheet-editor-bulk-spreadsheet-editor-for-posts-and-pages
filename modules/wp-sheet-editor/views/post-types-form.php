<?php
$post_types = VGSE()->allowed_post_types;

if( empty( $post_types )){
	return;
}
?>

<form action="<?php echo admin_url('admin-ajax.php'); ?>" method="POST" class="post-types-form">

	<p><?php _e('Available post types', VGSE()->textname); ?></p>

	<?php foreach ($post_types as $key => $post_type_name) { ?>
		<div class="post-type-field"><input type="checkbox" name="post_types[]" value="<?php echo $key; ?>" id="<?php echo $key; ?>"> <label for="<?php echo $key; ?>"><?php echo $post_type_name; ?></label></div>
	<?php } ?>
	<input type="hidden" name="action" value="vg_save_post_types_setting">
	<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('bep-nonce'); ?>">
	<button class="button button-primary hidden save-trigger"><?php _e('Save', VGSE()->textname); ?></button>
</form>