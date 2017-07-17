<?php


if (!defined('VGSE_DEBUG')) {
	define('VGSE_DEBUG', false);
}
if (!defined('VGSE_DIR')) {
	define('VGSE_DIR', __DIR__);
}
if (!defined('VGSE_KEY')) {
	define('VGSE_KEY', 'vg_sheet_editor');
}
if (!defined('VGSE_MAIN_FILE')) {
	define('VGSE_MAIN_FILE', __FILE__);
}
if (!defined('VGSE_CORE_MAIN_FILE')) {
	define('VGSE_CORE_MAIN_FILE', __FILE__);
}
require_once 'inc/options-init.php';
require_once 'inc/api/helpers.php';
require_once 'inc/api/data.php';
require_once 'inc/api/toolbar.php';
require_once 'inc/api/columns.php';
require_once 'inc/tgm-init.php';
require_once 'inc/teasers/formulas.php';
require_once 'inc/teasers/upgrade-popup.php';
require_once 'inc/teasers/woocommerce.php';
require_once 'inc/teasers/filters.php';
require_once 'inc/teasers/post-types.php';
require_once 'inc/teasers/email-optin.php';
require_once 'inc/integrations/visual-composer.php';

if (!class_exists('WP_Sheet_Editor')) {

	class WP_Sheet_Editor {

		private $post_type;
		var $version = '1.4.7';
		var $textname = 'vg_sheet_editor';
		var $options_key = 'vg_sheet_editor';
		var $plugin_url = null;
		var $plugin_dir = null;
		var $options = null;
		var $texts = null;
		var $data_helpers = null;
		var $helpers = null;
		var $allowed_post_types = null;
		var $registered_columns = null;
		var $toolbar = null;
		var $columns = null;
		var $support_links = array();
		var $buy_link = null;
		static private $instance = null;

		/**
		 * Creates or returns an instance of this class.
		 *
		 * 
		 */
		static function get_instance() {
			if (null == WP_Sheet_Editor::$instance) {
				WP_Sheet_Editor::$instance = new WP_Sheet_Editor();
				WP_Sheet_Editor::$instance->init();
			}
			return WP_Sheet_Editor::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

		private function __construct() {
			
		}

		/**
		 * Register core toolbar items
		 */
		private function _register_toolbar_items() {
			$post_types = $this->post_type;
			if (empty($post_types)) {
				return;
			}
			if (!is_array($post_types)) {
				$post_types = array($post_types);
			}
			foreach ($post_types as $post_type) {
				// secondary
				VGSE()->toolbar->register_item('support', array(
					'type' => 'button',
					'content' => __('Help', VGSE()->textname),
					'icon' => 'fa fa-question-circle',
					'toolbar_key' => 'secondary',
					'extra_html_attributes' => 'data-remodal-target="modal-support"',
						), $post_type);
				VGSE()->toolbar->register_item('extensions', array(
					'type' => 'button',
					'content' => __('All Extensions', VGSE()->textname),
					'icon' => 'fa fa-rocket',
					'toolbar_key' => 'secondary',
					'extra_html_attributes' => 'data-remodal-target="modal-extensions"',
						), $post_type);

				// primary
				$this->toolbar->register_item('save', array(
					'allow_to_hide' => false,
					'type' => 'button', // html | switch | button
					'icon' => 'fa fa-save', // Font awesome icon name , including font awesome prefix: fa fa-XXX. Only for type=button.
					'content' => __('Save all changes', VGSE()->textname), // if type=button : button label | if type=html : html string.
					'css_class' => 'primary button-only-icon', // .button will be added to all items also.	
					'extra_html_attributes' => 'data-remodal-target="bulk-save"', // useful for adding data attributes
						), $post_type);
				$this->toolbar->register_item('add_rows', array(
					'type' => 'html', // html | switch | button
					'content' => '<button name="addrow" id="addrow" class="button button-only-icon"><i class="fa fa-plus"></i> ' . __('Create new posts', VGSE()->textname) . '</button><input type="number" min="1" value="1" class="number_rows" /> <input type="hidden" id="post_type_new_row" value="' . $post_type . '" />', // if type=button : button label | if type=html : html string.
					'help_tooltip' => __('Add rows for new posts', VGSE()->textname),
						), $post_type);
				$this->toolbar->register_item('load', array(
					'allow_to_hide' => false,
					'type' => 'button', // html | switch | button
					'content' => __('Load', VGSE()->textname),
					'container_class' => 'hidden',
						), $post_type);
				$this->toolbar->register_item('cells_format', array(
					'type' => 'switch', // html | switch | button
					'content' => __('Show cells as simple text', VGSE()->textname),
					'id' => 'formato',
					'toolbar_key' => ( defined('VGSE_WC_FILE') ) ? 'secondary' : 'primary',
					'help_tooltip' => __('When this is disabled dates will be displayed in a calendar and fields with multiple options as dropdowns , when enabled they will be displayed as simple text', VGSE()->textname),
					'default_value' => false,
						), $post_type);
				$this->toolbar->register_item('infinite_scroll', array(
					'type' => 'switch', // html | switch | button
					'content' => __('Load more items on scroll', VGSE()->textname),
					'id' => 'infinito',
					'toolbar_key' => ( defined('VGSE_WC_FILE') ) ? 'secondary' : 'primary',
					'help_tooltip' => __('When this is enabled more items will be loaded to the bottom of the spreadsheet when you reach the end of the page', VGSE()->textname),
					'default_value' => VGSE()->options['be_load_items_on_scroll'] == true,
						), $post_type);
			}

			do_action('vg_sheet_editor/toolbar/core_items_registered');
		}

		/**
		 * Register core columns
		 */
		private function _register_columns() {

			$post_types = $this->post_type;

			if (empty($post_types)) {
				return;
			}
			if (!is_array($post_types)) {
				$post_types = array($post_types);
			}
			foreach ($post_types as $post_type) {
				VGSE()->columns->register_item('ID', $post_type, array(
					'data_type' => 'post_data', //String (post_data,post_meta|meta_data)	
					'unformated' => array('data' => 'ID', 'renderer' => 'html', 'readOnly' => true), //Array (Valores admitidos por el plugin de handsontable)
					'colum_width' => 75, //int (Ancho de la columna)
					'title' => __('ID', $this->textname), //String (Titulo de la columna)
					'type' => '', // String (Es para saber si será un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|(vacio)
					'supports_formulas' => false,
					'allow_to_hide' => false,
					'allow_to_save' => false,
					'allow_to_rename' => false,
					'formated' => array('data' => 'ID', 'renderer' => 'html', 'readOnly' => true),
				));
				if ($post_type === 'attachment') {
					VGSE()->columns->register_item('guid', $post_type, array(
						'data_type' => 'post_data',
						'unformated' => array('data' => 'guid', 'renderer' => 'html', 'readOnly' => true),
						'colum_width' => 150,
						'type' => 'inline_image',
						'supports_formulas' => false,
						'title' => __('Preview', VGSE()->textname), //String (Titulo de la columna)
						'allow_to_hide' => true,
						'allow_to_rename' => true,
						'allow_to_save' => false,
						'formated' => array('data' => 'guid', 'renderer' => 'html', 'readOnly' => true),
					));
				}
				VGSE()->columns->register_item('title', $post_type, array(
					'data_type' => 'post_data',
					'unformated' => array('data' => 'title'),
					'colum_width' => 300,
					'title' => __('Title', $this->textname),
					'type' => '',
					'supports_formulas' => true,
					'formated' => array('data' => 'title', 'renderer' => 'html'),
					'allow_to_hide' => true,
					'allow_to_rename' => true,
				));
				if ($post_type !== 'attachment') {
					VGSE()->columns->register_item('post_name', $post_type, array(
						'data_type' => 'post_data', //String (post_data,post_meta|meta_data)	
						'unformated' => array('data' => 'post_name', 'renderer' => 'html', 'readOnly' => (bool) !VGSE()->options['be_allow_edit_slugs']), //Array (Valores admitidos por el plugin de handsontable)
						'colum_width' => 300, //int (Ancho de la columna)
						'title' => __('URL Slug', $this->textname), //String (Titulo de la columna)
						'type' => '', // String (Es para saber si será un boton que abre popup, si no dejar vacio) boton_tiny|boton_gallery|boton_gallery_multiple|(vacio)
						'supports_formulas' => ( isset(VGSE()->options['be_allow_edit_slugs']) ) ? (bool) VGSE()->options['be_allow_edit_slugs'] : false,
						'allow_to_hide' => true,
						'allow_to_save' => ( isset(VGSE()->options['be_allow_edit_slugs']) ) ? (bool) VGSE()->options['be_allow_edit_slugs'] : false,
						'allow_to_rename' => true,
						'formated' => array('data' => 'post_name', 'renderer' => 'html', 'readOnly' => ( isset(VGSE()->options['be_allow_edit_slugs']) ) ? (bool) !VGSE()->options['be_allow_edit_slugs'] : true),
					));
				}
				if ($post_type === 'attachment') {
					VGSE()->columns->register_item('post_mime_type', $post_type, array(
						'data_type' => 'post_data',
						'unformated' => array('data' => 'post_mime_type'),
						'colum_width' => 150,
						'title' => __('Format', VGSE()->textname),
						'type' => '',
						'supports_formulas' => false,
						'formated' => array('data' => 'post_mime_type', 'renderer' => 'text', 'readOnly' => true),
						'allow_to_hide' => true,
						'allow_to_save' => false,
						'allow_to_rename' => true,
					));
					VGSE()->columns->register_item('_wp_attachment_image_alt', $post_type, array(
						'data_type' => 'meta_data',
						'unformated' => array('data' => '_wp_attachment_image_alt'),
						'colum_width' => 150,
						'title' => __('Alt text', VGSE()->textname),
						'type' => '',
						'supports_formulas' => true,
						'formated' => array('data' => '_wp_attachment_image_alt', 'renderer' => 'text'),
						'allow_to_hide' => true,
						'allow_to_rename' => true,
					));
				}
				if (post_type_supports($post_type, 'editor') || $post_type === 'attachment') {
					VGSE()->columns->register_item('content', $post_type, array(
						'data_type' => 'post_data',
						'unformated' => array('data' => 'content', 'renderer' => 'html', 'readOnly' => true),
						'colum_width' => 75,
						'title' => ( $post_type !== 'attachment' ) ? __('Content', VGSE()->textname) : __('Description', VGSE()->textname),
						'type' => 'boton_tiny',
						'supports_formulas' => true,
						'formated' => array('data' => 'content', 'renderer' => 'html', 'readOnly' => true),
						'allow_to_hide' => true,
						'allow_to_save' => false,
						'allow_to_rename' => true,
					));
				}
				VGSE()->columns->register_item('date', $post_type, array(
					'data_type' => 'post_data',
					'unformated' => array('data' => 'date'),
					'colum_width' => 100,
					'title' => __('Date', $this->textname),
					'type' => '',
					'supports_formulas' => true,
					'formated' => array('data' => 'date', 'type' => 'date', 'dateFormat' => 'MM-DD-YYYY', 'correctFormat' => true, 'defaultDate' => date('m-d-Y'), 'datePickerConfig' => array('firstDay' => 0, 'showWeekNumber' => true, 'numberOfMonths' => 1)),
					'allow_to_hide' => true,
					'allow_to_rename' => true,
				));
				VGSE()->columns->register_item('modified', $post_type, array(
					'data_type' => 'post_data',
					'unformated' => array('data' => 'modified', 'renderer' => 'html', 'readOnly' => true),
					'colum_width' => 100,
					'title' => __('Modified Date', $this->textname),
					'type' => '',
					'supports_formulas' => false,
					'formated' => array('data' => 'modified', 'renderer' => 'html', 'readOnly' => true),
					'allow_to_hide' => true,
					'allow_to_save' => false,
					'allow_to_rename' => true,
				));
				if (post_type_supports($post_type, 'author')) {
					VGSE()->columns->register_item('author', $post_type, array(
						'data_type' => 'post_data',
						'unformated' => array('data' => 'author'),
						'colum_width' => 120,
						'title' => ( $post_type !== 'attachment' ) ? __('Author', VGSE()->textname) : __('Uploaded by', VGSE()->textname),
						'type' => '',
						'supports_formulas' => true,
						'formated' => array('data' => 'author', 'editor' => 'select', 'selectOptions' => array(VGSE()->data_helpers, 'get_authors_list')),
						'allow_to_hide' => true,
						'allow_to_rename' => true,
					));
				}
				if (post_type_supports($post_type, 'excerpt') || $post_type === 'attachment') {
					VGSE()->columns->register_item('excerpt', $post_type, array(
						'data_type' => 'post_data',
						'unformated' => array('data' => 'excerpt'),
						'colum_width' => 400,
						'title' => ( $post_type !== 'attachment' ) ? __('Excerpt', VGSE()->textname) : __('Caption', VGSE()->textname),
						'type' => '',
						'supports_formulas' => true,
						'formated' => array('data' => 'excerpt', 'renderer' => 'html'),
						'allow_to_hide' => true,
						'allow_to_rename' => true,
					));
				}


				$post_statuses = get_post_statuses();

				if (!isset($post_statuses['trash'])) {
					$post_statuses['trash'] = 'Trash';
				}
				if (( $post_type === 'page' && !current_user_can('publish_pages') ) || ( $post_type !== 'page' && !current_user_can('publish_posts'))) {
					unset($post_statuses['publish']);
				}
				if (($post_type === 'page' && !current_user_can('delete_pages')) || ($post_type !== 'page' && !current_user_can('delete_posts'))) {
					unset($post_statuses['trash']);
				}


				VGSE()->columns->register_item('status', $post_type, array(
					'data_type' => 'post_data',
					'unformated' => array('data' => 'status'),
					'colum_width' => 100,
					'title' => __('Status', $this->textname),
					'type' => '',
					'supports_formulas' => true,
					'formated' => array('data' => 'status', 'editor' => 'select', 'selectOptions' => $post_statuses),
					'allow_to_hide' => true,
					'allow_to_rename' => true,
				));
				if (post_type_supports($post_type, 'comments')) {
					VGSE()->columns->register_item('comment_status', $post_type, array(
						'data_type' => 'post_data',
						'unformated' => array('data' => 'comment_status'),
						'colum_width' => 100,
						'title' => __('Comments', $this->textname),
						'type' => '',
						'supports_formulas' => true,
						'formated' => array(
							'data' => 'comment_status',
							'type' => 'checkbox',
							'checkedTemplate' => 'open',
							'uncheckedTemplate' => 'closed',
						),
						'default_value' => 'open',
						'allow_to_hide' => true,
						'allow_to_rename' => true,
					));
				}

				if (post_type_supports($post_type, 'page-attributes') && $post_type !== 'product' && $post_type !== 'attachment') {

					VGSE()->columns->register_item('parent', $post_type, array(
						'data_type' => 'post_data',
						'unformated' => array('data' => 'parent'),
						'colum_width' => 100,
						'title' => __('Page Parent', $this->textname),
						'type' => '',
						'supports_formulas' => true,
						'formated' => array('data' => 'parent', 'type' => 'autocomplete', 'source' => array(VGSE()->data_helpers, 'get_all_post_titles_from_post_type'), 'callback_args' => array($post_type, ARRAY_N, true)),
						'allow_to_hide' => true,
						'allow_to_rename' => true,
					));
				}
				if (post_type_supports($post_type, 'thumbnail')) {
					VGSE()->columns->register_item('_thumbnail_id', $post_type, array(
						'data_type' => 'meta_data',
						'unformated' => array('data' => '_thumbnail_id', 'renderer' => 'html', 'readOnly' => true),
						'colum_width' => 300,
						'supports_formulas' => true,
						'title' => __('Featured Image', $this->textname),
						'type' => 'boton_gallery', //boton_gallery|boton_gallery_multiple (Multiple para galeria)
						'formated' => array('data' => '_thumbnail_id', 'renderer' => 'html', 'readOnly' => true),
						'allow_to_hide' => true,
						'allow_to_save' => true,
						'allow_to_rename' => true,
					));
				}


				$taxonomies = get_object_taxonomies($post_type, 'objects');

				if (!empty($taxonomies) && is_array($taxonomies)) {
					foreach ($taxonomies as $taxonomy) {

						if (!$taxonomy->show_ui) {
							continue;
						}
						VGSE()->columns->register_item($taxonomy->name, $post_type, array(
							'data_type' => 'post_terms',
							'unformated' => array('data' => $taxonomy->name),
							'colum_width' => 150,
							'title' => $taxonomy->label,
							'type' => '',
							'supports_formulas' => true,
							'formated' => array('data' => $taxonomy->name, 'type' => 'autocomplete', 'source' => array(VGSE()->data_helpers, 'get_taxonomy_terms'), 'callback_args' => array($taxonomy->name)),
							'allow_to_hide' => true,
							'allow_to_rename' => true,
						));
					}
				}
			}

			do_action('vg_sheet_editor/core_columns_registered');
		}

		/**
		 * Plugin init
		 */
		function init() {
			do_action('vg_sheet_editor/before_initialized');

			// Exit if frontend and it´s not allowed
			if (!is_admin() && !apply_filters('vg_sheet_editor/allowed_on_frontend', false)) {
				return;
			}

			// Init internal APIs
			$this->data_helpers = WP_Sheet_Editor_Data::get_instance();
			$this->helpers = WP_Sheet_Editor_Helpers::get_instance();
			$this->toolbar = WP_Sheet_Editor_Toolbar::get_instance();
			$this->columns = WP_Sheet_Editor_Columns::get_instance();

			$this->buy_link = ( function_exists('vgse_freemius') ) ? vgse_freemius()->pricing_url() : 'https://wpsheeteditor.com';

			$options = get_option($this->options_key);
			if (empty($options)) {
				$options = array(
					'last_tab' => null,
					'be_post_types' => array(
						'page',
						'post',
					),
					'be_posts_per_page' => 10,
					'be_load_items_on_scroll' => 1,
					'be_fix_first_columns' => 1,
					'be_posts_per_page_save' => 4,
					'be_timeout_between_batches' => 30,
					'be_disable_post_actions' => 0,
					'be_allow_edit_slugs' => 0,
				);
				update_option($this->options_key, $options);
			}

			$this->options = $options;

			do_action('vg_sheet_editor/initialized');

			// Set allowed post types
			$this->allowed_post_types = apply_filters('vg_sheet_editor/allowed_post_types', array('post' => 'Posts', 'page' => 'Pages'));

			$post_type = $options['be_post_types'];


			$this->support_links = apply_filters('vg_sheet_editor/support_links', array(
				array(
					'url' => 'http://wordpress.org/support/plugin/wp-sheet-editor',
					'label' => __('Support forum for the free plugin', VGSE()->textname),
				),
				array(
					'url' => 'https://wpsheeteditor.com/support/?utm_source=product&utm_medium=' . VGSE()->helpers->get_plugin_mode() . '&utm_campaign=help',
					'label' => __('Support forum for paying customers', VGSE()->textname),
				),
				array(
					'url' => 'https://wpsheeteditor.com/documentation/tutorials/?utm_source=product&utm_medium=' . VGSE()->helpers->get_plugin_mode() . '&utm_campaign=help',
					'label' => __('Tutorials', VGSE()->textname),
				),
				array(
					'url' => 'https://wpsheeteditor.com/documentation/faq/?utm_source=product&utm_medium=' . VGSE()->helpers->get_plugin_mode() . '&utm_campaign=help',
					'label' => __('FAQ', VGSE()->textname),
				),
				array(
					'url' => 'https://wpsheeteditor.com/category/help/?utm_source=product&utm_medium=' . VGSE()->helpers->get_plugin_mode() . '&utm_campaign=help',
					'label' => __('Articles', VGSE()->textname),
				),
				array(
					'url' => 'https://wpsheeteditor.com/documentation/developers/?utm_source=product&utm_medium=' . VGSE()->helpers->get_plugin_mode() . '&utm_campaign=help',
					'label' => __('Developers', VGSE()->textname),
				),
			));
			$this->plugin_url = plugins_url('/', __FILE__);
			$this->plugin_dir = __DIR__;

			$this->post_type = $post_type;

			// Hook to the filter before registering columns
			add_filter('vg_sheet_editor/columns/can_add_item', array($this, 'disallow_file_uploads_for_some_users'), 10, 3);

			if (!empty($post_type)) {
				$this->_register_columns();
				$this->_register_toolbar_items();
			}

			// Text used in the js files
			$this->texts = array(
				'posts_not_found' => __('Oops, nothing found', $this->textname),
				'use_other_image' => __('Use Another Image', $this->textname),
				'no_options_available' => __('No options available', $this->textname),
				'posts_loaded' => __('Items loaded in the spreadsheet', $this->textname),
				'new_rows_added' => __('New rows added', $this->textname),
				'formula_applied' => __('The formula has been executed. ¿Do you want to reload the page to see the changes?', $this->textname),
				'saving_stop_error' => __('<p>The changes were not saved completely. The process was canceled due to an error .</p><p>You can close this popup.</p>', $this->textname),
				'paged_batch_saved' => __('{updated} items saved of {total} items that need saving.', $this->textname),
				'everything_saved' => __('All items have been saved.', $this->textname),
				'save_changes_on_leave' => __('Please check if you have unsaved changes. If you have, please save them or they will be dismissed.', $this->textname),
				'no_changes_to_save' => __('We did not find changes to save. You either haven´t made changes or you changed some cells that auto save (the item content and images cells save automatically.).', $this->textname),
				'http_error_400' => __('The server did not accept our request. Bad request, please try refresh the page and try again.', $this->textname),
				'http_error_403' => __('The server didn´t accept our request. You don´t have permission to do this action. Please log in again.', $this->textname),
				'http_error_500_502_505' => __('The server is not available. Please try again later.', $this->textname),
				'http_error_try_now' => __('The server is not available. Do you want to try again?', $this->textname),
				'http_error_503' => __('The server wasn´t able to process our request. Server error. Please try again later.', $this->textname),
				'http_error_509' => __('The server has exceeded its allocated resources and is not able to process our request.', $this->textname),
				'http_error_504' => __('The server is busy and took too long to respond to our request. Please try again later.', $this->textname),
				'http_error_default' => __('The server could not process our request. Please try again later.', $this->textname),
			);

			// Init wp hooks
			add_action('admin_menu', array($this, 'register_menu'));
			add_action('admin_enqueue_scripts', array($this, 'register_scripts'), 999);
			add_action('admin_enqueue_scripts', array($this, 'register_styles'));
			add_action('admin_enqueue_scripts', array($this, 'enqueue_media_wp_media'));
			add_action('admin_init', array($this, 'redirect_to_welcome_page'));
			add_action('admin_init', array($this, 'redirect_to_whats_new_page'));
			add_action('wp_dashboard_setup', array($this, 'register_dashboard_widgets'));

// Ajax actions
			add_action('wp_ajax_bep_load_data', array($this, 'load_rows'));
			add_action('wp_ajax_bep_save_data', array($this, 'save_rows'));
			add_action('wp_ajax_vgse_find_post_by_name', array($this, 'find_post_by_name'));
			add_action('wp_ajax_save_individual_post', array($this, 'save_single_post_data'));
			add_action('wp_ajax_insert_individual_post', array($this, 'insert_individual_post'));
			add_action('wp_ajax_set_featured_image', array($this, 'set_featured_image'));
			add_action('wp_ajax_get_image_preview', array($this, 'get_image_preview'));
			add_action('wp_ajax_set_featured_gallery', array($this, 'set_featured_gallery'));
			add_action('wp_ajax_get_gallery_preview', array($this, 'get_gallery_preview'));
			add_action('wp_ajax_get_wp_post_single_data', array($this, 'get_wp_post_single_data'));
			add_action('wp_ajax_be_search_taxonomy_terms', array($this, 'search_taxonomy_terms'));
			add_action('wp_ajax_vg_save_post_types_setting', array($this, 'save_post_types_setting'));
			add_action('wp_ajax_vg_disable_quick_setup', array($this, 'disable_quick_setup'));

			// Internal hooks
			add_action('vg_sheet_editor/editor_page/after_content', array($this, 'render_support_modal'));
			add_action('vg_sheet_editor/editor_page/after_content', array($this, 'render_extensions_modal'));
			add_filter('vg_sheet_editor/load_rows/output', array($this, 'add_lock_to_readonly_cells'), 10, 2);

			load_plugin_textdomain($this->textname, false, basename(dirname(__FILE__)) . '/lang/');
		}

		function disallow_file_uploads_for_some_users($allowed, $key, $args) {

			if (in_array($args['type'], array('boton_gallery', 'boton_gallery_multiple')) && !current_user_can('upload_files')) {
				return false;
			}
			return $allowed;
		}

		/**
		 * Find posts by name
		 */
		function find_post_by_name() {
			$data = VGSE()->helpers->clean_data($_REQUEST);
			$nonce = $data['nonce'];

			if (!wp_verify_nonce($nonce, 'bep-nonce')) {
				wp_send_json_error(array('message' => __('Request not allowed. Try again later.', $this->textname)));
			}

			$post_type = (!empty($data['post_type']) ) ? sanitize_text_field($data['post_type']) : false;
			$search = (!empty($data['search']) ) ? sanitize_text_field($data['search']) : false;

			if (empty($post_type) || empty($search)) {
				wp_send_json_error(array('message' => __('Missing parameters.', $this->textname)));
			}

			$query_args = array(
				'post_type' => explode(',', $post_type),
				's' => $search,
				'posts_per_page' => -1,
			);
			$posts_found = new WP_Query($query_args);

			if (!$posts_found->have_posts()) {
				wp_send_json_error(array('message' => __('No items found.', $this->textname)));
			}

			$out = array();
			foreach ($posts_found->posts as $post) {
				$out[] = array(
					'id' => $post->post_type . '--' . $post->ID,
					'text' => $post->post_title . ' ( ID: ' . $post->ID . ', ' . $post->post_type . ' )',
				);
			}
			wp_send_json_success(array('data' => $out));
		}

		function add_lock_to_readonly_cells($data, $wp_query) {

			$column_keys = array(
				'modified',
				'ID',
			);

			if (!VGSE()->options['be_allow_edit_slugs']) {
				$column_keys[] = 'post_name';
			}
			foreach ($data as $post_index => $post_row) {
				foreach ($post_row as $column_key => $column_value) {

					if (!in_array($column_key, $column_keys)) {
						continue;
					}

					$cell_settings = $this->columns->get_item($column_key, $wp_query['post_type']);

					if ($cell_settings['allow_to_save'] || strpos($data[$post_index][$column_key], 'vg-cell-blocked') !== false) {
						continue;
					}
					$data[$post_index][$column_key] = '<i class="fa fa-lock vg-cell-blocked"></i> ' . $column_value;
				}
			}
			return $data;
		}

		/**
		 * Register dashboard widgets.
		 * Currently the only widget is "Editions stats".
		 */
		function register_dashboard_widgets() {
			add_meta_box('vg_sheet_editor_usage_stats', __('WP Sheet Editor Usage', VGSE()->textname), array($this, 'render_usage_stats_widget'), 'dashboard', 'normal', 'high');
		}

		function render_usage_stats_widget() {
			require 'views/usage-stats-widget.php';
		}

		/**
		 * Redirect to welcome page after plugin activation
		 */
		function redirect_to_welcome_page() {
			// Bail if no activation redirect
			$flag_key = 'vgse_welcome_redirect';
			$flag = get_option($flag_key, '');

			if ($flag === 'no') {
				return;
			}
			update_option($flag_key, 'no');

			// Bail if activating from network, or bulk			
			if (is_network_admin() || isset($_GET['activate-multi'])) {
				return;
			}

			wp_redirect(add_query_arg(array('page' => 'vg_sheet_editor_setup'), admin_url('admin.php')));
			exit();
		}

		/**
		 * Redirect to "whats new" page after plugin update
		 */
		function redirect_to_whats_new_page() {

			// bail if settings are empty = fresh install
			if (empty(VGSE()->options)) {
				return;
			}

			// bail if there aren´t new features for this release			
			if (!file_exists(VGSE_DIR . '/views/whats-new/' . VGSE()->version . '.php')) {
				return;
			}

			// exit if the page was already showed
			if (get_option('vgse_hide_whats_new_' . VGSE()->version)) {
				return;
			}

			// Delete the redirect transient
			update_option('vgse_hide_whats_new_' . VGSE()->version, 'yes');

			// Bail if activating from network, or bulk
			if (is_network_admin() || isset($_GET['activate-multi'])) {
				return;
			}

			wp_redirect(add_query_arg(array('page' => 'vg_sheet_editor_whats_new'), admin_url('admin.php')));
			exit();
		}

		/**
		 * Disable quick setup screen. It will show "quick usage screen" instead.
		 */
		function disable_quick_setup() {
			$data = VGSE()->helpers->clean_data($_REQUEST);

			$nonce = $data['nonce'];

			if (!wp_verify_nonce($nonce, 'bep-nonce')) {
				wp_send_json_error();
			}
			update_option('vgse_disable_quick_setup', true);

			wp_send_json_success();
		}

		/**
		 * Enable the spreadsheet editor on some post types
		 */
		function save_post_types_setting() {
			$data = VGSE()->helpers->clean_data($_REQUEST);

			$post_types = $data['post_types'];
			$nonce = $data['nonce'];

			if (!empty($post_types) && wp_verify_nonce($nonce, 'bep-nonce')) {
				$settings = get_option(VGSE()->options_key, array());

				$settings['be_post_types'] = $post_types;

				update_option(VGSE()->options_key, $settings);

				wp_send_json_success();
			} else {
				wp_send_json_error();
			}
		}

		function render_support_modal($post_type) {
			require 'views/support-modal.php';
		}

		function render_extensions_modal($post_type) {
			require 'views/extensions-modal.php';
		}

		/**
		 * Controller for saving individual field of post
		 */
		function save_single_post_data() {
			if (!wp_verify_nonce($_REQUEST['nonce'], 'bep-nonce')) {
				wp_send_json_error(array('message' => __('You dont have enough permissions to view this page.', $this->textname)));
			}
			$_REQUEST = VGSE()->helpers->clean_data($_REQUEST);
			$content = html_entity_decode($_REQUEST['content']);
			$id = (int) $_REQUEST['post_id'];
			$key = $_REQUEST['key'];
			$type = $_REQUEST['type'];


			if (VGSE()->options['be_disable_post_actions']) {
				$post_type = get_post_type($id);
				VGSE()->helpers->remove_all_post_actions($post_type);
			}

			do_action('vg_sheet_editor/save_single_post_data/before', $id, $content, $key, $type);
			$result = $this->data_helpers->save_single_post_data($id, $content, $key, $type);



			do_action('vg_sheet_editor/save_single_post_data/after', $result, $id, $content, $key, $type);
			if (is_wp_error($result)) {

				$errors = $result->get_error_messages();
				wp_send_json_success(array('message' => sprintf(__('Error: %s', $this->textname), implode(', ', $errors))));
			} else {
				VGSE()->helpers->increase_counter('editions');
				VGSE()->helpers->increase_counter('processed');

				$title = get_the_title($id);
				wp_send_json_success(array('message' => sprintf(__('Saved: %s', $this->textname), $title)));
			}
		}

		/**
		 * Save images cells, multiple images
		 */
		function set_featured_gallery() {
			if (!wp_verify_nonce($_REQUEST['nonce'], 'bep-nonce')) {
				wp_send_json_error(array('message' => __('You dont have enough permissions to view this page.', $this->textname)));
			}
			$data = VGSE()->helpers->clean_data($_REQUEST);

			$result = $this->_save_images(array(
				'post_id' => $data['post_id'], // int
				'id' => $data['ids'], // str
				'key' => $data['key'], // str
			));
			if (!$result) {
				wp_send_json_error(array('message' => __('The image could not be saved. Try again later.', $this->textname)));
			}

			wp_send_json_success(array('message' => __('Image saved.', $this->textname)));
		}

		/**
		 * Save image cell, single image
		 */
		function set_featured_image() {
			if (!wp_verify_nonce($_REQUEST['nonce'], 'bep-nonce')) {
				wp_send_json_error(array('message' => __('You dont have enough permissions to view this page.', $this->textname)));
			}
			$data = VGSE()->helpers->clean_data($_REQUEST);

			$result = $this->_save_images(array(
				'post_id' => $data['post_id'], // int
				'id' => $data['id'], // str
				'key' => $data['key'], // str
			));
			if (!$result) {
				wp_send_json_error(array('message' => __('The image could not be saved. Try again later.', $this->textname)));
			}

			wp_send_json_success(array('message' => __('Image saved.', $this->textname)));
		}

		/**
		 * Save images to WP gallery
		 * @param array $args
		 * @return boolean
		 */
		function _save_images($args = array()) {
			$defaults = array(
				'post_id' => null, // int
				'id' => null, // str
				'key' => null, // str
			);

			$data = VGSE()->helpers->clean_data(wp_parse_args($args, $defaults));

			if (empty($data['id']) || empty($data['post_id']) || empty($data['key'])) {
				return false;
			}

			$post_id = (int) $data['post_id'];

			$attachment_ids = VGSE()->helpers->maybe_replace_urls_with_file_ids(explode(',', $data['id']), $post_id);

			if (empty($attachment_ids)) {
				return false;
			}

			$attachment_id = implode(',', $attachment_ids);
			$key = $data['key'];

			VGSE()->helpers->increase_counter('editions', count($attachment_ids));
			VGSE()->helpers->increase_counter('processed');

			update_post_meta($post_id, $key, $attachment_id);
			return true;
		}

		/**
		 * Search taxonomy term
		 * @global obj $wpdb
		 */
		function search_taxonomy_terms() {
			$post_type = (!empty($_REQUEST['post_type']) ) ? sanitize_text_field($_REQUEST['post_type']) : false;
			$search = (!empty($_REQUEST['search']) ) ? sanitize_text_field($_REQUEST['search']) : false;

			if (empty($post_type) || empty($search)) {
				wp_send_json_error(array('message' => __('Missing parameters.', $this->textname)));
			}

			$taxonomies = VGSE()->helpers->get_post_type_taxonomies_single_data($post_type, 'name');

			if (empty($taxonomies)) {
				wp_send_json_error(array('message' => __('No taxonomies found.', $this->textname)));
			}
			global $wpdb;

			$results = $wpdb->get_results("SELECT term.slug id,term.name text,tax.taxonomy taxonomy FROM $wpdb->term_taxonomy as tax JOIN $wpdb->terms as term ON term.term_id = tax.term_id WHERE tax.taxonomy IN ('" . implode("','", $taxonomies) . "') AND tax.count > 0 AND term.name LIKE '%" . esc_sql($search) . "%' ", ARRAY_A);

			if (!$results || is_wp_error($results)) {
				$results = array();
			}

			$output_format = ( isset($_REQUEST['output_format'])) ? $_REQUEST['output_format'] : '';
			if (empty($output_format)) {
				$output_format = '%taxonomy%--%slug%';
			} else {
				$output_format = sanitize_text_field($output_format);
			}
			$taxonomies_labels = array();
			$out = array();
			foreach ($results as $result) {

				if (!isset($taxonomies_labels[$result['taxonomy']])) {
					$tmp_tax = get_taxonomy($result['taxonomy']);
					$taxonomies_labels[$result['taxonomy']] = $tmp_tax->label;
				}

				$output_key = strtr($output_format, array(
					'%name%' => $result['text'],
					'%taxonomy%' => $result['taxonomy'],
					'%slug%' => $result['id'],
				));
				$out[] = array(
					'id' => $output_key,
					'text' => $result['text'] . ' ( ' . $taxonomies_labels[$result['taxonomy']] . ' )',
				);
			}
			wp_send_json_success(array('data' => $out));
		}

		/**
		 * Register admin pages
		 */
		function register_menu() {
			add_menu_page(__('WP Sheet Editor', $this->textname), __('WP Sheet Editor', $this->textname), 'manage_options', 'vg_sheet_editor_setup', array($this, 'render_quick_setup_page'), VGSE()->plugin_url . 'assets/imgs/icon-20x20.png');

			add_submenu_page(null, __('Sheet Editor', $this->textname), __('Sheet Editor', $this->textname), 'manage_options', 'vg_sheet_editor_whats_new', array($this, 'render_whats_new_page'));

			if (empty($this->post_type)) {
				return;
			}
			if (!is_array($this->post_type)) {
				$this->post_type = array($this->post_type);
			}


			foreach ($this->post_type as $post_type) {
//				VGSE()->helpers->dd( $post_type );
				if (VGSE()->helpers->is_post_type_allowed($post_type)) {
					$page_slug = 'bulk-edit-' . $post_type;
					$post_type_label = $this->helpers->get_post_type_label($post_type);
					add_submenu_page('vg_sheet_editor_setup', __('Sheet Editor', $this->textname), __('Edit ' . $post_type_label, $this->textname), 'edit_posts', $page_slug, array($this, 'render_editor_page'));

					if ($post_type === 'post') {
						add_submenu_page('edit.php', __('Sheet Editor', $this->textname), __('Sheet Editor', $this->textname), 'edit_posts', $page_slug, array($this, 'render_editor_page'));
					} elseif ($post_type === 'attachment') {
						add_submenu_page('upload.php', __('Sheet Editor', $this->textname), __('Sheet Editor', $this->textname), 'edit_others_posts', $page_slug, array($this, 'render_editor_page'));
					} else {
						add_submenu_page('edit.php?post_type=' . $post_type, __('Sheet Editor', $this->textname), __('Sheet Editor', $this->textname), 'edit_others_posts', $page_slug, array($this, 'render_editor_page'));
					}
				}
			}
		}

		/**
		 * Render editor page
		 */
		function render_editor_page() {
			if (!current_user_can('edit_posts')) {
				wp_die(__('You dont have enough permissions to view this page.', $this->textname));
			}

			require 'views/editor-page.php';
		}

		/**
		 * Render quick setup page
		 */
		function render_quick_setup_page() {
			if (!current_user_can('manage_options')) {
				wp_die(__('You dont have enough permissions to view this page.', $this->textname));
			}

			require 'views/quick-setup.php';
		}

		/**
		 * Render "whats new" page
		 */
		function render_whats_new_page() {
			if (!current_user_can('manage_options')) {
				wp_die(__('You dont have enough permissions to view this page.', $this->textname));
			}

			require 'views/whats-new.php';
		}

		/*
		 * Register js scripts
		 */

		function register_scripts() {
			$current_post = VGSE()->helpers->get_post_type_from_query_string();

			if (!VGSE()->helpers->is_post_type_allowed($current_post)) {
				return;
			}

			$pages_to_load_assets = $this->frontend_assets_allowed_on_pages();
			if (empty($_GET['page']) ||
					!in_array($_GET['page'], $pages_to_load_assets)) {
				return;
			}

			$this->_register_scripts($current_post);
		}

		function _register_scripts($current_post = null, $spreadsheet_columns = null) {
			if (!$spreadsheet_columns) {
				$spreadsheet_columns = VGSE()->columns->get_post_type_items($current_post, true);
			}

			if (VGSE_DEBUG) {
				wp_enqueue_script('select2_js', $this->plugin_url . 'assets/vendor/select2/dist/js/select2.min.js', array('jquery'), $this->version, false);
				wp_enqueue_script('tipso_js', $this->plugin_url . 'assets/vendor/tipso/src/tipso.min.js', array('jquery'), $this->version, false);
				wp_enqueue_script('modal_js', $this->plugin_url . 'assets/vendor/remodal/dist/remodal.min.js', array('jquery'), $this->version, false);
				wp_enqueue_script('labelauty', $this->plugin_url . 'assets/vendor/labelauty/source/jquery-labelauty.js', array('jquery'), $this->version, false);

				wp_enqueue_script('notifications_js', $this->plugin_url . 'assets/vendor/oh-snap/ohsnap.js', array('jquery'), $this->version, false);
				wp_enqueue_script('handsontable_js', $this->plugin_url . 'assets/vendor/handsontable/dist/handsontable.full.js', array(), $this->version, false);


				wp_enqueue_script('text_editor_js', $this->plugin_url . 'assets/vendor/jqueryte/dist/jquery-te-1.4.0.min.js', array(), $this->version, false);
				wp_enqueue_script('bep_nanobar', $this->plugin_url . 'assets/vendor/nanobar/nanobar.js', array(), $this->version, false);

				wp_enqueue_script('bep_global', $this->plugin_url . 'assets/js/global.js', array(), $this->version, false);

				wp_enqueue_script('bep_init_js', $this->plugin_url . 'assets/js/init.js', array('handsontable_js'), $this->version, false);
				wp_enqueue_script('bep_post-status-plugin_js', $this->plugin_url . 'assets/js/post-status-plugin.js', array('bep_init_js'), $this->version, false);
				$localize_handle = 'bep_global';
			} else {

				wp_enqueue_script('bep_libraries_js', $this->plugin_url . 'assets/vendor/js/libraries.min.js', array(), $this->version, false);
				wp_enqueue_script('bep_init_js', $this->plugin_url . 'assets/js/scripts.min.js', array('bep_libraries_js'), $this->version, false);
				$localize_handle = 'bep_init_js';
			}
			$columns = array();
			$titles = array();
			$columsFormat = array();
			$columsUnformat = array();
			if (is_array($spreadsheet_columns)) {
				foreach ($spreadsheet_columns as $column_key => $item) {
					$columns[$column_key] = $item['colum_width'];
					$titles[$column_key] = $item['title'];
					$columsFormat[] = $item['formated'];
					$columsUnformat[] = $item['unformated'];
				}
			}

			wp_localize_script($localize_handle, 'vgse_sheet_settings', apply_filters('vg_sheet_editor/js_data/localize', array(
				'startRows' => 0,
				'startCols' => !empty($spreadsheet_columns) ? count($spreadsheet_columns) : 0,
				'colWidths' => !empty($columns) ? array_map('intval', $columns) : array(),
				'colHeaders' => $titles,
				'columnsUnformat' => ($columsUnformat),
				'columnsFormat' => ($columsFormat),
				'total_posts' => (!empty($this->post_type) ) ? VGSE()->data_helpers->total_posts($this->post_type[array_search($current_post, $this->post_type)]) : 0,
				'posts_per_page' => (!empty($this->options) && !empty($this->options['be_posts_per_page']) ) ? (int) $this->options['be_posts_per_page'] : 10,
				'save_posts_per_page' => (!empty($this->options) && !empty($this->options['be_posts_per_page_save']) ) ? (int) $this->options['be_posts_per_page_save'] : 4,
				'texts' => $this->texts,
				'wait_between_batches' => (!empty($this->options) && !empty($this->options['be_timeout_between_batches']) ) ? (int) $this->options['be_timeout_between_batches'] : 30,
				'custom_handsontable_args' => json_encode(apply_filters('vg_sheet_editor/handsontable/custom_args', array(
						'fixedColumnsLeft' => (!empty($this->options['be_fix_first_columns']) ) ? 2 : false,
								), $current_post), JSON_FORCE_OBJECT),
							), $current_post));
		}

		/**
		 * Get pages allowed to load frontend assets.
		 * @return array
		 */
		function frontend_assets_allowed_on_pages() {

			$allowed_pages = array();
			foreach ($this->allowed_post_types as $post_type_key => $post_type) {
				$allowed_pages[] = 'bulk-edit-' . $post_type_key;
			}
			$allowed_pages[] = 'vg_sheet_editor_setup';
			$allowed_pages[] = 'vg_sheet_editor_whats_new';
			$allowed_pages = apply_filters('vg_sheet_editor/scripts/pages_allowed', $allowed_pages);

			return $allowed_pages;
		}

		/**
		 * Register CSS files.
		 */
		function register_styles() {
			$current_post = VGSE()->helpers->get_post_type_from_query_string();

			if (!VGSE()->helpers->is_post_type_allowed($current_post)) {
				return;
			}

			$pages_to_load_assets = $this->frontend_assets_allowed_on_pages();
			if (empty($_GET['page']) ||
					!in_array($_GET['page'], $pages_to_load_assets)) {
				return;
			}

			$this->_register_styles();
		}

		function _register_styles() {
			if (VGSE_DEBUG) {
				wp_enqueue_style('fontawesome', $this->plugin_url . 'assets/vendor/font-awesome/css/font-awesome.min.css', '', $this->version, 'all');
				wp_enqueue_style('select2_styles', $this->plugin_url . 'assets/vendor/select2/dist/css/select2.min.css', '', $this->version, 'all');
				wp_enqueue_style('tipso_styles', $this->plugin_url . 'assets/vendor/tipso/src/tipso.min.css', '', $this->version, 'all');
				wp_enqueue_style('labelauty_styles', $this->plugin_url . 'assets/vendor/labelauty/source/jquery-labelauty.css', '', $this->version, 'all');
				wp_enqueue_style('handsontable_css', $this->plugin_url . 'assets/vendor/handsontable/dist/handsontable.full.css', '', $this->version, 'all');

				wp_enqueue_style('text_editor_css', $this->plugin_url . 'assets/vendor/jqueryte/dist/jquery-te-1.4.0.css', '', $this->version, 'all');
				wp_enqueue_style('plugin_css', $this->plugin_url . 'assets/css/style.css', '', $this->version, 'all');
				wp_enqueue_style('loading_anim_css', $this->plugin_url . 'assets/css/loading-animation.css', '', $this->version, 'all');
				wp_enqueue_style('modal_css', $this->plugin_url . 'assets/vendor/remodal/dist/remodal.css', '', $this->version, 'all');
				wp_enqueue_style('modal_theme_css', $this->plugin_url . 'assets/vendor/remodal/dist/remodal-default-theme.css', '', $this->version, 'all');
			} else {
				wp_enqueue_style('bep_libraries_css', $this->plugin_url . 'assets/vendor/css/libraries.min.css', '', $this->version, 'all');
				wp_enqueue_style('plugin_css', $this->plugin_url . 'assets/css/styles.min.css', '', $this->version, 'all');
			}
			$css_src = includes_url('css/') . 'editor.css';
			wp_enqueue_style('tinymce_css', $css_src, '', $this->version, 'all');
		}

		/*
		 * Controller for loading posts to the spreadsheet
		 */

		function load_rows($settings = array()) {

			if (!$settings) {
				$settings = $_REQUEST;
			}
			if (!wp_verify_nonce($settings['nonce'], 'bep-nonce')) {
				$message = array('message' => __('You dont have enough permissions to view this page.', $this->textname));
				if (!empty($settings['output']) && $settings['output'] === 'silent') {
					return new WP_Error($message);
				} else {
					wp_send_json_error($message);
				}
			}

			global $wpdb;
//			Start Profiling to debug mysql queries and execution time.
//			vgse_start_load_rows_profiling();

			$incoming_data = apply_filters('vg_sheet_editor/load_rows/raw_incoming_data', $settings);
			$clean_data = apply_filters('vg_sheet_editor/load_rows/sanitized_incoming_data', VGSE()->helpers->clean_data($incoming_data));

			$post_statuses = get_post_statuses();
			$post_statuses_keys = array_keys($post_statuses);
			$args = array(
				'post_type' => $clean_data['post_type'],
				'posts_per_page' => (!empty($this->options) && !empty($this->options['be_posts_per_page']) ) ? (int) $this->options['be_posts_per_page'] : 10,
				'paged' => (isset($clean_data['paged']) ? (int) $clean_data['paged'] : 1)
			);


			$qry = array(
				'post_type' => $args['post_type'],
				'posts_per_page' => $args['posts_per_page'],
				'paged' => $args['paged'],
				'post_status' => $post_statuses_keys,
			);
			if (( $args['post_type'] === 'page' && !current_user_can('edit_others_pages') ) || ( $args['post_type'] !== 'page' && !current_user_can('edit_others_posts') )) {
				$qry['author'] = get_current_user_id();
			}

			if ($args['post_type'] === 'attachment') {
				$qry['post_status'] = array_merge($post_statuses_keys, array('inherit'));
			}



			// Exit if the user is not allowed to edit pages
			if ($args['post_type'] === 'page' && !current_user_can('edit_pages')) {
				$message = __('User not allowed to edit pages', VGSE()->textname);
				if (!empty($settings['output']) && $settings['output'] === 'silent') {
					return new WP_Error($message);
				} else {
					wp_send_json_error(array('message' => $message) );
				}
			}

			// Exclude published pages or posts if the user is not allowed to edit them
			if (( $args['post_type'] === 'page' && !current_user_can('edit_published_pages') ) || ( $args['post_type'] !== 'page' && !current_user_can('edit_published_posts') )) {
				if (!isset($qry['post_status'])) {
					$qry['post_status'] = $post_statuses_keys;
				}
				$qry['post_status'] = VGSE()->helpers->remove_array_item_by_value('publish', $qry['post_status']);
			}

			// Exclude private pages or posts if the user is not allowed to edit or read them
			if (( $args['post_type'] === 'page' && !current_user_can('read_private_pages') ) || ( $args['post_type'] !== 'page' && !current_user_can('read_private_posts') ) || ( $args['post_type'] === 'page' && !current_user_can('edit_private_pages') ) || ( $args['post_type'] !== 'page' && !current_user_can('edit_private_posts') )) {
				if (!isset($qry['post_status'])) {
					$qry['post_status'] = $post_statuses_keys;
				}
				$qry['post_status'] = VGSE()->helpers->remove_array_item_by_value('private', $qry['post_status']);
			}

			if (!empty($settings['wp_query_args'])) {
				$qry = wp_parse_args($settings['wp_query_args'], $qry);
			}
			$qry = apply_filters('vg_sheet_editor/load_rows/wp_query_args', $qry, $clean_data);
			$query = new WP_Query($qry);
			$data = array();

			if ($query->have_posts()) {

				$count = 0;
				$spreadsheet_columns = VGSE()->columns->get_post_type_items($clean_data['post_type'], true);

				$posts = apply_filters('vg_sheet_editor/load_rows/found_posts', $query->posts, $qry, $clean_data, $spreadsheet_columns);

				$data = apply_filters('vg_sheet_editor/load_rows/preload_data', $data, $posts, $qry, $clean_data, $spreadsheet_columns);

				foreach ($posts as $post) {

					$GLOBALS['post'] = & $post;
					setup_postdata($post);

					$post_id = $post->ID;


					if (!apply_filters('vg_sheet_editor/load_rows/can_edit_item', true, $post, $qry, $spreadsheet_columns)) {
						continue;
					}

					$data[$post_id]['post_type'] = $post->post_type;

					$allowed_columns_for_post = apply_filters('vg_sheet_editor/load_rows/allowed_post_columns', $spreadsheet_columns, $post, $qry);

					//Obtiene los datos de la tabla en base a los datos del array de init_vars()
					foreach ($allowed_columns_for_post as $item => $value) {

						if (isset($data[$post_id][$item])) {
							continue;
						}
						$item_custom_data = apply_filters('vg_sheet_editor/load_rows/get_cell_data', false, $post, $qry, $item, $value);

						if (!empty($item_custom_data)) {
							$data[$post_id][$item] = $item_custom_data;
							continue;
						}

						if (empty($value['type'])) {

							if ($value['data_type'] === 'post_data') {
								$data[$post_id][$item] = VGSE()->data_helpers->get_post_data($item, $post->ID);
							}
							if ($value['data_type'] === 'meta_data') {
								$data[$post_id][$item] = get_post_meta($post->ID, $item, true);
							}
							if ($value['data_type'] === 'post_terms') {
								$data[$post_id][$item] = VGSE()->data_helpers->get_post_terms($post->ID, $item);
							}
						} else {
							if ($value['type'] === 'boton_gallery') {
								$data[$post_id][$item] = VGSE()->helpers->get_gallery_cell_content($post->ID, $item, $value['data_type']);
							}
							if ($value['type'] === 'boton_gallery_multiple') {
								$data[$post_id][$item] = VGSE()->helpers->get_gallery_cell_content($post->ID, $item, $value['data_type'], true);
							}
							if ($value['type'] === 'boton_tiny') {
								$data[$post_id][$item] = VGSE()->helpers->get_tinymce_cell_content($post->ID, $item, $value['data_type']);
							}
							if ($value['type'] === 'inline_image') {
								$data[$post_id][$item] = $this->get_inline_image_html($post->ID, $item, $value['data_type']);
							}
							if ($value['type'] === 'handsontable') {
								$data[$post_id][$item] = $this->helpers->get_handsontable_cell_content($post->ID, $item, $value);
							}
						}

						// Use default value if the field is empty, or if the cell is a 
						// checkbox and the value is not in the checkbox values.
						if ((empty($data[$post_id][$item]) && !empty($value['default_value']) ) ||
								(!empty($data[$post_id][$item]) && !empty($value['formated']['type']) && $value['formated']['type'] === 'checkbox' && !in_array($data[$post_id][$item], array($value['formated']['checkedTemplate'], $value['formated']['uncheckedTemplate'])) )) {
							$data[$post_id][$item] = $value['default_value'];
						}
					}
					$count++;
				}
			} else {

				$message = array('message' => __('Posts not found.', $this->textname));
				if (!empty($settings['output']) && $settings['output'] === 'silent') {
					return new WP_Error($message);
				} else {
					wp_send_json_error($message);
				}
			}

			wp_reset_postdata();
			wp_reset_query();


//			End Profiling to debug mysql queries and execution time.
//			vgse_end_load_rows_profiling(__FUNCTION__);

			$data = apply_filters('vg_sheet_editor/load_rows/output', $data, $qry, $spreadsheet_columns, $clean_data);


			if (!empty($settings['output']) && $settings['output'] === 'silent') {
				return $data;
			} else {
				wp_send_json_success(array_values($data));
			}
		}

		/**
		 * Get image html
		 * @param int $post_id
		 * @param string $key
		 * @param string $data_source
		 * @return string
		 */
		function get_inline_image_html($post_id, $key, $data_source) {

			$out = '';
			if ($data_source === 'post_data') {
				$post = get_post($post_id);

				if (!empty($post->$key)) {
					$url = $post->$key;

					if (strpos($url, WP_CONTENT_URL) === false) {
						$image_url = $url;
					} else {
						$image_id = $this->helpers->get_attachment_id_from_url($url);
					}
				}
			} elseif ($data_source === 'meta_data') {
				$image_id = get_post_meta($post_id, $key, true);
			}

			if (empty($image_url) && !empty($image_id)) {

				$thumb_url_array = wp_get_attachment_image_src($image_id, array(100, 100), true);
				$image_url = $thumb_url_array[0];
			}

			if (!empty($image_url)) {
				$out = '<img src="' . $image_url . '" width="100px" height="100px" />';
			}
			return $out;
		}

		/*
		 * Controller for saving posts changes
		 */

		function save_rows() {
			if (!wp_verify_nonce($_REQUEST['nonce'], 'bep-nonce')) {
				wp_send_json_error(array('message' => __('You dont have enough permissions to view this page.', $this->textname)));
			}

			global $wpdb;
//			Start Profiling to debug mysql queries and execution time.
//			vgse_start_save_rows_profiling();

			$data = apply_filters('vg_sheet_editor/save_rows/incoming_data', $_REQUEST['data'], $_REQUEST);
			$post_type = sanitize_text_field($_REQUEST['post_type']);
			$spreadsheet_columns = VGSE()->columns->get_post_type_items($post_type);


			if (VGSE()->options['be_disable_post_actions']) {
				VGSE()->helpers->remove_all_post_actions($post_type);
			}

			do_action('vg_sheet_editor/save_rows/before_saving_rows', $data, $post_type, $spreadsheet_columns);

			$editions_count = 0;


			// Suspend cache invalidation to reduce mysql queries during saving
			wp_suspend_cache_invalidation();

			foreach ($data as $row_index => $item) {
				if (is_string($item['ID'])) {
					$post_id = (int) trim(wp_strip_all_tags($item['ID']));
				} elseif (is_int($item['ID'])) {
					$post_id = $item['ID'];
				}

				if (empty($post_id)) {
					continue;
				}
				$my_post = array();

				//Guarda los datos de la tabla en base a los datos del array de init_vars()
				foreach ($spreadsheet_columns as $key => $value) {

					if (!isset($item[$key])) {
						continue;
					}
					do_action('vg_sheet_editor/save_rows/before_saving_cell', $item, $post_type, $value, $key, $spreadsheet_columns);
					if (!$value['allow_to_save']) {
						continue;
					}
					if ($value['data_type'] === 'post_data' && empty($value['type'])) {
						if ($key === 'ID' || $key === 'comment_status') {
							$my_post[$key] = $this->data_helpers->set_post($key, $item[$key], $post_id);
						} else {
							$final_key = $key;
							if (strpos($final_key, 'post_') === false) {
								$final_key = 'post_' . $final_key;
							}
							$my_post[$final_key] = $this->data_helpers->set_post($key, $item[$key], $post_id);
						}
					}
					// @todo Encontrar forma de sanitizar
					if ($value['data_type'] === 'meta_data' || $value['data_type'] === 'post_meta') {
						if (empty($value['type'])) {
							$result = update_post_meta($post_id, $key, $item[$key]);
						} elseif (in_array($value['type'], array('boton_gallery', 'boton_gallery_multiple'))) {

							$result = $this->_save_images(array(
								'post_id' => $post_id, // int
								'id' => $item[$key], // str
								'key' => $key, // str
							));
						}


						if ($result) {
							$editions_count++;
						}
					}
					if ($value['data_type'] === 'post_terms') {

						$terms_saved = VGSE()->data_helpers->prepare_post_terms_for_saving($item[$key], $key);
						wp_set_object_terms($post_id, $terms_saved, $key);
					}

					$new_value = $item[$key];
					$post_id = $post_id;
					$cell_args = $value;
					do_action('vg_sheet_editor/save_rows/after_saving_cell', $post_type, $post_id, $key, $new_value, $cell_args, $spreadsheet_columns);
				}

				if (!empty($my_post)) {
					if (empty($my_post['ID'])) {
						$my_post['ID'] = $post_id;
					}
					if (!empty($my_post['post_title'])) {
						$my_post['post_title'] = wp_strip_all_tags($my_post['post_title']);
					}
					if (!empty($my_post['post_date'])) {
						$my_post['post_date_gmt'] = get_gmt_from_date($my_post['post_date']);
						$my_post['edit_date'] = true;
					}

					$original_post = get_post($my_post['ID'], ARRAY_A);

					// count how many fields were modified
					foreach ($original_post as $key => $original_value) {
						if (isset($my_post[$key]) && $my_post[$key] !== $original_value) {
							$editions_count++;
						}
					}

					$post_id = wp_update_post($my_post, true);
				}
			}
			do_action('vg_sheet_editor/save_rows/after_saving_rows', $data, $post_type, $spreadsheet_columns, $_REQUEST);


			// Enable cache invalidation to its original state
			wp_suspend_cache_invalidation(false);

			VGSE()->helpers->increase_counter('editions', $editions_count);
			VGSE()->helpers->increase_counter('processed', count($data));

//			Finish Profiling to debug mysql queries and execution time.
//			vgse_end_save_rows_profiling( __FUNCTION__ );

			wp_send_json_success(array('message' => __('Changes saved successfully', $this->textname)));
		}

		/*
		 * Controller for saving new post.
		 */

		function insert_individual_post() {
			if (!wp_verify_nonce($_REQUEST['nonce'], 'bep-nonce')) {
				wp_send_json_error(array('message' => __('You dont have enough permissions to view this page.', $this->textname)));
			}
			$_REQUEST = VGSE()->helpers->clean_data($_REQUEST);
			$post_type = $_REQUEST['post_type'];
			$rows = (int) $_REQUEST['rows'];
			$data = array();
			$spreadsheet_columns = VGSE()->columns->get_post_type_items($post_type);

			if (VGSE()->options['be_disable_post_actions']) {
				VGSE()->helpers->remove_all_post_actions($post_type);
			}

			$new_posts_ids = apply_filters('vg_sheet_editor/add_new_posts/create_new_posts', array(), $post_type, $rows, $spreadsheet_columns);


			if (empty($new_posts_ids)) {

				for ($i = 0; $i < $rows; $i++) {
					$my_post = array(
						'post_title' => __('Add title here', $this->textname),
						'post_type' => $post_type,
						'post_content' => ' ',
						'post_status' => 'draft',
						'post_author' => get_current_user_id(),
					);

					$my_post = apply_filters('vg_sheet_editor/add_new_posts/post_data', $my_post);
					$post_id = wp_insert_post($my_post);

					if (!$post_id || is_wp_error($post_id)) {
						wp_send_json_error(array('message' => __('The item could not be saved. Please try again in other moment.', $this->textname)));
					}

					do_action('vg_sheet_editor/add_new_posts/after', $post_id, $post_type, $rows, $spreadsheet_columns);

					$new_posts_ids[] = $post_id;
				}
			}

			if (!empty($new_posts_ids)) {
				$data = VGSE()->load_rows(array(
					'nonce' => $_REQUEST['nonce'],
					'post_type' => $post_type,
					'wp_query_args' => array(
						'post__in' => $new_posts_ids,
					),
					'output' => 'silent',
					'filters' => '&wc_display_variations=yes'
						));
				VGSE()->helpers->increase_counter('editions', count($new_posts_ids));
				VGSE()->helpers->increase_counter('processed', count($new_posts_ids));
			}

			$out = apply_filters('vg_sheet_editor/add_new_posts/output', $data, $post_type, $spreadsheet_columns);
			wp_send_json_success(array('message' => array_values($out)));
		}

		/*
		 * Enqueue wp media scripts on editor page
		 */

		function enqueue_media_wp_media() {
			$current_post = VGSE()->helpers->get_post_type_from_query_string();

			if (!VGSE()->helpers->is_post_type_allowed($current_post)) {
				return;
			}

			$pages_to_load_assets = $this->frontend_assets_allowed_on_pages();
			if (empty($_GET['page']) ||
					!in_array($_GET['page'], $pages_to_load_assets)) {
				return;
			}
			wp_enqueue_media();
		}

		/*
		 * Get image preview html
		 */

		function get_image_preview() {
			if (!wp_verify_nonce($_REQUEST['nonce'], 'bep-nonce')) {
				wp_send_json_error(array('message' => __('You dont have enough permissions to view this page.', $this->textname)));
			}
			$_REQUEST = VGSE()->helpers->clean_data($_REQUEST);
			$post_id = (int) $_REQUEST['id'];
			$key = $_REQUEST['key'];
			$imgid = $_REQUEST['localValue'];
			
			if(empty($imgid)){
			$imgid = get_post_meta($post_id, $key, true);
			}

			$img = wp_get_attachment_image_src($imgid, 'full');

			if (!$img) {
				$out = '<div><p>' . __('Image not found', VGSE()->textname) . '</p></div>';
			} else {
				$out = '<div><img src="' . $img[0] . '" width="425px" /></div>';
			}
			wp_send_json_success(array('message' => $out));
		}

		/*
		 * Get gallery preview html
		 */

		function get_gallery_preview() {
			if (!wp_verify_nonce($_REQUEST['nonce'], 'bep-nonce')) {
				wp_send_json_error(array('message' => __('You dont have enough permissions to view this page.', $this->textname)));
			}
			$_REQUEST = VGSE()->helpers->clean_data($_REQUEST);
			$post_id = (int) $_REQUEST['id'];
			$key = $_REQUEST['key'];

			$imgids = $_REQUEST['localValue'];
			
			if(empty($imgids)){
			$imgids = get_post_meta($post_id, $key, true);
			}

			$imgs = explode(',', $imgids);

			$out = '<ul class="be-gallery">';
			foreach ($imgs as $img) {
				$image = wp_get_attachment_image_src($img, 'full');
				$out .= '<li><img src="' . $image[0] . '" width="225px" /></li>';
			}
			$out .= '</ul>';
			wp_send_json_success(array('message' => $out));
		}

		/*
		 * Get tinymce editor content
		 */

		function get_wp_post_single_data() {
			if (!wp_verify_nonce($_REQUEST['nonce'], 'bep-nonce')) {
				wp_send_json_error(array('message' => __('You dont have enough permissions to view this page.', $this->textname)));
			}
			$_REQUEST = VGSE()->helpers->clean_data($_REQUEST);

			$post_id = (int) $_REQUEST['pid'];
			$key = $_REQUEST['key'];
			$type = $_REQUEST['type'];
			$raw = (!empty($_REQUEST['raw']) ) ? $_REQUEST['raw'] : false;

			if ($type === 'post_data') {
				$content = VGSE()->data_helpers->get_post_data($key, $post_id);
				if ($raw) {
					$out = $content;
				} else {
					$out = html_entity_decode(htmlspecialchars_decode($content));
				}
			} elseif ($type === 'meta_data' || $type === 'post_meta') {
				$content = get_post_meta($post_id, $key, true);
				if ($raw) {
					$out = $content;
				} else {
					$out = html_entity_decode(htmlspecialchars_decode($content));
				}
			}


			wp_send_json_success(array('message' => $out));
		}

	}

}

if (!function_exists('VGSE')) {

	function VGSE() {
		return WP_Sheet_Editor::get_instance();
	}

}

add_action('wp_loaded', 'VGSE', 999);

