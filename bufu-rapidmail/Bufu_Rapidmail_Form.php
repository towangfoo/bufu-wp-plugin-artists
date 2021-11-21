<?php

require_once 'Bufu_Rapidmail_Client.php';

class Bufu_Rapidmail_Form
{
	private static $post_element_namespace = 'bufu_rapidmail';

	private static $get_param_redirect_ack = 'nlok';
	private static $get_param_redirect_ack_ok = 'y';

	/**
	 * @var Bufu_Rapidmail_Client
	 */
	private $client;

	// instance caches for form template variables
	private $apiErrors          = null;
	private $showSuccessMessage = false;
	private $validationErrors   = null;
	private $validationValues   = null;

	/**
	 * Bufu_Rapidmail_ThemeHelper constructor.
	 */
	public function __construct( Bufu_Rapidmail_Client $client )
	{
		$this->client = $client;
	}

	/**
	 * Create hooks into WP.
	 */
	public function initHooks()
	{
		add_action( 'wp_loaded', [$this, 'hook_wp_loaded'] );
	}

	/**
	 * Get form HTML.
	 * @param array $options
	 * @return string
	 */
	public function getFormHtml( array $options = [] )
	{
		// assign variables used in template
		$fields = $this->getFormFields();
		$formSettings = [
			'id'                => 'newsletter-signup-form',
			'element_namespace' => self::$post_element_namespace,
			'url'               => '/',
			'redirect'          => '/?' . self::$get_param_redirect_ack . '=' . self::$get_param_redirect_ack_ok,
		];
		$options = array_merge([
			'container_id'        => 'newsletter-signup-form', // use form id by default, override to use custom page anchor
			'submit_button_label' => __('Sign up', 'bufu-rapidmail'),
		], $options);
		$apiErrors        = $this->apiErrors;
		$showSuccessMessage = $this->showSuccessMessage;
		$validationErrors = $this->validationErrors;
		$validationValues = $this->validationValues;

		// get template HTML
		ob_start();
		include 'templates/form.php';
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	// -----------------------------------------------------------------------------------------------------------------
	// ----- Hooks into WP ---------------------------------------------------------------------------------------------

	/**
	 * Event hook: when WP is fully loaded and initialized
	 */
	public function hook_wp_loaded()
	{
		// process $_POST
		$this->processPostDataAndMaybeRedirect();

		// check for $_GET flag used to indicate signup success
		if (
			is_array($_GET) &&
			array_key_exists(self::$get_param_redirect_ack, $_GET) &&
			$_GET[self::$get_param_redirect_ack] === self::$get_param_redirect_ack_ok
		) {
			$this->showSuccessMessage = true;
		}
	}

	// -----------------------------------------------------------------------------------------------------------------
	// ----- private methods -------------------------------------------------------------------------------------------

	/**
	 * Get form field definitions
	 * @return array
	 */
	private function getFormFields()
	{
		return [
			'firstname' => [
				'label' => __('First name', 'bufu-rapidmail'),
			],
			'lastname'  => [
				'label' => __('Last name', 'bufu-rapidmail'),
			],
			'email'     => [
				'label' => __('Email address', 'bufu-rapidmail'),
			],
			'interest'  => [
				'label' => __('I want to hear about:', 'bufu-rapidmail'),
				'options' => [
					'news'         => __('News', 'bufu-rapidmail'),
					'schoene'      => 'Gerhard Schöne',      // artist name, no translation needed
					'gundermann'   => 'Gerhard Gundermann',  // artist name, no translation needed
					'reiser'       => 'Rio Reiser',          // artist name, no translation needed
					'scherben'     => 'Ton Steine Scherben', // artist name, no translation needed
				],
				'default' => ['news', 'schoene', 'gundermann', 'reiser', 'scherben']
			],
			'interest2'  => [
				'label' => __('I am looking for press or event organizer information:', 'bufu-rapidmail'),
				'options' => [
					'print'        => __('Medien Print', 'bufu-rapidmail'),
					'radio'        => __('Medien Radio', 'bufu-rapidmail'),
					'fernsehen'    => __('Medien Fernsehen', 'bufu-rapidmail'),
					'kirchen'      => __('Veranstalter Kirchen', 'bufu-rapidmail'),
					'veranstalter' => __('Veranstalter', 'bufu-rapidmail'),
				],
				'default' => [ ]
			],
		];
	}

	/**
	 * Process $_POST data and trigger signup API call.
	 * Redirect to target URL, if set in post
	 * @return bool|null success or null, if not applicable
	 */
	private function processPostDataAndMaybeRedirect()
	{
		if ( array_key_exists(self::$post_element_namespace, $_POST) && is_array($_POST[self::$post_element_namespace]) ) {
			$data = $_POST[self::$post_element_namespace];
			if ( $this->isValid($data) && $this->signup($data) ) {
				if ( array_key_exists('target', $data) ) {
					wp_redirect( $data['target'], 300 );
				}

				$this->showSuccessMessage = true;
				return true;
			}
			else {
				return false;
			}
		}

		return null;
	}

	/**
	 * Validate POST data.
	 * @param array $post
	 * @return bool
	 */
	private function isValid( array $post )
	{
		$errors = [];

		// check text inputs for value
		$fields = $this->getFormFields();
		foreach ( ['firstname', 'lastname', 'email'] as $f ) {
			if ( !array_key_exists($f, $post) || empty($post[$f]) ) {
				$errors[$f] = sprintf(__('Missing value for %s', 'bufu-rapidmail'), $fields[$f]['label']);
			}
		}

		// check for valid email
		if ( !is_email($post['email']) && !array_key_exists('email', $errors) ) {
			$errors['email'] = __('Invalid email address', 'bufu-rapidmail');
		}

		// check for at least one interest segment
		if ( !array_key_exists('interest', $post) || !is_array($post['interest']) && count($post['interest']) < 1 ) {
			$errors['interest'] = __('Select at least one topic', 'bufu-rapidmail');
		}

		if ( count($errors) > 0 ) {
			$this->validationErrors = $errors;
			$this->validationValues = $post;
			return false;
		}

		return true;
	}

	/**
	 * Signup API call.
	 * @param array $data
	 * @return bool
	 */
	private function signup( array $data )
	{
		$result = $this->client->subscribe($data);
		if ( $result === true ) {
			return true;
		}

		$errors = $this->client->getLastErrors();
		if ( is_array($errors) && count($errors) > 0 ) {
			$this->apiErrors = $errors;
		}
		else {
			$this->apiErrors = [
				__('Sorry, an error occurred while signing you up', 'bufu-rapidmail'),
			];
		}

		$this->validationValues = $data;
		return false;
	}
}