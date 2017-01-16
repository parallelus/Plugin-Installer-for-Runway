<?php

class Plugin_Installer_Admin_Object extends Runway_Admin_Object {

	public $option_key;
	public $plugin_installer_options;

	public function __construct( $settings ) {

		parent::__construct( $settings );

		$this->option_key = $settings['option_key'];
		$this->init_options();

	}

	// Add hooks & crooks
	public function add_actions() {

		// Init action
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'init', array( $this, 'register_plugins_in_dir' ) );
		add_action( 'tgmpa_register', array( $this, 'required_theme_plugins' ) );
		//add_action( 'admin_notices', array($this, 'activate_pluging_first_time') );

	}

	public function activate_pluging_first_time() {

		global $shortname, $themePlugins;

		$option_key_activated_plugins = $shortname . 'plugin_installer_first_activation';
		$activated_plugins_option     = get_option( $option_key_activated_plugins );
		if ( empty( $activated_plugins_option ) ) {
			add_filter( 'install_plugin_complete_actions', '__return_false', 999 );
			$plugins      = get_option( $this->option_key );
			$plugin_names = array_keys( $plugins['plugin_options'] );

			$rpi_class = new Runway_Plugin_Installer;
			$rpi_class->set_first_activation();

			if ( isset( $themePlugins ) && is_array( $themePlugins ) ) {
				$is_activated = false;
				$plugins_list = array();

				foreach ( $themePlugins as $key => $val ) {
					$themePlugin_info = array();
					if ( in_array( $val['Name'], $plugin_names ) && ! file_exists( ABSPATH . 'wp-content/plugins/' . $key ) ) {

						if ( ! $is_activated ) {
							echo '<div class="updated first-activated"><p><img class="img-first-activated" src="' .
							     admin_url( 'images/spinner.gif' ) . '" style="vertical-align: middle;"" />&nbsp;&nbsp;' .
							     __( 'Please wait while we\'re preparing the theme for your WordPress install...', 'runway' ) .
							     '</p></div>';
							$is_activated = true;
						}

						$themePlugin_info['name']   = $val['name'];
						$themePlugin_info['slug']   = $key;
						$themePlugin_info['source'] = $val['source'];

						$rpi_class->do_plugin_install( $themePlugin_info );
						$plugins_list[] = $val['name'];
					}
				}

				if ( $is_activated ) {
					echo '<div class="updated"><p>' .
					     __( 'The following plugins were installed and activated successfully: ', 'runway' ) .
					     '<strong>' . implode( ', ', $plugins_list ) . '</strong>.' .
					     '</p></div>';
					?>

					<script type="text/javascript">
						jQuery(document).ready(function ($) {
							$('img.img-first-activated').remove();
							$('div.first-activated p').text('The theme is installed.');
						});
					</script>

				<?php }

			}

			update_option( $option_key_activated_plugins, 1 );
		}

	}

	public function init() {

		if ( isset( $_REQUEST['navigation'] ) && ! empty( $_REQUEST['navigation'] ) ) {
			global $plugin_installer_admin;
			$plugin_installer_admin->navigation = $_REQUEST['navigation'];

			if ( $plugin_installer_admin->navigation == 'add-plugin-by-url' ) {
				if ( empty( $_POST ) || ! wp_verify_nonce( $_POST['plugin-by-url-field'], 'plugin-by-url-action' ) ) {
					print __( 'Sorry, your nonce did not verify.', 'runway' );
					exit;
				}

				$parsed_url = parse_url( $_POST['plugin_url'] );

				if ( ! filter_var( $_POST['plugin_url'], FILTER_VALIDATE_URL ) || ! isset( $parsed_url['host'] ) || ( isset( $parsed_url['host'] ) && $parsed_url['host'] != 'wordpress.org' ) ) {
					$plugin_installer_admin->plugin_install_url_message = __( 'Enter valid url to plugin.', 'runway' );
				} else {
					$splitted_path = explode( '/', $parsed_url['path'] );
					if ( $splitted_path[ count( $splitted_path ) - 1 ] != '' ) {
						$plugin_slug = $splitted_path[ count( $splitted_path ) - 1 ];
					} else if ( isset( $splitted_path[ count( $splitted_path ) - 2 ] ) ) {
						$plugin_slug = $splitted_path[ count( $splitted_path ) - 2 ];
					} else {
						$plugin_slug                                        = "";
						$plugin_installer_admin->plugin_install_url_message = __( 'Enter valid url to plugin.', 'runway' );
					}

					if ( $plugin_slug != "" ) {
						$url                                                                    = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $plugin_slug ), 'install-plugin_' . $plugin_slug );
						$this->plugin_installer_options                                         = $plugin_installer_admin->get_options();
						$this->plugin_installer_options['plugin_wp_repository'][ $plugin_slug ] = array( 'slug' => $plugin_slug );
						$this->update_options();

						wp_redirect( str_replace( '&amp;', '&', $url ) );
						die();
					}
				}
			}
		}

	}

	public function load_objects() {
	}

	public function init_options() {

		$this->plugin_installer_options = $this->get_options();
		if ( isset( $this->plugin_installer_options ) ) {
			add_option( $this->option_key, array(
				'plugin_options' => array(),
			), '', 'yes' );
			$this->plugin_installer_options = $this->get_options();
		}

	}

	public function get_options() {

		return get_option( $this->option_key );

	}

	public function update_options() {

		$old_value = get_option( $this->option_key );
		if ( $old_value != $this->plugin_installer_options ) {
			update_option( $this->option_key, $this->plugin_installer_options );
		}

	}

	public function register_plugins_in_dir() {

		#-----------------------------------------------------------------
		# Register a plugin
		#-----------------------------------------------------------------
		global $themePlugins, $plugin_installer;
		$plugins_list = $plugin_installer->get_all_plugins_list();

		foreach ( $plugins_list as $plugin_key => $plugin_info ) {
			$plugin_name            = $plugin_info['Name'];
			$plugin_slug            = $plugin_info['slug'];
			$plugin_install_file    = isset( $plugin_info['file'] ) ? $plugin_info['file'] : '';
			$plugin_version         = isset( $plugin_info['Version'] ) ? $plugin_info['Version'] : '';
			$plugin_install_version = isset( $plugin_info['install_version'] ) ? $plugin_info['install_version'] : '';

			if ( strstr( $plugin_info['source'], RUNWAY_PLUGIN_INSTALLER_WP_REPOSITORY_URL ) !== false ) {
				$source = $plugin_info['source'];
			} else {
				if ( strstr( $plugin_info['source'], 'plugin-installer/plugins/' ) ) {
					$source = get_template_directory() . '/extensions/plugin-installer/plugins/' . $plugin_install_file;
				} else {
					$source = get_template_directory() . '/extensions/plugin-installer/extensions/' . $plugin_install_file;
				}
			}
			$required = 'true';

			if ( isset( $this->plugin_installer_options ) &&
			     isset( $this->plugin_installer_options['plugin_options'] )
			     && isset( $this->plugin_installer_options['plugin_options'][ $plugin_name ] )
			     && ! empty( $this->plugin_installer_options['plugin_options'][ $plugin_name ]['required'] )
			) {
				$required = $this->plugin_installer_options['plugin_options'][ $plugin_name ]['required'];
			} else {
				if ( IS_CHILD && ! isset( $this->plugin_installer_options['plugin_options'][ $plugin_name ]['required'] ) ) {
					$theme             = wp_get_theme( get_template() );
					$parent_shortname  = sanitize_title( $theme->get( 'Name' ) . '_' );
					$parent_option_key = $parent_shortname . 'plugin_installer';
					$parent_options    = get_option( $parent_option_key );
					$required          = isset( $parent_options['plugin_options'][ $plugin_name ]['required'] ) ?
						$parent_options['plugin_options'][ $plugin_name ]['required'] :
						'true';
				} else {
					$required = 'true';
				}
			}

			// Specify plugin details
			$themePlugins[ $plugin_slug ] = array(
				'Name'               => $plugin_name, // The plugin name
				'name'               => $plugin_name, // The plugin name
				'slug'               => $plugin_slug, // The plugin slug (typically the folder name)
				'source'             => $source, // The plugin source
				'required'           => $required, // If false, the plugin is only 'recommended' instead of required
				'version'            => $plugin_version, // E.g. 1.0.0. If set, the active plugin must be this version or higher, otherwise a notice is presented
				'file_path'          => $plugin_slug, // The plugin name
				'force_activation'   => true, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch
				'force_deactivation' => false, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins
				'external_url'       => '', // If set, overrides default API URL and points to an external URL
			);

			$this->plugin_installer_options['plugin_options'][ $plugin_name ]['required'] = $required;

			// Plugin update
			// ---------------------------------------------------------------
			if ( version_compare( $plugin_version, $plugin_install_version, '>' ) ) {
				if ( file_exists( ABSPATH . 'wp-content/plugins/' . $plugin_slug ) ) {
					$args = array(
						'new_version'  => $plugin_version,
						'plugin_slug'  => $plugin_slug, // Your plugin slug (typically the plugin folder name, e.g. "soliloquy")
						'plugin_path'  => $plugin_slug, // The plugin basename (e.g. plugin_basename( __FILE__ )) // The plugin folder and main file.
						'plugin_url'   => WP_PLUGIN_URL . '/' . $plugin_slug, // The HTTP URL to the plugin (e.g. WP_PLUGIN_URL . '/soliloquy')
						'remote_url'   => get_home_url(), // The remote API URL that should be pinged when retrieving plugin update info
						'download_url' => $source, // str_replace('\\', '/', get_template_directory()) .'/extensions/plugin-installer/plugins/'. $plugin_install_file,
						'plugin_name'  => $plugin_name,
						'version'      => $plugin_install_version
					);

					${"config_$plugin_slug"}            = new TGM_Updater_Config( $args );
					${"namespace_updater_$plugin_slug"} = new TGM_Updater( ${"config_$plugin_slug"} ); // Be sure to replace "namespace" with your own custom namespace
					${"namespace_updater_$plugin_slug"}->update_plugins(); // Be sure to replace "namespace" with your own custom namespace
				}
			}
		}

		$this->update_options();

	}

  	/**
	 * Register the required plugins for this theme.
	 *
	 * In this example, we register two plugins - one included with the TGMPA library
	 * and one from the .org repo.
	 *
	 * The variable passed to tgmpa_register_plugins() should be an array of plugin
	 * arrays.
	 *
	 * This function is hooked into tgmpa_init, which is fired within the
	 * Runway_Plugin_Installer class constructor.
	 */
	public function required_theme_plugins() {

		global $themePlugins;

		/**
		 * Array of plugin arrays. Required keys are name and slug.
		 * If the source is NOT from the .org repo, then source is also required.
		 */
		$plugins = array();
		if ( isset( $themePlugins ) && count( $themePlugins ) ) {
			foreach ( $themePlugins as $slug => $plugin_data ) {
				$plugins[] = $plugin_data;
			}
		}

		// Change this to your theme text domain, used for internationalising strings
		$theme_text_domain = 'runway';

		/**
		 * Array of configuration settings. Amend each line as needed.
		 * If you want the default strings to be available under your own theme domain,
		 * leave the strings uncommented.
		 * Some of the strings are added into a sprintf, so see the comments at the
		 * end of each line for what each argument will be.
		 */
		$config = array(
			'domain'       	   => $theme_text_domain,         // Text domain - likely want to be the same as your theme.
			'default_path' 	   => '',                         // Default absolute path to pre-packaged plugins
			'parent_menu_slug' => 'themes.php', 			  // Default parent menu slug
			'parent_url_slug'  => 'themes.php', 			  // Default parent URL slug
			'menu'         	   => 'install-required-plugins', // Menu slug
			'has_notices'      => true,                       // Show admin notices or not
			'is_automatic'     => true,					   	  // Automatically activate plugins after installation or not
			'message' 		   => '',						  // Message to output right before the plugins table
			'strings'      	   => array(
				'page_title'                      => __( 'Install Required Plugins', 'runway' ),
				'menu_title'                      => __( 'Theme Plugins', 'runway' ),
				'installing'                      => __( 'Installing Plugin: %s', 'runway' ), // %1$s = plugin name
				'oops'                            => __( 'Something went wrong with the plugin API.', 'runway' ),
				'notice_can_install_required'     => _n_noop( 'This theme requires the following plugin: %1$s.', 'This theme requires the following plugins: %1$s.', 'runway' ), // %1$s = plugin name(s)
				'notice_can_install_recommended'  => _n_noop( 'This theme recommends the following plugin: %1$s.', 'This theme recommends the following plugins: %1$s.', 'runway' ), // %1$s = plugin name(s)
				'notice_cannot_install'           => _n_noop( 'Sorry, but you do not have the correct permissions to install the %s plugin. Contact the administrator of this site for help on getting the plugin installed.', 'Sorry, but you do not have the correct permissions to install the %s plugins. Contact the administrator of this site for help on getting the plugins installed.', 'runway' ), // %1$s = plugin name(s)
				'notice_can_activate_required'    => _n_noop( 'The following required plugin is currently inactive: %1$s.', 'The following required plugins are currently inactive: %1$s.', 'runway' ), // %1$s = plugin name(s)
				'notice_can_activate_recommended' => _n_noop( 'The following recommended plugin is currently inactive: %1$s.', 'The following recommended plugins are currently inactive: %1$s.', 'runway' ), // %1$s = plugin name(s)
				'notice_cannot_activate' 		  => _n_noop( 'Sorry, but you do not have the correct permissions to activate the %s plugin. Contact the administrator of this site for help on getting the plugin activated.', 'Sorry, but you do not have the correct permissions to activate the %s plugins. Contact the administrator of this site for help on getting the plugins activated.', 'runway' ), // %1$s = plugin name(s)
				'notice_ask_to_update' 			  => _n_noop( 'The following plugin needs to be updated to its latest version to ensure maximum compatibility with this theme: %1$s.', 'The following plugins need to be updated to their latest version to ensure maximum compatibility with this theme: %1$s.', 'runway' ), // %1$s = plugin name(s)
				'notice_cannot_update' 			  => _n_noop( 'Sorry, but you do not have the correct permissions to update the %s plugin. Contact the administrator of this site for help on getting the plugin updated.', 'Sorry, but you do not have the correct permissions to update the %s plugins. Contact the administrator of this site for help on getting the plugins updated.', 'runway' ), // %1$s = plugin name(s)
				'install_link' 					  => _n_noop( 'Begin installing plugin', 'Begin installing plugins', 'runway' ),
				'activate_link' 				  => _n_noop( 'Activate installed plugin', 'Activate installed plugins', 'runway' ),
				'return'                          => __( 'Return to Required Plugins Installer', 'runway' ),
				'plugin_activated'                => __( 'Plugin activated successfully.', 'runway' ),
				'complete' 						  => __( 'All plugins installed and activated successfully. %s', 'runway' ), // %1$s = dashboard link
				'nag_type'						  => 'updated' // Determines admin notice type - can only be 'updated' or 'error'
			)
		);

		if ( function_exists( 'tgmpa' ) ) {
			tgmpa( $plugins, $config );
		}

	}

	public function make_plugin_from_extension( $extension = null ) {

		global $extm;

		if ( $extension != null ) {
			$sourcePath = $extm->extensions_dir . $extension;
			$zipPath    = dirname( __FILE__ ) . '/extensions/' . $extension . '.zip';

			if ( file_exists( $sourcePath ) ) {
				$pathInfo   = pathInfo( $sourcePath );
				$parentPath = $pathInfo['dirname'];

				$ext_info       = $extm->get_extension_data( $extm->extensions_dir . $extension . '/load.php' );
				$ext            = $this->make_plugin_header( $ext_info, $extension . '/load.php' );
				$load_extension = $this->make_extension_loader( $extension );
				
				if ( function_exists( 'get_runway_wp_filesystem' ) ) {
					$wp_filesystem = get_runway_wp_filesystem();
				} else {
					if ( ! function_exists( 'WP_Filesystem' ) ) {
						require_once( ABSPATH . 'wp-admin/includes/file.php' );
					}

					WP_Filesystem();
					global $wp_filesystem;
				}

				$extensions_path = apply_filters( 'rf_prepared_path', dirname( __FILE__ ) . '/extensions' );

				if ( ! $wp_filesystem->is_dir( $extensions_path ) ) {
					$wp_filesystem->mkdir( $extensions_path );
				}

				$permissions = $wp_filesystem->getchmod( $extensions_path );
				if ( $permissions < '755' ) {
					$wp_filesystem->chmod( $extensions_path, 0755 );
				}

				if ( class_exists( 'ZipArchive', false ) ) {
					$z = new ZipArchive();
					if ( $z->open( $zipPath, ZIPARCHIVE::CREATE ) ) {
						$z->addEmptyDir( $extension );
						$z->addFromString( $extension . '/' . $extension . '.php', $ext );
						$z->addFromString( $extension . '/load-extension.php', $load_extension );
						self::ext_to_plugin( $sourcePath, $z, strlen( "$parentPath/" ), $extension . '/' );
						$z->close();
					}
				} else {
					require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );

					$temp_dir          = apply_filters( 'rf_prepared_path', get_temp_dir() );
					$tmp_extension_dir = $temp_dir . 'extensions/' . $extension;

					$wp_filesystem->rmdir( $temp_dir . 'extensions', true );
					$wp_filesystem->mkdir( $temp_dir . 'extensions' );
					$wp_filesystem->mkdir( $tmp_extension_dir );
					$wp_filesystem->mkdir( $tmp_extension_dir . '/' . $extension );

					//copy_dir( $sourcePath, $tmp_extension_dir . '/' . $extension );
					$this->add_to_tmp_dir_r( $sourcePath, $tmp_extension_dir . '/' . $extension );
					$wp_filesystem->put_contents( $tmp_extension_dir . '/' . $extension . '.php', $ext );
					$wp_filesystem->put_contents( $tmp_extension_dir . '/load-extension.php', $load_extension );

					$z = new PclZip( $zipPath );
					$z->create( array( $tmp_extension_dir ), '', $temp_dir . 'extensions' );

					$wp_filesystem->rmdir( $temp_dir . 'extensions', true );
				}

			}
		} else {
			return false;
		}

	}

	private function add_to_tmp_dir_r( $path, $path_in_tmp, $exclude = array() ) {
		if ( ! file_exists( $path ) ) return;
		if ( function_exists( 'get_runway_wp_filesystem' ) ) {
			$wp_filesystem = get_runway_wp_filesystem();
		} else {
			if ( ! function_exists( 'WP_Filesystem' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
			}

			WP_Filesystem();
			global $wp_filesystem;
		}

		$files = runway_scandir( $path );
		foreach ( $files as $file ) {
			if ( !in_array( $file, $exclude ) ) {
				if ( is_dir( $path . '/' . $file ) ) {
					$wp_filesystem->mkdir( apply_filters( 'rf_prepared_path', $path_in_tmp . '/' . $file ) );
					$this->add_to_tmp_dir_r( $path . '/' . $file, $path_in_tmp . '/' . $file . '/', $exclude );
				}
				elseif ( is_file( $path . '/' . $file ) ) {
					$wp_filesystem->copy(
						apply_filters( 'rf_prepared_path', $path . '/' . $file ),
						apply_filters( 'rf_prepared_path', $path_in_tmp . '/' . $file )
					);
				}
			}
		}
	}

	private function make_extension_loader( $extension ) {

		$load_extension_str = <<< 'EOD'
<?php

global $pagenow;

if ( 
	file_exists( get_theme_root() . '/runway-framework/framework/load.php' ) 
	&& $pagenow !== 'customize.php' 
	&& ! isset( $_REQUEST['wp_customize'] ) 
) {
	require_once( get_theme_root() . '/runway-framework/framework/load.php' );

	// include extension
	require_once( dirname(__FILE__) . '/%s/load.php' );
}

EOD;

		return sprintf( $load_extension_str, $extension );

	}

	private function make_plugin_header( $ext_info, $load_php ) {

		$content_str = <<< 'EOD'
<?php
/*
Plugin Name: %1$s
Plugin URI: %2$s
Description: %3$s
*/

global $shortname;

if ( $shortname === '' || $shortname == null ) {
	$themeTitle = wp_get_theme();
	$shortname  = apply_filters( 'shortname', sanitize_title( $themeTitle . '_' ) );
}

$current_extensions = get_option( $shortname . 'extensions-manager' );
$theme_name         = preg_replace( '/_$/', '', $shortname );
$is_child           = ( ! file_exists( get_stylesheet_directory() . '/framework/' ) ) ? true : false;

if (
	$current_extensions == false 
	|| ( $current_extensions != false && isset( $current_extensions['extensions'][ $theme_name ] ) 
	&& ! in_array( '%4$s', $current_extensions['extensions'][ $theme_name ]['active'] ) )
) {
	require_once( dirname(__FILE__) . '/load-extension.php' );
} else {

	if ( $is_child ) {
		function %5$s_error_notice() {
			?>
			
			<div class='error'>
				<p>
				<?php echo sprintf( 
					__( 'The "%%s"  plugin cannot work, as the "%%s" extension already works. Please switch off the extension to have the plugin work', 'runway' ), 
					'%1$s', 
					'%1$s' 
				); 
				?>
				</p>
			</div>
			
			<?php
		}
		add_action( 'admin_notices', '%5$s_error_notice' );
	}
}

EOD;

		return sprintf(
			$content_str,
			$ext_info['Name'],
			array_key_exists( 'ExtensionURI', $ext_info ) ? $ext_info['ExtensionURI'] : '',
			array_key_exists( 'Description', $ext_info ) ? $ext_info['Description'] : '',
			$load_php,
			strtolower( preg_replace( "/\s/", '_', $ext_info['Name'] ) )
		);

	}

	/**
	 * Add files and sub-directories in a folder to zip file.
	 *
	 * @param string $folder
	 * @param ZipArchive $zipFile
	 * @param int $exclusiveLength Number of text to be exclusived from the file path.
	 */
	private static function ext_to_plugin( $folder, $zipFile, $exclusiveLength, $zipPath = '' ) {

		$handle = opendir( $folder );
		while ( false !== $f = readdir( $handle ) ) {
			if ( $f != '.' && $f != '..' ) {
				$filePath = "$folder/$f";
				// Remove prefix from file path before add to zip.
				$localPath = substr( $filePath, $exclusiveLength );
				if ( is_file( $filePath ) ) {
					$zipFile->addFile( $filePath, $zipPath . $localPath );
				} elseif ( is_dir( $filePath ) ) {
					// Add sub-directory.
					$zipFile->addEmptyDir( $zipPath . '/' . $localPath );
					self::ext_to_plugin( $filePath, $zipFile, $exclusiveLength, $zipPath );
				}
			}
		}

		closedir( $handle );

	}

	public function delete_from_list( $plugin_info ) {

		global $wp_filesystem;

		if ( IS_CHILD ) {
			if ( file_exists( $plugin_info['source'] ) ) {
				$wp_filesystem->delete( $plugin_info['source'] );
			}

			unset( $this->plugin_installer_options['plugin_options'][ $plugin_info['name'] ] );
			unset( $this->plugin_installer_options['plugin_wp_repository'][ $plugin_info['slug'] ] );

			$this->update_options();
		}

	}

}
