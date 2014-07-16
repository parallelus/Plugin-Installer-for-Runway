<h4><?php _e( 'Copy a plugin in .zip format to Plugin Installer plugins folder', 'framework' ) ?></h4>
<p class="install-help"><?php _e( 'If you have a plugin in a .zip format, you may copy it by uploading it here.', 'framework' ) ?></p>
<form method="post" enctype="multipart/form-data" action="<?php echo self_admin_url( 'admin.php?page=plugin-installer&navigation=add-plugin' ) ?>">
	<?php wp_nonce_field( 'plugin-upload-action', 'plugin-upload-field' ) ?>
	<label class="screen-reader-text" for="plugzip"><?php _e( 'Plugin zip file', 'framework' ); ?></label>
	<input type="file" id="plugzip" name="plugzip" />
	<input type="submit" class="button" value="<?php esc_attr_e( 'Copy Now', 'framework' ) ?>" name="plug-submit" />
</form>	
