<div class="cb-gallery-box">
	<input type="hidden" name="<?php echo $token; ?>[metabox]" value="1" />
	<ul class="attachments ui-sortable">
		<?php
			foreach ($attachments as $attachment) {
				$type = explode('/', $attachment->post_mime_type);
		?>
			<li class="attachment" id="attachment-<?php echo $attachment->ID; ?>">
				<a class="remove-link" onclick="return cb_gallery.attachment.remove(this);" title="Remove"></a>
				<div class="attachment-preview type-<?php echo $type[0]; ?> subtype-<?php echo $type[1]; ?>">
					<div class="thumbnail">
						<div class="centered">
							<?php echo wp_get_attachment_image($attachment->ID, 'thumbnail', 'thumbnail', true); ?>
						</div>
					</div>
					<div class="attachment-actions">
						<a class="edit-link" target="_blank" href="<?php echo get_edit_post_link($attachment->ID); ?>"><?php _e('Edit') ?></a>
					</div>
				</div>
				<input type="hidden" name="<?php echo $token; ?>[a][<?php echo $gallery_type->term_id; ?>][]" value="<?php echo $attachment->ID; ?>" />
			</li>
		<?php } ?>
	</ul>
	<div class="button-row">
		<a class="button button-large button-primary add-media" onclick="return cb_gallery.modal.open(this, {token:'<?php echo $token; ?>', term_id:<?php echo $gallery_type->term_id; ?>});" data-add-button="<?php echo __('Add'); ?>" data-title="<?php echo __('Select Media: '.$gallery_type->name); ?>"><?php echo __('Add Media'); ?></a>
	</div>
</div>
<style type="text/css">
	#<?php echo $args['id']; ?> .inside {
		padding: 0;
		margin: 0;
	}
</style>