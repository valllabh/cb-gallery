<div class="form-field">
	<label><?php _e('Applicable to Post types'); ?></label>
	<input type="hidden" name="applicable_post_types_sent" value="1" />
	<?php foreach ($all_post_types as $post_type) { ?>							
		<label class="post-type">
			<input type="checkbox" value="<?php echo $post_type->name; ?>" name="applicable_post_types[]"/>
			<?php echo $post_type->label; ?>
		</label>
	<?php } ?>
</div>
<div class="form-field">
	<label><?php _e('Applicable to Taxonomies') ?></label>
	<input type="hidden" name="applicable_taxonomies_sent" value="1" />
	<?php foreach ($all_taxonomies as $taxonomy) { ?>
		<label class="taxonomy-type">
			<input type="checkbox" value="<?php echo $taxonomy->name; ?>" name="applicable_taxonomies[]"/>
			<?php echo $taxonomy->label; ?> ( <?php echo implode(', ', $taxonomy->post_types) ?> )
		</label>
	<?php } ?>
</div>