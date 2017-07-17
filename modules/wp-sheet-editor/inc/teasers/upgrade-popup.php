<?php
if (!class_exists('WP_Sheet_Editor_Popup_Teaser')) {

	/**
	 * Display Popup to tease users of the free 
	 * version into purchasing the premium plugin.
	 */
	class WP_Sheet_Editor_Popup_Teaser {

		static private $instance = false;
        var $post_types = array();

		private function __construct() {
			
		}

		function init() {
			if (!is_admin()) {
				return;
			}

			if ( defined('VGSE_ANY_PREMIUM_ADDON') && VGSE_ANY_PREMIUM_ADDON ) {
				return;
			}

            $post_types = VGSE()->helpers->get_all_post_types_names();

			if (isset($post_types['post'])) {
				unset($post_types['post']);
			}
			if (isset($post_types['page'])) {
				unset($post_types['page']);
			}

			$this->post_types = $post_types;

			add_action('vg_sheet_editor/editor_page/after_content', array($this, 'render_popup'));
		}


		function render_popup($current_post_type) {

            if( $current_post_type === 'product'){
                return;
            }
            $random_id = rand();
			?>

			<style>
				.vg-naked-list {	
					list-style: initial;
					text-align: left;
					margin-left: 30px;
				}
                .popup-teaser li {
                    width: 50%;
                    margin-bottom: 25px;
                    min-height: 146px;
					float: left;
                }
                .popup-teaser li.full-width {
                    width: 100%;
                }
                .vgse-styled-teaser.popup-teaser {
                    margin-bottom: 0;
                    padding-bottom: 0;
                }
				.popup-teaser li i {
					display: block;
					font-size: 50px;
				}
				.popup-teaser li b {
					display: block;
				}
			</style>
			<div class="remodal remodal<?php echo $random_id; ?>" data-remodal-id="modal-popup-teaser" data-remodal-options="closeOnOutsideClick: true, hashTracking: false">

				<div class="modal-content vgse-styled-teaser popup-teaser">
					<h3><?php _e('Thank you for using our plugin', VGSE()->textname); ?></h3>
					<p><?php _e('You can do more with the premium version.', VGSE()->textname); ?></p>
<a href="<?php echo VGSE()->buy_link; ?>" class="remodal-confirm apply-formula-submit-outside" target="_blank"><?php _e('Buy extension now!', VGSE()->textname); ?></a>
					<ul>

                    <?php 
                    $post_types_labels = array();
                    foreach ($this->post_types as $post_type_tease) {
					$label = VGSE()->helpers->get_post_type_label($post_type_tease);
					
					if( $post_type_tease === 'product'){
						$label = 'WooCommerce ' . $label;
					}

                    $post_types_labels[] = $label;
                    }
                    
                    ?>
						<li><i class="fa fa-edit"></i> <?php printf( __('<b>Edit all your post types</b> Including %s', VGSE()->textname), implode(', ', $post_types_labels ) ); ?></li>						
						<li><i class="fa fa-search"></i> <b><?php _e('Advanced Search', VGSE()->textname); ?></b><?php _e('Find the posts that you need to edit.', VGSE()->textname); ?></li>
  
  <?php if (VGSE()->helpers->is_plugin_active('woocommerce/woocommerce.php') ){ ?>
  <li><i class="fa fa-edit"></i> <b><?php _e('Edit all the Products Fields', VGSE()->textname); ?></b><?php _e('Stock, Images, Categories, Attributes, Descriptions, etc.', VGSE()->textname); ?></li>
  <!--<li><i class="fa fa-list-ul"></i> <b><?php _e('Edit Variable Products', VGSE()->textname); ?></b></li> -->
      <?php } ?>
  <li><i class="fa fa-terminal"></i> <b><?php _e('Update all your Posts at Once', VGSE()->textname); ?></b> <?php _e('Increase or decrease prices and stock, modify descriptions, copy images between posts, etc.', VGSE()->textname); ?> </li>
        
						<li><i class="fa fa-sort-amount-asc"></i> <?php _e('<b>Auto fill post fields</b> Edit your posts even Faster.', VGSE()->textname); ?></li>						
						<li><i class="fa fa-list-ol"></i> <?php _e('<b>Add Custom Fields</b> Add custom columns and Edit other plugin fields in the spreadsheet.', VGSE()->textname); ?></li>						
						<li><i class="fa fa-exchange"></i> <?php _e('<b>Sort, rename, and hide columns</b> Customize the spreadsheet', VGSE()->textname); ?></li>						
						<li class="full-width"><i class="fa fa-rocket"></i> <?php _e('<b>Compatible with </b> Visual Composer, YOAST SEO, WooCommerce, and Advanced Custom Fields.', VGSE()->textname); ?></li>						
									
					</ul>					
				</div>
				<a href="<?php echo VGSE()->buy_link; ?>" class="remodal-confirm apply-formula-submit-outside" target="_blank"><?php _e('Buy extension now!', VGSE()->textname); ?></a>
				<button data-remodal-action="confirm" class="remodal-cancel"><?php _e('Close', VGSE()->textname); ?></button>
			</div>

            <script>
            function vgseDisplayPopupTeaser(){                
            setTimeout( function(){
                if( ! jQuery('.remodal-is-opened').length ){
                    jQuery('.remodal<?php echo $random_id; ?>').remodal().open();
                } else {
                    vgseDisplayPopupTeaser();
                }
            }, 300000 );
        }
        vgseDisplayPopupTeaser();
            </script>
			<?php
		}

		/**
		 * Creates or returns an instance of this class.
		 *
		 * 
		 */
		static function get_instance() {
			if (null == WP_Sheet_Editor_Popup_Teaser::$instance) {
				WP_Sheet_Editor_Popup_Teaser::$instance = new WP_Sheet_Editor_Popup_Teaser();
				WP_Sheet_Editor_Popup_Teaser::$instance->init();
			}
			return WP_Sheet_Editor_Popup_Teaser::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}


add_action('vg_sheet_editor/initialized', 'vgse_init_Popup_teaser');

if( ! function_exists('vgse_init_Popup_teaser')){
function vgse_init_Popup_teaser() {
	WP_Sheet_Editor_Popup_Teaser::get_instance();
}
}
