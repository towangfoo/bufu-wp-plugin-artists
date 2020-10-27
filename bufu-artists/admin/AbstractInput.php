<?php

/**
 * Created by PhpStorm.
 * User: towangfoo
 * Date: 24.10.20
 * Time: 12:35
 */
abstract class AbstractInput
{
	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var array
	 */
	private $options;

	/**
	 * @var mixed
	 */
	private $value;

	public function __construct($name, array $options)
	{
		$this->name    = $name;
		$this->options = $options;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param mixed $value
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}

	/**
	 * @param int|null $idx
	 * @return mixed
	 */
	public function getValue($idx = null)
	{
		return ($this->isMultiple() && is_array($this->value) && is_int($idx)) ? $this->value[$idx] : $this->value;
	}

	/**
	 * @return array
	 */
	protected function getOptions()
	{
		return $this->options;
	}

	/**
	 * Whether the input may render multiple items of itself
	 * @return bool
	 */
	public function isMultiple()
	{
		return (array_key_exists('multiple', $this->options) && $this->options['multiple'] === true);
	}

	/**
	 * @return int|false
	 */
	protected function getMinimumRequiredLinesForMultiple()
	{
		if (!$this->isMultiple()) {
			return false;
		}

		if (array_key_exists('min', $this->options) && is_int($this->options['min'])) {
			return $this->options['min'];
		}
		else {
			return 0;
		}
	}

	/**
	 * @return int|false
	 */
	protected function getMaximumLinesForMultiple()
	{
		if (!$this->isMultiple()) {
			return false;
		}

		if (array_key_exists('max', $this->options) && is_int($this->options['max'])) {
			return $this->options['max'];
		}
		else {
			return 0;
		}
	}

	/**
	 * @return void
	 */
	public function addMetaBox()
	{
		$options = $this->getOptions();

		$context = (array_key_exists('context', $options)) ? $options['context'] : 'normal';
		$screen  = (array_key_exists('screen', $options))  ? $options['screen']  : $options['post_type'];
		$renderMethod = $this->isMultiple() ? 'renderMultiple' : 'render';

		add_meta_box($this->getName(), $options['title'], [ $this, $renderMethod ], $screen, $context, "high");

	}

	/**
	 * Render single input.
	 * @return void
	 */
	abstract public function render();

	/**
	 * Render multiple input.
	 * @return void
	 */
	abstract public function renderMultiple();

	/**
	 * @param bool $isVisible
	 * @return string
	 */
	protected function getRemoveButtonHtml($isVisible = true)
	{
		$hidden = ($isVisible) ? '' : ' hidden';
		return '<button class="button'. $hidden .'" data-action-id="multiple-item-remove" title="'. __('Remove item', 'bufu-artists') .'">' . __('Remove', 'bufu-artists') . '</button>';
	}

	/**
	 * @param int $min
	 * @param int $max
	 * @param array $attrs
	 * @param bool $isHidden
	 * @return string
	 */
	protected function getAddItemButtonHtml($min = 0, $max = 0, $attrs = [], $isHidden = false)
	{
		$dataMin = ( $min > 0 ) ? ' data-min="'. $min .'"' : '';
		$dataMax = ( $max > 0 ) ? ' data-max="'. $max .'"' : '';

		$attrsStr = empty( $attrs ) ? '' : join(' ', $attrs );
		$hidden = ($isHidden) ? ' hidden' : '';

		return '<button class="button'. $hidden .'" data-action-id="multiple-item-add"'. $dataMin . $dataMax . $attrsStr .' title="'. __('Add item', 'bufu-artists') .'">' . __('Add item', 'bufu-artists') . '</button>';
	}
}