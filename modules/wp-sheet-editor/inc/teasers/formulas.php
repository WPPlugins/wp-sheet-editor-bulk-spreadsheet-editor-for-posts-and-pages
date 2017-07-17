<?php
if (!class_exists('WP_Sheet_Editor_Formulas_Teaser')) {

	/**
	 * Display formulas item in the toolbar to tease users of the free 
	 * version into purchasing the premium plugin.
	 */
	class WP_Sheet_Editor_Formulas_Teaser {

		static private $instance = false;

		private function __construct() {
			
		}

		function init() {

			if(class_exists('WP_Sheet_Editor_Formulas')){
				return;
			}
			add_action('vg_sheet_editor/toolbar/core_items_registered', array($this, 'register_toolbar_items'));
			add_action('vg_sheet_editor/editor_page/after_content', array($this, 'render_formulas_form'));
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
				VGSE()->toolbar->register_item('run_formula', array(
					'type' => 'button',
					'content' => __('Apply changes in bulk', VGSE()->textname),
					'icon' => 'fa fa-terminal',
					'extra_html_attributes' => 'data-remodal-target="modal-formula"',
					'toolbar_key' => 'secondary',
						), $post_type);
			}
		}

		function render_formulas_form($current_post_type) {
			?>

			<style>
				.vg-naked-list {	
					list-style: initial;
					text-align: left;
					margin-left: 30px;
				}
			</style>
			<div class="remodal remodal4" data-remodal-id="modal-formula" data-remodal-options="closeOnOutsideClick: false, hashTracking: false">

				<div class="modal-content">
					<h3><?php _e('Bulk Update feature', VGSE()->textname); ?></h3>

					<p><?php _e('The "bulk update" feature allows you to update several posts at once <br/>and you can do a lot of cool things, for example:', VGSE()->textname); ?></p>

					<ul class="vg-naked-list">
						<li><?php _e('Replace words or phrases in your posts titles, content, or other fields', VGSE()->textname); ?></li>
						<li><?php _e('Increase or decrease products prices', VGSE()->textname); ?></li>
						<li><?php _e('Increase or decrease products stock', VGSE()->textname); ?></li>
						<li><?php _e('Move all your drafts to published posts or any other status', VGSE()->textname); ?></li>
						<li><?php _e('Set hundreds of products at once as out of stock or in stock', VGSE()->textname); ?></li>
						<li><?php _e('Add call to actions or any text at the beginning or ending of your posts', VGSE()->textname); ?></li>
						<li><?php _e('Replace old shortcodes with new shortcodes in all your posts', VGSE()->textname); ?></li>
						<li><?php _e('Set the same featured image in all the posts in a category', VGSE()->textname); ?></li>
						<li><?php _e('Move hundreds of posts to the trash', VGSE()->textname); ?></li>
						<li><?php _e('Etc.', VGSE()->textname); ?></li>						
					</ul>
					<p><?php _e('Imagine being able to do all those changes to hundreds or thousands of posts at once in just a few minutes. The formulas feature is available as premium extension.', VGSE()->textname); ?></p>
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
			if (null == WP_Sheet_Editor_Formulas_Teaser::$instance) {
				WP_Sheet_Editor_Formulas_Teaser::$instance = new WP_Sheet_Editor_Formulas_Teaser();
				WP_Sheet_Editor_Formulas_Teaser::$instance->init();
			}
			return WP_Sheet_Editor_Formulas_Teaser::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}


add_action('vg_sheet_editor/initialized', 'vgse_init_formulas_teaser');

if( ! function_exists('vgse_init_formulas_teaser')){
function vgse_init_formulas_teaser() {
	WP_Sheet_Editor_Formulas_Teaser::get_instance();
}
}
