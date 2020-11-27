<?php

require_once 'InputInterface.php';
require_once 'InputCheckbox.php';
require_once 'InputMedia.php';
require_once 'InputSelect.php';
require_once 'InputText.php';

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
		$thePostType = get_post_type();

		$inputs = [
			$this->getPostTypeArtist() => $this->getInputFieldsArtist(),
			$this->getPostTypeAlbum()  => $this->getInputFieldsAlbum(),
			$this->getPostTypeEvent()  => $this->getInputFieldsEvent(),
		];

		$inputErrors     = [];
		$inputReferences = [];

		if ( $this->isFrontPagePost() ) {
			foreach ($this->getInputFieldsFrontPage() as $key => $options) {
				$name = $this->getInputName($key, $options);
				$options['screen'] = null;
				$input = $this->createInput($name, $options);
				if ($input instanceof WP_Error) {
					$inputErrors[] = $input;
				}
				else {
					$inputReferences[] = $input;
				}
			}
		}
		else if (array_key_exists($thePostType, $inputs)) {
			foreach ($inputs[$thePostType] as $key => $options) {
				$name = $this->getInputName($key, $options);
				$options['post_type'] = $thePostType;
				$input = $this->createInput($name, $options);
				if ($input instanceof WP_Error) {
					$inputErrors[] = $input;
				}
				else {
					$inputReferences[] = $input;
				}
			}
		}

		if (count($inputErrors) > 0) {
			var_dump($inputErrors);
			exit();
		}
	}

	/**
	 * Save custom input field values from $_POST.
	 */
	public function savePost()
	{
		$post = $this->getPost();

		// only handle front-page attributes
		if ( $this->isFrontPagePost() ) {
			foreach ($this->getInputFieldsFrontPage() as $k => $i) {
				$name = $this->getInputName($k, $i);
				update_post_meta($post->ID, $name, $_POST[$name]);
			}
		}

		// only handle artist-typed posts
		else if ($post->post_type === $this->getPostTypeArtist()) {
			foreach ($this->getInputFieldsArtist() as $k => $i) {
				$name = $this->getInputName($k, $i);
				update_post_meta($post->ID, $name, $_POST[$name]);
			}
		}

		// only handle lyric-typed posts
		else if ($post->post_type === $this->getPostTypeAlbum()) {
			foreach ($this->getInputFieldsAlbum() as $k => $i) {
				$name = $this->getInputName($k, $i);
				update_post_meta($post->ID, $name, $_POST[$name]);
			}
		}

		// only handle event-typed posts
		else if ($post->post_type === $this->getPostTypeEvent()) {
			foreach ($this->getInputFieldsEvent() as $k => $i) {
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
		$postType = $this->getPostTypeAlbum();
		foreach ($this->getInputFieldsAlbum() as $k => $f) {
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

	/**
	 * Add scripts required for media upload meta fields in artist edit page
	 */
	public function enqueueMediaUploadScripts()
	{
		$thePostType = get_post_type();

		$postTypesToInlcudeOn = [
			$this->getPostTypeArtist(),
			'page',
		];

		if( in_array($thePostType, $postTypesToInlcudeOn) ) {
			// enqueue media uploader scripts
			wp_enqueue_media();

			wp_register_script( 'bufu-artist-admin-meta-media-upload', plugins_url( 'assets/js/media-uploader.js' , __FILE__ ), array( 'jquery' ) );
			wp_localize_script( 'bufu-artist-admin-meta-media-upload', 'bufu_artist_admin_meta_media_upload',
				[
					'title'  => __( 'Choose or Upload Image', 'bufu-artists' ),
					'button' => __( 'Use this image', 'bufu-artists' ),
				]
			);

			wp_enqueue_script( 'bufu-artist-admin-meta-media-upload' );
		}
	}

	/**
	 * Add scripts and styles required for artist module's functionality in admin GUI
	 */
	public function enqueueModuleScripts()
	{
		$thePostType = get_post_type();

		$postTypesToInlcudeOn = [
			$this->getPostTypeArtist(),
			$this->getPostTypeEvent(),
			$this->getPostTypeAlbum(),
			'page',
		];

		if( in_array($thePostType, $postTypesToInlcudeOn) ) {
			wp_enqueue_script('bufu-artists-admin-scripts', plugins_url( 'assets/js/bufu-admin.js' , __FILE__ ));
			wp_enqueue_style('bufu-artists-admin-styles', plugins_url( 'assets/admin.css' , __FILE__ ));
		}
	}

	// ------ public callbacks for form element and option -------------------------------------------------------------
	// -----------------------------------------------------------------------------------------------------------------

	/**
	 * @return array
	 */
	public function getAllArtistsSelectOptions()
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
	
	// ------ private methods ------------------------------------------------------------------------------------------
	// -----------------------------------------------------------------------------------------------------------------

	/**
	 * Define the custom inputs used on the artist post type.
	 * @return array
	 */
	private function getInputFieldsArtist()
	{
		$post = $this->getPost();
		if ($post && $post->post_type === $this->getPostTypeArtist()) {
			$artistName = $post->post_title;
		}
		else {
			$artistName = __("this artist", 'bufu-artist');
		}

		return [
			'website' => [
				'type'  => 'url',
				'title' => __('Artist website', 'bufu-artists'),
			],
			'sortBy' => [
				'type'  => 'text',
				'title' => __('Sort string', 'bufu-artists'),
			],
			'stageImage' => [
				'type'     => 'media_upload',
				'title'    => __('Profile stage image', 'bufu-artists'),
				'context'  => 'side'
			],
			'profileVisible' => [
				'type'      => 'checkbox',
				'title'     => __('Display profile page', 'bufu-artists'),
				'context'   => 'side',
				'labelText' => sprintf(__('Display a profile page for %s', 'bufu-artist'), $artistName),
				'value_on'  => 'yes',
				'value_off' => 'no',
			],
		];
	}

	/**
	 * Define the custom inputs used on the album post type.
	 * @return array
	 */
	private function getInputFieldsAlbum()
	{
		return [
			'selectArtist' => [
				'type'    => 'select',
				'title'   => _n('Artist', 'Artists', 1, 'bufu-artists'),
				'value_options' => $this->getAllArtistsSelectOptions(),
//				'context' => 'side'
			],
			'albumRelease' => [
				'type'  => 'date',
				'title' => __('Release date', 'bufu-artists'),
			],
			'albumLabel' => [
				'type'  => 'text',
				'title' => __('Release Label', 'bufu-artists'),
			],
			'shopUrl' => [
				'type'  => 'url',
				'title' => __('Product URL', 'bufu-artists'),
			]
		];
	}

	/**
	 * Define the custom inputs used on the lyrics post type.
	 * @return array
	 */
	private function getInputFieldsEvent()
	{
		return [
			'selectArtist' => [
				'type'    => 'select',
				'title'   => _n('Artist', 'Artists', 1, 'bufu-artists'),
				'value_options' => $this->getAllArtistsSelectOptions(),
//				'context' => 'side'
			]
		];
	}

	/**
	 * Define the custom inputs used on the front page.
	 * @return array
	 */
	private function getInputFieldsFrontPage()
	{
		return [
			'imgArtistsReel' => [
				'type'     => 'media_upload',
				'title'    => __('Artists slider images', 'bufu-artists'),
				'multiple' => true,
				'min'      => 1,
				'max'      => 5,
			],
			'imgConcerts'  => [
				'type'     => 'media_upload',
				'title'    => __('Concerts link image', 'bufu-artists'),
			],
			'imgShop' => [
				'type'     => 'media_upload',
				'title'    => __('Shop link image', 'bufu-artists'),
			],
			'selectArtist' => [
				'type'     => 'select',
				'title'    => __('Featured artists', 'bufu-artists'),
				'value_options' => $this->getAllArtistsSelectOptions(),
				'multiple' => true,
				'min'      => 3,
				'max'      => 6,
			],
		];
	}

	/**
	 * Get the post type identifier for artists
	 * @return string
	 */
	private function getPostTypeArtist()
	{
		return $this->config['post_type']['artist'];
	}

	/**
	 * Get the post type identifier for albums
	 * @return string
	 */
	private function getPostTypeAlbum()
	{
		return $this->config['post_type']['album'];
	}

	/**
	 * Get the post type identifier for events
	 * @return string
	 */
	private function getPostTypeEvent()
	{
		return $this->config['post_type']['event'];
	}

	/**
	 * Get the name for an input element.
	 * Corresponds to the custom meta key.
	 * @param string $key
	 * @param array $data
	 * @return string
	 */
	private function getInputName($key, $data)
	{
		return (array_key_exists('name', $data)) ? $data['name'] : "_bufu_artist_{$key}";
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
	 * Check whether a given post is the front page.
	 *
	 * @return bool
	 */
	private function isFrontPagePost()
	{
		$type = get_post_type();
		$postId = get_the_ID();
		$fpId = (int) get_option('page_on_front');

		if (! $postId || $type !== 'page') {
			return false;
		}

		return $postId === $fpId;
	}

	/**
	 * @param $name
	 * @param $options
	 * @return InputInterface|WP_Error
	 */
	private function createInput($name, $options)
	{
		if (!array_key_exists('type', $options)) {
			return new WP_Error(sprintf(__('Missing required option: `%s`', 'bufu-artists'), 'type'));
		}

		$input = null;
		switch ($options['type']) {
			case 'text':
			case 'email':
			case 'date':
			case 'url':
				$input = InputText::create($name, $options);
				break;
			case 'select':
				$input = InputSelect::create($name, $options);
				break;
			case 'checkbox':
				$input = InputCheckbox::create($name, $options);
				break;
			case 'media_upload':
				$input = InputMedia::create($name, $options);
				break;
			default:
				return new WP_Error(sprintf(__('Unknown input type: `%s`', 'bufu-artists'), $options['type']));
		}

		if ($input instanceof InputInterface) {

			$custom = $this->getPostCustomFields();
			if (array_key_exists($name, $custom)) {

				$rawValue = $custom[$name][0];
				$value    = null;

				if ($input->isMultiple()) {
					$value = [];
					if (!empty($rawValue)) {
						$unserialized = unserialize($custom[$name][0]);
						if (is_array($unserialized)) {
							$value = $unserialized;
						}
					}
				}
				else {
					$value = $rawValue;
				}

				$input->setValue($value);
			}

			$input->addMetaBox();
		}

		return $input;
	}
}