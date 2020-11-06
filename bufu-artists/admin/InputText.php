<?php

require_once 'AbstractInput.php';
require_once 'InputInterface.php';

/**
 * Text input element.
 * @author Steffen Muecke <mail@quellkunst.de
 */
class InputText extends AbstractInput implements InputInterface
{
	/**
	 * @var int
	 */
	private $multipleIndex = 0;

	/**
	 * @param $name
	 * @param array $options
	 * @return InputInterface|WP_Error
	 */
	public static function create($name, array $options)
	{
		$requiredOptions = ['post_type', 'title', 'type'];

		// option `screen` overrides `post_type`
		if (array_key_exists('screen', $options)) {
			unset($requiredOptions[0]);
		}

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
	{
		$min  = $this->getMinimumRequiredLinesForMultiple();
		$max  = $this->getMaximumLinesForMultiple();
		$curr = count($this->getValue());

		echo '<div class="multiple">';
		for ($this->multipleIndex = 0; $this->multipleIndex < max($min, $curr); $this->multipleIndex ++) {
			echo '<div class="multiple-item">';
			echo $this->getHtml();
			echo $this->getRemoveButtonHtml(( $curr > $min ));
			echo '</div>';
		}
		echo '<div class="multiple-item template hidden">';
		echo $this->getHtml(true);
		echo $this->getRemoveButtonHtml();
		echo '</div>';
		echo $this->getAddItemButtonHtml($min, $max, [], ( $curr === $max ));
		echo '</div>';
	}

	/**
	 * Get html to render.
	 * @param bool $isTemplate
	 * @return string
	 */
	private function getHtml($isTemplate = false)
	{
		$options = $this->getOptions();
		$multiple = $this->isMultiple();

		$name = $this->getName();
		if ($multiple) {
			$name .= '[]';
		}

		$value  = ($isTemplate) ? null : (($multiple) ? $this->getValue($this->multipleIndex) : $this->getValue());
		$type   = $options['type'];
		$disabled = ($isTemplate) ? ' disabled="disabled"' : '';

		$html = "<input type=\"{$type}\" name=\"{$name}\" value=\"{$value}\" {$disabled} />";

		return $html;
	}
}