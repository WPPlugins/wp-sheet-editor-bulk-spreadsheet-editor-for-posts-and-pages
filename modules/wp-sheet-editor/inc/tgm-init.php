<?php

/**
 * This file represents the registration of
 * the required plugins.
 *
 *
 * @see http://tgmpluginactivation.com/configuration/ for detailed documentation.
 *
 * @package    TGM-Plugin-Activation
 * @subpackage Example
 * @version    2.5.2 for plugin WP Sheet Editor by VegaCorp
 * @author     Thomas Griffin, Gary Jones, Juliette Reinders Folmer
 * @copyright  Copyright (c) 2011, Thomas Griffin
 * @license    http://opensource.org/licenses/gpl-2.0.php GPL v2 or later
 * @link       https://github.com/TGMPA/TGM-Plugin-Activation
 */
/**
 * Include the TGM_Plugin_Activation class.
 */
require_once VGSE_DIR . '/vendor/TGM-Plugin-Activation-2.5.2/class-tgm-plugin-activation.php';

add_action('tgmpa_register', 'vgse_register_required_plugins');

/**
 * Register the required plugins for this theme.
 *
 * In this example, we register five plugins:
 * - one included with the TGMPA library
 * - two from an external source, one from an arbitrary source, one from a GitHub repository
 * - two from the .org repo, where one demonstrates the use of the `is_callable` argument
 *
 * The variable passed to tgmpa_register_plugins() should be an array of plugin
 * arrays.
 *
 * This function is hooked into tgmpa_init, which is fired within the
 * TGM_Plugin_Activation class constructor.
 */
if( ! function_exists('vgse_register_required_plugins')){
function vgse_register_required_plugins() {
	
			if (class_exists('ReduxFramework')) {
				return;
			}
	/*
	 * Array of plugin arrays. Required keys are name and slug.
	 * If the source is NOT from the .org repo, then source is also required.
	 */
	$plugins = array(
		array(
			'name' => 'Redux Framework',
			'slug' => 'redux-framework',
			'required' => true,
		),
	);

	/*
	 * Array of configuration settings. Amend each line as needed.
	 *
	 * TGMPA will start providing localized text strings soon. If you already have translations of our standard
	 * strings available, please help us make TGMPA even better by giving us access to these translations or by
	 * sending in a pull-request with .po file(s) with the translations.
	 *
	 * Only uncomment the strings in the config array if you want to customize the strings.
	 */
	$config = array(
		'id' => 'vg_sheet_editor', // Unique ID for hashing notices for multiple instances of TGMPA.
		'default_path' => '', // Default absolute path to bundled plugins.
		'menu' => 'tgmpa-install-plugins', // Menu slug.
		'parent_slug' => 'plugins.php', // Parent menu slug.
		'capability' => 'manage_options', // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
		'has_notices' => true, // Show admin notices or not.
		'dismissable' => true, // If false, a user cannot dismiss the nag message.
		'dismiss_msg' => '', // If 'dismissable' is false, this message will be output at top of nag.
		'is_automatic' => true, // Automatically activate plugins after installation or not.
		'message' => '', // Message to output right before the plugins table.
		'strings' => array(
			'page_title' => __('Install Required Plugins', 'tgmpa'),
			'menu_title' => __('Install Plugins', 'tgmpa'),
			'installing' => __('Installing Plugin: %s', 'tgmpa'),
			'oops' => __('Something went wrong with the plugin API.', 'tgmpa'),
			'notice_can_install_required' => _n_noop(
					'The plugin WP Sheet Editor requires the following plugin: %1$s.', 'The plugin WP Sheet Editor requires the following plugins: %1$s.', VGSE()->textname
			),
			'notice_can_install_recommended' => _n_noop(
					'This plugin recommends the following plugin: %1$s.', 'This plugin recommends the following plugins: %1$s.', 'tgmpa'
			),
			'notice_cannot_install' => _n_noop(
					'Sorry, but you do not have the correct permissions to install the %1$s plugin.', 'Sorry, but you do not have the correct permissions to install the %1$s plugins.', 'tgmpa'
			),
			'notice_ask_to_update' => _n_noop(
					'The following plugin needs to be updated to its latest version to ensure maximum compatibility with this plugin: %1$s.', 'The following plugins need to be updated to their latest version to ensure maximum compatibility with this plugin: %1$s.', 'tgmpa'
			),
			'notice_ask_to_update_maybe' => _n_noop(
					'There is an update available for: %1$s.', 'There are updates available for the following plugins: %1$s.', 'tgmpa'
			),
			'notice_cannot_update' => _n_noop(
					'Sorry, but you do not have the correct permissions to update the %1$s plugin.', 'Sorry, but you do not have the correct permissions to update the %1$s plugins.', 'tgmpa'
			),
			'notice_can_activate_required' => _n_noop(
					'The following required plugin is currently inactive: %1$s.', 'The following required plugins are currently inactive: %1$s.', 'tgmpa'
			),
			'notice_can_activate_recommended' => _n_noop(
					'The following recommended plugin is currently inactive: %1$s.', 'The following recommended plugins are currently inactive: %1$s.', 'tgmpa'
			),
			'notice_cannot_activate' => _n_noop(
					'Sorry, but you do not have the correct permissions to activate the %1$s plugin.', 'Sorry, but you do not have the correct permissions to activate the %1$s plugins.', 'tgmpa'
			),
			'install_link' => _n_noop(
					'Begin installing plugin', 'Begin installing plugins', 'tgmpa'
			),
			'update_link' => _n_noop(
					'Begin updating plugin', 'Begin updating plugins', 'tgmpa'
			),
			'activate_link' => _n_noop(
					'Begin activating plugin', 'Begin activating plugins', 'tgmpa'
			),
			'return' => __('Return to Required Plugins Installer', 'tgmpa'),
			'dashboard' => __('Return to the dashboard', 'tgmpa'),
			'plugin_activated' => __('Plugin activated successfully.', 'tgmpa'),
			'activated_successfully' => __('The following plugin was activated successfully:', 'tgmpa'),
			'plugin_already_active' => __('No action taken. Plugin %1$s was already active.', 'tgmpa'),
			'plugin_needs_higher_version' => __('Plugin not activated. A higher version of %s is needed for this plugin. Please update the plugin.', 'tgmpa'),
			'complete' => __('All plugins installed and activated successfully. %1$s', 'tgmpa'),
			'dismiss' => __('Dismiss this notice', 'tgmpa'),
			'contact_admin' => __('Please contact the administrator of this site for help.', 'tgmpa'),
		)
	);

	tgmpa($plugins, $config);
}
}