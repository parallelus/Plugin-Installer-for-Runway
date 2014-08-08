<?php

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
		return $plugins;
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

	private function get_header_info($plugins_zip_path, $plug_file){
		$plugin_info = array();
		$file_data = '';

		$zip = new ZipArchive;
		$info = pathinfo($plug_file);
		if ($zip->open($plugins_zip_path . $plug_file)) {
			for ($i = 0; $i < $zip->numFiles; $i++) {
				$source = $zip->getNameIndex($i);
				if (strpos($source, '.php') !== false) {
					$file_content = $zip->getFromName($source);
					if (strpos($file_content, 'Plugin Name')) {
						$main_file = $source;
						$file_data = $file_content;
					}
				}
			}
		}
 		$zip->close();	
		
		if(!function_exists('WP_Filesystem'))
			require_once(ABSPATH . 'wp-admin/includes/file.php');
		WP_Filesystem();
		global $wp_filesystem;

		if($file_data != ''){
			$default_headers = array(
				'Name' => __('Plugin Name', 'framework'),
				'PluginURI' => __('Plugin URI', 'framework'),
				'Version' => __('Version', 'framework'),
				'Description' => __('Description', 'framework'),
				'Author' => __('Author', 'framework'),
				'AuthorURI' => __('Author URI', 'framework'),
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