<?php
/*
    Extension Name: Plugin Installer
    Extension URI: https://github.com/parallelus/Plugin-Installer-for-Runway
    Version: 0.9.7
    Description: Package and auto-install plugins with themes.
    Author: Parallelus
    Author URI: http://runwaywp.com
*/

#-----------------------------------------------------------------
# Plugin Install and Update Classes
#-----------------------------------------------------------------

require_once( 'install/class-plugin-installer.php' );
require_once( 'install/class-tgmpa-list-table.php' );
require_once( 'install/class-plugin-installer-skin.php' );
require_once( 'update/class-tgm-updater-config.php' );
require_once( 'update/class-tgm-updater.php' );

// Settings
$fields  = array(
	'var'   => array(),
	'array' => array()
);
$default = array();

$settings = array(
	'name'          => __( 'Plugin Installer', 'runway' ),
	'option_key'    => $shortname . 'plugin_installer',
	'fields'        => $fields,
	'default'       => $default,
	'parent_menu'   => 'settings',
	'wp_containers' => 'none',
	'file'          => __FILE__,
	'js'            => array(
		'jquery',
		FRAMEWORK_URL . 'extensions/plugin-installer/js/field-extended.js',
	),
);

// Required components
include( 'object.php' );

global $plugin_installer, $plugin_installer_admin, $rpi_class;
$plugin_installer = new Plugin_Installer_Object( $settings );

// Load admin components
if ( is_admin() ) {
	include( 'settings-object.php' );
	$plugin_installer_admin = new Plugin_Installer_Admin_Object( $settings );

	add_action( 'admin_notices', 'site_admin_plugin_notice' );
	add_action( 'admin_menu', 'remove_submenu' );
}

$plugin_installer->include_custom_php();

function site_admin_plugin_notice() {

	$rpi_class = new Runway_Plugin_Installer;
	$rpi_class->notices();

}

function remove_submenu() {

	global $plugin_installer, $theme_name;

	$plugins          = $plugin_installer->get_all_plugins_list();
	$are_all_activate = true;
	foreach ( $plugins as $plugin ) {
		if ( ! is_plugin_active( $plugin['slug'] ) ) {
			$are_all_activate = false;
		}
	}

	if ( $theme_name === 'runway-framework' && $are_all_activate ) {
		remove_submenu_page( 'options-general.php', 'plugin-installer' );
	}

}

function plugin_installer_report( $reports_object ) {

	$plugins_dir = FRAMEWORK_DIR . 'extensions/plugin-installer/plugins/';
	$reports_object->assign_report( array(
		'source'          => __( 'Plugin Installer', 'runway' ),
		'report_key'      => 'plugins_dir_exists',
		'path'            => $plugins_dir,
		'success_message' => sprintf( __( 'Plugin Installer directory (%s) is exists.', 'runway' ), $plugins_dir ),
		'fail_message'    => sprintf( __( 'Plugin Installer directory (%s) is not exists.', 'runway' ), $plugins_dir ),
	), 'DIR_EXISTS' );

	$reports_object->assign_report( array(
		'source'          => __( 'Plugin Installer', 'runway' ),
		'report_key'      => 'plugins_dir_writable',
		'path'            => $plugins_dir,
		'success_message' => sprintf( __( 'Plugin Installer directory (%s) is writable.', 'runway' ), $plugins_dir ),
		'fail_message'    => sprintf( __( 'Plugin Installer directory (%s) is not writable.', 'runway' ), $plugins_dir ),
	), 'IS_WRITABLE' );

}
add_action( 'add_report', 'plugin_installer_report' );

// Setup a custom button in the title
function title_button_new_plugin( $title ) {

	if ( IS_CHILD && get_template() == 'runway-framework' && $_GET['page'] == 'plugin-installer' ) {
		$title .= ' <a href="' . admin_url( 'admin.php?page=plugin-installer&navigation=add-plugin' ) .
		          '" class="add-new-h2">' .
		          __( 'Add New', 'runway' ) .
		          '</a> ';
	}

	return $title;

}
add_filter( 'framework_admin_title', 'title_button_new_plugin' );
