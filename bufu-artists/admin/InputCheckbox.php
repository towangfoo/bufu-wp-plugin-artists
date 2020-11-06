<?php

require_once 'AbstractInput.php';
require_once 'InputInterface.php';

/**
 * Checkbox input element.
 * @author Steffen Muecke <mail@quellkunst.de
 */
class InputCheckbox extends AbstractInput implements InputInterface
{

	/**
	 * @param $name
	 * @param array $options
	 * @return InputInterface|WP_Error
	 */
	public static function create($name, array $options)
	{
		$requiredOptions = ['post_type', 'title', 'labelText', 'value_on', 'value_off'];

		foreach ($requiredOptions as $o) {
			if (!array_key_exists($o, $options)) {
				return new WP_Error(sprintf(__('Input `%s` missing required option: `%s`', 'bufu-artists'), $name, $o));
			}
		}

		return new self($name, $options);
	}

	/**
	 * Render single input.
	 * @return void
	 */
	public function render()
	{
		echo $this->getHtml();
	}

	/**
	 * Render multiple input.
	 * @return void
	 */
	public function renderMultiple()
	{}

	/**
	 * @param bool $isTemplate
	 * @return string
	 */
	private function getHtml()
	{
		$options = $this->getOptions();
		$name = $this->getName();
		$val = $this->getValue();

		$checked = ($val === $options['value_on']) ? ' checked' : '';

		// render
		$html  = "<input type=\"hidden\" name=\"{$name}\" value=\"{$options['value_off']}\">";
		$html .= "<input type=\"checkbox\" id=\"{$name}_checkbox\" name=\"{$name}\" value=\"{$options['value_on']}\"{$checked}>";
		$html .= "<label  for=\"{$name}_checkbox\">{$options['labelText']}</label>";

		return $html;
	}
}