<?php
if ( isset( $info_message ) && $info_message != "" ) {
	?>

	<div class="error"><?php echo $info_message; ?></div>

	<?php
}
?>

<h2><?php _e( 'Upload a File', 'runway' ); ?></h2>

<h4><?php _e( 'Copy a plugin in .zip format to Plugin Installer plugins folder', 'runway' ) ?></h4>
<p class="install-help"><?php _e( 'If you have a plugin in a .zip format, you may copy it by uploading it here.', 'runway' ) ?></p>
<form method="post" enctype="multipart/form-data" action="<?php echo self_admin_url( 'admin.php?page=plugin-installer&navigation=add-plugin' ) ?>">
	<?php wp_nonce_field( 'plugin-upload-action', 'plugin-upload-field' ) ?>
	<label class="screen-reader-text" for="plugzip"><?php _e( 'Plugin zip file', 'runway' ); ?></label>
	<input type="file" id="plugzip" name="plugzip"/>
	<input type="submit" class="button" value="<?php esc_attr_e( 'Copy Now', 'runway' ) ?>" name="plug-submit"/>
</form>

<h2><?php _e( 'OR', 'runway' ); ?></h2>

<h2><?php _e( 'Select a Plugin from the WordPress Repository', 'runway' ); ?></h2>

<form method="post" action="<?php echo self_admin_url( 'admin.php?page=plugin-installer&navigation=add-plugin-by-url' ); ?>">
	<?php wp_nonce_field( 'plugin-by-url-action', 'plugin-by-url-field' ) ?>
	<p class="install-help"><?php _e( 'Plugin URL', 'runway' ) ?></p>
	<input type="text" id="plugin_url" name="plugin_url"/>
	<input type="submit" class="button" value="<?php _e( 'Select Plugin', 'runway' ); ?>" name="plugin_url_submit"/>
</form>
