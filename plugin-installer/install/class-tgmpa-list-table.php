<?php
/**
 * WP_List_Table isn't always available. If it isn't available,
 * we load it here.
 *
 * @since 2.2.0
 */
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

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
			$table_data        = array();
			$i                 = 0;
			$installed_plugins = get_plugins();

			if ( is_array( $themePlugins ) ) {
				foreach ( $themePlugins as $plugin ) {

					$table_data[ $i ]['sanitized_plugin'] = $plugin['name'];
					$table_data[ $i ]['slug']             = $this->_get_plugin_data_from_name( $plugin['name'] );

					$external_url = $this->_get_plugin_data_from_name( $plugin['name'], 'external_url' );
					$source       = $this->_get_plugin_data_from_name( $plugin['name'], 'source' );

					if ( $external_url && preg_match( '|^http(s)?://|', $external_url ) ) {
						//$table_data[ $i ]['plugin'] = '<strong><a href="' . esc_url( $external_url ) . '" title="' . esc_attr( rf__( $plugin['name'] ) ) . '" target="_blank">' . rf__( $plugin['name'] ) . '</a></strong>';
						$table_data[ $i ]['plugin'] = sprintf( '<strong><a href="%s" title="%s" target="_blank">%s</a></strong>',
							esc_url( $source ),
							esc_attr( rf__( $plugin['name'] ) ),
							rf__( $plugin['name'] )
						);
					} else {
						$table_data[ $i ]['plugin'] = '<strong>' . rf__( $plugin['name'] ) . '</strong>'; // No hyperlink
					}

					if ( isset( $table_data[ $i ]['plugin'] ) && (array) $table_data[ $i ]['plugin'] ) {
						$plugin['name'] = $table_data[ $i ]['plugin'];
					}

					if ( isset( $plugin['external_url'] ) && ! empty( $plugin['external_url'] ) ) {
						/** The plugin is linked to an external source */
						$table_data[ $i ]['source'] = __( 'External Link', 'runway' );
					} elseif ( isset( $plugin['source'] ) ) {
						/** The plugin is from the WordPress repository */
						if ( strstr( $plugin['source'], RUNWAY_PLUGIN_INSTALLER_WP_REPOSITORY_URL ) !== false ) {
							$table_data[ $i ]['source'] = __( 'WordPress Repository', 'runway' );
						} /** The plugin must be from a private repository */
						else if ( preg_match( '|^http(s)?://|', $plugin['source'] ) ) {
							$table_data[ $i ]['source'] = __( 'Private Repository', 'runway' );
						} /** The plugin is pre-packaged with the theme */
						else {
							$table_data[ $i ]['source'] = __( 'Pre-Packaged', 'runway' );
						}
					} else {
						$table_data[ $i ]['source'] = __( 'Unknown', 'runway' );
					}

					$table_data[ $i ]['type'] = $plugin['required'] == 'true' ? __( 'Required', 'runway' ) : __( 'Recommended', 'runway' );

					if ( ! isset( $installed_plugins[ $plugin['file_path'] ] ) ) {
						$table_data[ $i ]['status'] = sprintf( '%1$s', __( 'Not Installed', 'runway' ) );
					} elseif ( is_plugin_inactive( $plugin['file_path'] ) ) {
						$table_data[ $i ]['status'] = sprintf( '%1$s', __( 'Installed But Not Activated', 'runway' ) );
					} else {
						$table_data[ $i ]['status'] = sprintf( '%1$s', __( 'Installed And Activated', 'runway' ) );
					}

					$table_data[ $i ]['file_path'] = $plugin['file_path'];
					$table_data[ $i ]['url']       = isset( $plugin['source'] ) ? $plugin['source'] : 'repo';

					$i++;
				}

			}

			/** Sort plugins by Required/Recommended type and by alphabetical listing within each type */
			$resort = array();
			$req    = array();
			$rec    = array();

			/** Grab all the plugin types */
			foreach ( $table_data as $plugin ) {
				$resort[] = $plugin['type'];
			}

			/** Sort each plugin by type */
			foreach ( $resort as $type ) {
				if ( 'Required' == $type ) {
					$req[] = $type;
				} else {
					$rec[] = $type;
				}
			}

			/** Sort alphabetically each plugin type array, merge them and then sort in reverse    (lists Required plugins first) */
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
		 *
		 * @return string|boolean Plugin slug if found, false otherwise
		 */
		protected function _get_plugin_data_from_name( $name, $data = 'slug' ) {
			global $themePlugins;

			foreach ( $themePlugins as $plugin => $values ) {
				if ( $name == $values['name'] && isset( $values[ $data ] ) ) {
					return $values[ $data ];
				}
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
					return $item[ $column_name ];
			}

		}

		/**
		 * Create default title column along with action links of 'Install'
		 * and 'Activate'.
		 *
		 * @since 2.2.0
		 *
		 * @param array $item
		 *
		 * @return string The action hover links
		 */
		public function column_plugin( $item ) {

			$installed_plugins = get_plugins();

			/** No need to display any hover links */
			if ( is_plugin_active( $item['file_path'] ) ) {
				$actions = array();
			}
			/** We need to display the 'Install' hover link */
			if ( ! isset( $installed_plugins[ $item['file_path'] ] ) ) {

				$actions = array(
					'install' => sprintf(
						'<a href="%1$s" title="' . __( 'Install', 'runway' ) . ' %2$s">' . __( 'Install', 'runway' ) . '</a>',
						esc_url(
							wp_nonce_url(
								add_query_arg(
									array(
										'page'          => Runway_Plugin_Installer::$instance->menu,
										'action'        => 'install',
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

			} /** We need to display the 'Activate' hover link */
			elseif ( is_plugin_inactive( $item['file_path'] ) ) {

				$actions = array(
					'activate' => sprintf(
						'<a href="%1$s" title="' . __( 'Activate', 'runway' ) . ' %2$s">' . __( 'Activate', 'runway' ) . '</a>',
						esc_url(
							add_query_arg(
								array(
									'page'                 => Runway_Plugin_Installer::$instance->menu,
									'action'               => 'activate',
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
				if ( IS_CHILD ) {
					$actions = array(
						'' => sprintf(
							' <a href="%1$s" title="' . __( 'Delete from the list', 'runway' ) . ' %2$s">' . __( 'Delete from the list', 'runway' ) . '</a>',
							esc_url(
								add_query_arg(
									array(
										'page'                 => Runway_Plugin_Installer::$instance->menu,
										'action'               => 'delete-from-list',
										'plugin'               => $item['slug'],
										'plugin_name'          => $item['sanitized_plugin'],
										'plugin_source'        => $item['url'],
//										'tgmpa-activate'       => 'activate-plugin',
										'tgmpa-delete-nonce'   => wp_create_nonce( 'tgmpa-delete' ),
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
		 *
		 * @return string The input checkbox with all necessary info
		 */
		public function column_cb( $item ) {

			$value = $item['file_path'] . ',' . $item['url'] . ',' . $item['sanitized_plugin'];

			return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" id="%3$s" />',
				$this->_args['singular'],
				$value,
				$item['sanitized_plugin']
			);

		}

		public function column_type( $item ) {
			global $plugin_installer_admin;

			$plugin_options_saved = $plugin_installer_admin->get_options();
			$needs                = array(
				'Required'    => 'true',
				'Recommended' => 'false',
			);

			if ( IS_CHILD ) {

				$type = '<select class="wp-list-select" name="plugin_options[' . $item['sanitized_plugin'] . '][required]">';
				foreach ( $needs as $key => $value ) {
					$selected = ( isset( $plugin_options_saved['plugin_options'][ $item['sanitized_plugin'] ] )
					              && $value == $plugin_options_saved['plugin_options'][ $item['sanitized_plugin'] ]['required'] ) ?
						' selected="selected"' : '';
					$type .= '<option value="' . $value . '"' . $selected . '>' . $key . '</option>';
				}
				$type .= '</select>';
			} else {
				foreach ( $needs as $key => $value ) {
					if ( isset( $plugin_options_saved['plugin_options'][ $item['sanitized_plugin'] ] )
					     && $value == $plugin_options_saved['plugin_options'][ $item['sanitized_plugin'] ]['required']
					) {
						$type = $key;
					}
				}
			}

			return $type;

		}

		public function column_action( $item ) {

			$button = '<input type="hidden" name="action_save[' . $item['sanitized_plugin'] . ']" />' .
			          '<button type="submit" class="button-primary ajax-save">' . __( 'Save', 'runway' ) . '</button>';

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

			printf( '%s<a href="%s">%s</a>',
				__( 'No plugins to install or activate.', 'runway' ),
				esc_url( admin_url() ),
				__( 'Return to the Dashboard', 'runway' )
			);

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

			if ( IS_CHILD ) {
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

			if ( IS_CHILD ) {
				$actions['tgmpa-bulk-delete'] = __( 'Delete', 'runway' );
			}

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

			$link = '';

			/** Bulk installation process */
			if ( 'tgmpa-bulk-install' === $this->current_action() ) {
				check_admin_referer( 'bulk-' . $this->_args['plural'] );

				$this->process_bulk_install();
				$this->process_bulk_activate();

				$link = admin_url( 'options-general.php?page=plugin-installer' );
			} /** Bulk activation process */
			else if ( 'tgmpa-bulk-activate' === $this->current_action() ) {
				check_admin_referer( 'bulk-' . $this->_args['plural'] );

				$this->process_bulk_activate();

				$link = admin_url( 'options-general.php?page=plugin-installer' );
			} /** Bulk delete process */
			else if ( 'tgmpa-bulk-delete' === $this->current_action() ) {
				check_admin_referer( 'bulk-' . $this->_args['plural'] );

				$this->process_bulk_delete();

				$link = admin_url( 'admin.php?page=plugin-installer' );
			}

			if ( ! empty( $link ) ) {
				$redirect = '<script type="text/javascript">window.location = "' . esc_url_raw( $link ) . '";</script>';
				echo $redirect;
			}

		}

		public function process_bulk_install() {

			/** Prep variables to be populated */
			$plugins_to_install = array();
			$plugin_installs    = array();
			$plugin_path        = array();
			$plugin_name        = array();

			/** Look first to see if information has been passed via WP_Filesystem */
			if ( isset( $_GET[ sanitize_key( 'plugins' ) ] ) ) {
				$plugins = explode( ',', stripslashes( $_GET[ sanitize_key( 'plugins' ) ] ) );
			} /** Looks like the user can use the direct method, take from $_POST */
			elseif ( isset( $_POST[ sanitize_key( 'plugin' ) ] ) ) {
				$plugins = (array) $_POST[ sanitize_key( 'plugin' ) ];
			} /** Nothing has been submitted */
			else {
				$plugins = array();
			}

			/** Grab information from $_POST if available */
			if ( isset( $_POST[ sanitize_key( 'plugin' ) ] ) ) {
				foreach ( $plugins as $plugin_data ) {
					$plugins_to_install[] = explode( ',', $plugin_data );
				}

				foreach ( $plugins_to_install as $plugin_data ) {
					$plugin_installs[] = '';
					$plugin_path[]     = $plugin_data[1];
					$plugin_name[]     = $plugin_data[2];
				}
			} /** Information has been passed via $_GET */
			else {
				foreach ( $plugins as $key => $value ) {
					/** Grab plugin slug for each plugin */
					if ( 0 == $key % 3 || 0 == $key ) {
						$plugins_to_install[] = $value;
						$plugin_installs[]    = $value;
					}
				}
			}

			/** Look first to see if information has been passed via WP_Filesystem */
			if ( isset( $_GET[ sanitize_key( 'plugin_paths' ) ] ) ) {
				$plugin_paths = explode( ',', stripslashes( $_GET[ sanitize_key( 'plugin_paths' ) ] ) );
			} /** Looks like the user doesn't need to enter his FTP creds */
			elseif ( isset( $_POST[ sanitize_key( 'plugin' ) ] ) ) {
				$plugin_paths = (array) $plugin_path;
			} /** Nothing has been submitted */
			else {
				$plugin_paths = array();
			}

			/** Look first to see if information has been passed via WP_Filesystem */
			if ( isset( $_GET[ sanitize_key( 'plugin_names' ) ] ) ) {
				$plugin_names = explode( ',', stripslashes( $_GET[ sanitize_key( 'plugin_names' ) ] ) );
			} /** Looks like the user doesn't need to enter his FTP creds */
			elseif ( isset( $_POST[ sanitize_key( 'plugin' ) ] ) ) {
				$plugin_names = (array) $plugin_name;
			} /** Nothing has been submitted */
			else {
				$plugin_names = array();
			}

			$b = 0; // Incremental variable

			/** Loop through plugin slugs and remove already installed plugins from the list */
			foreach ( $plugin_installs as $key => $plugin ) {
				if ( preg_match( '|.php$|', $plugin ) ) {
					unset( $plugin_installs[ $key ] );

					/** If the plugin path isn't in the $_GET variable, we can unset the corresponding path */
					if ( ! isset( $_GET[ sanitize_key( 'plugin_paths' ) ] ) ) {
						unset( $plugin_paths[ $b ] );
					}

					/** If the plugin name isn't in the $_GET variable, we can unset the corresponding name */
					if ( ! isset( $_GET[ sanitize_key( 'plugin_names' ) ] ) ) {
						unset( $plugin_names[ $b ] );
					}
				}
				$b++;
			}
			/** No need to proceed further if we have no plugins to install */
			if ( empty( $plugin_installs ) ) {
				return false;
			}

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
						'page'         => Runway_Plugin_Installer::$instance->menu,
						'tgmpa-action' => 'install-selected',
						'plugins'      => urlencode( implode( ',', $plugins ) ),
						'plugin_paths' => urlencode( implode( ',', $plugin_paths ) ),
						'plugin_names' => urlencode( implode( ',', $plugin_names ) ),
					),
					admin_url( Runway_Plugin_Installer::$instance->parent_url_slug )
				),
				'bulk-plugins'
			);

			$method = ''; // Leave blank so WP_Filesystem can populate it as necessary
			$fields = array(
				sanitize_key( 'action' ),
				sanitize_key( '_wp_http_referer' ),
				sanitize_key( '_wpnonce' )
			); // Extra fields to pass to WP_Filesystem

			if ( false === ( $creds = request_filesystem_credentials( $url, $method, false, false, $fields ) ) ) {
				return true;
			}

			if ( ! WP_Filesystem( $creds ) ) {
				request_filesystem_credentials( $url, $method, true, false, $fields ); // Setup WP_Filesystem
				return true;
			}

			require_once ABSPATH . 'wp-admin/includes/plugin-install.php'; // Need for plugins_api
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php'; // Need for upgrade classes
			require_once( 'class-plugin-installer-bulk.php' );

			/** Store all information in arrays since we are processing a bulk installation */
			$api          = array();
			$sources      = array();

			$c = 0; // Incremental variable

			/** Loop through each plugin to install and try to grab information from WordPress API, if not create 'tgmpa-empty' scalar */
			foreach ( $plugin_installs as $plugin ) {
				$api[ $c ] = plugins_api( 'plugin_information', array(
					'slug'   => $plugin,
					'fields' => array( 'sections' => false )
				) ) ? plugins_api( 'plugin_information', array(
					'slug'   => $plugin,
					'fields' => array( 'sections' => false )
				) ) : (object) $api[ $c ] = 'tgmpa-empty';
				$c++;
			}

			if ( is_wp_error( $api ) ) {
				wp_die( Runway_Plugin_Installer::$instance->strings['oops'] . var_dump( $api ) );
			}

			$d = 0;    // Incremental variable

			/** Capture download links from $api or set install link to pre-packaged/private repo */
			foreach ( $api as $object ) {
				$sources[ $d ] = isset( $object->download_link ) && 'repo' == $plugin_paths[ $d ] ? $object->download_link : $plugin_paths[ $d ];
				$d++;
			}

			/** Finally, all the data is prepared to be sent to the installer */
			$url   = add_query_arg( array( 'page' => Runway_Plugin_Installer::$instance->menu ), admin_url( Runway_Plugin_Installer::$instance->parent_url_slug ) );
			$nonce = 'bulk-plugins';
			$names = $plugin_names;

			/** Create a new instance of TGM_Bulk_Installer */
			$installer = new Runway_Bulk_Installer( $skin = new Runway_Bulk_Installer_Skin( compact( 'url', 'nonce', 'names' ) ) );

			/** Process the bulk installation submissions */
			$installer->bulk_install( $sources );

		}

		public function process_bulk_activate() {

			$plugins             = isset( $_POST[ sanitize_key( 'plugin' ) ] ) ? (array) $_POST[ sanitize_key( 'plugin' ) ] : array();
			$plugins_to_activate = array();

			/** Split plugin value into array with plugin file path, plugin source and plugin name */
			foreach ( $plugins as $i => $plugin ) {
				$plugins_to_activate[] = explode( ',', $plugin );
			}

			foreach ( $plugins_to_activate as $i => $array ) {
				if ( ! preg_match( '|.php$|', $array[0] ) ) {// Plugins that haven't been installed yet won't have the correct file path
					unset( $plugins_to_activate[ $i ] );
				}
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
			if ( empty( $plugins_to_activate ) ) {
				return;
			}

			$plugins      = array();
			$plugin_names = array();

			foreach ( $plugins_to_activate as $plugin_string ) {
				$plugins[]      = $plugin_string[0];
				$plugin_names[] = $plugin_string[2];
			}

			/** Now we are good to go - let's start activating plugins */
			$activate = activate_plugins( $plugins );

			if ( is_wp_error( $activate ) ) {
				echo '<div id="message" class="error"><p>' . rf__( $activate->get_error_message() ) . '</p></div>';
			}

			/** Update recently activated plugins option */
			$recent = (array) get_option( 'recently_activated' );

			foreach ( $plugins as $plugin => $time ) {
				if ( isset( $recent[ $plugin ] ) ) {
					unset( $recent[ $plugin ] );
				}
			}

			update_option( 'recently_activated', $recent );

			unset( $_POST ); // Reset the $_POST variable in case user wants to perform one action after another

		}

		public function process_bulk_delete() {

			$plugins           = isset( $_POST[ sanitize_key( 'plugin' ) ] ) ? (array) $_POST[ sanitize_key( 'plugin' ) ] : array();
			$plugins_to_delete = array();

			/** Split plugin value into array with plugin file path, plugin source and plugin name */
			foreach ( $plugins as $i => $plugin ) {
				$plugins_to_delete[] = explode( ',', $plugin );
			}

			foreach ( $plugins_to_delete as $i => $array ) {
				if ( ! preg_match( '|.php$|', $array[0] ) ) { // Plugins that haven't been installed yet won't have the correct file path
					unset( $plugins_to_delete[ $i ] );
				}
			}

			if ( ! empty( $plugins_to_delete ) ) {
				foreach ( $plugins_to_delete as $value ) {
					unlink( $value[1] );
					$custom_php = str_replace( '.zip', '-custom.php', $value[1] );
					if ( file_exists( $custom_php ) ) {
						unlink( $custom_php );
					}
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
