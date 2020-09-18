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
	 * Add all defined custom input fields to the post edit page for the custom post types.
	 */
	public function addAll()
	{
		foreach ($this->getInputFieldsArtist() as $k => $i) {
			$renderMethod = "echoInputHtml" . ucfirst($k);
			$name = $this->getInputName($k, $i);
			$context = (array_key_exists('context', $i)) ? $i['context'] : 'normal';
			add_meta_box($name, $i['title'], [ $this, $renderMethod ], $this->getPostTypeArtist(), $context, "high");
		}

		foreach ($this->getInputFieldsLyric() as $k => $i) {
			$renderMethod = "echoInputHtml" . ucfirst($k);
			$name = $this->getInputName($k, $i);
			$context = (array_key_exists('context', $i)) ? $i['context'] : 'normal';
			add_meta_box($name, $i['title'], [ $this, $renderMethod ], $this->getPostTypeLyric(), $context, "high");
		}
	}

	/**
	 * Save custom input field values from $_POST.
	 */
	public function savePost()
	{
		$post = $this->getPost();

		// only handle artist-typed posts
		if ($post->post_type === $this->getPostTypeArtist()) {
			foreach ($this->getInputFieldsArtist() as $k => $i) {
				$name = $this->getInputName($k, $i);
				update_post_meta($post->ID, $name, $_POST[$name]);
			}
		}

		// only handle lyric-typed posts
		if ($post->post_type === $this->getPostTypeLyric()) {
			foreach ($this->getInputFieldsLyric() as $k => $i) {
				$name = $this->getInputName($k, $i);
				update_post_meta($post->ID, $name, $_POST[$name]);
			}
		}
	}

	/**
	 * Add custom meta fields to REST Api.
	 * Fields names are available in the data object directly (NOT within the meta array).
	 */
	public function registerCustomMetaFieldsForApi()
	{
		// artist
		$postType = $this->getPostTypeArtist();
		foreach ($this->getInputFieldsArtist() as $k => $f) {
			$name = $this->getInputName($k, $f);
			register_rest_field($postType, $name, [
				'get_callback' => function($object, $field) {
					$post_meta = get_post_meta($object['id']);
					return $post_meta[$field][0];
				},
				'update_callback' => function($value, $object, $field) {
					return ($value) ? update_post_meta($object->ID, $field, $value) : false;
				}
			]);
		}

		// lyrics
		$postType = $this->getPostTypeLyric();
		foreach ($this->getInputFieldsLyric() as $k => $f) {
			$name = $this->getInputName($k, $f);
			register_rest_field($postType, $name, [
				'get_callback' => function($object, $field) {
					$post_meta = get_post_meta($object['id']);
					return $post_meta[$field][0];
				},
				'update_callback' => function($value, $object, $field) {
					return ($value) ? update_post_meta($object->ID, $field, $value) : false;
				}
			]);
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
		return $this->getInputHtml('website', $this->getInputFieldsArtist());
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
	 * Get rendered form element for 'selectArtist' input.
	 * @return string
	 */
	public function getInputHtmlSelectArtist()
	{
		// input definition
		$fields = $this->getInputFieldsLyric();
		$key    = 'selectArtist';
		$input  = $fields[$key];
		$name   = $this->getInputName($key, $input);

		// options
		$artists = $this->getAvailableArtists();
		$emptyOption = __("Not selected", 'bufu-artists');

		// current value
		$custom = $this->getPostCustomFields();
		$currentValue = (empty($custom[$name][0])) ? null : intval($custom[$name][0], 10);

		// render
		$html  = "<select name=\"{$name}\">";
		$html .= "<option>{$emptyOption}</option>";
		foreach ($artists as $id => $title) {
			$selected = ($id === $currentValue) ? ' selected="selected"' : '';
			$html .= "<option value=\"{$id}\"{$selected}>{$title}</option>";
		}
		$html .= '</select>';

		return $html;
	}

	/**
	 * Echo 'selectArtist' form element.
	 * @return void
	 */
	public function echoInputHtmlSelectArtist()
	{
		echo $this->getInputHtmlSelectArtist();
	}

	/**
	 * Get rendered form element for 'album' input.
	 * @return string
	 */
	public function getInputHtmlAlbum()
	{
		return $this->getInputHtml('album', $this->getInputFieldsLyric());
	}

	/**
	 * Echo 'album' form element.
	 * @return void
	 */
	public function echoInputHtmlAlbum()
	{
		echo $this->getInputHtmlAlbum();
	}

	/**
	 * Get rendered form element for 'sortBy' input.
	 * @return string
	 */
	public function getInputHtmlSortBy()
	{
		return $this->getInputHtml('sortBy', $this->getInputFieldsArtist());
	}

	/**
	 * Echo 'sortBy' form element.
	 * @return void
	 */
	public function echoInputHtmlSortBy()
	{
		echo $this->getInputHtmlSortBy();
	}

	// ------ private methods ------------------------------------------------------------------------------------------
	// -----------------------------------------------------------------------------------------------------------------

	/**
	 * @param string $key
	 * @param array $fields
	 * @return string html
	 * @throws Exception
	 */
	private function getInputHtml($key, array $fields)
	{
		if (!array_key_exists($key, $fields)) {
			throw new \Exception(sprintf(__('Input with key %s is not defined', 'bufu-artists'), $key));
		}

		// input definition
		$input = $fields[$key];
		$name = $this->getInputName($key, $input);

		$custom = $this->getPostCustomFields();
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
	private function getInputFieldsArtist()
	{
		return [
			'website' => [
				'type'  => 'url',
				'title' => __('Artist website', 'bufu-artists'),
			],
			'sortBy' => [
				'type'  => 'text',
				'title' => __('Sort string', 'bufu-artists'),
			]
		];
	}

	/**
	 * Define the custom inputs used on the lyrics post type.
	 * @return array
	 */
	private function getInputFieldsLyric()
	{
		return [
			'selectArtist' => [
				'type'    => 'select',
				'title'   => _n('Artist', 'Artists', 1, 'bufu-artists'),
//				'context' => 'side'
			],
			'album' => [
				'type'  => 'text',
				'title' => __('Album name', 'bufu-artists'),
			]
		];
	}

	/**
	 * Get the post type identifier
	 * @return string
	 */
	private function getPostTypeArtist()
	{
		return $this->config['post_type']['artist'];
	}

	/**
	 * Get the post type identifier
	 * @return string
	 */
	private function getPostTypeLyric()
	{
		return $this->config['post_type']['lyric'];
	}

	/**
	 * Get the name for an input element.
	 * @param string $key
	 * @param array $data
	 * @return string
	 */
	private function getInputName($key, $data)
	{
		return (array_key_exists('name', $data)) ? $data['name'] : "bufu_artist_{$key}";
	}

	/**
	 * @return array
	 */
	private function getPostCustomFields()
	{
		$post = $this->getPost();
		return get_post_custom($post->ID);
	}

	/**
	 * @return WP_Post
	 */
	private function getPost()
	{
		/* @var $post WP_Post */
		global $post;
		return $post;
	}

	/**
	 * @return array
	 */
	private function getAvailableArtists()
	{
		// query for your post type
		$query  = new WP_Query([
			'post_type'      => $this->getPostTypeArtist(),
			'posts_per_page' => -1
		]);

		$items = $query->posts;
		if (!is_array($items)) {
			return [];
		}

		return wp_list_pluck($items, 'post_title', 'ID');
	}
}