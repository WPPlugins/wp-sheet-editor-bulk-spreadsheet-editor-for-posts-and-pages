<?php

/**
  ReduxFramework Config File
  For full documentation, please visit: https://docs.reduxframework.com
 * */
if (!class_exists('WP_Sheet_Editor_Redux_Setup')) {

	class WP_Sheet_Editor_Redux_Setup {

		public $args = array();
		public $sections = array();
		public $theme;
		public $pts;
		public $ReduxFramework;

		public function __construct() {

			if (!class_exists('ReduxFramework')) {
				return;
			}

			// This is needed. Bah WordPress bugs.  ;)
			if (true == Redux_Helpers::isTheme(__FILE__)) {
				$this->initSettings();
			} else {
				add_action('init', array($this, 'initSettings'), 10);
			}
		}

		public function initSettings() {

			// Just for demo purposes. Not needed per say.
			$this->theme = wp_get_theme();

			// Set the default arguments
			$this->setArguments();

			// Set a few help tabs so you can see how it's done
			$this->setHelpTabs();

			// Create the sections and fields
			$this->setSections();

			if (!isset($this->args['opt_name'])) { // No errors please
				return;
			}

			// If Redux is running as a plugin, this will remove the demo notice and links
			add_action('redux/loaded', array($this, 'remove_demo'));


			add_action('redux/page/' . $this->args['opt_name'] . '/enqueue', array($this, 'add_custom_css_to_panel'));

			$this->ReduxFramework = new ReduxFramework($this->sections, $this->args);
		}

		function add_custom_css_to_panel() {
			wp_register_style(
					'vgse-redux-custom-css', ( VGSE_DEBUG ) ? VGSE()->plugin_url . 'assets/css/reduxframework.css' : VGSE()->plugin_url . 'assets/css/styles.min.css', array('redux-admin-css'), // Be sure to include redux-admin-css so it's appended after the core css is applied
					time(), 'all'
			);
			wp_enqueue_style('vgse-redux-custom-css');
			
		}

		/**

		  Filter hook for filtering the args. Good for child themes to override or add to the args array. Can also be used in other functions.

		 * */
		function change_arguments($args) {
			//$args['dev_mode'] = true;

			return $args;
		}

		/**

		  Filter hook for filtering the default value of any given field. Very useful in development mode.

		 * */
		function change_defaults($defaults) {
			$defaults['str_replace'] = 'Testing filter hook!';

			return $defaults;
		}

		// Remove the demo link and the notice of integrated demo from the redux-framework plugin
		function remove_demo() {

			// Used to hide the demo mode link from the plugin page. Only used when Redux is a plugin.
			if (class_exists('ReduxFrameworkPlugin')) {
				remove_filter('plugin_row_meta', array(ReduxFrameworkPlugin::instance(), 'plugin_metalinks'), null, 2);

				// Used to hide the activation notice informing users of the demo panel. Only used when Redux is a plugin.
				remove_action('admin_notices', array(ReduxFrameworkPlugin::instance(), 'admin_notices'));
			}
		}

		public function setSections() {

			$this->sections[] = array(
				'icon' => 'el-icon-cogs',
				'title' => __('General settings', VGSE()->textname),
				'fields' => array(
					array(
				'id' => 'info_normal_234343',
				'type' => 'info',
				'desc' => __('In this page you can quickly set up the spreadsheet editor. This all you need to use the editor. The settings on the other tabs are completely optional and allow you to tweak the performance of the editor among other things.', VGSE()->textname),
			),
					array(
						'id' => 'be_post_types',
						'type' => 'select',
						'title' => __('Post Types', VGSE()->textname),
						'desc' => __('On which post types do you want to enable the editor?', VGSE()->textname),
						'options' => VGSE()->allowed_post_types,
						'multi' => true,
						'default' => 'post',
					),
					array(
						'id' => 'be_posts_per_page',
						'type' => 'text',
						'validate' => 'numeric',
						'title' => __('How many posts do you want to display on the spreadsheet?', VGSE()->textname),
						'default' => 10,
					),
					array(
						'id' => 'be_load_items_on_scroll',
						'type' => 'switch',
						'title' => __('Load more items on scroll?', VGSE()->textname),
						'desc' => __('When this is enabled more items will be loaded to the bottom of the spreadsheet when you reach the end of the page. You can enable / disable in the spreadsheet too.', VGSE()->textname),
						'default' => true,
					),
					array(
						'id' => 'be_fix_first_columns',
						'type' => 'switch',
						'title' => __('Fix first 2 columns at the left side?', VGSE()->textname),
						'desc' => __('When this is enabled the first 2 columns will always be visible while scrolling horizontally.', VGSE()->textname),
						'default' => true,
					),
					) );
			
			$this->sections[] = array(
				'icon' => 'el-icon-plane',
				'title' => __('Advanced', VGSE()->textname),
				'fields' => array(
					array(
						'id' => 'be_posts_per_page_save',
						'type' => 'text',
						'validate' => 'numeric',
						'title' => __('How many posts do you want to save per batch?', VGSE()->textname),
						'desc' => __('When you edit a large amount of posts in the spreadsheet editor we can´t save all the changes at once, so we do it in batches. The recommended value is 4 , which means we will process only 4 posts at once. You can adjust it as it works best for you. If you get errors when saving you should lower the number', VGSE()->textname),
						'default' => 4,
					),
					array(
						'id' => 'be_timeout_between_batches',
						'type' => 'text',
						'validate' => 'numeric',
						'title' => __('How long do you want to wait between batches? (in seconds)', VGSE()->textname),
						'desc' => __('When you edit a large amount of posts in the spreadsheet editor we can´t save all the changes at once, so we do it in batches. But your server can´t handle all the batches one after another so we need to wait a few seconds after every batch to give your server a little break. The recommended value is 30 seconds, you can adjust it as it works best for you. If you get errors when saving you should increase the number to give your server a longer break after each batch', VGSE()->textname),
						'default' => 30,
					),
					array(
						'id' => 'be_disable_post_actions',
						'type' => 'switch',
						'title' => __('Disable post actions while saving?', VGSE()->textname),
						'desc' => __('Some plugins execute a task after a post is created or updated. For example, there are plugins that share your new posts on your social profiles, other plugins that notify users after a post is updated, etc. There might be an issue with those plugins. For example, if you use a plugin that shares your new posts on your twitter account and update 100 posts in the spreadsheet editor you might end up with 100 tweets shared in your twitter account. So enable this option if you want to update / create posts silently without executing those functions.', VGSE()->textname),
						'default' => false,
					),
					array(
						'id' => 'be_allow_edit_slugs',
						'type' => 'switch',
						'title' => __('Allow to edit post slugs?', VGSE()->textname),
						'desc' => __('Imagine you edit hundreds of slugs using a formula by accident, you would end up with hundreds of posts with broken links on your social media profiles and Google. That´s why by default you can´t edit post slugs. But if you need to edit slugs, you can enable it here. Use it at your own risk, and you should disable this option inmediately after you finish editing the slugs to prevent future accidents. ', VGSE()->textname),
						'default' => false,
					),
				)
			);
		}

		public function setHelpTabs() {
			
		}

		/**

		  All the possible arguments for Redux.
		  For full documentation on arguments, please refer to: https://github.com/ReduxFramework/ReduxFramework/wiki/Arguments

		 * */
		public function setArguments() {


			$this->args = array(
				'opt_name' => VGSE()->options_key,
				'display_name' => __('WP Sheet Editor', VGSE()->textname),
				'display_version' => VGSE()->version,
				'page_slug' => VGSE()->options_key,
				'page_title' => __('WP Sheet Editor', VGSE()->textname),
				'update_notice' => false,
				'admin_bar' => false,
				'menu_type' => 'submenu',
//				'menu_icon' => VGSE()->plugin_url . 'assets/imgs/icon-20x20.png',
				'menu_title' => __('Settings', VGSE()->textname),
				'allow_sub_menu' => true,
				'page_parent' => 'vg_sheet_editor_setup',
				'default_mark' => '*',
				'hints' =>
				array(
					'icon' => 'el-icon-question-sign',
					'icon_position' => 'right',
					'icon_color' => 'lightgray',
					'icon_size' => 'normal',
					'tip_style' =>
					array(
						'color' => 'light',
					),
					'tip_position' =>
					array(
						'my' => 'top left',
						'at' => 'bottom right',
					),
					'tip_effect' =>
					array(
						'show' =>
						array(
							'duration' => '500',
							'event' => 'mouseover',
						),
						'hide' =>
						array(
							'duration' => '500',
							'event' => 'mouseleave unfocus',
						),
					),
				),
				'output' => true,
				'output_tag' => true,
				'compiler' => true,
				'page_icon' => 'icon-themes',
				'dev_mode' => VGSE_DEBUG,
				'page_permissions' => 'manage_options',
				'save_defaults' => true,
				'show_import_export' => true,
				'transient_time' => '3600',
				'network_sites' => true,
			);
		}

	}

	new WP_Sheet_Editor_Redux_Setup();
}

/**
 * Disable dev mode. For some reason it doesnt disable when 
 * I change the dev_mode argument when constructing the options page.
 * So I took this code from the redux-developer-mode-disabler plugin
 */
if (!function_exists('vg_redux_disable_dev_mode_plugin')) {

	function vg_redux_disable_dev_mode_plugin($redux) {
		if ($redux->args['opt_name'] != 'redux_demo') {
			$redux->args['dev_mode'] = false;
			$redux->args['forced_dev_mode_off'] = false;
		}
	}

	add_action('redux/construct', 'vg_redux_disable_dev_mode_plugin');
}
