<?php

/**
 * Created by PhpStorm.
 * User: towangfoo
 * Date: 18.09.20
 * Time: 09:22
 */
class State
{
	private $stateFile = 'state.json';

	private $path;

	public function __construct()
	{
		$this->path = realpath(__DIR__);
	}

	protected function getFilePath()
	{
		return $this->path . '/' . $this->stateFile;
	}

	/**
	 * Get state information from file.
	 * @return array|null
	 * @throws \RuntimeException
	 */
	public function loadState()
	{
		$file = $this->getFilePath();

		if (!file_exists($file)) {
			return null;
		}
		$raw = file_get_contents($file);
		$decoded = json_decode($raw, true);

		if (json_last_error() !== 0) {
			throw new \RuntimeException(sprintf("Json error while decoding state file: %s", json_last_error_msg()));
		}

		return $decoded;
	}

	/**
	 * Write state information to file.
	 * @param array $data
	 * @return void
	 * @throws \RuntimeException
	 */
	public function saveState(array $data)
	{
		$file = $this->getFilePath();

		$encoded = json_encode($data);

		if (json_last_error() !== 0) {
			throw new \RuntimeException(sprintf("Json error while encoding state: %s", json_last_error_msg()));
		}

		if (file_put_contents($file, $encoded) === false) {
			throw new \RuntimeException("Failed to write to state file");
		}
	}
}