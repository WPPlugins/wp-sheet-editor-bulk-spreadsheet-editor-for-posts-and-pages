<?php
if (!class_exists('WP_Sheet_Editor_Optin')) {

	/**
	 * Display an email optin offering a premium module for the 
	 * users of the free version of the plugin.
	 */
	class WP_Sheet_Editor_Optin {

		static private $instance = false;
		var $notice_key = 'vgse_optin_display';

		private function __construct() {
			
		}

		/**
		 * Creates or returns an instance of this class.
		 *
		 * 
		 */
		static function get_instance() {
			if (null == WP_Sheet_Editor_Optin::$instance) {
				WP_Sheet_Editor_Optin::$instance = new WP_Sheet_Editor_Optin();
				WP_Sheet_Editor_Optin::$instance->init();
			}
			return WP_Sheet_Editor_Optin::$instance;
		}

		function init() {
			if (!is_admin()) {
				return;
			}

			if (defined('VGSE_ANY_PREMIUM_ADDON') && VGSE_ANY_PREMIUM_ADDON) {
				return;
			}
				
			add_action('vg_sheet_editor/quick_setup_page/quick_setup_screen/after_content', array(
					$this,
					'render_optin_notice'
				));
		}


		function render_optin_notice() {
			?>
			<hr>
			<div class="vgse-optin-notice">
			<h3><?php _e('Free extension.', VGSE()->textname); ?></h3>
				<?php echo sprintf(__( '<p>Download The <b>Auto fill cells</b> extension for free, and you will be able to copy information between posts.</p>
				<p><img src="%s" /></p><p>Copy categories , excerpts , dates , authors , etc.</p>
				<p>Please enter your email below and we will send you the download link for free.</p><p>We will also send you tips on how to use the spreadsheet editor and updates notifications.</p>', VGSE()->textname), VGSE()->plugin_url . 'assets/imgs/drag-down-autofill-demo.gif' ); ?>
				<form action="http://newsletters.apps.vegacorp.me/lists/bh871wnhpee1a/subscribe" method="post" accept-charset="utf-8" target="_blank">

					<div class="form-group">
						<label><?php _e('Email', VGSE()->textname); ?></label>
						<?php 
        $user = get_userdata(get_current_user_id());
        ?>
						<input type="email" class="form-control vg-email" name="EMAIL" placeholder="<?php _e('My email is....', VGSE()->textname); ?>" value="<?php echo $user->user_email; ?>" required style="
							   min-width: 220px;
							   display: inline-block;
							   ">
						<button type="submit" class="button-primary"><?php _e('Download extension for free', VGSE()->textname); ?></button> 
						
						</div>

					<br class="clearfix"> </br>

				</form>

			</div>


			<?php
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}

add_action('vg_sheet_editor/initialized', 'vgse_init_optin');

if( ! function_exists('vgse_init_optin')){
function vgse_init_optin() {
	WP_Sheet_Editor_Optin::get_instance();
}
}
