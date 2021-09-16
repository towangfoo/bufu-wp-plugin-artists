<?php

require_once 'Bufu_Artists_Scraper.php';
require_once 'Bufu_Artists_ThemeHelper.php';
require_once 'admin/AdminInputs.php';
require_once 'admin/ArtistsSettingsPage.php';
require_once 'widgets/Bufu_Widget_EventsByArtist.php';
require_once 'widgets/Bufu_Widget_ArtistsWall.php';
require_once 'widgets/Bufu_Widget_ArtistsSearch.php';
require_once 'widgets/Bufu_Widget_InterviewsByArtist.php';
require_once 'widgets/Bufu_Widget_ReviewsByArtist.php';
require_once 'widgets/Bufu_Widget_PostArchive.php';
require_once 'widgets/Bufu_Widget_SubpageList.php';

class Bufu_Artists {

	public static $postTypeNameArtist = 'bufu_artist';
	public static $postTypeNameAlbum  = 'bufu_album';
	public static $postTypeNameEvent  = 'tribe_events';
	public static $postTypeNameInterview = 'bufu_interview';
	public static $postTypeNameReview = 'bufu_review';

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
	 * @var Bufu_Artists_Scraper
	 */
	private $scraper;

	/**
	 * @var ArtistsSettingsPage
	 */
	private $adminSettingsPage;

	/**
	 * @var bool
	 */
	private $queryWithArtistStartingLettersModified = false;

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
                'interview' => self::$postTypeNameInterview,
                'review' => self::$postTypeNameReview,
			]
		], $this);

//		$this->adminSettingsPage = new ArtistsSettingsPage();
	}

	/**
	 * Hook into WP.
	 */
	public function initHooks()
	{
		add_action('add_meta_boxes', [$this, 'hook_admin_add_meta_boxes']);
		add_action('admin_init', [$this, 'hook_admin_init']);
		add_action('init', [$this, 'hook_init']);
		add_action('rest_api_init', [$this, 'hook_rest_api_init']);
		add_action('plugins_loaded', [$this, 'hook_plugins_loaded']);
		add_action('save_post', [$this, 'hook_save_post']);
		add_action('the_post', [$this, 'hook_the_post']);
		add_action( 'widgets_init', [$this, 'hook_widgets_init'] );

		// hook into query creation using filters
		add_filter( 'pre_get_posts', [$this, 'filter_pre_get_posts'] );

		// hook into tribe_events_calendar on saving events (data migration)
		// @TODO: remove later, when production is stable
//		add_action( 'tribe_events_event_save', [$this, 'hook_tribe_events_event_save'] );

		// display custom columns in admin post lists
		add_filter( 'manage_bufu_album_posts_columns', [$this, 'filter_manage_bufu_album_posts_columns'] );
		add_filter( 'manage_bufu_interview_posts_columns', [$this, 'filter_manage_bufu_interview_posts_columns'] );
		add_filter( 'manage_bufu_review_posts_columns', [$this, 'filter_manage_bufu_review_posts_columns'] );
		add_filter( 'manage_tribe_events_posts_columns', [$this, 'filter_manage_tribe_events_posts_columns'] );
		add_action( 'manage_bufu_album_posts_custom_column', [$this, 'hook_manage_posts_custom_column'], 10, 2 );
		add_action( 'manage_bufu_interview_posts_custom_column', [$this, 'hook_manage_posts_custom_column'], 10, 2 );
		add_action( 'manage_bufu_review_posts_custom_column', [$this, 'hook_manage_posts_custom_column'], 10, 2 );
		add_action( 'manage_tribe_events_posts_custom_column', [$this, 'hook_manage_posts_custom_column'], 10, 2 );
		add_action( 'manage_edit-bufu_album_sortable_columns', [$this, 'hook_register_sortable_columns'], 10, 2 );
		add_action( 'manage_edit-bufu_interview_sortable_columns', [$this, 'hook_register_sortable_columns'], 10, 2 );
		add_action( 'manage_edit-bufu_review_sortable_columns', [$this, 'hook_register_sortable_columns'], 10, 2 );
		add_action( 'manage_edit-tribe_events_sortable_columns', [$this, 'hook_register_sortable_columns'] );
		add_filter( 'posts_clauses', [$this, 'filter_posts_clauses'], 1000, 2 ); // we need to be last on this filter (i.e. after the-events-calendar plugin)

		// filter by artist in admin list pages
		add_filter( 'parse_query', [$this, 'filter_admin_posts_filterby'] );
		add_action( 'restrict_manage_posts', [$this, 'hook_admin_posts_add_filterby'] );

		// add filter for custom date formatting settings
		add_filter( 'tribe_events_event_schedule_details_formatting', [$this, 'filter_tribe_events_event_schedule_details_formatting'] );

		// add custom filter for artists to tribe filter bar
		add_action( 'tribe_events_filters_create_filters', [$this, 'hook_tribe_filter_bar_create_filters'] );
		add_filter( 'tribe_context_locations', [$this, 'filter_tribe_filter_bar_context_locations'] );
		add_filter( 'tribe_events_filter_bar_context_to_filter_map', [$this, 'filter_tribe_filter_bar_map'] );

		// add plugin assets
		add_action( 'admin_enqueue_scripts', [$this, 'hook_admin_enqueue_scripts'] );

//		$this->adminSettingsPage->initHooks();
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

		// register custom post type for interviews
		$err = $this->addCustomPostTypeInterview();
		if ($err instanceof WP_Error) {
			echo $this->getErrorMessage($err);
			return;
		}

		// register custom post type for reviews
		$err = $this->addCustomPostTypeReview();
		if ($err instanceof WP_Error) {
			echo $this->getErrorMessage($err);
			return;
		}

		$this->addCustomMetaForTribeEvents();

		$this->addCustomMetaForFrontPage();

		$this->addCustomMetaForPages();

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

		$artistsWallWidget = new Bufu_Widget_ArtistsWall();
		$artistsWallWidget->setThemeHelper($this->getThemeHelper());
		register_widget( $artistsWallWidget );

		$artistSearchWidget = new Bufu_Widget_ArtistsSearch();
		register_widget( $artistSearchWidget );

		$postArchiveWidget = new Bufu_Widget_PostArchive();
		register_widget( $postArchiveWidget );

		$chroniclesWidget = new Bufu_Widget_SubpageList();
		register_widget( $chroniclesWidget );

		$interviewsWidget = new Bufu_Widget_InterviewsByArtist();
		$interviewsWidget->setThemeHelper($this->getThemeHelper());
		register_widget( $interviewsWidget );

		$reviewsWidget = new Bufu_Widget_ReviewsByArtist();
		$reviewsWidget->setThemeHelper($this->getThemeHelper());
		register_widget( $reviewsWidget );
	}

	// -----------------------------------------------------------------------------------------------------------------
	// ----- hooks into Tribe Events / Filter Bar plugins  -------------------------------------------------------------

//	/**
//	 * Called, when a tribe_event is being saved through the v1 REST API.
//	 * This should only happen upon data migration.
//	 * The v1 API does not handle meta fields in the UPDATE call, so this is done here.
//	 * @param int $eventId
//	 */
//	public function hook_tribe_events_event_save($eventId)
//	{
//		$wp   = $this->getGlobalWP();
//		$data = $_POST;
//
//		// check that we got the event id
//		if (!is_int($eventId) || $eventId < 1) {
//			return;
//		}
//
//		// check that the request was made through the REST v1 API
//		if (strpos($wp->request, 'wp-json/tribe/events/v1') !== 0) {
//			return;
//		}
//
//		// check for meta data in $_POST
//		if (!is_array($data) || !array_key_exists('meta', $data)) {
//			return;
//		}
//
//		// save
//		foreach ($data['meta'] as $f => $v) {
//			update_post_meta($eventId, $f, $v);
//		}
//	}

	/**
	 * @var string the slug name used for the custom artist filter in TEC filterbar.
	 */
	private $_customArtistFilterSlug = 'bufu_artist_filter';

	/**
	 * Create instance of custom filter for TEC filterbar.
	 */
	public function hook_tribe_filter_bar_create_filters()
	{
		if ( ! class_exists( 'Tribe__Events__Filterbar__Filter' ) ) {
			return;
		}

		include_once __DIR__ . '/tribe-events/filter/class-custom-filter-artist.php';

		new \Artist_Custom_Filter(
			_n('Artist', 'Artists', 2, 'bufu-artists'),
			$this->_customArtistFilterSlug
		);
	}

	/**
     * For TEC Views v2, integrate into Context and make query params accessible.
	 * @param array $locations
	 * @return array
	 */
	public function filter_tribe_filter_bar_context_locations( array $locations )
	{
		if ( ! class_exists( 'Tribe__Events__Filterbar__Filter' ) ) {
			return $locations;
		}

		// Read the filter selected values, if any, from the URL request vars.
		$locations[$this->_customArtistFilterSlug] = [
            'read' => [ \Tribe__Context::REQUEST_VAR => 'tribe_' . $this->_customArtistFilterSlug ]
        ];

		return $locations;
	}

	/**
	 * Map our  custom filter to TEC filterbar.
	 * @param array $map
	 * @return array
	 */
	public function filter_tribe_filter_bar_map( array $map )
	{
		if ( ! class_exists( 'Tribe__Events__Filterbar__Filter' ) ) {
			return $map;
		}

		include_once __DIR__ . '/tribe-events/filter/class-custom-filter-artist.php';

		$map[$this->_customArtistFilterSlug] = 'Artist_Custom_Filter';

		return $map;
	}

	/**
	 * Populate custom columns in post list for bufu_album ans tribe_events
	 * @param $column
	 * @param $postId
	 */
	public function hook_manage_posts_custom_column( $column, $postId )
	{
		if ( $column === 'artist' ) {
			$artistId = get_post_meta($postId, '_bufu_artist_selectArtist', true);
			$artistList = $this->getThemeHelper()->getArtistsSelectOptions();

			if (array_key_exists($artistId, $artistList)) {
				echo "<a href=\"/wp-admin/post.php?post={$artistId}&action=edit\">{$artistList[$artistId]}</a>";
			}
			else {
				echo '--';
			}
		}
		elseif ( $column === 'release' ) {
			echo get_post_meta($postId, '_bufu_artist_albumRelease', true);
		}
		elseif ( $column === 'publisher' ) {
			echo get_post_meta($postId, '_bufu_artist_albumLabel', true);
		}
        elseif ( $column === 'interview_source' ) {
			echo get_post_meta($postId, '_bufu_artist_interview_source', true);
		}
        elseif ( $column === 'review_source' ) {
			echo get_post_meta($postId, '_bufu_artist_review_source', true);
		}
        elseif ( $column === 'review_author' ) {
			echo get_post_meta($postId, '_bufu_artist_review_author', true);
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
		// admin lists - handle sorting params
		if ( is_admin() && is_main_query() ) {
			if ('artist_name' === $query->get('orderby')) {
				// when using the sort-by `artist` feature in post list column (e.g. albums and events)
				// set a flag in the query - see self::filter_join_clauses() for implementation
				$query->set('bufu_artist_join_artist_sortBy', true);
			}
			elseif ('publisher' === $query->get('orderby')) {
				// when using the sort-by `publisher` feature in post list column (albums)
				$this->modifyQuery_admin_sortPostsListBy($query, $this::$postTypeNameAlbum, '_bufu_artist_albumLabel');
			}
			elseif ('release' === $query->get('orderby')) {
				// when using the sort-by `release` feature in post list column (albums)
				$this->modifyQuery_admin_sortPostsListBy($query, $this::$postTypeNameAlbum, '_bufu_artist_albumRelease');
			}
			else {
				// sort artist list by sorting field
				$this->modifyQuery_admin_sortArtistPosts($query);
			}
		}

		if ( is_archive() && is_main_query() ) {
			$this->modifyQuery_archive_sortArtistPosts($query);
			$this->modifyQuery_artists_WhereProfileIsVisible($query);

			// only apply to artist archive queries on the public site
			if ( $query->query['post_type'] === self::$postTypeNameArtist && isset($_GET['l']) && !empty($_GET['l']) ) {
                $query->set('bufu_artist-starts-with', $_GET['l']);
                add_filter( 'posts_where', [$this, 'filter_artists_starting_with_letter'], 10, 2 );
			}

		}
	}

	/**
	 * Manually modify query parts after the intended query has been constructed in WP.
	 * This filter should be used with caution, to not affect any unintended queries!
	 * @param array $clauses
	 * @param WP_Query $query
	 * @return array
	 */
	public function filter_posts_clauses( array $clauses, WP_Query $query )
	{
		global $wpdb;

		// add artist sortBy custom field `_bufu_artist_sortBy` into select and order by that
		if ($query->get('bufu_artist_join_artist_sortBy') === true) {
			$fields = &$clauses['fields'];
			if (!empty($fields)) $fields .= ', ';
			$fields .= "ARTISTMETASORT.meta_value AS ArtistSortBy";

			$join = &$clauses['join'];
			if (!empty($join)) $join .= ' ';
			// add relation to artist post from postmeta
			$join .= "LEFT JOIN {$wpdb->postmeta} ARTISTMETAID ON ARTISTMETAID.post_id = {$wpdb->posts}.ID AND ARTISTMETAID.meta_key = '_bufu_artist_selectArtist'";
			$join .= ' ';
			// join `_bufu_artist_sortBy` value from postmeta
			$join .= "LEFT JOIN {$wpdb->postmeta} ARTISTMETASORT ON ARTISTMETASORT.post_id = ARTISTMETAID.meta_value and ARTISTMETASORT.meta_key = '_bufu_artist_sortBy'";

			$orderby = &$clauses['orderby'];
			$orderby = "ArtistSortBy ASC, {$wpdb->posts}.post_title ASC";
		}

		return $clauses;
	}

	/**
	 * Modify date format in tribe Events Schedule Block (Single Event page).
	 *
	 * @param array $settings
	 * @return array
	 */
	public function filter_tribe_events_event_schedule_details_formatting(array $settings = [])
	{
		return array_merge($settings, [
			'show_end_time' => false,
		]);
	}

	/**
	 * Custom columns for list of albums
	 */
	public function filter_manage_bufu_album_posts_columns( $columns )
	{
		$columns = [
			'cb'        => $columns['cb'],
			'title'     => $columns['title'],
			'artist'    => _n('Artist', 'Artists', 1, 'bufu-artists'),
			'release'   => __('Release date', 'bufu-artists'),
			'publisher' => __('Release Label', 'bufu-artists'),
			'comments'  => $columns['comments'],
//			'author'    => $columns['author'],
			'date'      => $columns['date'],
		];

		return $columns;
	}

	/**
	 * Custom columns for list of interviews
	 */
	public function filter_manage_bufu_interview_posts_columns( $columns )
	{
		$columns = [
			'cb'        => $columns['cb'],
			'title'     => $columns['title'],
			'artist'    => _n('Artist', 'Artists', 1, 'bufu-artists'),
			'interview_source' => __('Source information', 'bufu-artists'),
			'comments'  => $columns['comments'],
			'date'      => $columns['date'],
		];

		return $columns;
	}

	/**
	 * Custom columns for list of reviews
	 */
	public function filter_manage_bufu_review_posts_columns( $columns )
	{
		$columns = [
			'cb'        => $columns['cb'],
			'title'     => $columns['title'],
			'artist'    => _n('Artist', 'Artists', 1, 'bufu-artists'),
			'review_source'    => __('Source information', 'bufu-artists'),
			'review_author' => __('Author information', 'bufu-artists'),
			'comments'  => $columns['comments'],
//			'author'    => $columns['author'],
			'date'      => $columns['date'],
		];

		return $columns;
	}

	/**
	 * Custom columns for list of events
	 */
	public function filter_manage_tribe_events_posts_columns( $columns )
	{
		$columns['artist'] = _n('Artist', 'Artists', 1, 'bufu-artists');

		return $columns;
	}

	/**
	 * Make artist column sortable.
	 * @param $columns
	 * @return mixed
	 */
	public function hook_register_sortable_columns( $columns )
	{
		$columns['artist']    = 'artist_name';
		$columns['publisher'] = 'publisher'; // in albums only
		$columns['release']   = 'release';   // in albums only
		$columns['interview_source'] = 'interview_source';   // in interviews only
		$columns['review_source'] = 'review_source';   // in reviews only
		$columns['review_author'] = 'review_author';   // in reviews only

		return $columns;
	}

	/**
	 * Add custom filters to query for admin posts list
	 */
	public function filter_admin_posts_filterby( WP_Query $query )
	{
		global $pagenow;
		if ( is_admin() && $pagenow === 'edit.php' && $query->is_main_query() && isset($_GET['bufu_filterby_artist']) && $_GET['bufu_filterby_artist'] != '' ) {
            $query->query_vars['meta_key']   = '_bufu_artist_selectArtist';
            $query->query_vars['meta_value'] = intval($_GET['bufu_filterby_artist']);
		}

		return $query;
	}

	public function filter_artists_starting_with_letter($where, WP_Query $query)
	{
	    $letter = strtoupper($query->get('bufu_artist-starts-with'));

		if ($this->queryWithArtistStartingLettersModified || !preg_match('/^[A-Z]$/', $letter)) {
		    return $where;
		}

	    global $wpdb;

		$escaped = $wpdb->esc_like($letter);

        $where .= ' AND (';
        $where .= $wpdb->posts . '.post_title LIKE \''. $escaped .'%\'';
        $where .= ' OR ';
        $where .= $wpdb->posts . '.post_title LIKE \'% '. $escaped .'%\'';
        $where .= ')';

        $this->queryWithArtistStartingLettersModified = true;

        return $where;
	}

	/**
	 * Render UI for custom admin posts list filters
	 */
	public function hook_admin_posts_add_filterby()
	{
	    $availableForTypes = [self::$postTypeNameAlbum, self::$postTypeNameEvent, self::$postTypeNameInterview, self::$postTypeNameReview];

		$type = 'post';
		if (isset($_GET['post_type'])) {
			$type = $_GET['post_type'];
		}

		if (in_array($type, $availableForTypes)) {
			$options = $this->getThemeHelper()->getArtistsSelectOptions();
			$current_v = isset($_GET['bufu_filterby_artist']) ? intval($_GET['bufu_filterby_artist']) : '';
			?>
			<select name="bufu_filterby_artist" placeholder="<?php echo sprintf(__('Filter by %s', 'bufu-artists'), _n('Artist', 'Artists', 1, 'bufu-artists')) ?>">
				<option value=""><?php _e('All artists', 'bufu-artists') ?> ...</option>
				<?php foreach ($options as $value => $label) {
					printf
					(
						'<option value="%s"%s>%s</option>',
						$value,
						$value === $current_v ? ' selected="selected"' : '',
						$label
					);
				} ?>
			</select>
			<?php
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

	/**
	 * Extract product data from their page in the store
     * @var array
	 */
	public function scrapeFeaturedProductsFromStore(array $urls)
	{
	    if (!$this->scraper) {
	        $this->scraper = new Bufu_Artists_Scraper();
        }

        $this->scraper->loadProducts($urls);
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

	private function addCustomMetaForPages()
	{
		register_post_meta('page', '_bufu_artist_pageShowChildren', [
			'single'      => true,
			'description' => __('Show links to child pages', 'bufu-artists'),
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
		register_post_meta('post', '_bufu_artist_featuredProducts', [
			'single'       => true,
			'description'  => __('Featured products from the store', 'bufu-artists'),
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
			'rewrite'     		=> [ 'slug' => 'kuenstler' ],
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

		register_post_meta(self::$postTypeNameArtist, '_bufu_artist_profileVisible', [
			'single'       => true,
			'description'  => __('Display profile page', 'bufu-artists'),
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
			'rewrite'     		=> [ 'slug' => 'alben' ],
			'public'            => true,
			'publicly_queryable'=> true,
			'has_archive'       => true,
			'show_ui'           => true,
			'show_in_nav_menus' => true,
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

		register_post_meta(self::$postTypeNameAlbum, '_bufu_artist_shopUrl', [
			'single'       => false,
			'description'  => __('Product URL', 'bufu-artists'),
			'show_in_rest' => true,
		]);

		return ($postType instanceof WP_Error) ? $postType : null;
	}

	/**
	 * Add custom post type for interviews.
	 * @return WP_Error|null
	 */
	private function addCustomPostTypeInterview()
	{
		if (post_type_exists(self::$postTypeNameInterview)) {
			return new WP_Error(sprintf(__('Post type already exists: `%s`', 'bufu-artists'), self::$postTypeNameInterview));
		}

		$postType = register_post_type(self::$postTypeNameInterview, [
			'labels' => [
				'name'          => _n('Interview', 'Interviews', 2, 'bufu-artists'),
				'singular_name' => _n('Interview', 'Interviews', 1, 'bufu-artists')
			],
			'description'       => __('Manage interviews', 'bufu-artists'),
			'rewrite'     		=> [ 'slug' => 'interviews' ],
			'public'            => true,
			'publicly_queryable'=> true,
			'has_archive'       => true,
			'show_ui'           => true,
			'show_in_nav_menus' => false,
			'show_in_rest'      => true,
			'rest_base'         => self::$postTypeNameInterview,
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

		register_post_meta(self::$postTypeNameInterview, '_bufu_artist_selectArtist', [
			'single'       => true,
			'description'  => __('The related artist', 'bufu-artists'),
			'show_in_rest' => true,
		]);

		register_post_meta(self::$postTypeNameInterview, '_bufu_artist_interview_source', [
			'single'       => true,
			'description'  => __('Source information', 'bufu-artists'),
			'show_in_rest' => true,
		]);

		return ($postType instanceof WP_Error) ? $postType : null;
	}

	/**
	 * Add custom post type for reviews.
	 * @return WP_Error|null
	 */
	private function addCustomPostTypeReview()
	{
		if (post_type_exists(self::$postTypeNameReview)) {
			return new WP_Error(sprintf(__('Post type already exists: `%s`', 'bufu-artists'), self::$postTypeNameReview));
		}

		$postType = register_post_type(self::$postTypeNameReview, [
			'labels' => [
				'name'          => _n('Review', 'Reviews', 2, 'bufu-artists'),
				'singular_name' => _n('Review', 'Reviews', 1, 'bufu-artists')
			],
			'description'       => __('Manage reviews', 'bufu-artists'),
			'rewrite'     		=> [ 'slug' => 'reviews' ],
			'public'            => true,
			'publicly_queryable'=> true,
			'has_archive'       => true,
			'show_ui'           => true,
			'show_in_nav_menus' => false,
			'show_in_rest'      => true,
			'rest_base'         => self::$postTypeNameReview,
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

		register_post_meta(self::$postTypeNameReview, '_bufu_artist_selectArtist', [
			'single'       => true,
			'description'  => __('The related artist', 'bufu-artists'),
			'show_in_rest' => true,
		]);

		register_post_meta(self::$postTypeNameReview, '_bufu_artist_review_source', [
			'single'       => true,
			'description'  => __('Source information', 'bufu-artists'),
			'show_in_rest' => true,
		]);
		register_post_meta(self::$postTypeNameReview, '_bufu_artist_review_author', [
			'single'       => true,
			'description'  => __('Author information', 'bufu-artists'),
			'show_in_rest' => true,
		]);
		register_post_meta(self::$postTypeNameReview, '_bufu_artist_review_type', [
			'single'       => true,
			'description'  => __('Review type', 'bufu-artists'),
			'show_in_rest' => true,
		]);


		return ($postType instanceof WP_Error) ? $postType : null;
	}

	/**
	 * Add taxonomy for artist post type.
	 */
	private function addTaxonomyArtistCategories()
	{
		register_taxonomy(self::$postTypeNameArtist . "_tx_cat", [ self::$postTypeNameArtist ], [
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
	 * Sort a posts list page by a custom column value
	 * @param WP_Query $query
	 * @param $postType
	 * @param $customField
	 */
	private function modifyQuery_admin_sortPostsListBy(WP_Query $query, $postType, $customField) {
		$postTypeOfQuery = $query->query['post_type'];
		$order = strtoupper($query->get('order'));

		if ( $postTypeOfQuery === $postType ) {
			$query->set('orderby', 'meta_value');
			$query->set('meta_key', $customField);
			$query->set('order', ($order === 'DESC') ? "DESC" : "ASC");
		}
	}

	/**
     * Sort artist posts by custom field, but not when displaying search results
	 * @param WP_Query $query
	 */
	private function modifyQuery_archive_sortArtistPosts(WP_Query $query)
	{
		$postType = $query->query['post_type'];
		$searchString = $query->query['s'];

		if ( $postType === self::$postTypeNameArtist && empty($searchString) ) {
			$query->set('orderby', 'meta_value');
			$query->set('meta_key', '_bufu_artist_sortBy');
			$query->set('order', 'ASC');
//			$query->set('posts_per_page', 20);
		}
	}

	/**
	 * @param WP_Query $query
	 * @return void
	 */
	private function modifyQuery_artists_WhereProfileIsVisible(WP_Query $query)
	{
		// only apply to artist archive queries on the public site
		if ( $query->query['post_type'] !== self::$postTypeNameArtist || !$query->is_archive() || $query->is_admin ) {
			return;
		}

		$queryMeta = $query->get('meta_query');
		if (!is_array($queryMeta)) {
			$queryMeta = [
				'relation' => 'AND'
            ];
		}

		// add custom meta filter
		$queryMeta[] = array(
			'key'		=> '_bufu_artist_profileVisible',
			'value'		=> 'yes',
			'compare'	=> '=',
		);

		$query->set('meta_query', $queryMeta);
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