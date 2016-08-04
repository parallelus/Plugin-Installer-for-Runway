<?php
if ( ! class_exists( 'Runway_Plugin_Installer_Skin' ) ) {

	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php'; // Need for upgrade classes

	class Runway_Plugin_Installer_Skin extends Plugin_Installer_Skin {

		public function before() {
			if ( ! empty( $this->api ) ) {
				$this->upgrader->strings['process_success'] = $this->options['first_activation'] ?
					'' : sprintf( __( 'Successfully installed the plugin <strong>%s %s</strong>.' ), $this->api->name, $this->api->version );
			}
		}

	}

}
