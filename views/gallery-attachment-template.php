<script type="text/html" id="tmpl-gallery-attachment">
	<li class="attachment" id="attachment-{{data.id}}">
		<a class="remove-link" onclick="return cb_gallery.attachment.remove(this);" title="Remove"></a>
		<div class="attachment-preview type-{{data.attributes.type}} subtype-{{data.attributes.subtype}} {{data.attributes.orientation}}">
			<div class="thumbnail">
				<div class="centered">
					<img src="{{data.thumb}}"/>
				</div>
			</div>
			<div class="attachment-actions">
				<a class="edit-link" target="_blank" href="{{data.attributes.editLink}}"><?php _e('Edit') ?></a>
			</div>
		</div>
		<input type="hidden" name="{{data.token}}[a][]" value="{{data.id}}" />
		<input type="hidden" name="{{data.token}}[at][{{data.id}}][]" value="{{data.term_id}}" />
	</li>
</script>