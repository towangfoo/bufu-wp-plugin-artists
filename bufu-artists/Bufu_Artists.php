<?php

require_once 'Bufu_Artists_ThemeHelper.php';
require_once 'admin/AdminInputs.php';
require_once 'widgets/Bufu_Widget_EventsByArtist.php';

class Bufu_Artists {

	public static $postTypeNameArtist = 'bufu_artist';
	public static $postTypeNameAlbum  = 'bufu_album';
	public static $postTypeNameEvent  = 'tribe_events';

	private static $translationSlug = 'bufu-artists';

	/**
	 * @var AdminInputs
	 */
	private $adminInputs;

	/**
	 * @var Bufu_Artists_ThemeHelper
	 */
	private $themeHelper;

	/**
	 * Bufu_Artists constructor.
	 */
	public function __construct()
	{
		$this->adminInputs = new AdminInputs([
			'post_type' => [
				'artist' => self::$postTypeNameArtist,
				'album'  => self::$postTypeNameAlbum,
				'event'  => self::$postTypeNameEvent,
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

		// register custom post type for albums
		$err = $this->addCustomPostTypeAlbum();
		if ($err instanceof WP_Error) {
			echo $this->getErrorMessage($err);
			return;
		}

		$this->addCustomMetaForTribeEvents();

		$this->addCustomMetaForFrontPage();

		// create taxonomy for artist categories
		$this->addTaxonomyArtistCategories();
	}

	/**
	 * Called upon to the add_meta_boxes event.
	 * Add input fields to the bufu_artist post type.
	 * @return void
	 */
	public function hook_admin_add_meta_boxes()
	{
		$this->adminInputs->addAll();
	}

	/**
	 * Called upon to the admin_init event.
	 * @return void
	 */
	public function hook_admin_init()
	{

	}

	/**
	 * Add scripts and styles to the admin GUI.
	 */
	public function hook_admin_enqueue_scripts()
	{
		$this->adminInputs->enqueueMediaUploadScripts();
		$this->adminInputs->enqueueModuleScripts();
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

	/**
	 * When retrieving a single post.
	 * @param WP_Post $post
	 */
	public function hook_the_post(WP_Post $post)
	{
		$this->addRelationsToPost($post);
	}

	/**
	 * When initializing widgets.
	 */
	public function hook_widgets_init()
	{
		$eventsWidget = new Bufu_Widget_EventsByArtist();
		$eventsWidget->setThemeHelper($this->getThemeHelper());

		register_widget( $eventsWidget );
	}

	// -----------------------------------------------------------------------------------------------------------------
	// ----- hooks into Tribe Events / Filter Bar plugins  -------------------------------------------------------------

	/**
	 * Called, when a tribe_event is being saved through the v1 REST API.
	 * This should only happen upon data migration.
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

	public function hook_tribe_filter_bar_create_filters()
	{
		if ( ! class_exists( 'Tribe__Events__Filterbar__Filter' ) ) {
			return;
		}

		include_once __DIR__ . '/tribe-events/filter/class-custom-filter-artist.php';

		new Artist_Custom_Filter(
			_n('Artist', 'Artists', 2, 'bufu-artists'),
			'bufu_artist_filter'
		);
	}

	public function hook_tribe_filter_bar_context_locations( array $locations )
	{
		// Read the filter selected values, if any, from the URL request vars.
		$locations['bufu_artist_filter'] = [ 'read' => [ Tribe__Context::REQUEST_VAR => 'bufuartist' ], ];

		return $locations;
	}

	/**
	 * Add custom filter to filter bar, allowing to filter events by artist.
	 * @param array $map
	 * @return array
	 */
	public function hook_tribe_filter_bar_map( array $map )
	{
		if ( ! class_exists( 'Tribe__Events__Filterbar__Filter' ) ) {
			return $map;
		}

		include_once __DIR__ . '/tribe-events/filter/class-custom-filter-artist.php';

		$map['bufu_artist_filter'] = 'Artist_Custom_Filter';

		return $map;
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
	// ----- ThemeHelper, class hierarchy access -----------------------------------------------------------------------

	/**
	 * @return Bufu_Artists_ThemeHelper
	 */
	public function getThemeHelper()
	{
		if ( !$this->themeHelper ) {
			$this->themeHelper = new Bufu_Artists_ThemeHelper($this);
		}

		return $this->themeHelper;
	}

	/**
	 * @return AdminInputs
	 */
	public function getAdminInputs()
	{
		return $this->adminInputs;
	}

	// -----------------------------------------------------------------------------------------------------------------
	// ----- private methods -------------------------------------------------------------------------------------------

	private function addCustomMetaForTribeEvents() {
		register_post_meta('tribe_events', '_bufu_artist_selectArtist', [
			'single'       => true,
			'description'  => __('The related artist', 'bufu-artists'),
			'show_in_rest' => true,
		]);
	}

	private function addCustomMetaForFrontPage() {
		register_post_meta('post', '_bufu_artist_selectArtist', [
			'single'       => true,
			'description'  => __('Featured artists', 'bufu-artists'),
			'show_in_rest' => true,
		]);
		register_post_meta('post', '_bufu_artist_imgArtistsReel', [
			'single'       => true,
			'description'  => __('Artists slider images', 'bufu-artists'),
			'show_in_rest' => false,
		]);
		register_post_meta('post', '_bufu_artist_imgConcerts', [
			'single'       => true,
			'description'  => __('Concerts link image', 'bufu-artists'),
			'show_in_rest' => false,
		]);
		register_post_meta('post', '_bufu_artist_imgShop', [
			'single'       => true,
			'description'  => __('Shop link image', 'bufu-artists'),
			'show_in_rest' => false,
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

		register_post_meta(self::$postTypeNameArtist, '_bufu_artist_website', [
			'single'       => true,
			'description'  => __('An artist\'s personal website', 'bufu-artists'),
			'show_in_rest' => true,
		]);

		register_post_meta(self::$postTypeNameArtist, '_bufu_artist_sortBy', [
			'single'       => true,
			'description'  => __('Internal sort string', 'bufu-artists'),
			'show_in_rest' => true,
		]);

		register_post_meta(self::$postTypeNameArtist, '_bufu_artist_stageImage', [
			'single'       => true,
			'description'  => __('Profile stage image', 'bufu-artists'),
			'show_in_rest' => true,
		]);

		return ($postType instanceof WP_Error) ? $postType : null;
	}

	/**
	 * Add custom post type for lyric.
	 * @return WP_Error|null
	 */
	private function addCustomPostTypeAlbum()
	{
		if ( post_type_exists(self::$postTypeNameAlbum) ) {
			return new WP_Error(sprintf(__('Post type already exists: `%s`', 'bufu-artists'), self::$postTypeNameAlbum));
		}


		// register custom post type for artists
		$postType = register_post_type(self::$postTypeNameAlbum, [
			'labels' => [
				'name'          => _n('Album', 'Albums', 2, 'bufu-artists'),
				'singular_name' => _n('Album', 'Albums', 1, 'bufu-artists')
			],
			'description'       => __('Manage an album', 'bufu-artists'),
			'rewrite'     		=> [ 'slug' => 'albums' ],
			'public'            => true,
			'publicly_queryable'=> true,
			'has_archive'       => true,
			'show_ui'           => false, // WIP
			'show_in_nav_menus' => false, // WIP
			'show_in_rest'      => true,
			'supports'          => [
				'title',
				'editor',
				'comments',
				'revisions',
				'author',
				'page-attributes',
				'thumbnail'
			],
			'capability_type'	=> 'post',
			'hierarchical' 		=> false,
		]);

		register_post_meta(self::$postTypeNameAlbum, '_bufu_artist_selectArtist', [
			'single'       => true,
			'description'  => __('The related artist', 'bufu-artists'),
			'show_in_rest' => true,
		]);

		register_post_meta(self::$postTypeNameAlbum, '_bufu_artist_albumRelease', [
			'single'       => true,
			'description'  => __('The album release date', 'bufu-artists'),
			'show_in_rest' => true,
		]);

		register_post_meta(self::$postTypeNameAlbum, '_bufu_artist_albumLabel', [
			'single'       => true,
			'description'  => __('At which label was the album released', 'bufu-artists'),
			'show_in_rest' => true,
		]);

		register_post_meta(self::$postTypeNameAlbum, '_bufu_artist_tracks', [
			'single'       => false,
			'description'  => __('The list of tracks', 'bufu-artists'),
			'show_in_rest' => true,
		]);

		register_post_meta(self::$postTypeNameAlbum, '_bufu_artist_lyrics', [
			'single'       => false,
			'description'  => __('The list of tracks', 'bufu-artists'),
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
		$html .= sprintf(__('Error in plugin %s: %s', 'bufu-artists'), 'BuschFunk Artists', $err->get_error_message());
		$html .= '</div></p>';

		return $html;
	}

	/**
	 * Load translations for the plugin.
	 * @return void
	 */
	private function loadTranslations()
	{
		load_muplugin_textdomain(self::$translationSlug, self::$translationSlug . '/languages/');
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
				$query->set('meta_key', '_bufu_artist_sortBy');
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
			$query->set('meta_key', '_bufu_artist_sortBy');
			$query->set('order', 'ASC');
			$query->set('posts_per_page', 20);
		}
	}

	private function addRelationsToPost(WP_Post $post)
	{
		$selectedArtistId = get_post_meta($post->ID, '_bufu_artist_selectArtist', true);
		if ($selectedArtistId) {
			$artist = get_post((int) $selectedArtistId);
			if ($artist && $artist->post_type === self::$postTypeNameArtist) {
				$post->bufu_artist = $artist;
			}
		}
	}
}