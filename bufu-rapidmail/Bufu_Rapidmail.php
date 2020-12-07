<?php

require_once 'Bufu_Rapidmail_Admin.php';
require_once 'Bufu_Rapidmail_Client.php';
require_once 'Bufu_Rapidmail_Form.php';
require_once 'Bufu_Rapidmail_ThemeHelper.php';

class Bufu_Rapidmail
{
	private static $translationSlug = 'bufu-rapidmail';

	/**
	 * @var array
	 */
	const SETTINGS_KEYS = [
		'username' => 'bufu_rapidmail_apiv3_username',
		'password' => 'bufu_rapidmail_apiv3_password',
		'listId'   => 'bufu_rapidmail_apiv3_recipientlist',
	];

	/**
	 * @var Bufu_Rapidmail_ThemeHelper
	 */
	private $themeHelper;

	/**
	 * @var Bufu_Rapidmail_Admin
	 */
	private $admin;

	/**
	 * @var Bufu_Rapidmail_Client
	 */
	private $client;

	/**
	 * @var Bufu_Rapidmail_Form
	 */
	private $form;

	/**
	 * Bufu_Rapidmail constructor.
	 */
	public function __construct()
	{
		$this->client      = new Bufu_Rapidmail_Client();

		$this->admin       = new Bufu_Rapidmail_Admin($this->client);
		$this->form        = new Bufu_Rapidmail_Form($this->client);

		$this->themeHelper = new Bufu_Rapidmail_ThemeHelper($this->form);
	}

	/**
	 * Hook into WP.
	 */
	public function initHooks()
	{
		add_action('plugins_loaded', [$this, 'hook_plugins_loaded']);

		$this->admin->initHooks();
		$this->form->initHooks();
	}

	// -----------------------------------------------------------------------------------------------------------------
	// ----- Hooks into WP ---------------------------------------------------------------------------------------------

	/**
	 * Called upon to the plugins_loaded event.
	 * Load plugin translations.
	 * @return void
	 */
	public function hook_plugins_loaded()
	{
		$this->loadTranslations();
	}
	
	// -----------------------------------------------------------------------------------------------------------------
	// ----- ThemeHelper access ----------------------------------------------------------------------------------------

	/**
	 * @return Bufu_Rapidmail_ThemeHelper
	 */
	public function getThemeHelper()
	{
		return $this->themeHelper;
	}

	// -----------------------------------------------------------------------------------------------------------------
	// ----- private methods -------------------------------------------------------------------------------------------

	/**
	 * Load translations for the plugin.
	 * @return void
	 */
	private function loadTranslations()
	{
		load_muplugin_textdomain(self::$translationSlug, self::$translationSlug . '/languages/');
	}
}