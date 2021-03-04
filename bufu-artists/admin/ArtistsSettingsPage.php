<?php

/**
 * Create a settings page for artists
 */
class ArtistsSettingsPage
{
	private static $formValueKey = 'bufu-artist-settings-form';

	public function initHooks()
	{
		add_action('admin_menu', [$this, 'addPage']);

		// handle POST data, when the right form is in there
		if (isset($_POST['form_identifier']) && $_POST['form_identifier'] === self::$formValueKey) {
			$this->handlePost($_POST);
		}
	}

	public function addPage()
	{
		add_submenu_page('edit.php?post_type=bufu_artist', __('Settings', 'bufu-artists'),   __('Settings', 'bufu-artists'), 'edit_posts', 'settings', [$this, 'renderSettingsPage']);
	}

	public function renderSettingsPage()
	{
		?>
		<div class="bufu-artist-settings wrapper">
			<h1><?php _e('Settings', 'bufu-artists') ?></h1>
			<?php echo $this->getFormFeedback() ?>

			<form method="post" action="edit.php?post_type=bufu_artist&page=settings">
				<input type="hidden" name="form_identifier" value="<?php echo self::$formValueKey ?>">
				<div class="item">
					<label for="bufu-artist-settings-action"><?php _e( 'Select an action to perform on save', 'bufu-artists' ) ?>:</label>
					<select id="bufu-artist-settings-action" name="action">
						<option></option>
						<option value="refresh_tx_ltr"><?php _e('Refresh artist starting letter taxonomy', 'bufu-artists' ) ?></option>
					</select>
				</div>
				<?php submit_button(__('Save', 'bufu-artists'), 'primary', 'save') ?>
			</form>
		</div>
		<?php
	}

	public function handlePost(array $data)
	{
		if ($data['action'] === 'refresh_tx_ltr') {
			// TODO
		}
	}

	public function getFormFeedback()
	{
		return '';
	}
}