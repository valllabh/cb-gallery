<div class="form-field">
	<h4><?php _e('Applicable to Post types'); ?></h4>
	<input type="hidden" name="applicable_post_types_sent" value="1" />
	<?php foreach ($all_post_types as $post_type) { ?>							
		<label class="applicable-to">
			<input type="checkbox" value="<?php echo $post_type->name; ?>" name="applicable_post_types[]"/>
			<?php echo $post_type->label; ?>
		</label>
	<?php } ?>
</div>
<div class="form-field">
	<h4><?php _e('Applicable to Taxonomies') ?></h4>
	<input type="hidden" name="applicable_taxonomies_sent" value="1" />
	<?php foreach ($all_taxonomies as $taxonomy) { ?>
		<label class="applicable-to">
			<input type="checkbox" value="<?php echo $taxonomy->name; ?>" name="applicable_taxonomies[]"/>
			<?php echo $taxonomy->label; ?> ( <?php echo implode(', ', $taxonomy->post_types) ?> )
		</label>
	<?php } ?>
</div>
<div class="form-field">
	<h4><?php _e('Applicable to User Roles') ?></h4>
	<input type="hidden" name="applicable_user_roles_sent" value="1" />
	<?php foreach ($all_user_roles as $user_role => $user_role_name) { ?>
		<label class="applicable-to">
			<input type="checkbox" value="<?php echo $user_role; ?>" name="applicable_user_roles[]" />
			<?php echo $user_role_name; ?>
		</label>
	<?php } ?>
</div>