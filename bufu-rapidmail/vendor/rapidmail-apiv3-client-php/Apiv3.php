<?php

/**
 * Original taken from `rapidmail-newsletter-software` WP plugin by Rapidmail GmbH.
 * Slightly modified:
 *     - allow for custom query params in ::subscribeRecipient()
 *     - return WP_Error or WP_HTTP_Response details from ::subscribeRecipient()
 *     - added the base URL directly in here
 *
 * Plugin Name: rapidmail newsletter marketing
 * Description: Widget für die Integration eines rapidmail Anmeldeformulars in der Sidebar sowie ein Plugin für die Gewinnung von Abonnenten über die Kommentarfunktion.
 * Author: rapidmail GmbH
 * Version: 2.1.5
 * Author URI: http://www.rapidmail.de
 * License: GPL2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Min WP Version: 4.6
 */

namespace Rapidmail\Api;

require_once 'AdapterInterface.php';

/**
 * APIv3 adapter class
 */
class Apiv3 implements AdapterInterface {

	/**
	 * @var string
	 */
	const API_BASE_URL = 'https://apiv3.emailsys.net';

	/**
	 * @var string
	 */
	const API_GET_RL_RESOURCE = '/v1/recipientlists/%u';

	/**
	 * @var string
	 */
	const API_GET_RLS_RESOURCE = '/v1/recipientlists';

	/**
	 * @var string
	 */
	const API_CREATE_RCPT_RESOURCE = '/v1/recipients';

	/**
	 * @var string
	 */
	const API_GET_FORM_FIELDS_RESOURCE = '/v1/forms/%u-default';

	/**
	 * @var string
	 */
	private $username;

	/**
	 * @var string
	 */
	private $password;

	/**
	 * @var int
	 */
	private $recipientlistId;

	/**
	 * @var bool
	 */
	private $isAuthenticated = false;

	/**
	 * Constructor
	 *
	 * @param string $username
	 * @param string $password
	 * @param int $recipientlistId
	 */
	public function __construct($username, $password, $recipientlistId) {

		$this->username = $username;
		$this->password = $password;
		$this->recipientlistId = $recipientlistId;

	}

	/**
	 * @inheritdoc
	 */
	public function isAuthenticated() {

		if ($this->isAuthenticated) {
			return true;
		}

		$response = $this->request($this->url(self::API_GET_RLS_RESOURCE));

		if (\is_wp_error($response)) {
			return false;
		}

		/** @var $response \WP_HTTP_Response */
		$response = $response['http_response'];
		$statusCode = $response->get_status();
		return $this->isAuthenticated = $statusCode >= 200 && $statusCode < 300;

	}

	/**
	 * @inheritdoc
	 */
	public function isConfigured() {
		return !empty($this->username) && !empty($this->password);
	}

	/**
	 * Trigger wp_remote_get call
	 *
	 * @param string $url
	 * @param array $args
	 * @return \WP_HTTP_Response|\WP_Error
	 */
	private function request($url, array $args = []) {

		if (!isset($args['headers'])) {
			$args['headers'] = [];
		}

		$args['headers']['Authorization'] = 'Basic ' . \base64_encode($this->username . ':' . $this->password);
//		$args['headers']['User-Agent'] = 'rapidmail Wordpress Plugin ' . Rapidmail::PLUGIN_VERSION . ' on Wordpress ' . \get_bloginfo('version');
		$args['headers']['Accept'] = 'application/json';

		return \wp_remote_request(
			$url,
			$args
		);

	}

	/**
	 * Get list of recipientlists
	 *
	 * @return array
	 */
	public function getRecipientlists() {

		$recipientlists = [];
		$page = 1;

		do {

			$response = $this->request($this->url(self::API_GET_RLS_RESOURCE, [], ['page' => $page]));

			if (\is_wp_error($response)) {
				return [];
			}

			/** @var $response \WP_HTTP_Response */
			$response = $response['http_response'];
			$statusCode = (int)$response->get_status();

			if ($statusCode === 200) {

				$response = \json_decode($response->get_data(), true);

				foreach ($response['_embedded']['recipientlists'] AS $recipientlist) {
					$recipientlists[$recipientlist['id']] = $recipientlist['name'];
				}

			}

			$page++;

		} while ($statusCode === 200 && $response['page'] < $response['page_count']);

		return $recipientlists;

	}

	/**
	 * @inheritdoc
	 */
	public function getRecipientlist($recipientlistId) {

		$response = $this->request($this->url(self::API_GET_RL_RESOURCE, [$recipientlistId]));

		if (\is_wp_error($response)) {
			return null;
		}

		/** @var $response \WP_HTTP_Response */
		$response = $response['http_response'];

		if ((int)$response->get_status() === 200) {
			return \json_decode($response->get_data(), true);
		}

		return null;

	}

	/**
	 * Get full URL for given resource URL
	 *
	 * @param string $resource_url
	 * @param array $args
	 * @param array $queryParams
	 * @return string
	 */
	private function url($resource_url, array $args = [], array $queryParams = []) {

		$url = self::API_BASE_URL . \vsprintf(
			$resource_url,
			$args
		);

		if (!empty($queryParams)) {
			$url .= '?' . http_build_query($queryParams);
		}

		return $url;

	}

	/**
	 * @param int $recipientlistId
	 * @param array $recipientData
	 * @return bool|\WP_Error|array
	 */
	public function subscribeRecipient($recipientlistId, array $recipientData, array $queryParams = []) {

		$recipientData['recipientlist_id'] = $recipientlistId;

		// allow overriding query params
		$queryParams = array_merge(['send_activationmail' => 'yes', 'track_stats' => 'yes'], $queryParams);

		$response = $this->request($this->url(self::API_CREATE_RCPT_RESOURCE, [], $queryParams), [
			'method' => 'POST',
			'body' => json_encode($recipientData),
			'headers' => [
				'Content-Type' => 'application/json'
			]
		]);

		if (\is_wp_error($response)) {
			return $response;
		}

		/** @var $response \WP_HTTP_Response */
		$response = $response['http_response'];

		if (intval($response->get_status()) === 201) {
			return true;
		}

		return \json_decode($response->get_data(), true);
	}

	/**
	 * @inheritdoc
	 */
	public function getFormFields($recipientlistId)
	{

		$response = $this->request($this->url(self::API_GET_FORM_FIELDS_RESOURCE, [$recipientlistId]), [
			'method' => 'GET',
			'headers' => [
				'Content-Type' => 'application/json'
			]
		]);

		if (\is_wp_error($response)) {
			return null;
		}

		/** @var $response \WP_HTTP_Response */
		$response = $response['http_response'];

		if (intval($response->get_status()) === 200) {
			return \json_decode($response->get_data(), true)['fields'];
		}

		return null;

	}

}
