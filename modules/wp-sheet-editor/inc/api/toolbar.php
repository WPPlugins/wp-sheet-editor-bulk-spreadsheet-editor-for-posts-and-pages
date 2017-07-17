<?php

if (!class_exists('WP_Sheet_Editor_Toolbar')) {

	class WP_Sheet_Editor_Toolbar {

		static private $instance = false;
		private $registered_items = array();

		private function __construct() {
			
		}

		function init() {
			
		}

		/**
		 * Register toolbar item
		 * @param string $key
		 * @param array $args
		 * @param string $post_type
		 */
		function register_item($key, $args = array(), $post_type = 'post') {
			$defaults = array(
				'type' => 'button', // html | switch | button
				'icon' => '', // Font awesome icon name , including font awesome prefix: fa fa-XXX. Only for type=button. 
				'help_tooltip' => '', // help message, accepts html with entities encoded.
				'content' => '', // if type=button : button label | if type=html : html string.
				'css_class' => '', // .button will be added to all items also.	
				'key' => $key,
				'extra_html_attributes' => '', // useful for adding data attributes
				'container_id' => '',
				'label' => $args['content'],
				'id' => '',
				'allow_in_frontend' => true,
				'allow_to_hide' => true,
				'container_class' => '',
				'default_value' => '1', // only if type=switch - 1=on , 0=off
				'toolbar_key' => 'primary'
			);

			$args = wp_parse_args($args, $defaults);

			if (empty($post_type)) {
				$post_type = 'post';
			}

			if (empty($this->registered_items[$post_type])) {
				$this->registered_items[$post_type] = array();
			}
			if (empty($this->registered_items[$post_type][$args['toolbar_key']])) {
				$this->registered_items[$post_type][$args['toolbar_key']] = array();
			}
			$this->registered_items[$post_type][$args['toolbar_key']][$key] = $args;
		}

		/**
		 * Get individual toolbar item
		 * @return array
		 */
		function get_item($item_key, $post_type = 'post', $toolbar_key = 'primary') {
			$post_type_items = $this->get_post_type_items($post_type, $toolbar_key);
			if (isset($post_type_items[$item_key])) {
				return $post_type_items[$item_key];
			} else {
				return false;
			}
		}

		/**
		 * Get individual toolbar item as html
		 * @return string
		 */
		function get_rendered_item($item_key, $post_type = 'post', $toolbar_key = 'primary') {
			$item = $this->get_item($item_key, $post_type, $toolbar_key);

			$content = '';
			if ($item['type'] === 'button') {
				$content .= '<button name="' . $item['key'] . '" class="button ' . $item['css_class'] . '" ' . $item['extra_html_attributes'] . '  id="' . $item['id'] . '" >';
				if (!empty($item['icon'])) {
					$content .= '<i class="' . $item['icon'] . '"></i> ';
				}
				$content .= $item['content'] . '</button>';

				if (!empty($item['url'])) {
					$content = str_replace('<button', '<a href="' . $item['url'] . '" ', $content);
					$content = str_replace('</button', '</a', $content);
				}
			} elseif ($item['type'] === 'html') {
				$content .= $item['content'];
			} elseif ($item['type'] === 'switch') {
				$content .= '<input type="checkbox" ';
				if ($item['default_value']) {
					$content .= ' value="1" checked';
				} else {
					$content .= ' value="0" ';
				}
				$content .= ' id="' . $item['id'] . '"  data-labelauty="' . $item['content'] . '" class="' . $item['css_class'] . '" ' . $item['extra_html_attributes'] . ' /> ';
			}

			if (empty($content)) {
				return false;
			}

			$out = '<div class="button-container ' . $item['key'] . '-container ' . $item['container_class'] . '" id="' . $item['container_id'] . '">' . $content;

			if (!empty($item['help_tooltip'])) {
				$out .= '<a href="#" class="tipso" data-tipso="' . $item['help_tooltip'] . '">( ? )</a> ';
			}
			$out .= '</div>';
			return $out;
		}

		/**
		 * Get all toolbar items by post type rendered as html
		 * @return string
		 */
		function get_rendered_post_type_items($post_type, $toolbar_key = 'primary') {
			$items = $this->get_post_type_items($post_type, $toolbar_key);

			if (!$items) {
				return false;
			}

			$out = '';
			foreach ($items as $item_key => $item) {
				$rendered_item = $this->get_rendered_item($item_key, $post_type, $toolbar_key);

				if (!empty($rendered_item)) {
					$out .= $rendered_item;
				}
			}

			return $out;
		}

		/**
		 * Get all toolbar items
		 * @return array
		 */
		function get_items() {
			$items = apply_filters('vg_sheet_editor/toolbar/get_items', $this->registered_items );

			return $items;
		}

		/**
		 * Get all toolbar items by post type
		 * @return array
		 */
		function get_post_type_items($post_type, $toolbar_key = 'primary') {
			$items = $this->get_items();

			if (isset($items[$post_type][$toolbar_key])) {
				return $items[$post_type][$toolbar_key];
			} else {
				false;
			}
		}

		/**
		 * Creates or returns an instance of this class.
		 *
		 */
		static function get_instance() {
			if (null == WP_Sheet_Editor_Toolbar::$instance) {
				WP_Sheet_Editor_Toolbar::$instance = new WP_Sheet_Editor_Toolbar();
				WP_Sheet_Editor_Toolbar::$instance->init();
			}
			return WP_Sheet_Editor_Toolbar::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}