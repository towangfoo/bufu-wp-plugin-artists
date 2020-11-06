<?php

require_once 'AbstractInput.php';
require_once 'InputInterface.php';

/**
 * Media upload input element, using the Wordpress media features.
 * @author Steffen Muecke <mail@quellkunst.de
 */
class InputMedia extends AbstractInput implements InputInterface
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
		$requiredOptions = ['post_type', 'title'];

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
		echo $this->getAddItemButtonHtml($min, $max, [
			'data-replace-with-count="IDX"'
		], ( $curr === $max ));
		echo '</div>';
	}

	private function getHtml($isTemplate = false)
	{
		$inputName = $this->getName();
		$multiple = $this->isMultiple();

		$idx      = ($isTemplate) ? 'IDX' : $this->multipleIndex;
		$elemId   = $inputName . '_' . $idx;
		$elemName = ($multiple) ? $inputName . '[]' : $inputName;
		$value    = ($isTemplate) ? null : (($multiple) ? $this->getValue($this->multipleIndex) : $this->getValue());
		$hidden   = (!$value) ? ' hidden' : '';
		$disabled = ($isTemplate) ? ' disabled="disabled"' : '';

		$html = '<fieldset><div>';
		$html .= '<img id="' . $elemId . '_preview" class="thumbnail'. $hidden .'" src="' . $value . '" alt="">';
		$html .= '<input type="hidden" name="'. $elemName .'" id="'. $elemId .'" value="' . $value . '"'. $disabled .'>';
		$html .= '<button type="button" class="button" id="'. $elemId .'_btn" data-media-uploader-target="#'. $elemId .'">'. __( "Choose image", "bufu-artists" ) . '</button>';
		$html .= '</div></fieldset>';

//		$html .= wp_nonce_field( $elemId .'_nonce', $elemId . '_process', true, false );

		return $html;
	}
}