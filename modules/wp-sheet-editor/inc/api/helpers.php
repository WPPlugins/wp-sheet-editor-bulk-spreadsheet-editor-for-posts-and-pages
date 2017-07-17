<?php
if( ! class_exists('WP_Sheet_Editor_Helpers')){
class WP_Sheet_Editor_Helpers {

	var $post_type;
	static private $instance = false;

	private function __construct() {
		
	}

	/**
	 * Get current plugin mode. If it´s free or pro.
	 * @return str
	 */
	function get_plugin_mode(){
		$mode = ( defined('VGSE_ANY_PREMIUM_ADDON') && VGSE_ANY_PREMIUM_ADDON) ? 'pro' : 'free';

		return $mode . '-plugin';
	}
	
	/**
	 * Maybe replace urls in a list with wp media file ids.
	 * 
	 * @param str|array $ids
	 * @param int|null $post_id
	 * @return array
	 */
	function maybe_replace_urls_with_file_ids($ids = array(), $post_id = null ) {	
    if( ! is_array( $ids )){
		$ids = array( $ids );
	}
	
	$ids = array_map('trim', $ids );
	
	$out = array();
	foreach( $ids as $id ){
		if( is_numeric( $id )){
			$out[] = $id;
		} elseif( filter_var( $id, FILTER_VALIDATE_URL)  ){

			if (strpos($id, WP_CONTENT_URL) !== false) {
				$media_file_id = $this->get_attachment_id_from_url($id);
			} else {
				$media_file_id = $this->add_file_to_gallery_from_url( $id, null, $post_id);
			}
			
			if( $media_file_id ){
				$out[] = $media_file_id;
			}
		}
	}
	
	return $out;
	
}
	
/**
 * Add file to gallery from url
 * Download a file from an external url and add it to 
 * the wordpress gallery.		 
 * @param str $url External file url
 * @param str $save_as New file name
 * @param int $post_id Append to the post ID
 * @return mixed Attachment ID on success, false on failure
 */

	function add_file_to_gallery_from_url($url, $save_as = null, $post_id = null) {
		if (!$url) {
			return false;
		}
		if (!$save_as) {
			$save_as = basename($url);
		}
		require_once(ABSPATH . 'wp-admin/includes/media.php');
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/image.php');

		// build up array like PHP file upload
		$file = array();
		$file['name'] = $save_as;
		$file['tmp_name'] = download_url(esc_url($url));

		if (is_wp_error($file['tmp_name'])) {
			unlink($file['tmp_name']);
			return false;
		}

		$attachmentId = media_handle_sideload($file, $post_id);

		// If error storing permanently, unlink
		if (is_wp_error($attachmentId)) {
			unlink($file['tmp_name']);
			return false;
		}

		// create the thumbnails
		$attach_data = wp_generate_attachment_metadata($attachmentId, get_attached_file($attachmentId));

		wp_update_attachment_metadata($attachmentId, $attach_data);
		return $attachmentId;
	}

	

	/**
	 * Get column textual value.
	 * 
	 * @param str $column_key
	 * @param int $post_id
	 * @return boolean|string
	 */
	function get_column_text_value($column_key, $post_id, $data_type = null ) {

		if( empty($data_type)){
		$post_type = get_post_type($post_id);

		$spreadsheet_columns = VGSE()->columns->get_post_type_items($post_type, false);

		$out = false;
		if (empty($spreadsheet_columns) || !is_array($spreadsheet_columns) || !isset($spreadsheet_columns[$column_key])) {
			return $out;
		}

		$column_settings = $spreadsheet_columns[$column_key];
$data_type = $column_settings['data_type'];
		}

		if ($data_type === 'post_data') {
			$out = VGSE()->data_helpers->get_post_data($column_key, $post_id);
		} elseif ($data_type === 'meta_data' || $data_type === 'post_meta') {
			$out = get_post_meta($post_id, $column_key, true);
		} elseif ($data_type === 'post_terms') {
			$out = VGSE()->data_helpers->get_post_terms($post_id, $column_key);
		}

		return $out;
	}

	/**
	 * Remove keys from array
	 * @param array $array
	 * @param array $keys
	 * @return array
	 */
public function remove_unlisted_keys($array, $keys = array() )
{
	$out = array();
	foreach($array as $key => $value ){
		if(in_array($key, $keys )){
			$out[ $key ] = $value;
		}
	}
	return $out;
}
	/**
	 * Rename array keys
	 * @param array $array Rest endpoint route
	 * @param array $keys_map Associative array of old keys => new keys.
	 * @return array
	 */
function rename_array_keys($array, $keys_map ) {

	foreach( $keys_map as $old => $new ){
		
		if( $old === $new ){
			continue;
		}
		if( isset( $array[$old] )){
  $array[$new] = $array[$old];
  unset($array[$old]);
		} else {
			  $array[$new] = '';
		}
	}
return $array;
}

/**
 * Add a post type element to posts rows.
 * @param array $rows
 * @return array
 */
public function add_post_type_to_rows($rows)
{
            $new_data           = array();
            foreach ($rows as $row) {
				if(isset($row['post_type'])){
					$new_data[] = $row;
				}
				if(is_int($row['ID'])){
					$post_id = $row['ID'];
				} elseif( is_string($row['ID'])){
                $post_id = (int) trim(wp_strip_all_tags($row['ID']));
				}
                
                if (empty($post_id)) {
                    continue;
                }
				$row['ID'] = $post_id;
                $post = get_post($post_id);
				$post_type = $post->post_type;
                
				$row['post_type'] = $post_type;
                    $new_data[] = $row;
            }
            return $new_data;
}

/**
 * Process array elements and replace old values with new values.
 * @param array $array
 * @param array $new_format
 * @return array
 */
function change_values_format($array, $new_format ){
$boolean_to_yes = array(array(
					'old' => true,
					'new' => 'yes'
				),array(
					'old' => false,
					'new' => 'no'
				));

	foreach($array as $key => $value ){
		if(!isset($new_format[$key])){
			continue;
		}

		if($new_format[$key] === 'boolean_to_yes_no'){
			$new_format[$key] = $boolean_to_yes;
		}
		foreach($new_format[$key] as $format ){
			if( $value === $format['old']){
				$array[$key] = $format['new'];
				break;
			}
		}
	}
	return $array;
}
	/**
	 * Make a rest request internally
	 * @param str $method Request method.
	 * @param str $route Rest endpoint route
	 * @param array $data Request arguments.
	 * @return obj
	 */
function create_rest_request( $method = 'GET', $route = '', $data = array() ){

	if(empty($route)){
		return false;
	}
	$request = new WP_REST_Request( $method , $route );

    // Add specified request parameters into the request.
    if ( !empty( $data ) ) {
        foreach ( $data as $param_name => $param_value ) {
            $request->set_param( $param_name, $param_value );
        }
    }
    $response = rest_do_request( $request );	
    return $response;
}


	/**
	 * Remove array item by value
	 * @param str $value
	 * @param array $array
	 * @return array
	 */
	function remove_array_item_by_value($value, $array) {
		$key = array_search($value, $array);
		if ($key) {
			unset($array[$key]);
		}
		return $array;
	}

	public function merge_arrays_by_value($array1, $array2, $value_key = '' )
	{

	foreach($array1 as $index => $item ){
			$filtered_array2 = wp_list_filter($array2, array(
$value_key => $item[$value_key]
			));
		
		$first_match = current($filtered_array2);
		$array1[ $index ] = wp_parse_args($array1[$index], $first_match);
	}
	return $array1;
	}

	/**
	 * is plugin active?
	 * @return boolean
	 */
	function is_plugin_active($plugin_file) {
		if (empty($plugin_file)) {
			return false;
		}
		if (in_array($plugin_file, apply_filters('active_plugins', get_option('active_plugins')))) {
			return true;
		} else {
			return false;
		}
	}

	public function is_editor_page()
	{
		if( isset( $_GET['page']) && strpos($_GET['page'], 'bulk-edit-') !== false ){
			return true;
		}
		return false;
	}

	/**
	 * Get handsontable cell content (html)
	 * @param int $id
	 * @param string $key
	 * @param string $type
	 * @return string
	 */
	function get_handsontable_cell_content($id, $key, $cell_args) {
		$post = get_post($id);
		$existing_value = apply_filters('vg_sheet_editor/handsontable_cell_content/existing_value', maybe_unserialize($this->get_column_text_value($key, $id, 'meta_data')), $post, $key, $cell_args);

		if(empty($existing_value)){
			$existing_value = array();
		}

		$modal_settings = array();

		$modal_settings['post_id'] = $id;
		$modal_settings['post_title'] = $post->post_title;
		$modal_settings['post_type'] = $post->post_type;
		$modal_settings['edit_label'] = (!empty($cell_args['edit_button_label'])) ? $cell_args['edit_button_label'] : __('Edit', VGSE()->textname );
		$modal_settings['modal_id'] = (!empty($cell_args['edit_modal_id'])) ? $cell_args['edit_modal_id'] : '';
		$modal_settings['modal_title'] = (!empty($cell_args['edit_modal_title'])) ? $cell_args['edit_modal_title'] : '';
		$modal_settings['modal_description'] = (!empty($cell_args['edit_modal_description'])) ? $cell_args['edit_modal_description'] : '';
		$modal_settings['modal_local_cache'] = (!empty($cell_args['edit_modal_local_cache'])) ? $cell_args['edit_modal_local_cache'] : false;
		$modal_settings['modal_save_action'] = (!empty($cell_args['edit_modal_save_action'])) ? $cell_args['edit_modal_save_action'] : '';
		$modal_settings['modal_get_action'] = (!empty($cell_args['edit_modal_get_action'])) ? $cell_args['edit_modal_get_action'] : '';
		$modal_settings['handsontable_column_names'] = (!empty($cell_args['handsontable_column_names'])) ? $cell_args['handsontable_column_names'] : array();
		$modal_settings['handsontable_columns'] = (!empty($cell_args['handsontable_columns'])) ? $cell_args['handsontable_columns'] : array();
		$modal_settings['handsontable_column_widths'] = (!empty($cell_args['handsontable_column_widths'])) ? $cell_args['handsontable_column_widths'] : array();


		$out = '<a class="button button-handsontable" data-existing="' . htmlentities(json_encode(array_values($existing_value)), ENT_QUOTES, 'UTF-8') . '" ' 
		. 'data-modal-settings="' . htmlentities(json_encode($modal_settings), ENT_QUOTES, 'UTF-8') . '"><i class="fa fa-edit"></i> ' . $modal_settings['edit_label'] . '</a>';

		return apply_filters('vg_sheet_editor/handsontable_cell_content/output', $out, $id, $key, $cell_args);
	}
	/**
	 * Get tinymce cell content (html)
	 * @param int $id
	 * @param string $key
	 * @param string $type
	 * @return string
	 */
	function get_tinymce_cell_content($id, $key, $type) {
		$out = '<a class="btn-popup-content button button-tinymce-' . $key . '" data-type="' . $type . '" data-key="' . $key . '" data-id="' . $id . '"><i class="fa fa-edit"></i> ' . __('Edit', VGSE()->textname) . '</a>';

		return apply_filters('vg_sheet_editor/tinymce_cell_content', $out, $id, $key, $type);
	}

	/**
	 * Remove all post related actions.
	 * @param string $post_type
	 */
	function remove_all_post_actions($post_type) {

		foreach (array('transition_post_status', 'save_post', 'pre_post_update', 'add_attachment', 'edit_attachment', 'edit_post', 'post_updated', 'wp_insert_post', 'save_post_' . $post_type) as $act) {
			remove_all_actions($act);
		}
	}

	/**
	 * Get image gallery cell content (html)
	 * @param int $id
	 * @param string $key
	 * @param string $type
	 * @param bool $multiple
	 * @return string
	 */
	function get_gallery_cell_content($id, $key, $type, $multiple = false) {

		if ($type === 'post_data') {
			$btn = VGSE()->data_helpers->get_post_data($key, (int) $id);
		} else {
			$btn = get_post_meta((int) $id, $key, true);
		}

//			$this->d( $id, $key, $type, $multiple, $btn );
		if (!empty($btn)) {
			$out = '<button class="set_custom_images ';
			if ($multiple) {
				$out .= 'multiple';
			}
			$out .=' button" data-type="' . $type . '" data-key="' . $key . '" data-id="' . $id . '">' . __('Use Another Image', VGSE()->textname) . '</button>';
			$out .= ' <a href="#image" data-remodal-target="image" class="view_custom_images ';
			if ($multiple) {
				$out .= 'multiple';
			}
			$out .=' button" data-type="' . $type . '" data-key="' . $key . '" data-id="' . $id . '">' . __('View Image', VGSE()->textname) . '</a>';
		} else {
			$out = '<button class="set_custom_images ';
			if ($multiple) {
				$out .= 'multiple';
			}
			$out .=' button" data-type="' . $type . '" data-key="' . $key . '" data-id="' . $id . '">' . __('Select Image', VGSE()->textname) . '</button> <a href="#image" data-remodal-target="image" class="view_custom_images ';
			if ($multiple) {
				$out .= 'multiple';
			}
			$out .=' button hidden" data-type="' . $type . '" data-key="' . $key . '" data-id="' . $id . '">' . __('View Image', VGSE()->textname) . '</a>';
		}
		return apply_filters('vg_sheet_editor/gallery_cell_content', $out, $id, $key, $type, $multiple);
	}

	/**
	 * Initialize class
	 * @param string $post_type
	 */
	function init($post_type = null) {

		$this->post_type = (!empty($post_type) ) ? $post_type : $this->get_post_type_from_query_string();
	}

	static function get_instance() {
		if (null == WP_Sheet_Editor_Helpers::$instance) {
			WP_Sheet_Editor_Helpers::$instance = new WP_Sheet_Editor_Helpers();
			WP_Sheet_Editor_Helpers::$instance->init();
		}
		return WP_Sheet_Editor_Helpers::$instance;
	}

	/**
	 * Dump
	 * 
	 * Dump any variable
	 * .
	 * @param int|string|array|object $var
	 * 
	 */
	function d($var) {
		if (defined('VGSE_DEBUG') && !VGSE_DEBUG) {
			return;
		}
		if (count(func_get_args()) > 1) {
			foreach (func_get_args() as $arg) {
				$this->d($arg);
			}
			return $this;
		}
		echo '<pre>';
		var_dump($var);
		echo '</pre>';
		return $this;
	}

	/**
	 * Dump and Die
	 * 
	 * @param int|string|array|object $var
	 */
	function dd($var) {
		if (defined('VGSE_DEBUG') && !VGSE_DEBUG) {
			return;
		}
		if (count(func_get_args()) > 1) {
			foreach (func_get_args() as $arg) {
				$this->d($arg);
			}
			die();
		}
		$this->d($var);
		die();
	}

	/**
	 * Get attachment ID from URL
	 * 
	 * It accepts auto-generated thumbnails URLs.
	 * 
	 * @global type $wpdb
	 * @param type $attachment_url
	 * @return type
	 */
	function get_attachment_id_from_url($attachment_url = '') {
		global $wpdb;
		$attachment_id = false;
		// If there is no url, return.
		if ('' == $attachment_url)
			return;
		// Get the upload directory paths
		$upload_dir_paths = wp_upload_dir();
		// Make sure the upload path base directory exists in the attachment URL, to verify that we're working with a media library image
		if (false !== strpos($attachment_url, $upload_dir_paths['baseurl'])) {
			// If this is the URL of an auto-generated thumbnail, get the URL of the original image
			$attachment_url = preg_replace('/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $attachment_url);
			// Remove the upload path base directory from the attachment URL
			$attachment_url = str_replace($upload_dir_paths['baseurl'] . '/', '', $attachment_url);
			// Finally, run a custom database query to get the attachment ID from the modified attachment URL
			$attachment_id = $wpdb->get_var($wpdb->prepare("SELECT wposts.ID FROM $wpdb->posts wposts, $wpdb->postmeta wpostmeta WHERE wposts.ID = wpostmeta.post_id AND wpostmeta.meta_key = '_wp_attached_file' AND wpostmeta.meta_value = '%s' AND wposts.post_type = 'attachment'", $attachment_url));
		}
		return $attachment_id;
	}

	/**
	 * Get post type from query string
	 * @return string
	 */
	function get_post_type_from_query_string() {
		$query_strings = $this->clean_data($_GET);
		if (!empty($query_strings['post_type'])) {
			$current_post = $query_strings['post_type'];
		} elseif (!empty($query_strings['page']) && strpos($query_strings['page'], 'bulk-edit-') !== false) {
			$current_post = str_replace('bulk-edit-', '', $query_strings['page']);
		} else {
			$current_post = 'post';
		}
		return $current_post;
	}

	/**
	 * Get post types as array
	 * @return array
	 */
	function post_type_array() {
		if (!is_array($this->post_type)) {
			$this->post_type = array($this->post_type);
		}
		return $this->post_type;
	}

	/**
	 * Is post type allowed?
	 * @param string $post_type
	 * @return boolean
	 */
	function is_post_type_allowed($post_type) {
		if (in_array($post_type, array_keys(VGSE()->allowed_post_types))) {
			return true;
		} else {
			return false;
		}
	}

	/*
	 * Clean $_POST or $_GET or $_REQUEST data
	 */

	/**
	 * Clean up data
	 * @param array $posts
	 * @return array
	 */
	function clean_data($posts) {

		$clean = array();
		if (is_array($posts)) {
			foreach ($posts as $post => $value) {
				if (!is_array($value)) {
					$clean[$post] = htmlspecialchars(rawurldecode(trim($value)), ENT_QUOTES, 'UTF-8');
				} else {
					$clean[$post] = $this->clean_data($value);
				}
			}
		} elseif (is_string($posts)) {
			$clean = strip_tags($posts);
		} else {
			$clean = $posts;
		}

		return $clean;
	}

	/**
	 * Get post type label from key
	 * @param string $post_type_key
	 * @return string
	 */
	function get_post_type_label($post_type_key) {

		// Get all post type *names*, that are shown in the admin menu
		$post_types = get_post_types(array(), 'objects', 'OR');

		$labels = wp_list_pluck($post_types, 'labels');
		$names = wp_list_pluck($labels, 'name');
		$name = $names[$post_type_key];

		return $name;
	}

	/**
	 * Get taxonomies registered with a post type
	 * @param string $post_type
	 * @return array
	 */
	function get_post_type_taxonomies($post_type) {
		$taxonomies = get_object_taxonomies($post_type, 'objects');
		$out = array();
		if (!empty($taxonomies) && is_array($taxonomies)) {
			foreach ($taxonomies as $taxonomy) {

				if (!$taxonomy->show_ui) {
					continue;
				}
				$out[] = $taxonomy;
			}
		}
		return $out;
	}

	/**
	 * Get all post types
	 * @return array
	 */
	function get_all_post_types() {
		$out = get_post_types(array(), 'objects', 'OR');
		return $out;
	}

	/**
	 * Get all post types names
	 * @return array
	 */
	function get_all_post_types_names( $include_private = true ) {
		$args = array();

		if(!$include_private){
			$args = array(
			'public' => true,
			'public_queryable' => true,
				);
		}

		$out = get_post_types($args, 'names', 'OR');
		return $out;
	}

	/**
	 * Get single data from all taxonomies registered with a post type.
	 * @param string $post_type
	 * @param string $field_key
	 * @return mixed
	 */
	function get_post_type_taxonomies_single_data($post_type, $field_key) {

		$taxonomies = $this->get_post_type_taxonomies($post_type);
		$out = array();
		if (!empty($taxonomies) && is_array($taxonomies)) {
			foreach ($taxonomies as $taxonomy) {
				$out[] = $taxonomy->$field_key;
			}
		}
		return $out;
	}

	/**
	 * Convert multidimensional arrays to unidimensional
	 * @param array $array
	 * @param array $return
	 * @return array
	 */
	function array_flatten($array, $return) {
		for ($x = 0; $x <= count($array); $x++) {
			if (!empty($array[$x]) && is_array($array[$x])) {
				$return = $this->array_flatten($array[$x], $return);
			} else {
				if (isset($array[$x])) {
					$return[] = $array[$x];
				}
			}
		}
		return $return;
	}

	/**
	 * Get a list of <option> tags of all enabled columns from a post type
	 * @param string $post_type
	 * @param array $filters
	 * @return string
	 */
	function get_post_type_columns_options($post_type, $filters = array(), $formula_format = false ) {

		$spreadsheet_columns = VGSE()->columns->get_post_type_items($post_type);
		$out = '';
		if (!empty($spreadsheet_columns) && is_array($spreadsheet_columns)) {
			if (!empty($filters)) {
				if (empty($filters['operator'])) {
					$filters['operator'] = 'AND';
				}
				$spreadsheet_columns = wp_list_filter($spreadsheet_columns, $filters['conditions'], $filters['operator']);
			}
			foreach ($spreadsheet_columns as $item => $value) {
				if( empty($value['value_type'])){
					$value['value_type'] = 'text';
				}
				$name = $value['title'];
				$key = $item;
				if( $formula_format ){
					$name = '$' . $item . '$ (' . $value['title'] .')';
					$key = '$' . $item . '$';
				}
				$out .= '<option value="' . $key . '" data-value-type="' . $value['value_type'] . '">' . $name . '</option>';
			}
		}

		return $out;
	}

	/**
	 * Increase editions counter. This is used to keep track of 
	 * how many posts have been edited using the spreadsheet editor.
	 * 
	 * This information is displayed on the dashboard widget.
	 */
	function increase_counter($key = 'editions', $count = 1) {
		$allowed_keys = array(
			'editions',
			'processed',
		);

		if (!in_array($key, $allowed_keys)) {
			return;
		}
		$counter = get_option('vgse_' . $key . '_counter', 0);

		$counter += $count;

		update_option('vgse_' . $key . '_counter', $counter);
	}

}
}