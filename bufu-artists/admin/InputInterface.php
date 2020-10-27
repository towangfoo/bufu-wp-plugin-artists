<?php

/**
 * Created by PhpStorm.
 * User: towangfoo
 * Date: 24.10.20
 * Time: 11:56
 */

interface InputInterface
{
	/**
	 * @param $name
	 * @param array $options
	 * @return InputInterface|WP_Error
	 */
	public static function create($name, array $options);

	/**
	 * @return void
	 */
	public function addMetaBox();

	/**
	 * @return string
	 */
	public function getName();

	/**
	 * @return mixed
	 */
	public function getValue();

	/**
	 * @param mixed $value
	 */
	public function setValue($value);

	/**
	 * @return bool
	 */
	public function isMultiple();
}