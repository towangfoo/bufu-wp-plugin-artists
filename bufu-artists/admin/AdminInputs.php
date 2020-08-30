<?php

/**
 * Define and interact with admin input fields for artists post type.
 */
class AdminInputs
{
	/**
	 * @var array
	 */
	private $config = [];

	/**
	 * AdminInputs constructor.
	 * @param array $config
	 */
	public function __construct(array $config)
	{
		$this->config = $config;
	}

	/**
	 * Add all defined custom input fields to the post edit page for the custom post type.
	 */
	public function addAll()
	{
		foreach ($this->getInputFields() as $k => $i) {
			$renderMethod = "echoInputHtml" . ucfirst($k);
			$name = $this->getInputName($k, $i);
			add_meta_box($name, $i['title'], [ $this, $renderMethod ], $this->getPostType(), "normal", "high");
		}
	}

	/**
	 * Save custom input field values from $_POST.
	 */
	public function savePost()
	{
		/* @var $post WP_Post */
		global $post;

		// only handle artist-typed posts
		if ($post->post_type !== $this->getPostType()) {
			return;
		}

		foreach ($this->getInputFields() as $k => $i) {
			$name = $this->getInputName($k, $i);
			update_post_meta($post->ID, $name, $_POST[$name]);
		}
	}

	// ------ callbacks for form element rendering ---------------------------------------------------------------------
	// -----------------------------------------------------------------------------------------------------------------

	/**
	 * Get rendered form element for 'website' input.
	 * @return string
	 */
	public function getInputHtmlWebsite()
	{
		return $this->getInputHtml('website');
	}

	/**
	 * Echo 'website' form element.
	 * @return void
	 */
	public function echoInputHtmlWebsite()
	{
		echo $this->getInputHtmlWebsite();
	}



	/**
	 * @param string $key
	 * @return string html
	 * @throws Exception
	 */
	private function getInputHtml($key)
	{
		$fields = $this->getInputFields();
		if (!array_key_exists($key, $fields)) {
			throw new \Exception(sprintf(__('Input with key %s is not defined', 'bufu-artists'), $key));
		}

		// input definition
		$input = $fields[$key];
		$name = $this->getInputName($key, $input);

		/* @var $post WP_Post */
		global $post;
		$custom = get_post_custom($post->ID);
		$value = $custom[$name][0];

		// render input element
		$html = '';
		if (in_array($input['type'], ['text', 'email', 'date', 'url'])) {
			$html = "<input type=\"{$input['type']}\" name=\"{$name}\" value=\"{$value}\" />";
		}

		return $html;
	}

	/**
	 * Define the custom inputs used on the artist post type.
	 * @return array
	 */
	private function getInputFields()
	{
		return [
			'website' => [
				'type'  => 'url',
				'title' => __('Artist website', 'bufu-artists'),
			]
		];
	}

	/**
	 * Get the post type identifier
	 * @return string
	 */
	private function getPostType()
	{
		return $this->config['post_type'];
	}

	/**
	 * Get the name for an input element.
	 * @param string $key
	 * @param array $data
	 * @return string
	 */
	public function getInputName($key, $data)
	{
		return (array_key_exists('name', $data)) ? $data['name'] : "bufu_artist_{$key}";
	}
}