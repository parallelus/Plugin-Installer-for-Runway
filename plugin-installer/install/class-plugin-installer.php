<?php

/**
 * Runway Extension for Plugin installation and activation. Based on TGM Plugin Activation class.
 *
 * @package   Runway Plugin Installer Class
 * @version   v1.0.1
 * @author    Thomas Griffin <thomas@thomasgriffinmedia.com>
 * @author    Gary Jones <gamajo@gamajo.com>
 * @author    Paralleus, Inc. http://para.llel.us
 *
 */

if ( ! class_exists( 'Runway_Plugin_Installer' ) ) {
	/**
 	 * Automatic plugin installation and activation library.
 	 *
 	 * Creates a way to automatically install and activate plugins from within themes.
 	 * The plugins can be either pre-packaged, downloaded from the WordPress
 	 * Plugin Repository or downloaded from a private repository.
 	 *
 	 * @since 1.0.0
 	 *
 	 * @package Runway-Plugin-Installer
 	 * @author Thomas Griffin <thomas@thomasgriffinmedia.com>
 	 * @author Gary Jones <gamajo@gamajo.com>
 	 */
	class Runway_Plugin_Installer {

		/**
	 	 * Holds a copy of itself, so it can be referenced by the class name.
	 	 *
	 	 * @since 1.0.0
	 	 *
	 	 * @var Runway_Plugin_Installer
	 	 */
		static $instance;

		/**
	 	 * Holds arrays of plugin details.
	 	 *
	 	 * @since 1.0.0
	 	 *
	 	 * @var array
	 	 */
		public $plugins = array();

		/**
	 	 * Parent menu slug for plugins page.
	 	 *
	 	 * @since 2.2.0
	 	 *
	 	 * @var string Parent menu slug. Defaults to 'themes.php'.
	 	 */
		public $parent_menu_slug = 'plugins.php';

		/**
	 	 * Parent URL slug for URL references.
	 	 *
	 	 * This is useful if you want to place the custom plugins page as a
	 	 * submenu item under a custom parent menu.
	 	 *
	 	 * @since 2.2.0
	 	 *
	 	 * @var string Parent URL slug. Defaults to 'themes.php'.
	 	 */
		public $parent_url_slug = 'plugins.php';
		public $parent_url_install_slug = 'update.php';
		public $parent_url_general_slug = 'options-general.php';
		/**
	 	 * Name of the querystring argument for the admin page.
	 	 *
	 	 * @since 1.0.0
	 	 *
	 	 * @var string
	 	 */
		public $menu = 'plugin-installer';

		/**
		 * Text domain for localization support.
		 *
		 * @since 1.1.0
		 *
		 * @var string
		 */
		public $domain = 'runway';

		/**
		 * Default absolute path to folder containing pre-packaged plugin zip files.
		 *
		 * @since 2.0.0
		 *
		 * @var string Absolute path prefix to packaged zip file location. Default is empty string.
		 */
		public $default_path = '';

		/**
		 * Flag to show admin notices or not.
		 *
		 * @since 2.1.0
		 *
		 * @var boolean
		 */
		public $has_notices = true;

		/**
		 * Flag to set automatic activation of plugins. Off by default.
		 *
		 * @since 2.2.0
		 *
		 * @var boolean
		 */
		public $is_automatic = false;

		/**
		 * Optional message to display before the plugins table.
		 *
		 * @since 2.2.0
		 *
		 * @var string Message filtered by wp_kses_post(). Default is empty string.
		 */
		public $message = '';

		/**
		 * Holds configurable array of strings.
		 *
		 * Default values are added in the constructor.
		 *
		 * @since 2.0.0
		 *
		 * @var array
		 */
		public $strings = array();

		public $first_activation = false;
		/**
		 * Adds a reference of this object to $instance, populates default strings,
		 * does the tgmpa_init action hook, and hooks in the interactions to init.
		 *
		 * @since 1.0.0
		 *
		 * @see Runway_Plugin_Installer::init()
		 */
		public function __construct() {

			self::$instance =& $this;

			$this->strings = array(
				'page_title'                      => __( 'Install Required Plugins', 'runway' ),
				'menu_title'                      => __( 'Install Plugins', 'runway' ),
				'installing'                      => __( 'Installing Plugin: %s', 'runway' ),
				'oops'                            => __( 'Something went wrong.', 'runway' ),
				'notice_can_install_required'     => _n_noop( 'This theme requires the following plugin: %1$s.', 'This theme requires the following plugins: %1$s.', 'runway' ),
				'notice_can_install_recommended'  => _n_noop( 'This theme recommends the following plugin: %1$s.', 'This theme recommends the following plugins: %1$s.', 'runway' ),
				'notice_cannot_install'           => _n_noop( 'Sorry, but you do not have the correct permissions to install the %s plugin. Contact the administrator of this site for help on getting the plugin installed.', 'Sorry, but you do not have the correct permissions to install the %s plugins. Contact the administrator of this site for help on getting the plugins installed.', 'runway' ),
				'notice_can_activate_required'    => _n_noop( 'The following required plugin is currently inactive: %1$s.', 'The following required plugins are currently inactive: %1$s.', 'runway' ),
				'notice_can_activate_recommended' => _n_noop( 'The following recommended plugin is currently inactive: %1$s.', 'The following recommended plugins are currently inactive: %1$s.', 'runway' ),
				'notice_cannot_activate'          => _n_noop( 'Sorry, but you do not have the correct permissions to activate the %s plugin. Contact the administrator of this site for help on getting the plugin activated.', 'Sorry, but you do not have the correct permissions to activate the %s plugins. Contact the administrator of this site for help on getting the plugins activated.', 'runway' ),
				'notice_ask_to_update'            => _n_noop( 'The following plugin needs to be updated to its latest version to ensure maximum compatibility with this theme: %1$s.', 'The following plugins need to be updated to their latest version to ensure maximum compatibility with this theme: %1$s.', 'runway' ),
				'notice_cannot_update'            => _n_noop( 'Sorry, but you do not have the correct permissions to update the %s plugin. Contact the administrator of this site for help on getting the plugin updated.', 'Sorry, but you do not have the correct permissions to update the %s plugins. Contact the administrator of this site for help on getting the plugins updated.', 'runway' ),
				'install_link' 					  => _n_noop( 'Install Now', 'Install Now', 'runway' ),
				'activate_link' 				  => _n_noop( 'Activate installed plugin', 'Activate installed plugins', 'runway' ),
				'return'                          => __( 'Return to Required Plugins Installer', 'runway' ),
				'plugin_activated'                => __( 'Plugin activated successfully.', 'runway' ),
				'complete'                        => __( 'All plugins installed successfully. %1$s', 'runway' ),
			);

			/** Annouce that the class is ready, and pass the object (for advanced use) */
			do_action_ref_array( 'tgmpa_init', array( $this ) );

			/** When the rest of WP has loaded, kick-start the rest of the class */
			add_action( 'init', array( $this, 'init' ) );

		}

		/**
		 * Initialise the interactions between this class and WordPress.
		 *
		 * Hooks in three new methods for the class: admin_menu, notices and styles.
		 *
		 * @since 2.0.0
		 *
		 * @see Runway_Plugin_Installer::admin_menu()
		 * @see Runway_Plugin_Installer::notices()
		 * @see Runway_Plugin_Installer::styles()
		 */
		public function init() {

			do_action( 'tgmpa_register' );
			/** After this point, the plugins should be registered and the configuration set */

			add_action( 'admin_init', array( $this, 'dismiss' ) ); // wasn't working in the condition below

			/** Proceed only if we have plugins to handle */
			if ( $this->plugins ) {
				$sorted = array(); // Prepare variable for sorting

				foreach ( $this->plugins as $plugin )
					$sorted[] = $plugin['name'];

				array_multisort( $sorted, SORT_ASC, $this->plugins ); // Sort plugins alphabetically by name

				add_action( 'admin_menu', array( $this, 'admin_menu' ) );
				// add_action( 'admin_init', array( &$this, 'dismiss' ) );
				add_filter( 'install_plugin_complete_actions', array( $this, 'actions' ) );

				/** Load admin bar in the header to remove flash when installing plugins */
				if ( $this->is_tgmpa_page() ) {
					remove_action( 'wp_footer', 'wp_admin_bar_render', 1000 );
					remove_action( 'admin_footer', 'wp_admin_bar_render', 1000 );
					add_action( 'wp_head', 'wp_admin_bar_render', 1000 );
					add_action( 'admin_head', 'wp_admin_bar_render', 1000 );
				}

				if ( $this->has_notices ) {
					add_action( 'admin_notices', array( $this, 'notices' ) );
					add_action( 'admin_init', array( $this, 'admin_init' ), 1 );
					add_action( 'admin_enqueue_scripts', array( $this, 'thickbox' ) );
					add_action( 'switch_theme', array( $this, 'update_dismiss' ) );
				}

				/** Setup the force activation hook */
				foreach ( $this->plugins as $plugin ) {
					if ( isset( $plugin['force_activation'] ) && true === $plugin['force_activation'] ) {
						add_action( 'admin_init', array( $this, 'force_activation' ) );
						break;
					}
				}

				/** Setup the force deactivation hook */
				foreach ( $this->plugins as $plugin ) {
					if ( isset( $plugin['force_deactivation'] ) && true === $plugin['force_deactivation'] ) {
						add_action( 'switch_theme', array( $this, 'force_deactivation' ) );
						break;
					}
				}
			}

		}

		/**
		 * Handles calls to show plugin information via links in the notices.
		 *
		 * We get the links in the admin notices to point to the TGMPA page, rather
		 * than the typical plugin-install.php file, so we can prepare everything
		 * beforehand.
		 *
		 * WP doesn't make it easy to show the plugin information in the thickbox -
		 * here we have to require a file that includes a function that does the
		 * main work of displaying it, enqueue some styles, set up some globals and
		 * finally call that function before exiting.
		 *
		 * Down right easy once you know how...
		 *
		 * @since 2.1.0
		 *
		 * @global string $tab Used as iframe div class names, helps with styling
		 * @global string $body_id Used as the iframe body ID, helps with styling
		 * @return null Returns early if not the TGMPA page.
		 */
		public function admin_init() {

			if ( ! $this->is_tgmpa_page() )
				return;

			if ( isset( $_REQUEST['tab'] ) && 'plugin-information' == $_REQUEST['tab'] ) {
				require_once ABSPATH . 'wp-admin/includes/plugin-install.php'; // Need for install_plugin_information()

				wp_enqueue_style( 'plugin-install' );

				global $tab, $body_id;
				$body_id = $tab = 'plugin-information';

				install_plugin_information();

				exit;
			}

		}

		/**
		 * Enqueues thickbox scripts/styles for plugin info.
		 *
		 * Thickbox is not automatically included on all admin pages, so we must
		 * manually enqueue it for those pages.
		 *
		 * Thickbox is only loaded if the user has not dismissed the admin
		 * notice or if there are any plugins left to install and activate.
		 *
		 * @since 2.1.0
		 */
		public function thickbox() {

			if ( ! get_user_meta( get_current_user_id(), 'tgmpa_dismissed_notice', true ) )
				add_thickbox();

		}

		/**
		 * Adds submenu page under 'Appearance' tab.
		 *
		 * This method adds the submenu page letting users know that a required
		 * plugin needs to be installed.
		 *
		 * This page disappears once the plugin has been installed and activated.
		 *
		 * @since 1.0.0
		 *
		 * @see Runway_Plugin_Installer::init()
		 * @see Runway_Plugin_Installer::install_plugins_page()
		 */
		public function admin_menu() {

			// Make sure privileges are correct to see the page
			if ( ! current_user_can( 'install_plugins' ) )
				return;

			$this->populate_file_path();

			foreach ( $this->plugins as $plugin ) {
				if ( ! is_plugin_active( $plugin['file_path'] ) ) {
					add_submenu_page(
						$this->parent_menu_slug,				// Parent menu slug
						$this->strings['page_title'],           // Page title
						$this->strings['menu_title'],           // Menu title
						'edit_theme_options',                   // Capability
						$this->menu,                            // Menu slug
						array( $this, 'install_plugins_page' ) // Callback
					);
				break;
				}
			}

		}

		/**
		 * Echoes plugin installation form.
		 *
		 * This method is the callback for the admin_menu method function.
		 * This displays the admin page and form area where the user can select to install and activate the plugin.
		 *
		 * @since 1.0.0
		 *
		 * @return null Aborts early if we're processing a plugin installation action
		 */
		public function install_plugins_page() {
			global $plugin_installer_admin;
			$action_save = false;

			if ( isset( $_POST )) {
				$plugin_options_saved = $plugin_installer_admin->plugin_installer_options;
				if ( isset( $_POST[sanitize_key( 'plugin_options' )] ) && isset( $_POST[sanitize_key( 'action_save' )] )) {
					foreach ($_POST[sanitize_key( 'action_save' )] as $key=>$value) {
						if ($value == 'save') {
							$action_save = true;
						}
					}
					if ($action_save) {
						$plugin_installer_admin->plugin_installer_options['plugin_options'] =
							array_replace_recursive( $plugin_options_saved['plugin_options'], $_POST[sanitize_key( 'plugin_options' )] );
						$plugin_installer_admin->update_options();
						echo("<script>location.href = '". esc_url( admin_url('admin.php?page=plugin-installer') ) ."';</script>");
					}
				}
			}

			/** Store new instance of plugin table in object */
			$plugin_table = new TGMPA_List_Table;

			/** Return early if processing a plugin installation action */
//			if ( isset( $_POST[sanitize_key( 'action' )] ) && 'tgmpa-bulk-install' == $_POST[sanitize_key( 'action' )] && $plugin_table->process_bulk_actions() || $this->do_plugin_install() )
			if ( isset( $_POST[sanitize_key( 'action' )] ) && 'tgmpa-bulk-install' == $_POST[sanitize_key( 'action' )] && $plugin_table->process_bulk_actions() ) {
				return;
			}

			?>
			<div class="tgmpa wrap">

				<?php //screen_icon( apply_filters( 'tgmpa_default_screen_icon', 'themes' ) ); ?>
				<h2><?php //echo esc_html( get_admin_page_title() ); ?></h2>
				<?php $plugin_table->prepare_items(); ?>
				<?php if ( isset( $this->message ) ) echo wp_kses_post( $this->message ); ?>

				<form id="tgmpa-plugins" action="" method="post">
            		<input type="hidden" name="tgmpa-page" value="<?php echo esc_attr($this->menu); ?>" />
            		<?php $plugin_table->display(); ?>
        		</form>

			</div>
			<?php

		}

		/**
		 * Installs a plugin or activates a plugin depending on the hover
		 * link clicked by the user.
		 *
		 * Checks the $_GET variable to see which actions have been
		 * passed and responds with the appropriate method.
		 *
		 * Uses WP_Filesystem to process and handle the plugin installation
		 * method.
		 *
		 * @since 1.0.0
		 *
		 * @uses WP_Filesystem
		 * @uses WP_Error
		 * @uses WP_Upgrader
		 * @uses Plugin_Upgrader
		 * @uses Plugin_Installer_Skin
		 *
		 * @return boolean True on success, false on failure
		 */
		public function do_plugin_install( $themePlugin_info = array() ) {
			global $themePlugins;

			/** All plugin information will be stored in an array for processing */
			$plugin = array();

			/** Checks for actions from hover links to process the installation */
			if ( $this->first_activation || ( isset( $_GET[sanitize_key( 'plugin' )] ) && ( isset( $_GET[sanitize_key( 'tgmpa-install' )] ) && 'install-plugin' == $_GET[sanitize_key( 'tgmpa-install' )] ) ) ) {

				if( $this->first_activation ) {
					$plugin['name']   = $themePlugin_info['name']; // Plugin name
					$plugin['slug']   = $themePlugin_info['slug']; // Plugin slug
					$plugin['source'] = $themePlugin_info['source']; // Plugin source
				} else {
					check_admin_referer( 'tgmpa-install' );

					$plugin['name']   = $_GET[sanitize_key( 'plugin_name' )]; // Plugin name
					$plugin['slug']   = $_GET[sanitize_key( 'plugin' )]; // Plugin slug
					$plugin['source'] = $_GET[sanitize_key( 'plugin_source' )]; // Plugin source
				}

				/** Pass all necessary information via URL if WP_Filesystem is needed */
				$url = wp_nonce_url(
					add_query_arg(
						array(
							'page'          => $this->menu,
							'plugin'        => $plugin['slug'],
							'plugin_name'   => $plugin['name'],
							'plugin_source' => $plugin['source'],
							'tgmpa-install' => 'install-plugin',
						),
						admin_url( $this->parent_url_slug )
					),
					'tgmpa-install'
				);

				$method = ''; // Leave blank so WP_Filesystem can populate it as necessary
				$fields = array( sanitize_key( 'tgmpa-install' ) ); // Extra fields to pass to WP_Filesystem

				if ( false === ( $creds = request_filesystem_credentials( $url, $method, false, false, $fields ) ) )
					return true;

				if ( ! WP_Filesystem( $creds ) ) {
					request_filesystem_credentials( $url, $method, true, false, $fields ); // Setup WP_Filesystem
					return true;
				}

				require_once ABSPATH . 'wp-admin/includes/plugin-install.php'; // Need for plugins_api
				require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php'; // Need for upgrade classes

				/** Set plugin source to WordPress API link if available */
				if ( isset( $plugin['source'] ) && strstr($plugin['source'], RUNWAY_PLUGIN_INSTALLER_WP_REPOSITORY_URL) !== false ) {
					$api = plugins_api( 'plugin_information', array( 'slug' => $plugin['slug'], 'fields' => array( 'sections' => false ) ) );

					if ( is_wp_error( $api ) )
						wp_die( $this->strings['oops'] . var_dump( $api ) );

					if ( isset( $api->download_link ) )
						$plugin['source'] = $api->download_link;
				}

				/** Set type, based on whether the source starts with http:// or https:// */
				$type = preg_match( '|^http(s)?://|', $plugin['source'] ) ? 'web' : 'upload';

				/** Prep variables for Plugin_Installer_Skin class */
				$title = sprintf( $this->strings['installing'], $plugin['name'] );
				$url   = add_query_arg( array( 'action' => 'install-plugin', 'plugin' => $plugin['slug'] ), 'update.php' );
				if ( isset( $_GET['from'] ) )
					$url .= add_query_arg( 'from', urlencode( stripslashes( $_GET['from'] ) ), $url );

					$url = esc_url_raw( $url );

				$nonce = 'install-plugin_' . $plugin['slug'];

				/** Prefix a default path to pre-packaged plugins */
				$source = ( 'upload' == $type ) ? $this->default_path . $plugin['source'] : $plugin['source'];
				//$source = $plugin['source'];

				/** Create a new instance of Plugin_Upgrader */
				$title = $this->first_activation ? '' : $title;
				$first_activation = $this->first_activation;
				$skin = new Runway_Plugin_Installer_Skin( compact( 'type', 'title', 'url', 'nonce', 'plugin', 'api', 'first_activation' ) );
				$upgrader = new Plugin_Upgrader( $skin );

				/** Perform the action and install the plugin from the $source urldecode() */
				if( ! $this->first_activation )
					$upgrader->install( $source );
				else {
					$upgrader->init();
					$upgrader->install_strings();
						$skin->upgrader->strings['downloading_package'] = ' ';
						$skin->upgrader->strings['unpack_package'] = ' ';
						$skin->upgrader->strings['installing_package'] = '';
						$skin->upgrader->strings['process_success'] = '';

					add_filter('upgrader_source_selection', array($upgrader, 'check_package') );
					add_action( 'upgrader_process_complete', 'wp_clean_plugins_cache', 9, 0 );

					$upgrader->run( array(
						'package' => $source,
						'destination' => WP_PLUGIN_DIR,
						'clear_destination' => false, // Do not overwrite files.
						'clear_working' => true,
						'hook_extra' => array(
							'type' => 'plugin',
							'action' => 'install',
						)
					) );

					remove_action( 'upgrader_process_complete', 'wp_clean_plugins_cache', 9 );
					remove_filter('upgrader_source_selection', array($upgrader, 'check_package') );
				}

				/** Flush plugins cache so we can make sure that the installed plugins list is always up to date */
				wp_cache_flush();

				/** Only activate plugins if the config option is set to true */
				if ( $this->is_automatic ) {
					$plugin_activate = $upgrader->plugin_info(); // Grab the plugin info from the Plugin_Upgrader method

					$activate = activate_plugin( $plugin_activate ); // Activate the plugin

					$this->populate_file_path(); // Re-populate the file path now that the plugin has been installed and activated

					if ( is_wp_error( $activate ) ) {
						echo '<div id="message" class="error"><p>' . rf__($activate->get_error_message()) . '</p></div>';
						echo '<p><a href="' . esc_url( add_query_arg( 'page', $this->menu, admin_url( $this->parent_url_slug ) ) ). '" title="' . esc_attr( $this->strings['return'] ) . '" target="_parent">' . __( 'Return to Required Plugins Installer', 'runway' ) . '</a></p>';
						return true; // End it here if there is an error with automatic activation
					}
					else {
						echo '<p>' . rf__($this->strings['plugin_activated']) . '</p>';
					}
				}

				/** Display message based on if all plugins are now active or not */
				$complete = array();
				foreach ( $this->plugins as $plugin ) {
					if ( ! is_plugin_active( $plugin['file_path'] ) && ! $this->first_activation ) {
						echo '<p><a href="' . esc_url( add_query_arg( 'page', $this->menu, admin_url( $this->parent_url_slug ) ) ) . '" title="' . esc_attr( $this->strings['return'] ) . '" target="_parent">' . rf__($this->strings['return']) . '</a></p>';
						$complete[] = $plugin;
						break;
					}
					/** Nothing to store */
					else {
						$complete[] = '';
					}
				}

				/** Filter out any empty entries */
				$complete = array_filter( $complete );

				/** All plugins are active, so we display the complete string and hide the plugin menu */
				if ( empty( $complete ) && ! $this->first_activation ) {
					echo '<p>' .  sprintf( $this->strings['complete'], '<a href="' . esc_url( admin_url() ). '" title="' . esc_attr( __( 'Return to the Dashboard', 'runway' ) ) . '">' . __( 'Return to the Dashboard', 'runway' ) . '</a>' ) . '</p>';
					//echo '<style type="text/css">#adminmenu .wp-submenu li.current { display: none !important; }</style>';
				}

				if ( self::plugin_has_to_be_activated_after_installation( $plugin['slug'] ) ) {
					$this->do_plugin_activate( $themePlugin_info );
				}

				return true;
			}
			/** Checks for actions from hover links to process the activation */
			elseif ( isset( $_GET[sanitize_key( 'plugin' )] ) && ( isset( $_GET[sanitize_key( 'tgmpa-activate' )] ) && 'activate-plugin' == $_GET[sanitize_key( 'tgmpa-activate' )] ) ) {

				check_admin_referer( 'tgmpa-activate', 'tgmpa-activate-nonce' );

				$this->do_plugin_activate( $themePlugin_info );
			}

			return false;

		}

		/**
		 * Check if plugin has to be activated after installation
		 *
		 * @param string $plugin_slug
		 *
		 * @return bool
		 */
		public static function plugin_has_to_be_activated_after_installation( $plugin_slug ) {

			global $themePlugins;

			if ( array_key_exists( $plugin_slug, $themePlugins )
			     && filter_var( $themePlugins[ $plugin_slug ]['required'], FILTER_VALIDATE_BOOLEAN )
			) {
				return true;
			} else {
				return false;
			}

		}

		public function do_plugin_activate( $themePlugin_info = array() ) {
			global $themePlugins;

			if( $this->first_activation ) {
				$plugin['name']   = $themePlugin_info['name']; // Plugin name
				$plugin['slug']   = $themePlugin_info['slug']; // Plugin slug
				$plugin['source'] = $themePlugin_info['source']; // Plugin source
			} else {
				//check_admin_referer( 'tgmpa-install' );

				$plugin['name']   = $_GET[sanitize_key( 'plugin_name' )]; // Plugin name
				$plugin['slug']   = $_GET[sanitize_key( 'plugin' )]; // Plugin slug
				$plugin['source'] = $_GET[sanitize_key( 'plugin_source' )]; // Plugin source
			}

			$plugin_data = get_plugins( '/' . $plugin['slug'] ); // Retrieve all plugins
			$plugin['slug'] = $this->first_activation ? $plugin['slug'].'/'.key($plugin_data) : $plugin['slug'];
			$plugin_file = array_keys( $plugin_data ); // Retrieve all plugin files from installed plugins
			$plugin_to_activate = $plugin['slug'];  // Match plugin slug with appropriate plugin file

			$activate = activate_plugin( $plugin_to_activate ); // Activate the plugin

			if ( is_wp_error( $activate ) && ! $this->first_activation ) {
				echo '<div id="message" class="error"><p>' . rf__($activate->get_error_message()) . '</p></div>';
				echo '<p><a href="' . esc_url(  add_query_arg( 'page', $this->menu, admin_url( $this->parent_url_slug ) ) ) . '" title="' . esc_attr( $this->strings['return'] ) . '" target="_parent">' . rf__($this->strings['return']) . '</a></p>';
				return true; // End it here if there is an error with activation
			}
			else {
				/** Make sure message doesn't display again if bulk activation is performed immediately after a single activation */
				if ( ! isset( $_POST[sanitize_key( 'action' )] ) && ! $this->first_activation ) {
					$msg = sprintf( __( 'The following plugin was installed and activated successfully: %s.', 'runway' ), '<strong>' . $plugin['name'] . '</strong>' );
					echo '<div id="message" class="updated"><p>' . $msg . '</p></div>';
				}
			}
			return true;
		}

		/**
		 * Echoes required plugin notice.
		 *
		 * Outputs a message telling users that a specific plugin is required for
		 * their theme. If appropriate, it includes a link to the form page where
		 * users can install and activate the plugin.
		 *
		 * @since 1.0.0
		 *
		 * @global object $current_screen
		 * @return null Returns early if we're on the Install page
		 */
		public function notices() {
			global $themePlugins;
			global $current_screen;

			/** Remove nag on the install page */
			//if ( $this->is_tgmpa_page() )
			//	return;

			$installed_plugins = get_plugins(); // Retrieve a list of all the plugins
			$this->plugins = ( isset( $themePlugins ) ) ? $themePlugins : array();
			$this->populate_file_path();

			$message              = array(); // Store the messages in an array to be outputted after plugins have looped through
			$install_link         = false; // Set to false, change to true in loop if conditions exist, used for action link 'install'
			$install_link_count   = 0; // Used to determine plurality of install action link text
			$activate_link        = false; // Set to false, change to true in loop if conditions exist, used for action link 'activate'
			$activate_link_count  = 0; // Used to determine plurality of activate action link text

			foreach ( $this->plugins as $plugin ) {
				/** If the plugin is installed and active, check for minimum version argument before moving forward */
				if ( is_plugin_active( $plugin['file_path'] ) ) {
					/** A minimum version has been specified */
					if ( isset( $plugin['version'] ) ) {
						if ( isset( $installed_plugins[$plugin['file_path']]['Version'] ) ) {
							/** If the current version is less than the minimum required version, we display a message */
							if ( version_compare( $installed_plugins[$plugin['file_path']]['Version'], $plugin['version'], '<' ) ) {
								if ( current_user_can( 'install_plugins' ) )
									$message['notice_ask_to_update'][] = $plugin['name'];
								else
									$message['notice_cannot_update'][] = $plugin['name'];
							}
						}
						/** Can't find the plugin, so iterate to the next condition */
						else {
							continue;
						}
					}
					/** No minimum version specified, so iterate over the plugin */
					else {
						continue;
					}
				}

				/** Not installed */
				if ( ! isset( $installed_plugins[$plugin['file_path']] ) ) {
					$has_required = false; // keeps track of any required pluings listed
					$has_recommended = false; // keeps track of recommended plugins
					$install_link = true; // We need to display the 'install' action link
					$install_link_count++; // Increment the install link count
					if ( current_user_can( 'install_plugins' ) ) {
						if ( $plugin['required'] == 'true' ) {
							$message['notice_can_install_required'][] = $plugin['name'];
							$has_required = true;
						}
						/** This plugin is only recommended */
						else {
							$message['notice_can_install_recommended'][] = $plugin['name'];
							$has_recommended = true;
						}
					}
					/** Need higher privileges to install the plugin */
					else {
						//$message['notice_cannot_install'][] = $plugin['name'];
					}
				}
				/** Installed but not active */
				elseif ( is_plugin_inactive( $plugin['file_path'] ) ) {
					$has_required = false; // keeps track of any required pluings listed
					$has_recommended = false; // keeps track of recommended plugins
					$activate_link = true; // We need to display the 'activate' action link
					$activate_link_count++; // Increment the activate link count
					if ( current_user_can( 'activate_plugins' ) ) {
						if ( ( isset( $plugin['required'] ) ) && ( $plugin['required'] == 'true' ) ) {
							$message['notice_can_activate_required'][] = $plugin['name'];
							$has_required = true;
						}
						/** This plugin is only recommended */
						else {
							$message['notice_can_activate_recommended'][] = $plugin['name'];
							$has_recommended = true;
						}
					}
					/** Need higher privileges to activate the plugin */
					else {
						//$message['notice_cannot_activate'][] = $plugin['name'];
					}
				}
			}

			/** Only process the nag messages if the user has not dismissed them already */
			if ( ! get_user_meta( get_current_user_id(), 'tgmpa_dismissed_notice', true ) ) {
				/** If we have notices to display, we move forward */
				if ( ! empty( $message ) ) {
					krsort( $message ); // Sort messages
					$rendered = ''; // Display all nag messages as strings

					/** Grab all plugin names */
					foreach ( $message as $type => $plugin_groups ) {
						$linked_plugin_groups = array();

						/** Count number of plugins in each message group to calculate singular/plural message */
						$count = count( $plugin_groups );

						/** Loop through the plugin names to make the ones pulled from the .org repo linked */
						foreach ( $plugin_groups as $plugin_group_single_name ) {
							$external_url = $this->_get_plugin_data_from_name( $plugin_group_single_name, 'external_url' );
							$source = $this->_get_plugin_data_from_name( $plugin_group_single_name, 'source' );

							if ( $external_url && preg_match( '|^http(s)?://|', $external_url ) ) {
								$linked_plugin_groups[] = '<a href="' . esc_url( $external_url ) . '" title="' . esc_attr( rf__($plugin_group_single_name) ) . '" target="_blank">' . rf__($plugin_group_single_name) . '</a>';
							}
							// elseif ( ! $source || preg_match( '|^http://wordpress.org/extend/plugins/|', $source ) ) {
							// 	$url = add_query_arg(
							// 		array(
							// 			'tab'       => 'plugin-information',
							// 			'plugin'    => $this->_get_plugin_data_from_name( $plugin_group_single_name ),
							// 			'TB_iframe' => 'true',
							// 			'width'     => '640',
							// 			'height'    => '500',
							// 		),
							// 		admin_url( 'plugin-install.php' )
							// 	);

							// 	$linked_plugin_groups[] = '<a href="' . esc_url( $url ) . '" class="thickbox" title="' . esc_attr( rf__($plugin_group_single_name) ) . '">' . rf__($plugin_group_single_name) . '</a>';
							// }
							else {
								$linked_plugin_groups[] = $plugin_group_single_name; // No hyperlink
							}

							if ( isset( $linked_plugin_groups ) && (array) $linked_plugin_groups )
								$plugin_groups = $linked_plugin_groups;
						}

						/** Setup variables to determine if action links are needed */

						$show_install_link  = $install_link ? '<a href="' . esc_url( add_query_arg( 'page', $this->menu, admin_url( $this->parent_url_general_slug ) ) ) . '">' . translate_nooped_plural( $this->strings['install_link'], $install_link_count, 'runway' ) . '</a>' : '';
						$show_activate_link = $activate_link ? '<a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">' . translate_nooped_plural( $this->strings['activate_link'], $activate_link_count, 'runway' ) . '</a>'  : '';

						/** Define all of the action links */
						$action_links = apply_filters(
							'tgmpa_notice_action_links',
							array(
								'install'  => ( current_user_can( 'install_plugins' ) ) ? $show_install_link : '',
								'activate' => ( current_user_can( 'activate_plugins' ) ) ? $show_activate_link : '',
								'dismiss'  => '<a class="dismiss-notice" href="' . esc_url( wp_nonce_url( add_query_arg( 'tgmpa-dismiss', 'dismiss_admin_notices' ), 'tgmpa-dismiss-' . get_current_user_id() ) ) . '" target="_parent">' . __( 'Dismiss this notice', 'runway' ) . '</a>',
							)
						);
						$action_links = array_filter( $action_links ); // Remove any empty array items

						$last_plugin = array_pop( $plugin_groups ); // Pop off last name to prep for readability

						if($type == "notice_can_install_required")
							$imploded    =  empty($plugin_groups)? '<em>' . $last_plugin . '</em>'  : '<em>' . ( implode( ', ', $plugin_groups ) . '</em> and <em>' . $last_plugin . '</em>');
						else
							$imploded    =  empty($plugin_groups)? '<em>' . $last_plugin . '</em>' : '<em>' .  implode( ', ', $plugin_groups ) . '</em> and <em>' . $last_plugin . '</em>';

						$rendered .= '<p>' . sprintf( translate_nooped_plural( $this->strings[$type], $count, 'runway' ), $imploded, $count ) . '</p>'; // All messages now stored
					}



					// Action Links to add
					if ( isset($action_links) && !empty($action_links) ) {
						// Dismiss link?
						if ( isset($has_recommended) && $has_recommended && (!isset($hasRequired) || !$hasRequired) ){
							$dismissLink = $action_links['dismiss'] . ' &nbsp; ';
						} else {
							$dismissLink = '';
						}
						// Activate link?
						if ( isset($activate_link) && $activate_link ){
							$activateLink = $action_links['activate'] . ' &nbsp; ';
						} else {
							$activateLink = '';
						}
						// Install link?
						if ( isset($install_link) && $install_link ){
							$installLink = $action_links['install'] . ' &nbsp; ';
						} else {
							$installLink = '';
						}

						$rendered .= '<p>' . $installLink . $activateLink . $dismissLink .'</p>';
					}

					/** Register the nag messages and prepare them to be processed */
					$this->strings['nag_type'] = 'updated';
               		if ( isset( $this->strings['nag_type'] ) )
						add_settings_error( 'tgmpa', 'tgmpa', $rendered, sanitize_html_class( strtolower( $this->strings['nag_type'] ), 'updated' ) );
					else
						add_settings_error( 'tgmpa', 'tgmpa', $rendered, 'updated' );
				}
			}

			/** Admin options pages already output settings_errors, so this is to avoid duplication */
			if ( 'options-general' !== $current_screen->parent_base )
				settings_errors( 'tgmpa' );

		}

		/**
		 * Add dismissable admin notices.
		 *
		 * Appends a link to the admin nag messages. If clicked, the admin notice disappears and no longer is visible to users.
		 *
		 * @since 2.1.0
		 */
		public function dismiss() {

			if ( isset( $_GET[ 'tgmpa-dismiss' ] ) && check_admin_referer( 'tgmpa-dismiss-' . get_current_user_id() ) )
				update_user_meta( get_current_user_id(), 'tgmpa_dismissed_notice', 1 );

		}

		/**
		 * Add individual plugin to our collection of plugins.
		 *
		 * If the required keys are not set, the plugin is not added.
		 *
		 * @since 2.0.0
		 *
		 * @param array $plugin Array of plugin arguments.
		 */
		public function register( $plugin ) {

			if ( ! isset( $plugin['slug'] ) || ! isset( $plugin['name'] ) )
				return;

			$this->plugins[] = $plugin;

		}

		/**
		 * Amend default configuration settings.
		 *
		 * @since 2.0.0
		 *
		 * @param array $config
		 */
		public function config( $config ) {

			$keys = array( 'default_path', 'parent_menu_slug', 'parent_url_slug', 'domain', 'has_notices', 'menu', 'is_automatic', 'message', 'strings' );

			foreach ( $keys as $key ) {
				if ( isset( $config[$key] ) ) {
					if ( is_array( $config[$key] ) ) {
						foreach ( $config[$key] as $subkey => $value )
							$this->{$key}[$subkey] = $value;
					} else {
						$this->$key = $config[$key];
					}
				}
			}

		}

		/**
		 * Amend action link after plugin installation.
		 *
		 * @since 2.0.0
		 *
		 * @param array $install_actions Existing array of actions
		 * @return array Amended array of actions
		 */
		public function actions( $install_actions ) {

			/** Remove action links on the TGMPA install page */
			if ( $this->is_tgmpa_page() )
				return false;

			return $install_actions;

		}

		/**
		 * Set file_path key for each installed plugin.
		 *
		 * @since 2.1.0
		 */
		public function populate_file_path() {

			/** Add file_path key for all plugins */
			foreach ( $this->plugins as $plugin => $values )
				$this->plugins[$plugin]['file_path'] = $this->_get_plugin_basename_from_slug( $values['slug'] );

		}

		/**
		 * Helper function to extract the file path of the plugin file from the
		 * plugin slug, if the plugin is installed.
		 *
		 * @since 2.0.0
		 *
		 * @param string $slug Plugin slug (typically folder name) as provided by the developer
		 * @return string Either file path for plugin if installed, or just the plugin slug
		 */
		protected function _get_plugin_basename_from_slug( $slug ) {

			$keys = array_keys( get_plugins() );

			foreach ( $keys as $key ) {
				if ( preg_match( '|^' . $slug .'|', $key ) )
					return $key;
			}

			return $slug;

		}

		/**
		 * Retrieve plugin data, given the plugin name.
		 *
		 * Loops through the registered plugins looking for $name. If it finds it,
		 * it returns the $data from that plugin. Otherwise, returns false.
		 *
		 * @since 2.1.0
		 *
		 * @param string $name Name of the plugin, as it was registered
		 * @param string $data Optional. Array key of plugin data to return. Default is slug
		 * @return string|boolean Plugin slug if found, false otherwise.
		 */
		protected function _get_plugin_data_from_name( $name, $data = 'slug' ) {

			foreach ( $this->plugins as $plugin => $values ) {
				if ( $name == $values['name'] && isset( $values[$data] ) )
					return $values[$data];
			}

			return false;

		}

		/**
		 * Determine if we're on the TGMPA Install page.
		 *
		 * We use $current_screen when it is available, and a slightly less ideal
		 * conditional when it isn't (like when displaying the plugin information
		 * thickbox).
		 *
		 * @since 2.1.0
		 *
		 * @global object $current_screen
		 * @return boolean True when on the TGMPA page, false otherwise.
		 */
		protected function is_tgmpa_page() {

			global $current_screen;

			if ( ! is_null( $current_screen ) && $this->parent_menu_slug == $current_screen->parent_file && isset( $_GET['page'] ) && $this->menu === $_GET['page'] )
				return true;

			if ( isset( $_GET['page'] ) && $this->menu === $_GET['page'] )
				return true;

			return false;

		}

		/**
		 * Delete dismissable nag option when theme is switched.
		 *
		 * This ensures that the user is again reminded via nag of required
		 * and/or recommended plugins if they re-activate the theme.
		 *
		 * @since 2.1.1
		 */
		public function update_dismiss() {

			delete_user_meta( get_current_user_id(), 'tgmpa_dismissed_notice' );

		}

		/**
		 * Forces plugin activation if the parameter 'force_activation' is
		 * set to true.
		 *
		 * This allows theme authors to specify certain plugins that must be
		 * active at all times while using the current theme.
		 *
		 * Please take special care when using this parameter as it has the
		 * potential to be harmful if not used correctly. Setting this parameter
		 * to true will not allow the specified plugin to be deactivated unless
		 * the user switches themes.
		 *
		 * @since 2.2.0
		 */
		public function force_activation() {

			/** Set file_path parameter for any installed plugins */
			$this->populate_file_path();

			$installed_plugins = get_plugins();

			foreach ( $this->plugins as $plugin ) {
				/** Oops, plugin isn't there so iterate to next condition */
				if ( isset( $plugin['force_activation'] ) && $plugin['force_activation'] && ! isset( $installed_plugins[$plugin['file_path']] ) )
					continue;
				/** There we go, activate the plugin */
				elseif ( isset( $plugin['force_activation'] ) && $plugin['force_activation'] && is_plugin_inactive( $plugin['file_path'] ) )
					activate_plugin( $plugin['file_path'] );
			}

		}

		/**
		 * Forces plugin deactivation if the parameter 'force_deactivation'
		 * is set to true.
		 *
		 * This allows theme authors to specify certain plugins that must be
		 * deactived upon switching from the current theme to another.
		 *
		 * Please take special care when using this parameter as it has the
		 * potential to be harmful if not used correctly.
		 *
		 * @since 2.2.0
		 */
		public function force_deactivation() {

			/** Set file_path parameter for any installed plugins */
			$this->populate_file_path();

			foreach ( $this->plugins as $plugin ) {
				/** Only proceed forward if the paramter is set to true and plugin is active */
				if ( isset( $plugin['force_deactivation'] ) && $plugin['force_deactivation'] && is_plugin_active( $plugin['file_path'] ) )
					deactivate_plugins( $plugin['file_path'] );
			}

		}

		public function set_first_activation() {
			$this->first_activation = true;
			$this->strings['return'] = '';
		}

	}
}

/** Create a new instance of the class */
new Runway_Plugin_Installer;

if ( ! function_exists( 'tgmpa' ) ) {
	/**
	 * Helper function to register a collection of required plugins.
	 *
	 * @since 2.0.0
	 * @api
	 *
	 * @param array $plugins An array of plugin arrays
	 * @param array $config Optional. An array of configuration values
	 */
	function tgmpa( $plugins, $config = array() ) {
		foreach ( $plugins as $plugin )
			Runway_Plugin_Installer::$instance->register( $plugin );

		if ( $config )
			Runway_Plugin_Installer::$instance->config( $config );
	}
}

/**
 * WP_List_Table isn't always available. If it isn't available,
 * we load it here.
 *
 * @since 2.2.0
 */
if ( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

if ( ! class_exists( 'TGMPA_List_Table' ) ) {
	/**
	 * List table class for handling plugins.
	 *
	 * Extends the WP_List_Table class to provide a future-compatible
	 * way of listing out all required/recommended plugins.
	 *
	 * Gives users an interface similar to the Plugin Administration
	 * area with similar (albeit stripped down) capabilities.
	 *
	 * This class also allows for the bulk install of plugins.
	 *
	 * @since 2.2.0
	 *
	 * @package Runway-Plugin-Installer
	 * @author Thomas Griffin <thomas@thomasgriffinmedia.com>
	 * @author Gary Jones <gamajo@gamajo.com>
	 */
	class TGMPA_List_Table extends WP_List_Table {

		/**
		 * References parent constructor and sets defaults for class.
		 *
		 * The constructor also grabs a copy of $instance from the TGMPA class
		 * and stores it in the global object Runway_Plugin_Installer::$instance.
		 *
		 * @since 2.2.0
		 *
		 * @global unknown $status
		 * @global string $page
		 */
		public function __construct() {

			global $status, $page;

			parent::__construct(
				array(
					'singular' => 'plugin',
					'plural'   => 'plugins',
					'ajax'     => false,
				)
			);

		}

		/**
		 * Gathers and renames all of our plugin information to be used by
		 * WP_List_Table to create our table.
		 *
		 * @since 2.2.0
		 *
		 * @return array $table_data Information for use in table
		 */
		protected function _gather_plugin_data() {
			global $themePlugins;

			/** Load thickbox for plugin links */
			Runway_Plugin_Installer::$instance->admin_init();
			Runway_Plugin_Installer::$instance->thickbox();

			/** Prep variables for use and grab list of all installed plugins */
			$table_data = array();
			$i = 0;
			$installed_plugins = get_plugins();

//			foreach ( Runway_Plugin_Installer::$instance->plugins as $plugin ) {
			if ( is_array($themePlugins) ) {
            	foreach ( $themePlugins as $plugin ) {

					//if ( is_plugin_active( $plugin['file_path'] ) )
						//continue; // No need to display plugins if they are installed and activated

					$table_data[$i]['sanitized_plugin'] = $plugin['name'];
					$table_data[$i]['slug'] = $this->_get_plugin_data_from_name( $plugin['name'] );

					$external_url = $this->_get_plugin_data_from_name( $plugin['name'], 'external_url' );
					$source = $this->_get_plugin_data_from_name( $plugin['name'], 'source' );

					if ( $external_url && preg_match( '|^http(s)?://|', $external_url ) ) {
						$table_data[$i]['plugin'] = '<strong><a href="' . esc_url( $external_url ) . '" title="' . esc_attr( rf__($plugin['name']) ) . '" target="_blank">' . rf__($plugin['name']) . '</a></strong>';
					}
					// elseif ( ! $source || preg_match( '|^http://wordpress.org/extend/plugins/|', $source ) ) {
					// 	$url = add_query_arg(
					// 		array(
					// 			'tab'       => 'plugin-information',
					// 			'plugin'    => $this->_get_plugin_data_from_name( $plugin['name'] ),
					// 			'TB_iframe' => 'true',
					// 			'width'     => '640',
					// 			'height'    => '500',
					// 		),
					// 		admin_url( 'plugin-install.php' )
					// 	);

					// 	$table_data[$i]['plugin'] = '<strong><a href="' . esc_url( $url ) . '" class="thickbox" title="' . esc_attr( rf__($plugin['name']) ) . '">' . rf__($plugin['name']) . '</a></strong>';
					// }
					else {
						$table_data[$i]['plugin'] = '<strong>' . rf__($plugin['name']) . '</strong>'; // No hyperlink
					}

					if ( isset( $table_data[$i]['plugin'] ) && (array) $table_data[$i]['plugin'] )
						$plugin['name'] = $table_data[$i]['plugin'];

					if ( isset( $plugin['external_url'] ) ) {
						/** The plugin is linked to an external source */
						$table_data[$i]['source'] = __( 'External Link', 'runway' );
					}
					elseif ( isset( $plugin['source'] ) ) {
						/** The plugin must be from a private repository */
						if ( preg_match( '|^http(s)?://|', $plugin['source'] ) )
							$table_data[$i]['source'] = __( 'Private Repository', 'runway' );
						/** The plugin is pre-packaged with the theme */
						else
							$table_data[$i]['source'] = __( 'Pre-Packaged', 'runway' );
					}
					/** The plugin is from the WordPress repository */
					else {
						$table_data[$i]['source'] = __( 'WordPress Repository', 'runway' );
					}

					$table_data[$i]['type'] = $plugin['required'] == 'true' ? __( 'Required', 'runway' ) : __( 'Recommended', 'runway' );

					if ( ! isset( $installed_plugins[$plugin['file_path']] ) )
						$table_data[$i]['status'] = sprintf( '%1$s', __( 'Not Installed', 'runway' ) );
					elseif ( is_plugin_inactive( $plugin['file_path'] ) )
						$table_data[$i]['status'] = sprintf( '%1$s', __( 'Installed But Not Activated', 'runway' ) );
					else
						$table_data[$i]['status'] = sprintf( '%1$s', __( 'Installed And Activated', 'runway' ) );

					$table_data[$i]['file_path'] = $plugin['file_path'];
					$table_data[$i]['url'] = isset( $plugin['source'] ) ? $plugin['source'] : 'repo';

					$i++;
				}
			}

			/** Sort plugins by Required/Recommended type and by alphabetical listing within each type */
			$resort = array();
			$req = array();
			$rec = array();

			/** Grab all the plugin types */
			foreach ( $table_data as $plugin )
				$resort[] = $plugin['type'];

			/** Sort each plugin by type */
			foreach ( $resort as $type )
				if ( 'Required' == $type )
					$req[] = $type;
				else
					$rec[] = $type;

			/** Sort alphabetically each plugin type array, merge them and then sort in reverse	(lists Required plugins first) */
			sort( $req );
			sort( $rec );
			array_merge( $resort, $req, $rec );
			array_multisort( $resort, SORT_DESC, $table_data );

			return $table_data;

		}

		/**
		 * Retrieve plugin data, given the plugin name. Taken from the
		 * Runway_Plugin_Installer class.
		 *
		 * Loops through the registered plugins looking for $name. If it finds it,
		 * it returns the $data from that plugin. Otherwise, returns false.
		 *
		 * @since 2.2.0
		 *
		 * @param string $name Name of the plugin, as it was registered
		 * @param string $data Optional. Array key of plugin data to return. Default is slug
		 * @return string|boolean Plugin slug if found, false otherwise
		 */
		protected function _get_plugin_data_from_name( $name, $data = 'slug' ) {
			global $themePlugins;
			foreach ( $themePlugins as $plugin => $values ) {
//			foreach ( Runway_Plugin_Installer::$instance->plugins as $plugin => $values ) {
				if ( $name == $values['name'] && isset( $values[$data] ) )
					return $values[$data];
			}

			return false;

		}

		/**
		 * Create default columns to display important plugin information
		 * like type, action and status.
		 *
		 * @since 2.2.0
		 *
		 * @param array $item
		 * @param string $column_name
		 */
		public function column_default( $item, $column_name ) {

			switch ( $column_name ) {
				case 'source':
				case 'type':
				case 'status':
					return $item[$column_name];
			}

		}

		/**
		 * Create default title column along with action links of 'Install'
		 * and 'Activate'.
		 *
		 * @since 2.2.0
		 *
		 * @param array $item
		 * @return string The action hover links
		 */
		public function column_plugin( $item ) {
			global $themePlugins;

			$installed_plugins = get_plugins();

			/** No need to display any hover links */
			if ( is_plugin_active( $item['file_path'] ) )
				$actions = array();
			/** We need to display the 'Install' hover link */
			if ( ! isset( $installed_plugins[$item['file_path']] ) ) {

				$actions = array(
					'install' => sprintf(
						'<a href="%1$s" title="'. __('Install', 'runway') .' %2$s">'. __('Install', 'runway') .'</a>',
						esc_url(
							wp_nonce_url(
								add_query_arg(
									array(
										'page'          => Runway_Plugin_Installer::$instance->menu,
										'action'		=> 'install',
										'plugin'        => $item['slug'],
										'plugin_name'   => $item['sanitized_plugin'],
										'plugin_source' => $item['url'],
										'tgmpa-install' => 'install-plugin',
									),
									admin_url( Runway_Plugin_Installer::$instance->parent_url_general_slug )
								),
								'tgmpa-install'
							)
						),
						$item['sanitized_plugin']
					),
				);

			}

			/** We need to display the 'Activate' hover link */
			elseif ( is_plugin_inactive( $item['file_path'] ) ) {

				$actions = array(
					'activate' => sprintf(
						'<a href="%1$s" title="'. __('Activate', 'runway') .' %2$s">'. __('Activate', 'runway') .'</a>',
						esc_url(
							add_query_arg(
								array(
									'page'                 => Runway_Plugin_Installer::$instance->menu,
									'action'  			   => 'activate',
									'plugin'               => $item['slug'],
									'plugin_name'          => $item['sanitized_plugin'],
									'plugin_source'        => $item['url'],
									'tgmpa-activate'       => 'activate-plugin',
									'tgmpa-activate-nonce' => wp_create_nonce( 'tgmpa-activate' ), 							),
								admin_url( Runway_Plugin_Installer::$instance->parent_url_general_slug )
							)
						),
						$item['sanitized_plugin']
					),
				);
			}

			else {
				if (IS_CHILD) {
					$actions = array(
						'' => sprintf(
							' <a href="%1$s" title="'. __('Delete from the list', 'runway') .' %2$s">'. __('Delete from the list', 'runway') .'</a>',
							esc_url(
								add_query_arg(
									array(
										'page'                 => Runway_Plugin_Installer::$instance->menu,
										'action'  			   => 'delete-from-list',
										'plugin'               => $item['slug'],
										'plugin_name'          => $item['sanitized_plugin'],
										'plugin_source'        => $item['url'],
										'tgmpa-activate'       => 'activate-plugin',
										'tgmpa-activate-nonce' => wp_create_nonce( 'tgmpa-activate' ),
									),
									admin_url( Runway_Plugin_Installer::$instance->parent_url_general_slug )
								)
							),
							$item['sanitized_plugin']
						),
					);
				} else {
					$actions = array();
				}
			}
			return sprintf( '%1$s %2$s', $item['plugin'], $this->row_actions( $actions ) );

		}

		/**
		 * Required for bulk installing.
		 *
		 * Adds a checkbox for each plugin.
		 *
		 * @since 2.2.0
		 *
		 * @param array $item
		 * @return string The input checkbox with all necessary info
		 */
		public function column_cb( $item ) {

			$value = $item['file_path'] . ',' . $item['url'] . ',' . $item['sanitized_plugin'];
			return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" id="%3$s" />', $this->_args['singular'], $value, $item['sanitized_plugin'] );

		}

		public function column_type($item) {
			global $themePlugins, $plugin_installer_admin;
			$plugin_options_saved = $plugin_installer_admin->get_options();

			$needs = array(
				'Required' => 'true',
				'Recommended' => 'false',
			);

			if (IS_CHILD) {

				$type = '<select class="wp-list-select" name="plugin_options[' . $item['sanitized_plugin'] . '][required]">';
				foreach ($needs as $key=>$value)
				{
					$selected = (isset($plugin_options_saved['plugin_options'][$item['sanitized_plugin']] )
						&& $value == $plugin_options_saved['plugin_options'][$item['sanitized_plugin']]['required']) ? ' selected="selected"' : '';
					$type .= '<option value="' . $value . '"' . $selected . '>' . $key . '</option>';
				}
				$type .= '</select>';
			} else {
				foreach ($needs as $key=>$value) {
					if (isset($plugin_options_saved['plugin_options'][$item['sanitized_plugin']] )
						&& $value == $plugin_options_saved['plugin_options'][$item['sanitized_plugin']]['required'])
						$type = $key;
				}
			}

			return $type;

		}

		public function column_action($item) {

			$button = '<input type="hidden" name="action_save[' . $item['sanitized_plugin'] . ']" />' .
				'<button type="submit" class="button-primary ajax-save">'.__('Save', 'runway').'</button>';
			return $button;

		}

		/**
		 * Sets default message within the plugins table if no plugins
		 * are left for interaction.
		 *
		 * Hides the menu item to prevent the user from clicking and
		 * getting a permissions error.
		 *
		 * @since 2.2.0
		 */
		public function no_items() {

			echo __( 'No plugins to install or activate.', 'runway') . '<a href="'. esc_url( admin_url() ) .'" title="'. esc_attr( $title ) .'">'. __('Return to the Dashboard', 'runway') .'</a>';
		}

		/**
		 * Output all the column information within the table.
		 *
		 * @since 2.2.0
		 *
		 * @return array $columns The column names
		 */
		public function get_columns() {

			$columns = array(
				'cb'     => '<input type="checkbox" />',
				'plugin' => __( 'Plugin', 'runway' ),
				'source' => __( 'Source', 'runway' ),
				'type'   => __( 'Type', 'runway' ),
				'status' => __( 'Status', 'runway' ),
			);

			if (IS_CHILD) {
				$columns = array_merge(
					$columns,
					array(
						'action' => __( 'Action', 'runway' ),
					)
				);
			}

			return $columns;

		}

		/**
		 * Defines all types of bulk actions for handling
		 * registered plugins.
		 *
		 * @since 2.2.0
		 *
		 * @return array $actions The bulk actions for the plugin install table
		 */
		public function get_bulk_actions() {

			$actions = array(
				'tgmpa-bulk-install'  => __( 'Install', 'runway' ),
				'tgmpa-bulk-activate' => __( 'Activate', 'runway' ),
			);

			if(IS_CHILD)
				$actions['tgmpa-bulk-delete'] = __( 'Delete', 'runway' );

			return $actions;

		}

		/**
		 * Processes bulk installation and activation actions.
		 *
		 * The bulk installation process looks either for the $_POST
		 * information or for the plugin info within the $_GET variable if
		 * a user has to use WP_Filesystem to enter their credentials.
		 *
		 * @since 2.2.0
		 */
		public function process_bulk_actions() {

			/** Bulk installation process */
			if ( 'tgmpa-bulk-install' === $this->current_action() ) {
				check_admin_referer( 'bulk-' . $this->_args['plural'] );

				/** Prep variables to be populated */
				$plugins_to_install = array();
				$plugin_installs    = array();
				$plugin_path        = array();
				$plugin_name        = array();

				/** Look first to see if information has been passed via WP_Filesystem */
				if ( isset( $_GET[sanitize_key( 'plugins' )] ) )
					$plugins = explode( ',', stripslashes( $_GET[sanitize_key( 'plugins' )] ) );
				/** Looks like the user can use the direct method, take from $_POST */
				elseif ( isset( $_POST[sanitize_key( 'plugin' )] ) )
					$plugins = (array) $_POST[sanitize_key( 'plugin' )];
				/** Nothing has been submitted */
				else
					$plugins = array();

				$a = 0; // Incremental variable

				/** Grab information from $_POST if available */
				if ( isset( $_POST[sanitize_key( 'plugin' )] ) ) {
					foreach ( $plugins as $plugin_data ) {
						$plugins_to_install[] = explode( ',', $plugin_data );
						//$plugins_to_install[0][0] = '';
					}

					foreach ( $plugins_to_install as $plugin_data ) {
						$plugin_installs[] = '';
						$plugin_path[]     = $plugin_data[1];
						$plugin_name[]     = $plugin_data[2];
					}
				}
				/** Information has been passed via $_GET */
				else {
					foreach ( $plugins as $key => $value ) {
						/** Grab plugin slug for each plugin */
						if ( 0 == $key % 3 || 0 == $key ) {
							$plugins_to_install[] = $value;
							$plugin_installs[]    = $value;
						}
						$a++;
					}
				}

				/** Look first to see if information has been passed via WP_Filesystem */
				if ( isset( $_GET[sanitize_key( 'plugin_paths' )] ) )
					$plugin_paths = explode( ',', stripslashes( $_GET[sanitize_key( 'plugin_paths' )] ) );
				/** Looks like the user doesn't need to enter his FTP creds */
				elseif ( isset( $_POST[sanitize_key( 'plugin' )] ) )
					$plugin_paths = (array) $plugin_path;
				/** Nothing has been submitted */
				else
					$plugin_paths = array();

				/** Look first to see if information has been passed via WP_Filesystem */
				if ( isset( $_GET[sanitize_key( 'plugin_names' )] ) )
					$plugin_names = explode( ',', stripslashes( $_GET[sanitize_key( 'plugin_names' )] ) );
				/** Looks like the user doesn't need to enter his FTP creds */
				elseif ( isset( $_POST[sanitize_key( 'plugin' )] ) )
					$plugin_names = (array) $plugin_name;
				/** Nothing has been submitted */
				else
					$plugin_names = array();

				$b = 0; // Incremental variable

				/** Loop through plugin slugs and remove already installed plugins from the list */
				foreach ( $plugin_installs as $key => $plugin ) {
					if ( preg_match( '|.php$|', $plugin ) ) {
						unset( $plugin_installs[$key] );

						/** If the plugin path isn't in the $_GET variable, we can unset the corresponding path */
						if ( ! isset( $_GET[sanitize_key( 'plugin_paths' )] ) )
							unset( $plugin_paths[$b] );

						/** If the plugin name isn't in the $_GET variable, we can unset the corresponding name */
						if ( ! isset( $_GET[sanitize_key( 'plugin_names' )] ) )
							unset( $plugin_names[$b] );
					}
					$b++;
				}
				/** No need to proceed further if we have no plugins to install */
				if ( empty( $plugin_installs ) )
					return false;

				/** Reset array indexes in case we removed already installed plugins */
				$plugin_installs = array_values( $plugin_installs );
				$plugin_paths    = array_values( $plugin_paths );
				$plugin_names    = array_values( $plugin_names );

				/** If we grabbed our plugin info from $_GET, we need to decode it for use */
				$plugin_installs = array_map( 'urldecode', $plugin_installs );
				$plugin_paths    = array_map( 'urldecode', $plugin_paths );
				$plugin_names    = array_map( 'urldecode', $plugin_names );

				/** Pass all necessary information via URL if WP_Filesystem is needed */
				$url = wp_nonce_url(
					add_query_arg(
						array(
							'page' 			=> Runway_Plugin_Installer::$instance->menu,
							'tgmpa-action' 	=> 'install-selected',
							'plugins' 		=> urlencode( implode( ',', $plugins ) ),
							'plugin_paths' 	=> urlencode( implode( ',', $plugin_paths ) ),
							'plugin_names' 	=> urlencode( implode( ',', $plugin_names ) ),
						),
						admin_url( Runway_Plugin_Installer::$instance->parent_url_slug )
					),
					'bulk-plugins'
				);

				$method = ''; // Leave blank so WP_Filesystem can populate it as necessary
				$fields = array( sanitize_key( 'action' ), sanitize_key( '_wp_http_referer' ), sanitize_key( '_wpnonce' ) ); // Extra fields to pass to WP_Filesystem

				if ( false === ( $creds = request_filesystem_credentials( $url, $method, false, false, $fields ) ) )
					return true;

				if ( ! WP_Filesystem( $creds ) ) {
					request_filesystem_credentials( $url, $method, true, false, $fields ); // Setup WP_Filesystem
					return true;
				}

				require_once ABSPATH . 'wp-admin/includes/plugin-install.php'; // Need for plugins_api
				require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php'; // Need for upgrade classes
				require_once('class-plugin-installer-bulk.php');

				/** Store all information in arrays since we are processing a bulk installation */
				$api          = array();
				$sources      = array();
				$install_path = array();

				$c = 0; // Incremental variable

				/** Loop through each plugin to install and try to grab information from WordPress API, if not create 'tgmpa-empty' scalar */
				foreach ( $plugin_installs as $plugin ) {
					$api[$c] = plugins_api( 'plugin_information', array( 'slug' => $plugin, 'fields' => array( 'sections' => false ) ) ) ? plugins_api( 'plugin_information', array( 'slug' => $plugin, 'fields' => array( 'sections' => false ) ) ) : (object) $api[$c] = 'tgmpa-empty';
					$c++;
				}

				if ( is_wp_error( $api ) )
					wp_die( Runway_Plugin_Installer::$instance->strings['oops'] . var_dump( $api ) );

				$d = 0;	// Incremental variable

				/** Capture download links from $api or set install link to pre-packaged/private repo */
				foreach ( $api as $object ) {
					$sources[$d] = isset( $object->download_link ) && 'repo' == $plugin_paths[$d] ? $object->download_link : $plugin_paths[$d];
					$d++;
				}

				/** Finally, all the data is prepared to be sent to the installer */
				$url   = add_query_arg( array( 'page' => Runway_Plugin_Installer::$instance->menu ), admin_url( Runway_Plugin_Installer::$instance->parent_url_slug ) );
				$nonce = 'bulk-plugins';
				$names = $plugin_names;

				/** Create a new instance of TGM_Bulk_Installer */
				$installer = new Runway_Bulk_Installer( $skin = new Runway_Bulk_Installer_Skin( compact( 'url', 'nonce', 'names' ) ) );

				/** Wrap the install process with the appropriate HTML */

//				echo '<div class="tgmpa wrap">';
//					screen_icon( apply_filters( 'tgmpa_default_screen_icon', 'themes' ) );
//					echo '<h2>' . esc_html( get_admin_page_title() ) . '</h2>';
					/** Process the bulk installation submissions */

				$installer->bulk_install( $sources );
//				echo '</div>';

				$this->process_bulk_activate();

				$link = admin_url('options-general.php?page=plugin-installer');
				$redirect = '<script type="text/javascript">window.location = "'. esc_url_raw($link) .'";</script>';
				echo  $redirect;

				//return true;
			}

			/** Bulk activation process */
			if ( 'tgmpa-bulk-activate' === $this->current_action() ) {
				check_admin_referer( 'bulk-' . $this->_args['plural'] );

				$this->process_bulk_activate();

				$link = admin_url('options-general.php?page=plugin-installer');
				$redirect = '<script type="text/javascript">window.location = "'. esc_url_raw($link) .'";</script>';
				echo  $redirect;
//				return true;
			}

			/** Bulk delete process */
			if ( 'tgmpa-bulk-delete' === $this->current_action() ) {
				check_admin_referer( 'bulk-' . $this->_args['plural'] );

				$this->process_bulk_delete();

			    $link = admin_url('admin.php?page=plugin-installer');
			    $redirect = '<script type="text/javascript">window.location = "'. esc_url_raw($link).'";</script>';
				echo  $redirect;	// escaped above
			}
		}

		public function process_bulk_activate() {
			$plugins             = isset( $_POST[sanitize_key( 'plugin' )] ) ? (array) $_POST[sanitize_key( 'plugin' )] : array();
			$plugins_to_activate = array();

			/** Split plugin value into array with plugin file path, plugin source and plugin name */
			foreach ( $plugins as $i => $plugin )
				$plugins_to_activate[] = explode( ',', $plugin );

			foreach ( $plugins_to_activate as $i => $array ) {
				if ( ! preg_match( '|.php$|', $array[0] ) ) // Plugins that haven't been installed yet won't have the correct file path
					unset( $plugins_to_activate[$i] );
			}

			if ( 'tgmpa-bulk-install' === $this->current_action() ) {
				// activate only required plugins after installation process
				foreach ( $plugins_to_activate as $i => $array ) {
					if ( ! Runway_Plugin_Installer::plugin_has_to_be_activated_after_installation( $array[0] ) ) {
						unset( $plugins_to_activate[ $i ] );
					}
				}
			}

			/** Return early if there are no plugins to activate */
			if ( empty( $plugins_to_activate ) )
				return;

			$plugins      = array();
			$plugin_names = array();

			foreach ( $plugins_to_activate as $plugin_string ) {
				$plugins[] = $plugin_string[0];
				$plugin_names[] = $plugin_string[2];
			}

			$count = count( $plugin_names ); // Count so we can use _n function
			$last_plugin = array_pop( $plugin_names ); // Pop off last name to prep for readability
			$imploded    = empty( $plugin_names ) ? '<strong>' . $last_plugin . '</strong>' : '<strong>' . ( implode( ', ', $plugin_names ) . '</strong> and <strong>' . $last_plugin . '</strong>.' );

			/** Now we are good to go - let's start activating plugins */
			$activate = activate_plugins( $plugins );

			if ( is_wp_error( $activate ) )
				echo '<div id="message" class="error"><p>' . rf__($activate->get_error_message()) . '</p></div>';
			// else
			// 	printf( '<div id="message" class="updated"><p>%1$s %2$s</p></div>', _n( 'The following plugin was activated successfully:', 'The following plugins were activated successfully:', $count, 'runway' ), $imploded );

				/** Update recently activated plugins option */
			$recent = (array) get_option( 'recently_activated' );

			foreach ( $plugins as $plugin => $time )
				if ( isset( $recent[$plugin] ) )
					unset( $recent[$plugin] );

			update_option( 'recently_activated', $recent );

			unset( $_POST ); // Reset the $_POST variable in case user wants to perform one action after another
		}

		public function process_bulk_delete() {
			$plugins = isset( $_POST[sanitize_key( 'plugin' )] ) ? (array) $_POST[sanitize_key( 'plugin' )] : array();
			$plugins_to_delete   = array();

			/** Split plugin value into array with plugin file path, plugin source and plugin name */
			foreach ( $plugins as $i => $plugin )
				$plugins_to_delete[] = explode( ',', $plugin );

			foreach ( $plugins_to_delete as $i => $array ) {
				if ( ! preg_match( '|.php$|', $array[0] ) ) // Plugins that haven't been installed yet won't have the correct file path
					unset( $plugins_to_delete[$i] );
			}

			if ( !empty( $plugins_to_delete ) ) {
				foreach($plugins_to_delete as $value) {
					unlink($value[1]);
					$custom_php = str_replace('.zip', '-custom.php', $value[1]);
					if(file_exists($custom_php) )
						unlink($custom_php);
				}
			}

			unset( $_POST );
		}

		/**
		 * Prepares all of our information to be outputted into a usable table.
		 *
		 * @since 2.2.0
		 */
		public function prepare_items() {

			$per_page              = 100; // Set it high so we shouldn't have to worry about pagination
			$columns               = $this->get_columns(); // Get all necessary column information
			$hidden                = array(); // No columns to hide, but we must set as an array
			$sortable              = array(); // No reason to make sortable columns
			$this->_column_headers = array( $columns, $hidden, $sortable ); // Get all necessary column headers

			/** Process our bulk actions here */
			$this->process_bulk_actions();

			/** Store all of our plugin data into $items array so WP_List_Table can use it */
			$this->items = $this->_gather_plugin_data();

		}

	}
}


if ( ! class_exists( 'Runway_Plugin_Installer_Skin' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php'; // Need for upgrade classes
	class Runway_Plugin_Installer_Skin extends Plugin_Installer_Skin {
		public function before() {
			if ( !empty($this->api) ) {
				$this->upgrader->strings['process_success'] = $this->options['first_activation'] ? '' : sprintf( __('Successfully installed the plugin <strong>%s %s</strong>.'), $this->api->name, $this->api->version);
			}
		}
	}
}