<table class="form-table">
	<tbody>

		<tr class="form-field form-required">
			<th scope="row" valign="top"><label><?php _e('Applicable to Post types') ?></label></th>
			<td>
				<input type="hidden" name="applicable_post_types_sent" value="1" />
				<?php foreach ($all_post_types as $post_type) { ?>							
					<label class="applicable-to">
						<input type="checkbox" value="<?php echo $post_type->name; ?>" name="applicable_post_types[]" <?php checked(isset($applicable_post_types[$tag->term_id]) ? in_array($post_type->name, $applicable_post_types[$tag->term_id]) : false); ?>/>
						<?php echo $post_type->label; ?>
					</label>
				<?php } ?>
				
			</td>
		</tr>

		<tr class="form-field form-required">
			<th scope="row" valign="top"><label><?php _e('Applicable to Taxonomies') ?></label></th>
			<td>
				<input type="hidden" name="applicable_taxonomies_sent" value="1" />
				<?php foreach ($all_taxonomies as $taxonomy) { ?>
					<label class="applicable-to">
						<input type="checkbox" value="<?php echo $taxonomy->name; ?>" name="applicable_taxonomies[]" <?php checked(isset($applicable_taxonomies[$tag->term_id]) ? in_array($taxonomy->name, $applicable_taxonomies[$tag->term_id]) : false); ?>/>
						<?php echo $taxonomy->label; ?> ( <?php echo implode(', ', $taxonomy->post_types) ?> )
					</label>
				<?php } ?>
			</td>
		</tr>

		<tr class="form-field form-required">
			<th scope="row" valign="top"><label><?php _e('Applicable to User Roles') ?></label></th>
			<td>
				<input type="hidden" name="applicable_user_roles_sent" value="1" />
				<?php foreach ($all_user_roles as $user_role => $user_role_name) { ?>
					<label class="applicable-to">
						<input type="checkbox" value="<?php echo $user_role; ?>" name="applicable_user_roles[]" <?php checked(isset($applicable_user_roles[$tag->term_id]) ? in_array($user_role, $applicable_user_roles[$tag->term_id]) : false); ?>/>
						<?php echo $user_role_name; ?>
					</label>
				<?php } ?>
			</td>
		</tr>

	</tbody>
</table>