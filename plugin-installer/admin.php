<?php
$navText = array();
switch ( $this->navigation ) {
	case 'add-plugin':
		$navText = array( __( 'Add New', 'runway' ) );
		break;
}

if ( ! empty( $navText ) ) {
	$this->navigation_bar( $navText );
} else {
	echo '<p>&nbsp;</p>';
}

if ( ! in_array( $this->navigation, array( 'add-plugin' ) ) ) {
	?>

	<h2 class="nav-tab-wrapper tab-controlls" style="padding-top: 9px;">
		<a href="<?php echo esc_url( $this->self_url() ); ?>"
		   class="nav-tab <?php echo ( $this->navigation == '' ) ? 'nav-tab-active' : ''; ?>">
			<?php _e( 'Plugins', 'runway' ); ?>
		</a>

		<?php if ( IS_CHILD && get_template() == 'runway-framework' ) { ?>
			<a href="<?php echo esc_url( $this->self_url( 'extensions' ) ); ?>"
			   class="nav-tab <?php echo ( $this->navigation == 'extensions' ) ? 'nav-tab-active' : ''; ?>">
				<?php _e( 'Extensions', 'runway' ); ?>
			</a>
		<?php } ?>

	</h2>

<?php }

global $plugin_installer, $plugin_installer_admin, $themePlugins;
$rpi_class = new Runway_Plugin_Installer;

if ( function_exists( 'runway_filesystem_method' ) ) {
	add_filter( 'filesystem_method', 'runway_filesystem_method' );
}

$link     = admin_url( 'admin.php?page=plugin-installer' );
$redirect = '<script type="text/javascript">window.location = "' . esc_url_raw( $link ) . '";</script>';

$action = isset( $_GET['action'] ) ? $_GET['action'] : '';
switch ( $action ) {
	case 'install':
		$rpi_class->do_plugin_install();
		echo $redirect; // escaped above

		break;

	case 'activate':
		if ( ! isset( $_REQUEST['tgmpa-activate-nonce'] ) || ! wp_verify_nonce( $_REQUEST['tgmpa-activate-nonce'], 'tgmpa-activate' ) ) {
			print __( 'Sorry, your nonce did not verify.', 'runway' );
			exit;
		}

		$rpi_class->do_plugin_activate();
		echo $redirect; // escaped above

		break;

	case 'delete-from-list':
		if ( ! isset( $_REQUEST['tgmpa-delete-nonce'] ) || ! wp_verify_nonce( $_REQUEST['tgmpa-delete-nonce'], 'tgmpa-delete' ) ) {
			print __( 'Sorry, your nonce did not verify.', 'runway' );
			exit;
		}
		
		if ( isset( $_GET['plugin_source'] ) && isset( $_GET['plugin_name'] ) && isset( $_GET['plugin'] ) ) {
			$plugin_info = array(
				'name'   => $_GET['plugin_name'],
				'slug'   => substr( $_GET['plugin'], 0, strpos( $_GET['plugin'], '/' ) ),
				'source' => $_GET['plugin_source']
			);
			$plugin_installer_admin->delete_from_list( $plugin_info );
			echo $redirect; // escaped above
		}

		break;

	case 'install-extension':
		if ( ! isset( $_REQUEST['install-extension'] ) || ! wp_verify_nonce( $_REQUEST['install-extension'], 'install-extension' ) ) {
			print __( 'Sorry, your nonce did not verify.', 'runway' );
			exit;
		}

		$extensions = $_POST['ext_chk'];
		if ( $extensions[0] == 'on' ) {
			unset( $extensions[0] );
		}

		foreach ( $extensions as $key => $ext ) {
			$tmp       = explode( '/', $ext );
			$extension = $tmp[0];
			$this->make_plugin_from_extension( $extension );
		}

		break;

	default:
		// nothing to do
		break;
}

switch ( $this->navigation ) {
	case 'add-plugin':
		if ( ! isset( $_POST['plug-submit'] ) ) {
			include_once 'views/add-plugin.php';
		} else {
			if ( empty( $_POST ) || ! wp_verify_nonce( $_POST['plugin-upload-field'], 'plugin-upload-action' ) ) {
				print __( 'Sorry, your nonce did not verify.', 'runway' );
				exit;
			} else {
				if ( isset( $_FILES['plugzip']['name'] ) && $_FILES['plugzip']['name'] != '' ) {
					$exploded = explode( '.', $_FILES['plugzip']['name'] );
					$file_ext = array_pop( $exploded );
					if ( $file_ext == 'zip' ) {
						$plugin_installer->load_new_plugin( $_FILES['plugzip'] );
						$rpi_class->install_plugins_page();
						echo $redirect; // escaped above
					} else {
						$info_message = __( 'File must have <b>.zip</b> extension Please choose another file.', 'runway' );
						include_once 'views/add-plugin.php';
					}
				} else {
					$info_message = __( 'Select a file', 'runway' );
					include_once 'views/add-plugin.php';
				}
			}
		}

		break;

	case 'extensions':
		global $extm;
		$vals['extm'] = $extm;
		$vals['exts'] = $extm->get_extensions_list( $extm->extensions_dir );
		$this->view( 'extensions', false, $vals );

		break;

	case 'add-plugin-by-url':
		if ( isset( $this->plugin_install_url_message ) && $this->plugin_install_url_message != "" ) {
			$info_message = $this->plugin_install_url_message;
		}
		include_once 'views/add-plugin.php';

		break;

	default :
		$rpi_class->install_plugins_page();

		break;
}
