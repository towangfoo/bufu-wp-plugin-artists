<?php

require_once 'admin/AdminInputs.php';

class Bufu_Artists {

	private static $postTypeNameArtist = 'bufu_artist';
	private static $postTypeNameLyric  = 'bufu_lyric';

	private static $pluginSlugArtist = 'bufu-artists';
	private static $pluginSlugLyric  = 'bufu-lyrics';

	/**
	 * @var AdminInputs
	 */
	private $adminInputs;

	/**
	 * Bufu_Artists constructor.
	 */
	public function __construct()
	{
		$this->adminInputs = new AdminInputs([
			'post_type' => [
				'artist' => self::$postTypeNameArtist,
				'lyric'  => self::$postTypeNameLyric,
				'event'  => 'tribe_events',
			]
		]);
	}


	// -----------------------------------------------------------------------------------------------------------------
	// ----- hooks into WP ---------------------------------------------------------------------------------------------

	/**
	 * Called upon to the init event.
	 * Add post type and taxonomy.
	 * @return void
	 */
	public function hook_init()
	{
		// register custom post type for artists
		$err = $this->addCustomPostTypeArtist();
		if ($err instanceof WP_Error) {
			echo $this->getErrorMessage($err);
			return;
		}

		// register custom post type for lyrics
		$err = $this->addCustomPostTypeLyric();
		if ($err instanceof WP_Error) {
			echo $this->getErrorMessage($err);
			return;
		}

		$this->addCustomMetaForTribeEvents();

		// create taxonomy for artist categories
		$this->addTaxonomyArtistCategories();
	}

	/**
	 * Called upon to the admin_init event.
	 * Add input fields to the bufu_artist post type.
	 * @return void
	 */
	public function hook_admin_init()
	{
		$this->adminInputs->addAll();
	}

	/**
	 * Called when the REST API is init.
	 * Add custom meta fields for post types.
	 * @return void
	 */
	public function hook_rest_api_init()
	{
		$this->adminInputs->registerCustomMetaFieldsForApi();
	}

	/**
	 * Called upon to the plugins_loaded event.
	 * Load plugin translations.
	 * @return void
	 */
	public function hook_plugins_loaded()
	{
		$this->loadTranslations();
	}

	/**
	 * Called upon to the save_post event.
	 * @return void
	 */
	public function hook_save_post()
	{
		$this->saveAdminInputValues();
	}

	public function hook_the_post(WP_Post $post)
	{
		$this->addRelationsToPost($post);
	}

	/**
	 * Called, when a tribe_event is being saved.
	 * The v1 API does not handle meta fields in the UPDATE call, so this is done here.
	 * @param int $eventId
	 */
	public function hook_tribe_events_event_save($eventId)
	{
		$wp   = $this->getGlobalWP();
		$data = $_POST;

		// check that we got the event id
		if (!is_int($eventId) || $eventId < 1) {
			return;
		}

		// check that the request was made through the REST v1 API
		if (strpos($wp->request, 'wp-json/tribe/events/v1') !== 0) {
			return;
		}

		// check for meta data in $_POST
		if (!is_array($data) || !array_key_exists('meta', $data)) {
			return;
		}

		// save
		foreach ($data['meta'] as $f => $v) {
			update_post_meta($eventId, $f, $v);
		}
	}

	// -----------------------------------------------------------------------------------------------------------------
	// ----- filters ---------------------------------------------------------------------------------------------------

	/**
	 * Modify query for posts.
	 * @param WP_Query $query
	 * @return void
	 */
	public function filter_pre_get_posts(WP_Query $query)
	{
		if ( is_admin() && is_main_query() ) {
			$this->modifyQuery_admin_sortArtistPosts($query);
		}

		if ( is_main_query() & is_archive() ) {
			$this->modifyQuery_archive_sortArtistPosts($query);
		}
	}

	// -----------------------------------------------------------------------------------------------------------------
	// ----- private methods -------------------------------------------------------------------------------------------

	private function addCustomMetaForTribeEvents() {
		register_post_meta('tribe_events', 'bufu_artist_selectArtist', [
			'single'       => true,
			'description'  => __('The related artist', 'bufu-artists'),
			'show_in_rest' => true,
		]);
	}

	/**
	 * Add custom post type for artist.
	 * @return WP_Error|null
	 */
	private function addCustomPostTypeArtist()
	{
		if (post_type_exists(self::$postTypeNameArtist)) {
			return new WP_Error(sprintf(__('Post type already exists: `%s`', 'bufu-artists'), self::$postTypeNameArtist));
		}


		// register custom post type for artists
		$postType = register_post_type(self::$postTypeNameArtist, [
			'labels' => [
				'name'          => _n('Artist', 'Artists', 2, 'bufu-artists'),
				'singular_name' => _n('Artist', 'Artists', 1, 'bufu-artists')
			],
			'description'       => __('Manage an artist\'s portfolio information', 'bufu-artists'),
			'rewrite'     		=> [ 'slug' => 'artists' ],
			'public'            => true,
			'publicly_queryable'=> true,
			'has_archive'       => true,
			'show_ui'           => true,
			'show_in_nav_menus' => false,
			'show_in_rest'      => true,
			'rest_base'         => self::$postTypeNameArtist,
			'supports'          => [
				'title',
				'editor',
				'comments',
				'revisions',
				'author',
				'excerpt',
				'page-attributes',
				'thumbnail',
			],
			'capability_type'	=> 'post',
			'hierarchical' 		=> false,
		]);

		register_post_meta(self::$postTypeNameArtist, 'bufu_artist_website', [
			'single'       => true,
			'description'  => __('An artist\'s personal website', 'bufu-artists'),
			'show_in_rest' => true,
		]);

		register_post_meta(self::$postTypeNameArtist, 'bufu_artist_sortBy', [
			'single'       => true,
			'description'  => __('Internal sort string', 'bufu-artists'),
			'show_in_rest' => true,
		]);

		return ($postType instanceof WP_Error) ? $postType : null;
	}

	/**
	 * Add custom post type for lyric.
	 * @return WP_Error|null
	 */
	private function addCustomPostTypeLyric()
	{
		if (post_type_exists(self::$postTypeNameLyric)) {
			return new WP_Error(sprintf(__('Post type already exists: `%s`', 'bufu-artists'), self::$postTypeNameLyric));
		}


		// register custom post type for artists
		$postType = register_post_type(self::$postTypeNameLyric, [
			'labels' => [
				'name'          => _n('Song text', 'Song texts', 2, 'bufu-artists'),
				'singular_name' => _n('Song text', 'Song texts', 1, 'bufu-artists')
			],
			'description'       => __('Manage an artist\'s song texts', 'bufu-artists'),
			'rewrite'     		=> [ 'slug' => 'lyrics' ],
			'public'            => true,
			'publicly_queryable'=> true,
			'has_archieve'      => true,
			'show_ui'           => true,
			'show_in_nav_menus' => false,
			'show_in_rest'      => true,
			'supports'          => [
				'title',
				'editor',
				'comments',
				'revisions',
				'author',
				'page-attributes'
			],
			'capability_type'	=> 'post',
			'hierarchical' 		=> false,
		]);

		register_post_meta(self::$postTypeNameLyric, 'bufu_artist_selectArtist', [
			'single'       => true,
			'description'  => __('The related artist', 'bufu-artists'),
			'show_in_rest' => true,
		]);

		register_post_meta(self::$postTypeNameLyric, 'bufu_artist_album', [
			'single'       => true,
			'description'  => __('The album that first contained this piece', 'bufu-artists'),
			'show_in_rest' => true,
		]);

		return ($postType instanceof WP_Error) ? $postType : null;
	}

	/**
	 * Add taxonomy for artist post type.
	 */
	private function addTaxonomyArtistCategories()
	{
		register_taxonomy(self::$postTypeNameArtist . "_tx", [ self::$postTypeNameArtist ], [
			"hierarchical" 		=> true,
			"label" 			=> _n('Artist Category', 'Artists Categories', 2, 'bufu-artists'),
			"singular_label" 	=> _n('Artist Category', 'Artists Categories', 1, 'bufu-artists'),
			"rewrite" 			=> true
		]);
	}

	/**
	 * Process plugin's input fields in the POST.
	 */
	private function saveAdminInputValues()
	{
		$this->adminInputs->savePost();
	}

	/**
	 * Create error message from a WP_Error
	 * @param WP_Error $err
	 * @return string
	 */
	private function getErrorMessage(WP_Error $err)
	{
		$html = '<div class="error"><p>';
		$html .= sprintf(__('Error in plugin %s: %s', 'bufu-artists'), __('BuschFunk Artists', 'bufu-artists'), $err->get_error_message());
		$html .= '</div></p>';

		return $html;
	}

	/**
	 * Load translations for the plugin.
	 * @return void
	 */
	private function loadTranslations()
	{
		load_muplugin_textdomain(self::$pluginSlugArtist, self::$pluginSlugArtist . '/languages/');
	}

	/**
	 * @return WP
	 */
	private function getGlobalWP()
	{
		global $wp;
		return $wp;
	}

	/**
	 * @param WP_Query $query
	 */
	private function modifyQuery_admin_sortArtistPosts(WP_Query $query)
	{
		if ( is_admin() && !isset( $_GET['orderby'] ) ) {
			$postType = $query->query['post_type'];

			if ( $postType === self::$postTypeNameArtist ) {
				$query->set('orderby', 'meta_value');
				$query->set('meta_key', 'bufu_artist_sortBy');
				$query->set('order', 'ASC');
			}
		}
	}

	/**
	 * @param WP_Query $query
	 */
	private function modifyQuery_archive_sortArtistPosts(WP_Query $query)
	{
		$postType = $query->query['post_type'];
		if ( $postType === self::$postTypeNameArtist ) {
			$query->set('orderby', 'meta_value');
			$query->set('meta_key', 'bufu_artist_sortBy');
			$query->set('order', 'ASC');
			$query->set('posts_per_page', 20);
		}
	}

	private function addRelationsToPost(WP_Post $post)
	{
		$selectedArtistId = get_post_meta($post->ID, 'bufu_artist_selectArtist', true);
		if ($selectedArtistId) {
			$artist = get_post((int) $selectedArtistId);
			if ($artist && $artist->post_type === self::$postTypeNameArtist) {
				$post->bufu_artist = $artist;
			}
		}
	}
}