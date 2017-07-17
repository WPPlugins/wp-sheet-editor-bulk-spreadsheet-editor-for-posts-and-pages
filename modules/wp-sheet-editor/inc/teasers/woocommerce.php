<?php
if (!class_exists('WP_Sheet_Editor_WooCommerce_Teaser')) {

	/**
	 * Display woocommerce item in the toolbar to tease users of the free 
	 * version into purchasing the premium plugin.
	 */
	class WP_Sheet_Editor_WooCommerce_Teaser {

		static private $instance = false;
		var $post_type = 'product';

		private function __construct() {
			
		}

		function init() {
			if (!is_admin()) {
				return;
			}

			if(class_exists('WP_Sheet_Editor_WooCommerce')){
				return;
			}
			add_filter('vg_sheet_editor/allowed_post_types', array($this, 'allow_product_post_type'));
			add_action('admin_enqueue_scripts', array($this, 'remove_conflicting_assets'), 99999999);
			add_action('admin_print_styles', array($this, 'remove_conflicting_assets'), 99999999);
			add_action('vg_sheet_editor/columns/post_type_items', array($this, 'filter_columns_settings'), 10, 3);
			add_action('vg_sheet_editor/editor_page/before_spreadsheet', array($this, 'render_columns_teaser'));
			add_filter('vg_sheet_editor/load_rows/wp_query_args', array($this, 'display_only_simple_products'));
		}
		
		function display_only_simple_products( $wp_query ){
			if( $wp_query['post_type'] === $this->post_type ){				
					$wp_query['tax_query'] = array(
        array(
            'taxonomy' => 'product_type',
            'field'    => 'slug',
            'terms'    => 'simple', 
        ),
    );
			}

			return $wp_query;
		}
		function render_columns_teaser($post_type) {
    if( $post_type !== $this->post_type){
		return;
	}
	?>
<style>
	.vgse-woocommerce-featured-teaser {
    background: #f1f1f1;
}
</style>
<div class="vgse-woocommerce-featured-teaser vgse-styled-teaser">
  
	<h3><?php _e('The best tool to manage your Store', VGSE()->textname); ?></h3>
<p><?php _e('You are using the LIMITED FREE version and <b>you can edit only simple products and prices.</b><br/> Below we show you the wonderful things that you can do with the PREMIUM version:', VGSE()->textname); ?></p>
<ul>
  <li><i class="fa fa-edit"></i> <b><?php _e('Edit all the Products Fields', VGSE()->textname); ?></b><?php _e('Stock, Images, Categories, Attributes, Descriptions, etc.', VGSE()->textname); ?></li>
  <!--<li><i class="fa fa-list-ul"></i> <b><?php _e('Edit Variable Products', VGSE()->textname); ?></b> <?php _e('Edit Variation prices, attributes, images, etc.', VGSE()->textname); ?></li>-->
  <li><i class="fa fa-search"></i> <b><?php _e('Advanced Search', VGSE()->textname); ?></b><?php _e('Find the products that you need to edit.', VGSE()->textname); ?></li>
  <li><i class="fa fa-terminal"></i> <b><?php _e('Update all your Products at Once', VGSE()->textname); ?></b> <?php _e('Increase or decrease prices and stock, modify descriptions, etc.', VGSE()->textname); ?> </li>
    </ul>
<a href="<?php echo VGSE()->buy_link; ?>" class="remodal-confirm apply-formula-submit-outside" target="_blank"><?php _e('Buy extension now!', VGSE()->textname); ?></a>
</div>
<?php 
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
		function filter_columns_settings($spreadsheet_columns, $post_type, $exclude_formatted_settings) {

			if (defined('VGSE_WC_TEASER_LIMIT_COLUMNS') && !VGSE_WC_TEASER_LIMIT_COLUMNS) {
				return $spreadsheet_columns;
			}
			if ($post_type !== $this->post_type) {
				return $spreadsheet_columns;
			}

			$new_columns = array();
			
			$allowed_columns = array(
				'ID',
				'title',
				'post_title',
				'status',
				'post_status',
			);
			
			foreach( $spreadsheet_columns as $key => $spreadsheet_column ){
				if( in_array($key, $allowed_columns)){
					$new_columns[ $key ] = $spreadsheet_column;
				}
			}
			
			$new_columns['_regular_price'] = array(
				'data_type' => 'meta_data',
				'unformated' => array('data' => '_regular_price'),
				'colum_width' => 150,
				'title' => __('Regular Price', VGSE()->textname),
				'type' => '',
				'supports_formulas' => true,
				'formated' => array('data' => '_regular_price'),
				'allow_to_hide' => true,
				'allow_to_rename' => true,
			);

			$new_columns['_sale_price'] = array(
				'data_type' => 'meta_data',
				'unformated' => array('data' => '_sale_price'),
				'colum_width' => 150,
				'title' => __('Sale Price', VGSE()->textname),
				'type' => '',
				'supports_formulas' => true,
				'formated' => array('data' => '_sale_price', 'renderer' => 'html'),
				'allow_to_hide' => true,
				'allow_to_rename' => true,
			);
			
			return $new_columns;
		}

		function remove_conflicting_assets() {
			if (class_exists('woocommerce') && !empty($_GET['page']) && strpos($_GET['page'], 'bulk-edit-') !== false) {

				$remove = array(
'select2',
'wc-admin-meta-boxes',
'woocommerce_settings',
'wc-enhanced-select',
'wc-shipping-zones',
'woocommerce-shop-as-customer',
'woocommerce_admin_styles',
'woocommerce_admin',
				);

				foreach ($remove as $handle) {
					wp_dequeue_style( $handle);
					wp_deregister_style($handle);
					wp_dequeue_script($handle);
					wp_deregister_script($handle);
				}
			}
		}

		/**
		 * Allow woocomerce product post type
		 * @param array $post_types
		 * @return array
		 */
		function allow_product_post_type($post_types) {

			if (!isset($post_types[$this->post_type])) {
				$post_types[$this->post_type] = VGSE()->helpers->get_post_type_label($this->post_type);
			}
			return $post_types;
		}


		/**
		 * Creates or returns an instance of this class.
		 *
		 * 
		 */
		static function get_instance() {
			if (null == WP_Sheet_Editor_WooCommerce_Teaser::$instance) {
				WP_Sheet_Editor_WooCommerce_Teaser::$instance = new WP_Sheet_Editor_WooCommerce_Teaser();
				WP_Sheet_Editor_WooCommerce_Teaser::$instance->init();
			}
			return WP_Sheet_Editor_WooCommerce_Teaser::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}


add_action('vg_sheet_editor/initialized', 'vgse_init_woocommerce_teaser');

if( ! function_exists('vgse_init_woocommerce_teaser')){
function vgse_init_woocommerce_teaser() {
	WP_Sheet_Editor_WooCommerce_Teaser::get_instance();
}
}
