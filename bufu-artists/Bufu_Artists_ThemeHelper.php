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

	/**
	 * Instance cache
	 * @var array
	 */
	private $_artistsSelectOptionsHavingConcerts;

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
	 * Get a list of artist names for use as select options, with the ID as the array key.
	 * This only gets artists that have events related to them.
	 * @param bool $todayOrInTheFuture when true, only include artists with concerts starting today or in the future
	 * @return array
	 */
	public function getArtistsSelectOptionsHavingConcerts($todayOrInTheFuture = true)
	{
		if (!is_array($this->_artistsSelectOptionsHavingConcerts)) {
			global $wpdb;

			$postTypeEvent = Bufu_Artists::$postTypeNameEvent;

			/** @var $queryPartInTheFuture array['<join clause>', '<where clause>'] */
			$queryPartInTheFuture = ['', ''];

			if ($todayOrInTheFuture) {
				$dt = new \DateTime();
				$dt->setTime(0,0,0);
				$now = $dt->format('Y-m-d H:i:s');

				$queryPartInTheFuture = [
					"LEFT JOIN {$wpdb->postmeta} as EventMeta2 ON Event.ID = EventMeta2.post_id",
					"AND EventMeta2.meta_key = '_EventStartDate' AND CAST(EventMeta2.meta_value as DATETIME) > '{$now}'"
				];
			}

			$query = "SELECT DISTINCT EventMeta1.meta_value FROM {$wpdb->posts} as Event LEFT JOIN {$wpdb->postmeta} as EventMeta1 ON Event.ID = EventMeta1.post_id {$queryPartInTheFuture[0]} WHERE Event.post_type = '{$postTypeEvent}' {$queryPartInTheFuture[1]} AND EventMeta1.meta_key = '_bufu_artist_selectArtist'";

			$artistIdsFromDB = $wpdb->get_results( $query );
			$artistIds = array_map( function($v) { return (int) $v->meta_value; }, $artistIdsFromDB );

			$artists = [];
			foreach ($this->getArtistsSelectOptions() as $id => $name) {
				if (in_array($id, $artistIds)) {
					$artists[$id] = $name;
				}
			}

			$this->_artistsSelectOptionsHavingConcerts = $artists;
		}
		return $this->_artistsSelectOptionsHavingConcerts;
	}


	/**
	 * Get a list of next upcoming concerts.
	 * If an artist is given in the first argument, only load concerts of that artist.
	 * @param WP_Post|null $artist
	 * @param int $num
	 * @param DateTime|null $from
	 * @param string $categorySlug
	 * @return WP_Post[]
	 */
	public function loadNextConcerts(WP_Post $artist = null, $num = 10, \DateTime $from = null, $categorySlug = null)
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

		if ($categorySlug) {
			$query->where('term_slug_in', 'tribe_events_cat',  [$categorySlug]);
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
	 * Get a list of all visible artists.
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
			'posts_per_page' => $num,
			'nopaging'    => false,
			'orderby'     => 'date',
			'order'       => 'DESC',
			'post_type'   => 'post',
		];

		if ($categorySlug) {
			$params['category_name'] = $categorySlug;
		}

		$query = new WP_Query($params);

		return $query->get_posts();
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
			$params['order']    = 'DESC';
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

	/**
	 * Load artist data from custom meta field.
	 * @param WP_Post $post
	 * @return null|WP_Post
	 */
	public function loadArtistFromCustomMeta(WP_Post $post)
	{
		$selectedArtistId = get_post_meta($post->ID, '_bufu_artist_selectArtist', true);
		if ($selectedArtistId) {
			$artist = get_post((int) $selectedArtistId);
			if ($artist && $artist->post_type === 'bufu_artist') {
				return $artist;
			}
		}

		return null;
	}

	/**
	 * Get the current letter to filter artist list with, from query params.
	 * @return null|string
	 */
	public function getCurrentArtistLetterFilter()
	{
		$letterFilter = isset($_GET['l']) ? $_GET['l'] : null;
		// ignore anything that is not a single letter
		if ($letterFilter && !preg_match('/^[a-z]$/', $letterFilter)) {
			$letterFilter = null;
		}

		return $letterFilter;
	}

	/**
	 * Get the starting letters of all words in artist names
	 * @return array
	 */
	public function getArtistLetterOptions()
	{
		$artists = $this->getArtistsSelectOptions();

		$letters = [];
		foreach ($artists as $n) {
			$nn = explode(" ", $n);
			foreach ($nn as $i) {
				$i0 = mb_substr($i, 0, 1);
				$i0lc = strtolower($i0);
				if (!in_array($i0lc, $letters) && preg_match('/^[A-Z]$/', $i0)) {
					$letters[] = $i0lc;
				}
			}
		}
		sort($letters);

		return $letters;
	}

	/**
	 * Get the name of an artist from the ID passed via a GET query parameter.
	 * @param string $paramName
	 * @return array|null
	 */
	public function getArtistNameFromQueryParamArtistId($paramName)
	{
		if (! array_key_exists($paramName, $_GET)) {
			return null;
		}

		$artists = $this->getArtistsSelectOptions();

		$id = intval($_GET[$paramName][0]);
		if (! is_integer($id)) {
			return null;
		}

		if (array_key_exists($id, $artists)) {
			return [
				'id'   => $id,
				'name' => $artists[$id]
			];
		} else {
			return null;
		}
	}
}