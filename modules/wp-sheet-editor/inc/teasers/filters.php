<?php
if (!class_exists('WP_Sheet_Editor_Filters_Teaser')) {

	/**
	 * Display filters item in the toolbar to tease users of the free 
	 * version into purchasing the premium plugin.
	 */
	class WP_Sheet_Editor_Filters_Teaser {

		static private $instance = false;

		private function __construct() {
			
		}

		function init() {

			if(class_exists('WP_Sheet_Editor_Filters')){
				return;
			}
			add_action('vg_sheet_editor/toolbar/core_items_registered', array($this, 'register_toolbar_items'));
			add_action('vg_sheet_editor/editor_page/after_content', array($this, 'render_filters_form'));
		}

		function register_toolbar_items() {

			$post_types = VGSE()->post_type;
			if (empty($post_types)) {
				return;
			}
			if (!is_array($post_types)) {
				$post_types = array($post_types);
			}
			foreach ($post_types as $post_type) {
				VGSE()->toolbar->register_item('run_filters', array(
					'type' => 'button',
					'content' => __('Search by keyword, category, status, author', VGSE()->textname),
					'icon' => 'fa fa-filter',
					'extra_html_attributes' => 'data-remodal-target="modal-filters"',
					'toolbar_key' => 'secondary',
						), $post_type);
			}
		}

		function render_filters_form($current_post_type) {
			?>

			<style>
				.vg-naked-list {	
					list-style: initial;
					text-align: left;
					margin-left: 30px;
				}
			</style>
			<div class="remodal remodal8" data-remodal-id="modal-filters" data-remodal-options="closeOnOutsideClick: false, hashTracking: false">

				<div class="modal-content">
					<h3><?php _e('Search in the spreadsheet', VGSE()->textname); ?></h3>
					<p><?php _e('The search allows you to filter the spreadsheet to display the items that you want to edit.', VGSE()->textname); ?></p>

					<p><?php _e('You can search using the following options.', VGSE()->textname); ?></p>

					<ul class="vg-naked-list">
						<li><?php _e('Keyword found in title or post content', VGSE()->textname); ?></li>						
						<li><?php _e('Post status', VGSE()->textname); ?></li>						
						<li><?php _e('Post author', VGSE()->textname); ?></li>						
						<li><?php _e('Categories, Tags, and other taxonomies', VGSE()->textname); ?></li>						
						<li><?php _e('Post date', VGSE()->textname); ?></li>						
						<li><?php _e('Etc', VGSE()->textname); ?></li>						
					</ul>
					<p><?php _e('This feature is available as premium extension.', VGSE()->textname); ?></p>

				</div>
				<br>
				<a href="<?php echo VGSE()->buy_link; ?>" class="remodal-confirm apply-formula-submit-outside" target="_blank"><?php _e('Buy extension now!', VGSE()->textname); ?></a>
				<button data-remodal-action="confirm" class="remodal-cancel"><?php _e('Close', VGSE()->textname); ?></button>
			</div>
			<?php
		}

		/**
		 * Creates or returns an instance of this class.
		 *
		 * 
		 */
		static function get_instance() {
			if (null == WP_Sheet_Editor_Filters_Teaser::$instance) {
				WP_Sheet_Editor_Filters_Teaser::$instance = new WP_Sheet_Editor_Filters_Teaser();
				WP_Sheet_Editor_Filters_Teaser::$instance->init();
			}
			return WP_Sheet_Editor_Filters_Teaser::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}


add_action('vg_sheet_editor/initialized', 'vgse_init_filters_teaser');

if( ! function_exists('vgse_init_filters_teaser')){
function vgse_init_filters_teaser() {
	WP_Sheet_Editor_Filters_Teaser::get_instance();
}
}
