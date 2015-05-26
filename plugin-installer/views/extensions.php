<form action="<?php echo esc_url($this->self_url('extensions') .'&action=install-extension'); ?>" method="post"><br>
<table class="wp-list-table widefat plugins">
	<thead>
		<tr>
			<th scope="col" id="cb" class="manage-column column-cb check-column" style="width: 0px;"><input type="checkbox" name="ext_chk[]" /></th>
			<th id="name" class="manage-column column-name"><?php echo __('Extension', 'framework'); ?></th>
			<th id="description" class="manage-column column-description"><?php echo __('Description', 'framework'); ?></th>
		</tr>
	</thead>
	<tbody id="the-list">
	<?php
if ( !empty( $exts ) ):
	foreach ( $exts as $ext => $ext_info ):
		$ext_cnt = !$extm->is_activated( $ext );
?>
		<?php if($extm->is_activated( $ext )): ?>
		<tr class="inactive">
			<th class="check-column">
				<input type="checkbox" name="ext_chk[]" value="<?php echo esc_attr($ext); ?>" />
			</th>
			<td class="plugin-title">
				<strong><?php rf_e($ext_info['Name']); ?></strong>
			</td>
			<td class="column-description desc">
				<?php
					// Item description
					$description = '<div class="plugin-description"><p>'. rf__($ext_info['Description']) .'</p></div>';
					// Item info
					$class = ( $ext_cnt ) ? 'inactive' : 'active' ;					
					$version = ( $ext_info['Version'] ) ? __('Version', 'framework').': '.$ext_info['Version'] : '';
					if ( $ext_info['Author'] ) {
						$author = ' | By '. $ext_info['Author'];
						if ( $ext_info['AuthorURI'] ) {
							$author = ' | By <a href="'. esc_url($ext_info['AuthorURI']) .'" title="'.__('Visit author homepage', 'framework').'">'. $ext_info['Author'] .'</a>';
						}
					}
					else {
						$author = ' | By Unknown';	
					}
					$plugin_link = ( $ext_info['ExtensionURI'] ) ? ' | <a href="'. esc_url($ext_info['ExtensionURI']) .'" title="'. esc_attr( __('Visit plugin site', 'framework') ).'">'.__('Visit plugin site', 'framework').'</a>' : '';
					$info = '<div class="'. esc_attr($class) .' second plugin-version-author-uri">'. $version . $author . $plugin_link .'</div>';

					// Print details
					echo  $description; // escaped above
					echo  $info; // escaped above
				?>
			</td>
		</tr>
	<?php endif; endforeach; else: ?>
		<tr calss="active">
			<td class="plugin-title">
				<?php echo __('Extensions not found', 'framework'); ?>.
			</td>
			<td class="column-description desc"> </td>
		</tr>
	<?php endif; ?>
	</tbody>
</table><br>

<input class="button-primary" type="submit" value="<?php echo __('Allow Install as Plugin', 'framework'); ?>">
</form>