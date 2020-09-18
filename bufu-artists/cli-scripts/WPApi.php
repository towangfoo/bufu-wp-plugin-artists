<?php

/**
 * Created by PhpStorm.
 * User: towangfoo
 * Date: 11.09.20
 * Time: 13:39
 */
class WPApi
{

	/**
	 * @var array
	 */
	private $config = [];

	public function __construct(array $config)
	{
		$this->config = $config;
	}

	/**
	 * @param array $params
	 * @param int $postId [optional]
	 * @return array|false
	 */
	public function savePost(array $params, $postId = null)
	{
		$url = $this->config['url'] . '/' . $this->config['endpoint'];

		// if a post id is given, update that
		if (is_int($postId) && $postId > 0) {
			$url .= "/{$postId}";
		}

		$response = $this->curlPost($url, $params);

		return $response;
	}

	/**
	 * @param $url
	 * @param array $data
	 * @return array
	 */
	private function curlPost($url, array $data)
	{
		$authUser = $this->config['authorization']['username'];
		$authPass = $this->config['authorization']['password'];

		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,  http_build_query($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, "{$authUser}:{$authPass}");
		
		$response = curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$curlErrNo = curl_errno($ch);
		$curlError = curl_error($ch);

		if ($curlErrNo !== 0) {
			return [
				'code'    => 'curl_error',
				'message' => $curlError,
				'data'    => [
					'status'          => $status,
					'curl_error_code' => $curlErrNo
				]
			];
		}
		else {
			return json_decode($response, true);
		}
	}
}