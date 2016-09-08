<?php

if ( ! class_exists( 'Runway_Bulk_Installer_Skin' ) ) {
	/**
	 * Installer skin to set strings for the bulk plugin installations..
	 *
	 * Extends Bulk_Upgrader_Skin and customizes to suit the installation of multiple
	 * plugins.
	 *
	 * @since 2.2.0
	 *
	 * @package Runway-Plugin-Installer
	 * @author Thomas Griffin <thomas@thomasgriffinmedia.com>
	 * @author Gary Jones <gamajo@gamajo.com>
	 */
	class Runway_Bulk_Installer_Skin extends Bulk_Upgrader_Skin {

		/**
		 * Holds plugin info for each individual plugin installation.
		 *
		 * @since 2.2.0
		 *
		 * @var array
		 */
		public $plugin_info = array();

		/**
		 * Holds names of plugins that are undergoing bulk installations.
		 *
		 * @since 2.2.0
		 *
		 * @var array
		 */
		public $plugin_names = array();

		/**
		 * Integer to use for iteration through each plugin installation.
		 *
		 * @since 2.2.0
		 *
		 * @var integer
		 */
		public $i = 0;

		/**
		 * Constructor. Parses default args with new ones and extracts them for use.
		 *
		 * @since 2.2.0
		 *
		 * @param array $args Arguments to pass for use within the class
		 */
		public function __construct( $args = array() ) {

			/** Parse default and new args */
			$defaults = array(
				'url'   => '',
				'nonce' => '',
				'names' => array()
			);
			$args     = wp_parse_args( $args, $defaults );

			/** Set plugin names to $this->plugin_names property */
			$this->plugin_names = $args['names'];

			/** Extract the new args */
			parent::__construct( $args );

		}

		/**
		 * Sets install skin strings for each individual plugin.
		 *
		 * Checks to see if the automatic activation flag is set and uses the
		 * the proper strings accordingly.
		 *
		 * @since 2.2.0
		 */
		public function add_strings() {

			/** Automatic activation strings */
			if ( Runway_Plugin_Installer::$instance->is_automatic ) {
				$this->upgrader->strings['skin_upgrade_start']        = __( 'The installation and activation process is starting. This process may take a while on some hosts, so please be patient.', 'runway' );
				$this->upgrader->strings['skin_update_successful']    = __( '%1$s installed and activated successfully.', 'runway' ) .
				                                                        ' <a onclick="%2$s" href="#" class="hide-if-no-js"><span>' .
				                                                        __( 'Show Details', 'runway' ) . '</span><span class="hidden">' .
				                                                        __( 'Hide Details', 'runway' ) .
				                                                        '</span>.</a>';
				$this->upgrader->strings['skin_upgrade_end']          = __( 'All installations and activations have been completed.', 'runway' );
				$this->upgrader->strings['skin_before_update_header'] = __( 'Installing and Activating Plugin %1$s (%2$d/%3$d)', 'runway' );
			} /** Default installation strings */
			else {
				$this->upgrader->strings['skin_upgrade_start']        = __( 'The installation process is starting. This process may take a while on some hosts, so please be patient.', 'runway' );
				$this->upgrader->strings['skin_update_failed_error']  = __( 'An error occurred while installing %1$s: <strong>%2$s</strong>.', 'runway' );
				$this->upgrader->strings['skin_update_failed']        = __( 'The installation of %1$s failed.', 'runway' );
				$this->upgrader->strings['skin_update_successful']    = __( '%1$s installed successfully.', 'runway' ) .
				                                                        ' <a onclick="%2$s" href="#" class="hide-if-no-js"><span>' .
				                                                        __( 'Show Details', 'runway' ) . '</span><span class="hidden">' .
				                                                        __( 'Hide Details', 'runway' ) . '</span>.</a>';
				$this->upgrader->strings['skin_upgrade_end']          = __( 'All installations have been completed.', 'runway' );
				$this->upgrader->strings['skin_before_update_header'] = __( 'Installing Plugin %1$s (%2$d/%3$d)', 'runway' );
			}

		}

		/**
		 * Outputs the header strings and necessary JS before each plugin installation.
		 *
		 * @since 2.2.0
		 */
		public function before( $title = '' ) {

			/** We are currently in the plugin installation loop, so set to true */
			$this->in_loop = true;
			?>

			<h4>
				<?php printf(
					rf__( $this->upgrader->strings['skin_before_update_header'] ),
					$this->plugin_names[ $this->i ],
					$this->upgrader->update_current,
					$this->upgrader->update_count
				); ?>
				<img alt="" src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>"
				     class="hidden waiting-<?php echo $this->upgrader->update_current; ?>"
				     style="vertical-align:middle;"/>
			</h4>
			<script>jQuery('<?php echo '.waiting-' . esc_js( $this->upgrader->update_current ); ?>').show();</script>
			<div class="update-messages hide-if-js" id="progress-<?php echo esc_attr( $this->upgrader->update_current ); ?>">
			<p>

			<?php
			/** Flush header output buffer */
			$this->before_flush_output();

		}

		/**
		 * Outputs the footer strings and necessary JS after each plugin installation.
		 *
		 * Checks for any errors and outputs them if they exist, else output
		 * success strings.
		 *
		 * @since 2.2.0
		 */
		public function after( $title = '' ) {

			/** Close install strings */
			echo '</p></div>';

			/** Output error strings if an error has occurred */
			if ( $this->error || ! $this->result ) {
				if ( $this->error ) {
					echo '<div class="error"><p>' .
					     sprintf(
						     rf__( $this->upgrader->strings['skin_update_failed_error'] ),
						     $this->plugin_names[ $this->i ],
						     $this->error
					     ) .
					     '</p></div>';
				} else {
					echo '<div class="error"><p>' .
					     sprintf(
						     rf__( $this->upgrader->strings['skin_update_failed'] ),
						     $this->plugin_names[ $this->i ]
					     ) .
					     '</p></div>';
				}

				echo '<script type="text/javascript">jQuery(\'#progress-' . esc_js( $this->upgrader->update_current ) . '\').show();</script>';
			}

			/** If the result is set and there are no errors, success! */
			if ( ! empty( $this->result ) && ! is_wp_error( $this->result ) ) {
				echo '<div class="updated"><p>' .
				     sprintf(
					     rf__( $this->upgrader->strings['skin_update_successful'] ),
					     $this->plugin_names[ $this->i ],
					     'jQuery(\'#progress-' . esc_js( $this->upgrader->update_current ) . '\').toggle();jQuery(\'span\', this).toggle(); return false;'
				     ) .
				     '</p></div>';
				echo '<script type="text/javascript">jQuery(\'.waiting-' . esc_js( $this->upgrader->update_current ) . '\').hide();</script>';
			}

			/** Set in_loop and error to false and flush footer output buffer */
			$this->reset();
			$this->after_flush_output();

		}

		/**
		 * Outputs links after bulk plugin installation is complete.
		 *
		 * @since 2.2.0
		 */
		public function bulk_footer() {

			/** Serve up the string to say installations (and possibly activations) are complete */
			parent::bulk_footer();

			/** Flush plugins cache so we can make sure that the installed plugins list is always up to date */
			wp_cache_flush();

			/** Display message based on if all plugins are now active or not */
			$complete = array();
			foreach ( Runway_Plugin_Installer::$instance->plugins as $plugin ) {
				if ( ! is_plugin_active( $plugin['file_path'] ) ) {
					echo '<p><a href="' . esc_url( add_query_arg( 'page', Runway_Plugin_Installer::$instance->menu, admin_url( Runway_Plugin_Installer::$instance->parent_url_slug ) ) ) .
					     '" title="' . esc_attr( Runway_Plugin_Installer::$instance->strings['return'] ) .
					     '" target="_parent">' .
					     sprintf( __( '%s', 'runway' ), Runway_Plugin_Installer::$instance->strings['return'] ) .
					     '</a></p>';
					$complete[] = $plugin;
					break;
				} /** Nothing to store */
				else {
					$complete[] = '';
				}
			}

			/** Filter out any empty entries */
			$complete = array_filter( $complete );

			/** All plugins are active, so we display the complete string and hide the menu to protect users */
			if ( empty( $complete ) ) {
				echo '<p>' .
				     sprintf(
					     Runway_Plugin_Installer::$instance->strings['complete'],
					     '<a href="' . esc_url( admin_url() ) . '" title="' .
					     esc_attr( __( 'Return to the Dashboard', 'runway' ) ) . '">' .
					     __( 'Return to the Dashboard', 'runway' ) .
					     '</a>'
				     ) .
				     '</p>';
			}

		}

		/**
		 * Flush header output buffer.
		 *
		 * @since 2.2.0
		 */
		public function before_flush_output() {

			wp_ob_end_flush_all();
			flush();

		}

		/**
		 * Flush footer output buffer and iterate $this->i to make sure the
		 * installation strings reference the correct plugin.
		 *
		 * @since 2.2.0
		 */
		public function after_flush_output() {

			wp_ob_end_flush_all();
			flush();
			$this->i++;

		}

	}
}