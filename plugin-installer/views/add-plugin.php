<?php 
	if(isset($info_message) && $info_message != "") {
		?>
<div class="error"><?php echo  $info_message; ?></div>
		<?php
	}
?>

<h2><?php _e('Upload a File', 'framework'); ?></h2>

<h4><?php _e( 'Copy a plugin in .zip format to Plugin Installer plugins folder', 'framework' ) ?></h4>
<p class="install-help"><?php _e( 'If you have a plugin in a .zip format, you may copy it by uploading it here.', 'framework' ) ?></p>
<form method="post" enctype="multipart/form-data" action="<?php echo self_admin_url( 'admin.php?page=plugin-installer&navigation=add-plugin' ) ?>">
	<?php wp_nonce_field( 'plugin-upload-action', 'plugin-upload-field' ) ?>
	<label class="screen-reader-text" for="plugzip"><?php _e( 'Plugin zip file', 'framework' ); ?></label>
	<input type="file" id="plugzip" name="plugzip" />
	<input type="submit" class="button" value="<?php esc_attr_e( 'Copy Now', 'framework' ) ?>" name="plug-submit" />
</form>	

<h2><?php _e('OR', 'framework'); ?></h2>

<h2><?php _e('Select a Plugin from the WordPress Repository', 'framework'); ?></h2>

<form method="post" action="<?php echo self_admin_url( 'admin.php?page=plugin-installer&navigation=add-plugin-by-url' ); ?>">
	<p class="install-help"><?php _e( 'Plugin URL', 'framework' ) ?></p>
	<input type="text" id="plugin_url" name="plugin_url" />
	<input type="submit" class="button" value="<?php _e('Select Plugin', 'framework'); ?>" name="plugin_url_submit" />
</form>