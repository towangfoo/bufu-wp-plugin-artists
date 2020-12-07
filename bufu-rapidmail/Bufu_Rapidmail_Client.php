<?php

require_once 'Bufu_Rapidmail.php';
require_once 'vendor/rapidmail-apiv3-client-php/AdapterInterface.php';
require_once 'vendor/rapidmail-apiv3-client-php/Apiv3.php';

class Bufu_Rapidmail_Client
{
	/**
	 * @var \Rapidmail\Api\AdapterInterface
	 */
	private $adapter;

	/**
	 * @var array|null
	 */
	private $lastErrors = null;

	/**
	 * @return \Rapidmail\Api\Apiv3
	 */
	private function getAdapter()
	{
		if (!$this->adapter) {
			$settings = $this->getSettings();
			$this->adapter = new \Rapidmail\Api\Apiv3($settings['username'], $settings['password'], $settings['listId']);
		}

		return $this->adapter;
	}

	/**
	 * @return array
	 */
	private function getSettings()
	{
		return [
			'username' => get_option(Bufu_Rapidmail::SETTINGS_KEYS['username'], ''),
			'password' => get_option(Bufu_Rapidmail::SETTINGS_KEYS['password'], ''),
			'listId'   => intval(get_option(Bufu_Rapidmail::SETTINGS_KEYS['listId'], 0)),
			'showApiErrors' => (get_option(Bufu_Rapidmail::SETTINGS_KEYS['showApiErrors'], 'no') === 'yes'),
		];
	}

	/**
	 * @return array|null
	 */
	public function getLastErrors()
	{
		return $this->lastErrors;
	}

	/**
	 * Check if we have valid credentials.
	 * Do not call from the frontend.
	 * @return bool
	 */
	public function isAuthenticated()
	{
		return $this->getAdapter()->isAuthenticated();
	}

	/**
	 * @return array
	 */
	public function getRecipientLists()
	{
		return $this->getAdapter()->getRecipientlists();
	}

	/**
	 * Subscribe a recipient to a list.
	 * @param array $data
	 * @return bool
	 */
	public function subscribe(array $data)
	{
		$validationErrors = [];
		$signupData = [];

		foreach (['email', 'firstname', 'lastname', 'interest'] as $f) {
			if (!array_key_exists($f, $data)) {
				$validationErrors[] = sprintf(__('Missing required subscriber field `%s`', 'bufu-rapidmail'), $f);
			}
			$signupData[$f] = $data[$f];
		}

		if (count($validationErrors) > 0) {
			$this->lastErrors = $validationErrors;
			return false;
		}

		// rename 'interest' to 'extra1' and make values a comma-separated string
		$signupData['extra1'] = join(",", $signupData['interest']);
		unset($signupData['interest']);

		// set status to `active`
		$signupData['status'] = 'active';

		// extra params
		$params = [
			'track_stats'         => 'yes',    // whether to track statistics about the subscriber
			'send_activationmail' => 'no',     // whether to send DOI activation mail
		];

		$listId = $this->getSettings()['listId'];
		$result = $this->getAdapter()->subscribeRecipient($listId, $signupData, $params);

		if ($result === true) {
			return true;
		}

		$this->lastErrors = [__('Sorry, an error occurred while signing you up', 'bufu-rapidmail')];

		// make error messages available to the outside?
		$showErrors = $this->getSettings()['showApiErrors'];
		if ($showErrors) {
			if ($result instanceof WP_Error) {
				$this->lastErrors[] = $result->get_error_message();
			}
			else if (is_array($result) && array_key_exists('detail', $result)) {
				$this->lastErrors[] = $result['detail'];
			}
		}

		return false;
	}
}