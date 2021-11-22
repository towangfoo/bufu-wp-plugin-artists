<?php

require_once 'Bufu_Rapidmail_Client.php';

class Bufu_Rapidmail_Admin
{
	/**
	 * @var Bufu_Rapidmail_Client
	 */
	private $client;

	/**
	 * Bufu_Rapidmail_Admin constructor.
	 * @param Bufu_Rapidmail_Client $client
	 */
	public function __construct( Bufu_Rapidmail_Client $client )
	{
		$this->client = $client;
	}

	public function initHooks()
	{
		add_action( 'admin_init', [$this, 'hook_admin_init'] );
		add_action( 'admin_menu', [$this, 'hook_admin_menu'] );
	}

	// -----------------------------------------------------------------------------------------------------------------
	// ----- Hooks into WP ---------------------------------------------------------------------------------------------

	/**
	 * Initialize admin settings.
	 */
	public function hook_admin_init()
	{
		add_settings_section(
			'bufu_rapidmail_settings',
			'',
			'',
			'bufu_rapidmail'
		);

		add_settings_field(
			Bufu_Rapidmail::SETTINGS_KEYS['username'],
			__('API Username', 'bufu-rapidmail'),
			[$this, 'render_apiUsername'],
			'bufu_rapidmail',
			'bufu_rapidmail_settings'
		);
		register_setting( 'bufu_rapidmail', Bufu_Rapidmail::SETTINGS_KEYS['username'] );

		add_settings_field(
			Bufu_Rapidmail::SETTINGS_KEYS['password'],
			\__('API Password', 'bufu-rapidmail'),
			[$this, 'render_apiPassword'],
			'bufu_rapidmail',
			'bufu_rapidmail_settings'
		);
		register_setting( 'bufu_rapidmail', Bufu_Rapidmail::SETTINGS_KEYS['password'] );

		add_settings_field(
			Bufu_Rapidmail::SETTINGS_KEYS['listId'],
			__('Recipient list', 'bufu-rapidmail'),
			[$this, 'render_recipientList'],
			'bufu_rapidmail',
			'bufu_rapidmail_settings'
		);
		register_setting( 'bufu_rapidmail', Bufu_Rapidmail::SETTINGS_KEYS['listId'] );

		add_settings_field(
			Bufu_Rapidmail::SETTINGS_KEYS['showApiErrors'],
			__('Show API errors?', 'bufu-rapidmail'),
			[$this, 'render_showApiErrors'],
			'bufu_rapidmail',
			'bufu_rapidmail_settings'
		);
		register_setting( 'bufu_rapidmail', Bufu_Rapidmail::SETTINGS_KEYS['showApiErrors'] );
	}

	/**
	 * Add admin option page
	 */
	public function hook_admin_menu()
	{
		add_options_page(
			__('Rapidmail', 'bufu-rapidmail'),
			__('Rapidmail', 'bufu-rapidmail'),
			'manage_options',
			'bufu_rapidmail',
			[$this, 'render_optionsPage']
		);
	}

	// -----------------------------------------------------------------------------------------------------------------
	// ------ form element render callbacks ----------------------------------------------------------------------------

	/**
	 * Render options page.
	 */
	public function render_optionsPage()
	{
		?>
		<div class="wrap">
			<h1><?php echo sprintf(__('Settings %s Rapidmail', 'bufu-rapidmail'), '›') ?></h1>
			<form method="post" action="options.php">
			<?php
				settings_fields( 'bufu_rapidmail' );
				do_settings_sections( 'bufu_rapidmail' );
				submit_button( __('Save changes', 'bufu-rapidmail'), 'primary', 'save' );
			?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render form element for API username.
	 * @return void
	 */
	public function render_apiUsername()
	{
		$apiClient = $this->client;

		$id = Bufu_Rapidmail::SETTINGS_KEYS['username'];
		$value = get_option( $id );

		echo '<input type="text" class="regular-text" value="' . esc_html( $value ) . '" name="'. $id .'" id="'. $id .'">';

		if ( $apiClient && $apiClient->isAuthenticated() ) {
			echo '&nbsp;<img src="' . esc_url( admin_url( 'images/yes.png' ) ) . '" alt="' . __('Valid credentials', 'bufu-rapidmail') . '" title="' . __('Valid credentials', 'bufu-rapidmail') . '" />';
		}
		elseif ( $this->isConfigured() ) {
			echo '&nbsp;<img src="' . esc_url( admin_url( 'images/no.png' ) ) . '" alt="' . __('Invalid credentials', 'bufu-rapidmail') . '" title="' . __('Invalid credentials', 'bufu-rapidmail') . '" />';
		}
	}

	/**
	 * Render form element for API password.
	 * @return void
	 */
	public function render_apiPassword()
	{
		$id = Bufu_Rapidmail::SETTINGS_KEYS['password'];

		echo '<input type="password" class="regular-text" value="" name="'. $id .'" id="'. $id .'">';
	}

	/**
	 * Render form element for recipient list selection.
	 * @return void
	 */
	public function render_recipientList()
	{
		$apiClient = $this->client;

		$id = Bufu_Rapidmail::SETTINGS_KEYS['listId'];
		$value = intval( get_option($id, 0) );

		echo '<select name="'. $id .'" id="'. $id .'">';

		if ( $apiClient && $apiClient->isAuthenticated() ) {
			echo '<option value="0">' . __('Please choose', 'bufu-rapidmail') . '...</option>';

			$recipientlists = $apiClient->getRecipientLists();

			foreach ( $recipientlists AS $id => $label ) {
				echo '<option value="' . $id . '"' . ( $value === $id ? ' selected="selected"' : '' ) . '>' . esc_html( $label ) . ' (ID ' . $id . ')</option>';
			}
		}
		else {
			echo '<option value="0">' . sprintf(__('Please provide valid credentials in the fields above and press `%s`', 'bufu-rapidmail'), __('Save changes', 'bufu-rapidmail')) . '</option>';
		}

		echo '</select>';
	}

	public function render_showApiErrors()
	{
		$id = Bufu_Rapidmail::SETTINGS_KEYS['showApiErrors'];
		$value = get_option( $id, 'no' );

		echo '<select name="'. $id .'" id="'. $id .'">';
		echo '<option value="no"'.  ($value === 'no' ?  ' selected="selected"' : '') .'>' . __('No', 'bufu-rapidmail') . '</option>';
		echo '<option value="yes"'. ($value === 'yes' ? ' selected="selected"' : '') .'>' . __('Yes', 'bufu-rapidmail') . '</option>';
		echo '</select>';
	}

	/**
	 * @return bool
	 */
	private function isConfigured()
	{
		$u = get_option( Bufu_Rapidmail::SETTINGS_KEYS['username'] );
		$p = get_option( Bufu_Rapidmail::SETTINGS_KEYS['password'] );

		return !empty( $u ) && !empty( $p );
	}
}