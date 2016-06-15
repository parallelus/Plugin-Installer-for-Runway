<?php
if ( ! defined( 'RUNWAY_PLUGIN_INSTALLER_WP_REPOSITORY_URL' ) ) {
	define( 'RUNWAY_PLUGIN_INSTALLER_WP_REPOSITORY_URL', 'https://wordpress.org/plugins/' );
}
if ( ! defined( 'RUNWAY_PLUGIN_INSTALLER_WP_API_URL' ) ) {
	define( 'RUNWAY_PLUGIN_INSTALLER_WP_API_URL', 'http://api.wordpress.org/plugins/info/1.0/' );
}

class Plugin_Installer_Object extends Runway_Object {
	public $option_key, $plugin_installer_options, $plugins_path;

	function __construct($settings) {

		$this->option_key = $settings['option_key'];
		$this->option_key_registered = $settings['option_key'].'_registered';
		$this->option_key_registered_all = $settings['option_key'].'_registered_all';

		$this->plugin_installer_options = get_option( $this->option_key );
		$this->plugins_zip_path = get_template_directory().'/extensions/plugin-installer/plugins/';
		$this->extensions_zip_path = get_template_directory().'/extensions/plugin-installer/extensions/';

		$this->plugins_path = ABSPATH . '/wp-content/plugins/';
	}

	/* ----Work with plugin installer---- */

	public function register_plugin($plugin = array(), $config = array()) {
		// TODO: custom plugin registration
	}

	function do_register_plugin( $plug_file, $config = array() ) {

		$plugin = $this->get_plugin_data( $plug_file );
		$this->register_plugin($plugin, $config) ;
		$this->do_install_plugin($plugin);
	}

	function do_register_all_plugins( ) {
		foreach (glob($this->plugins_zip_path.'*.zip') as $plug_file) {
            $info = pathinfo($plug_file);
            $plugin = $this->do_register_plugin($info['filename']);
		}

		foreach (glob($this->extensions_zip_path.'*.zip') as $plug_file) {
            $info = pathinfo($plug_file);
            $plugin = $this->do_register_plugin($info['filename']);
		}
	}

	function get_all_registered_plugins( ) {

		$this->plugin_installer_options['registered_all'] = get_option($this->option_key_registered_all);
		$plugins_registered = array();
		foreach( $this->plugin_installer_options['registered_all'] as $value)
			$plugins_registered[] = $value;
		return $plugins_registered;
	}

	function include_custom_php() {

		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		$plugins = $this->get_all_plugins_list();

		foreach($plugins as $plugin) {
			if( is_plugin_active( $plugin['slug']) ) {
				$custom_php = str_replace('.zip', '-custom.php', $plugin['source']);
				if(file_exists($custom_php) )
					require_once($custom_php);
			}
		}
	}

	function get_all_plugins_list( ) {

		$plugins = array();

		$file_names = glob($this->plugins_zip_path.'*.zip');
		if ( is_array ( $file_names ) ) {
			foreach ($file_names as $plug_file) {
            	$info = pathinfo($plug_file);
            	$key = $info['filename'];
            	$plugins[$key] = $this->get_plugin_data($info['basename']);
			}
		}

		$file_names = glob($this->extensions_zip_path.'*.zip');
		if ( is_array ( $file_names ) ) {
			foreach ( $file_names as $plug_file) {
            	$info = pathinfo($plug_file);
            	$key = $info['filename'];
            	$plugins[$key] = $this->get_plugin_data($info['basename'], true);
			}
		}

		$plugins = array_merge($plugins, $this->get_all_plugins_wp_repository_auto(), $this->get_all_plugins_wp_repository());

		return $plugins;
	}

	function get_all_plugins_wp_repository_auto( ) {

		$plugin_slug_installed = array();
		$plugins = get_plugins();

		foreach($plugins as $slug => $plugin) {
			$plugin_slug_installed[$slug] = substr($slug, 0, strpos($slug, '/') + 1);
		}

		$plugin_wp_repository = array();
		foreach($this->plugin_installer_options['plugin_options'] as $key => $val) {
			if( !empty($key) && isset($val['slug']) && !empty($val['slug']) ) {
				$slug = $val['slug'];

				// check if plugin is already installed
				if ( in_array($slug .'/', $plugin_slug_installed) ) {

					// get info from plugins
					$file = array_keys($plugin_slug_installed, $slug.'/');
					$plugin_data = get_plugin_data(WP_PLUGIN_DIR .'/'. $file[0]);

					$plugin_wp_repository[$key]['Title']  = $plugin_data['Name'];
					$plugin_wp_repository[$key]['Name']   = $plugin_data['Name'];
					$plugin_wp_repository[$key]['slug']   = $file[0];
					$plugin_wp_repository[$key]['source'] = RUNWAY_PLUGIN_INSTALLER_WP_REPOSITORY_URL.$val['slug'].'/';

				} else {

					// get info from wp api
					$args = array(
						'slug'   => $slug,
						'fields' => array(
							'description'       => true,
							'short_description' => true
						)
					);

					$response = wp_remote_post(
						RUNWAY_PLUGIN_INSTALLER_WP_API_URL,
						array(
							'body' => array(
								'timeout' => 15,
								'action'  => 'plugin_information',
								'request' => serialize((object)$args)
							)
						)
					);

					if ( !is_wp_error($response) ) {
						$plugin_wp = unserialize(wp_remote_retrieve_body($response));
						if ( !is_object($plugin_wp) ) {
							echo '<div class="error"><p>'.__('An error has occurred', 'runway').'</p></div>';
						}
						else {
							if ( $plugin_wp ) {
								$plugin_index = array_search($slug.'/', $plugin_slug_installed);

								$plugin_wp_repository[$key]['Title']  = $plugin_wp->name;
								$plugin_wp_repository[$key]['Name']   = $plugin_wp->name;
								$plugin_wp_repository[$key]['slug']   = !empty($plugin_index)? $plugin_index : $slug;
								$plugin_wp_repository[$key]['source'] = RUNWAY_PLUGIN_INSTALLER_WP_REPOSITORY_URL.$slug.'/';

							}
						}
					}
					else {
						echo '<div class="error"><p>'.__('An error has occurred', 'runway').'</p></div>';
					}

				}

			}

		}

		return $plugin_wp_repository;
	}

	function get_all_plugins_wp_repository( ) {

		$plugin_slug_installed = array();
		$plugins = get_plugins();
		foreach($plugins as $slug => $plugin) {
			$plugin_slug_installed[$slug] = substr($slug, 0, strpos($slug, '/') + 1);
		}

		$plugin_wp_repository = array();
		if(isset($this->plugin_installer_options['plugin_wp_repository']) && !empty($this->plugin_installer_options['plugin_wp_repository'])) {
			foreach($this->plugin_installer_options['plugin_wp_repository'] as $slug => $val) {
				if( !empty($slug) && in_array($slug.'/', $plugin_slug_installed)) {
					$file = array_keys($plugin_slug_installed, $slug.'/');
					$plugin_wp_repository[$slug] = get_plugin_data(WP_PLUGIN_DIR .'/'. $file[0]);
					$plugin_wp_repository[$slug]['Title'] = $plugin_wp_repository[$slug]['Name'];
					$plugin_wp_repository[$slug]['name'] = $plugin_wp_repository[$slug]['Name'];
					$plugin_wp_repository[$slug]['source'] = $plugin_wp_repository[$slug]['PluginURI'];
					$plugin_wp_repository[$slug]['slug'] = $file[0];
					$plugin_wp_repository[$slug]['file'] = '';
					$plugin_wp_repository[$slug]['install_version'] = $plugin_wp_repository[$slug]['Version'];
				}
			}
		}

		return $plugin_wp_repository;
	}

	function get_plugin_data( $plug_file, $is_ext = false ) {
		if(!$is_ext){
			$res = $this->get_header_info($this->plugins_zip_path, $plug_file);
		}
		else {
			$res = $this->get_header_info($this->extensions_zip_path, $plug_file);
		}

		return $res;

	}

	function scandir_recursive($dir, $results = array()){
	    $files = scandir($dir);

	    foreach($files as $key => $value){
	        $path = realpath($dir.'/'.$value);
	        if(!is_dir($path)) {
	            $results[] = $path;
	        } else if($value != "." && $value != "..") {
	            $this->scandir_recursive($path, $results);
	            $results[] = $path;
	        }
	    }

	    return $results;
	}

	private function unzip_file_ziparchive($plugins_zip_path, $plug_file) {
		$info = pathinfo($plug_file);
		$ret = array('main_file' => '', 'file_data' => '');
		$zip = new ZipArchive;
		if ($zip->open($plugins_zip_path . $plug_file)) {
			for ($i = 0; $i < $zip->numFiles; $i++) {
				$source = $zip->getNameIndex($i);
				if (strpos($source, '.php') !== false) {
					$file_content = $zip->getFromName($source);
					if (strpos($file_content, 'Plugin Name')) {
						$ret['main_file'] = $source;
						$ret['file_data'] = $file_content;
					}
				}
			}
 			$zip->close();
		}

		return $ret;
	}

	private function unzip_file_pclzip($plugins_zip_path, $plug_file) {
		if(!function_exists('WP_Filesystem'))
			require_once(ABSPATH . 'wp-admin/includes/file.php');
		WP_Filesystem();
		global $wp_filesystem;

		$info = pathinfo($plug_file);
		$ret = array('main_file' => '', 'file_data' => '');
		require_once(ABSPATH . 'wp-admin/includes/class-pclzip.php');
		if( $zip = new PclZip($plugins_zip_path . $plug_file) ) {
			if($info_extracted = $zip->extract(get_temp_dir().'extracted_plugins') ) {
				$list = $this->scandir_recursive(get_temp_dir().'extracted_plugins/'.$info_extracted[0]['stored_filename']);//out($list);
				foreach ($list as $source) {
					if (strpos($source, '.php') !== false) {
						$info_source = pathinfo($source);
					 	$file_content = file_get_contents($source);
						if (strpos($file_content, 'Plugin Name')) {
							$ret['main_file'] = $info_extracted[0]['stored_filename'].$info_source['basename'];//out($ret['main_file']);
							$ret['file_data'] = $file_content;
						}
					}
				}
			}
		}
		$wp_filesystem->rmdir(get_temp_dir().'extracted_plugins');

		return $ret;
	}

	private function get_header_info($plugins_zip_path, $plug_file){

		$plugin_info = array();
		$file_data = '';

		if ( class_exists( 'ZipArchive', false ) ) {
			$res = $this->unzip_file_ziparchive($plugins_zip_path, $plug_file);
		} else {
			$res = $this->unzip_file_pclzip($plugins_zip_path, $plug_file);
		}
		$main_file = $res['main_file'];
		$file_data = $res['file_data'];

		if(!function_exists('WP_Filesystem'))
			require_once(ABSPATH . 'wp-admin/includes/file.php');
		WP_Filesystem();
		global $wp_filesystem;

		if($file_data != ''){
			$default_headers = array(
				'Name' => __('Plugin Name', 'runway'),
				'PluginURI' => __('Plugin URI', 'runway'),
				'Version' => __('Version', 'runway'),
				'Description' => __('Description', 'runway'),
				'Author' => __('Author', 'runway'),
				'AuthorURI' => __('Author URI', 'runway'),
			);

			$plugin_info = $this->get_data_by_headers($file_data, $default_headers);

			$plugin_info['Title'] = $plugin_info['Name'];
			$plugin_info['AuthorName'] = $plugin_info['Author'];
			$plugin_info['name'] = $plugin_info['Name'];
			$plugin_info['source'] = $plugins_zip_path.$plug_file;
			$info = pathinfo($plugin_info['source']);
			$plugin_info['slug'] = $main_file;
			$plugin_info['file'] = $info['basename'];

			if(file_exists($this->plugins_path . '/'. $plugin_info['slug'] ) ) {

				//$file_data = file_get_contents($this->plugins_path . '/'. $plugin_info['slug'] );
				$file_data = $wp_filesystem->get_contents($this->plugins_path . '/'. $plugin_info['slug']);
			    $plugin_installed_info = $this->get_data_by_headers($file_data, $default_headers);
			    $plugin_info['install_version'] = $plugin_installed_info['Version'];
 			}
 			else
 				$plugin_info['install_version'] = '';


			return $plugin_info;
		}
		else{
			return false;
		}
	}

	private function get_data_by_headers($file_data, $default_headers , $context = ''){
		// Make sure we catch CR-only line endings.
		$file_data = str_replace( "\r", "\n", $file_data );

		if ( $context && $extra_headers = apply_filters( "extra_{$context}_headers", array() ) ) {
			$extra_headers = array_combine( $extra_headers, $extra_headers ); // keys equal values
			$all_headers = array_merge( $extra_headers, (array) $default_headers );
		} else {
			$all_headers = $default_headers;
		}

		foreach ( $all_headers as $field => $regex ) {
			if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( $regex, '/' ) . ':(.*)$/mi', $file_data, $match ) && $match[1] )
				$all_headers[ $field ] = _cleanup_header_comment( $match[1] );
			else
				$all_headers[ $field ] = '';
		}

		return $all_headers;
	}

	public function load_new_plugin( $file ) {

		$overrides = array( 'test_form' => false, 'test_type' => false );
		$plug_file = wp_handle_upload( $file, $overrides );
		$src = $plug_file['file'];
		$dst = $this->plugins_zip_path.$file['name'];
		copy($src, $dst);
	}

}
?>