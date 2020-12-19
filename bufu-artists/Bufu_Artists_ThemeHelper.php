<?php

require_once 'Bufu_Artists.php';

/**
 * Provide methods used from the bufu-theme.
 */
class Bufu_Artists_ThemeHelper
{
	/**
	 * @var Bufu_Artists
	 */
	private $bufuArtists;

	/**
	 * Instance cache
	 * @var array
	 */
	private $_artistsSelectOptions;

	public function __construct(Bufu_Artists $mainClass)
	{
		$this->bufuArtists = $mainClass;
	}

	/**
	 * Get a list of artist names for use as select options, with the ID as the array key
	 * @return array
	 */
	public function getArtistsSelectOptions()
	{
		if (!is_array($this->_artistsSelectOptions)) {
			$this->_artistsSelectOptions = $this->bufuArtists->getAdminInputs()->getAllArtistsSelectOptions();
		}
		return $this->_artistsSelectOptions;
	}

	/**
	 * Get a list of next upcoming concerts.
	 * If an artist is given in the first argument, only load concerts of that artist.
	 * @param WP_Post|null $artist
	 * @param int $num
	 * @param DateTime|null $from
	 * @return WP_Post[]
	 */
	public function loadNextConcerts(WP_Post $artist = null, $num = 10, \DateTime $from = null)
	{
		if ( !$from ) {
			$from = new \DateTime();
		}

		$query = tribe_events()
			->where('status', 'publish')
			->where('starts_after', $from->format("Y-m-d 00:00:00"));

		if ( $artist instanceof WP_Post && $artist->ID > 0 ) {
			$query->where('meta_equals', '_bufu_artist_selectArtist', $artist->ID ); // works despite the obvious API inconsistency
		}

		$query->order_by('event_date', "ASC")
			->per_page($num)
			->page(1);

		return $query->all();
	}

	/**
	 * Get artist posts in the order of IDs in the input array
	 *
	 * @param array $artistIds
	 * @return WP_Post[]
	 */
	public function loadArtistsById(array $artistIds = [])
	{
		if (empty($artistIds)) {
			return [];
		}

		$query = new WP_Query([
			'post_type' => Bufu_Artists::$postTypeNameArtist,
			'post__in'  => $artistIds
		]);


		$out = [];

		// sort by order of IDs in input array
		foreach ($query->posts as $artist) {
			$out[array_search($artist->ID, $artistIds, false)] = $artist;
		}

		ksort($out);

		return $out;
	}

	/**
	 * Get a lst of all visible artists.
	 * @return WP_Post[]
	 */
	public function loadAllVisibleArtists($random = false)
	{
		$params = [
			'post_type'      => Bufu_Artists::$postTypeNameArtist,
			'post_status'    => 'publish',
			'nopaging'       => true,
			'meta_query'     => [
				[
					'key'		=> '_bufu_artist_profileVisible',
					'value'		=> 'yes',
					'compare'	=> '=',
				]
			],
			'order' => 'ASC',
		];

		// how to order
		if ($random) {
			$params['orderby'] = 'rand';
		}
		else {
			$params['orderby']  = 'meta_value';
			$params['meta_key'] = '_bufu_artist_sortBy';
		}

		$query = new WP_Query($params);

		return $query->get_posts();
	}

	/**
	 * @param int $num
	 * @param string|null $categorySlug
	 * @return int[]|WP_Post[]
	 */
	public function loadRecentPosts($num, $categorySlug = null)
	{
		$params = [
			'numberposts' => $num,
			'orderby'     => 'date',
			'order'       => 'DESC',
			'post_type'   => 'post',
		];

		if ($categorySlug) {
			$params['category_name'] = $categorySlug;
		}

		return get_posts($params);
	}

	/**
	 * @param $postId
	 * @param string $order ['release', 'title']
	 * @return WP_Post[]
	 */
	public function loadAlbumsOfArtist( $postId, $order = 'release')
	{
		// now, this dows not work together!
		// adding a meta_query for one field in the where, and sorting by another meta field
		$params = [
			'post_type'      => Bufu_Artists::$postTypeNameAlbum,
			'post_status'    => 'publish',
			'nopaging'       => true,
			'meta_query'     => [
				[
					'key'		=> '_bufu_artist_selectArtist',
					'value'		=> $postId,
					'compare'	=> '=',
				]
			],
			'order' => 'ASC',
		];

		// how to order
		if ($order === 'release') {
			$params['orderby']  = 'meta_value';
			$params['meta_key'] = '_bufu_artist_albumRelease';
		}
		else {
			$params['orderby']  = 'title';
		}

		$query = new WP_Query($params);

		return $query->get_posts();
	}

	/**
	 * Echo a list of links to children of the passed page.
	 * @param int|WP_Post $pageId
	 */
	public function echoChildPagesLinks( $pageId )
	{
		if ( $pageId instanceof WP_Post ) {
			$pageId = $pageId->ID;
		}

		$showChildren = get_post_meta( $pageId, '_bufu_artist_pageShowChildren', true );
		if ( $showChildren !== '1' ) {
			return;
		}

		$children = get_children([
			'post_status' => 'publish' ,
			'post_parent' => $pageId,
			'orderby' => 'post_title',
			'order' => 'ASC'
		]);
		if ( count($children) < 1 ) {
			return;
		}

		echo '<ul class="page-children">';
		foreach ($children as $page) {
			/** @var $page WP_Post */
			echo '<li>';
			echo '<a href="'. get_permalink($page) .'">' . get_the_title($page) . '</a>';
			echo '</li>';
		}
		echo '</ul>';
	}

	/**
	 * Load product data from file.
	 * @return array|null
	 */
	public function loadFeaturedStoreProducts()
	{
		$pwd  = dirname(__FILE__);
		$file = $pwd . DIRECTORY_SEPARATOR . 'var/products.json';

		if (!file_exists($file)) {
			return null;
		}

		$data = file_get_contents($file);
		$result = json_decode($data, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			return null;
		}

		return $result;
	}
}