<?php

/**
 * Render the signup form.
 *
 * @var $fields array
 * @var $formSettings array
 * @var $options array from theme/ ThemeHelper
 * @var $apiErrors array|null
 * @var $showSuccessMessage bool
 * @var $validationErrors array|null
 * @var $validationValues array|null
 */

?>
<?php if (is_array($apiErrors)) : ?>
<?php foreach ($apiErrors as $err) : ?>
<p class="alert alert-danger"><?php echo esc_html($err) ?></p>
<?php endforeach; ?>
<?php endif; ?>

<?php if ($showSuccessMessage) : ?>
<p class="alert alert-success"><?php echo __('Thanks for subscribing to our newsletter. And welcome onboard!', 'bufu-rapidmail') ?></p>
<?php else : ?>
<form class="bufu-rapidmail-signup" id="<?php echo $formSettings['id'] ?>" action="<?php echo esc_attr($formSettings['url']) ?>#<?php echo esc_attr($options['container_id']) ?>" method="post">
	<input type="hidden" name="<?php echo $formSettings['element_namespace'] ?>[target]" value="<?php echo esc_attr($formSettings['redirect']) ?>#<?php echo  esc_attr($options['container_id']) ?>">

	<div class="form-row">
		<div class="col-6 col-lg-5">
			<div class="form-group">
				<input type="text" class="form-control<?php if (is_array($validationErrors) && isset($validationErrors['firstname'])) : ?> is-invalid<?php endif;?>" name="<?php echo $formSettings['element_namespace'] ?>[firstname]" placeholder="<?php echo esc_attr($fields['firstname']['label']) ?>" required<?php if (is_array($validationValues) && isset($validationValues['firstname'])) : ?> value="<?php echo esc_attr($validationValues['firstname']) ?>"<?php endif; ?>>
			<?php if (is_array($validationErrors) && isset($validationErrors['firstname'])) : ?>
				<div class="invalid-feedback"><?php echo esc_html($validationErrors['firstname']) ?></div>
			<?php endif; ?>
			</div>
		</div>
		<div class="col-6 col-lg-5">
			<div class="form-group">
				<input type="text" class="form-control<?php if (is_array($validationErrors) && isset($validationErrors['lastname'])) : ?> is-invalid<?php endif;?>" name="<?php echo $formSettings['element_namespace'] ?>[lastname]" placeholder="<?php echo esc_attr($fields['lastname']['label']) ?>" required<?php if (is_array($validationValues) && isset($validationValues['lastname'])) : ?> value="<?php echo esc_attr($validationValues['lastname']) ?>"<?php endif; ?>>
			<?php if (is_array($validationErrors) && isset($validationErrors['lastname'])) : ?>
				<div class="invalid-feedback"><?php echo esc_html($validationErrors['lastname']) ?></div>
			<?php endif; ?>
			</div>
		</div>
	</div>
	<div class="form-row">
		<div class="col-12 col-lg-10">
			<div class="form-group">
				<input type="email" class="form-control<?php if (is_array($validationErrors) && isset($validationErrors['email'])) : ?> is-invalid<?php endif;?>" name="<?php echo $formSettings['element_namespace'] ?>[email]" placeholder="<?php echo esc_attr($fields['email']['label']) ?>" required<?php if (is_array($validationValues) && isset($validationValues['email'])) : ?> value="<?php echo esc_attr($validationValues['email']) ?>"<?php endif; ?>>
			<?php if (is_array($validationErrors) && isset($validationErrors['email'])) : ?>
				<div class="invalid-feedback"><?php echo esc_html($validationErrors['email']) ?></div>
			<?php endif; ?>
			</div>
		</div>
	</div>
	<div class="form-row">
		<div class="col-12">
			<div class="form-group interest-segments">
				<p class="segments-label"><?php echo $fields['interest']['label'] ?></p>
				<?php foreach ($fields['interest']['options'] as $v => $l) :
					$elId = $formSettings['element_namespace'] . '-' . 'interest' . '-' . $v;
				?>
				<label for="<?php echo $elId ?>">
					<input type="checkbox" id="<?php echo $elId ?>" name="<?php echo $formSettings['element_namespace'] ?>[interest][]" value="<?php echo esc_attr($v) ?>"<?php if ((in_array($v, $fields['interest']['default']) && !is_array($validationValues)) || (is_array($validationValues) && isset($validationValues['interest'])) && in_array($v, $validationValues['interest'])) : ?> checked="checked"<?php endif; ?>> <?php echo esc_html($l) ?>
				</label>
				<?php endforeach; ?>
				<?php if (is_array($validationErrors) && isset($validationErrors['interest'])) : ?>
					<div class="invalid-feedback d-block"><?php echo esc_html($validationErrors['interest']) ?></div>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<button type="submit" class="btn btn-primary"><?php echo esc_html($options['submit_button_label']) ?></button>
</form>
<?php endif; ?>