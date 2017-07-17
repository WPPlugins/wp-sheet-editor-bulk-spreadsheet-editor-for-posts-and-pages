<?php

if (!class_exists('VG_Visual_Composer_Integration')) {

	class VG_Visual_Composer_Integration {

		static private $instance = false;

		private function __construct() {
			
		}

		function init() {
			
			add_filter('vg_sheet_editor/tinymce_cell_content', array($this, 'add_editor_buttons'), 10, 4);
			add_action('vg_sheet_editor/columns/post_type_items', array($this, 'filter_columns_settings'), 10, 2);
		}

		/**
		 * Modify spreadsheet columns settings.
		 * 
		 * It changes the names and settings of some columns.
		 * @param array $spreadsheet_columns
		 * @param string $post_type
		 * @param bool $exclude_formatted_settings
		 * @return array
		 */
		function filter_columns_settings($spreadsheet_columns, $post_type) {

			if (!$this->is_post_type_allowed($post_type)) {
				return $spreadsheet_columns;
			}

			if (!empty($spreadsheet_columns['content'])) {
				$spreadsheet_columns['content']['colum_width'] = 230;
			}
			return $spreadsheet_columns;
		}

		function is_post_type_allowed($post_type) {

			if (!function_exists('vc_enabled_frontend') || !function_exists('vc_editor_post_types')) {
				return false;
			}
			$vc_post_types = vc_editor_post_types();
			if (!in_array($post_type, $vc_post_types)) {
				return false;
			}

			return true;
		}

		function integration_allowed($id, $key, $type) {
			if (!function_exists('vc_enabled_frontend') || !function_exists('vc_editor_post_types')) {
				return false;
			}
			if ($type !== 'post_data' || (!in_array($key, array('content', 'post_content')))) {
				return false;
			}
			$post_type = get_post_type($id);

			if (!$this->is_post_type_allowed($post_type)) {
				return false;
			}

			return true;
		}

		function add_editor_buttons($html, $id, $key, $type) {

			if (!$this->integration_allowed($id, $key, $type)) {
				return $html;
			}
			$post_type = get_post_type($id);

			$html .= '<a href="' . add_query_arg(array(
						'vc_action' => 'vc_inline',
						'post_id' => $id,
						'post_type' => $post_type,
							), admin_url('post.php')) . '" class="button visual-composer-backend visual-composer-edit" target="_blank" /><i class="fa fa-edit"></i> ' . __('Live', VGSE()->textname) . '</a>';
			if (vc_enabled_frontend()) {
				$html .= '<a href="' . add_query_arg(array(
							'action' => 'edit',
							'post' => $id,
							'wpb_vc_js_status' => 'true',
								), admin_url('post.php')) . '" class="button visual-composer-live visual-composer-edit" target="_blank" /><i class="fa fa-edit"></i> ' . __('Backend', VGSE()->textname) . '</a>';
			}
			return $html;
		}

		/**
		 * Creates or returns an instance of this class.
		 *
		 */
		static function get_instance() {
			if (null == VG_Visual_Composer_Integration::$instance) {
				VG_Visual_Composer_Integration::$instance = new VG_Visual_Composer_Integration();
				VG_Visual_Composer_Integration::$instance->init();
			}
			return VG_Visual_Composer_Integration::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}

if (!function_exists('VG_Visual_Composer_Integration_Obj')) {

	function VG_Visual_Composer_Integration_Obj() {
		return VG_Visual_Composer_Integration::get_instance();
	}

}

VG_Visual_Composer_Integration_Obj();
