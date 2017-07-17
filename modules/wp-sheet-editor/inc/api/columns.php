<?php

if (!class_exists('WP_Sheet_Editor_Columns')) {

	class WP_Sheet_Editor_Columns {

		static private $instance = false;
		private $registered_items = array();

		private function __construct() {
			
		}

		function init() {
			
		}

		/**
		 * Register spreadsheet column
		 * @param string $key
		 * @param string $post_type
		 * @param array $args
		 */
		function register_item($key, $post_type = null, $args = array()) {
			$defaults = array(
				'data_type' => 'post_data', //String (post_data,post_meta|meta_data)	
				'unformated' => array('data' => 'ID', 'readOnly' => true), //Array (Valores admitidos por el plugin de handsontable)
				'colum_width' => 100, //int (Ancho de la columna)
				'title' => ucwords(str_replace(array('-', '_'), ' ', $key)), //String (Titulo de la columna)
				'type' => '', // String (Es para saber si será un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|(vacio)
				'supports_formulas' => false,
				'formated' => array('data' => 'ID', 'readOnly' => true), //Array (Valores admitidos por el plugin de handsontable)
				'allow_to_hide' => true,
				'allow_to_rename' => true,
				'allow_to_save' => true,
				'default_value' => '',
			);
			
			$args = wp_parse_args($args, $defaults);

			if( empty( $args['default_title'] ) ){
				$args['default_title'] = $args['title'];
			}

			if( empty( $args['key'] ) ){
				$args['key'] = $key;
			}

			if( empty($args['value_type'] ) ){ 
				if(!empty($args['type'] ) ){
				$args['value_type'] = $args['type'];
				} elseif($args['data_type'] === 'post_terms' ){
				$args['value_type'] = 'post_terms';
				} else {
					$args['value_type'] = 'text';
				}
			}
			
			// post_meta is an alias of meta_data
			if( $args['data_type'] === 'post_meta' ){
				$args['data_type'] = 'meta_data';
			}
			
			if (empty($post_type)) {
				$post_type = 'post';
			}


			if( ! apply_filters('vg_sheet_editor/columns/can_add_item', true, $key, $args, $post_type )){
				return;
			}
			if (!isset($this->registered_items[$post_type])) {
				$this->registered_items[$post_type] = array();
			}
			$this->registered_items[$post_type][$key] = $args;
		}

		/**
		 * Get all spreadsheet columns
		 * @return array
		 */
		function get_items() {
			$spreadsheet_columns = $this->registered_items;
			$spreadsheet_columns = apply_filters('vg_sheet_editor/columns/all_items', $spreadsheet_columns);

			return $spreadsheet_columns;
		}

		/**
		 * Get individual spreadsheet column
		 * @return array
		 */
		function get_item($item_key, $post_type = 'post', $run_callbacks = false) {
			$items = $this->get_post_type_items($post_type, $run_callbacks);
			if (isset($items[$item_key])) {
				return $items[$item_key];
			} else {
				return false;
			}
		}

		function _run_callbacks_on_items($items) {
			if (empty($items) || !is_array($items)) {
				return array();
			}
			foreach ($items as $column_key => $column_args) {
				if (isset($column_args['formated'])) {
					if (empty($column_args['formated']['callback_args'])) {
						$column_args['formated']['callback_args'] = array();
					}
					if (isset($column_args['formated']['selectOptions']) && is_callable($column_args['formated']['selectOptions'])) {
						$items[$column_key]['formated']['selectOptions'] = call_user_func_array($column_args['formated']['selectOptions'], $column_args['formated']['callback_args']);
					}
					if (isset($column_args['formated']['source']) && is_callable($column_args['formated']['source'])) {
						$items[$column_key]['formated']['source'] = call_user_func_array($column_args['formated']['source'], $column_args['formated']['callback_args']);
					}
				}
			}
			return $items;
		}

		/**
		 * Get all spreadsheet columns by post type
		 * @return array
		 */
		function get_post_type_items($post_type, $run_callbacks = false) {
			$items = $this->get_items();
			$out = array();
			if (isset($items[$post_type])) {
				$out = $items[$post_type];

				if ($run_callbacks) {
					$out = $this->_run_callbacks_on_items($out);
				}
			}

			$out = apply_filters('vg_sheet_editor/columns/post_type_items', $out, $post_type, $run_callbacks);
			return $out;
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WP_Sheet_Editor_Columns::$instance) {
				WP_Sheet_Editor_Columns::$instance = new WP_Sheet_Editor_Columns();
				WP_Sheet_Editor_Columns::$instance->init();
			}
			return WP_Sheet_Editor_Columns::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}