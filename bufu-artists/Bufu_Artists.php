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
		$this->addAdminInputs();
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


	// -----------------------------------------------------------------------------------------------------------------
	// ----- private methods -------------------------------------------------------------------------------------------

	/**
	 * @return void
	 */
	private function addAdminInputs()
	{
		$this->adminInputs->addAll();
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
			'has_archieve'      => true,
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
}