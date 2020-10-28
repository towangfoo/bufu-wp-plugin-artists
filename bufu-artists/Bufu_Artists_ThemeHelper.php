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
		return $this->bufuArtists->getAdminInputs()->getAllArtistsSelectOptions();
	}

	/**
	 * Get a list of next upcoming concerts.
	 * @param int $num
	 * @param DateTime|null $from
	 * @return WP_Post[]
	 */
	public function loadNextConcerts($num, \DateTime $from = null)
	{
		if ( !$from ) {
			$from = new \DateTime();
		}

		// query tribe_events plugin API
		$events = tribe_events()
			->where('status', 'publish')
			->where('starts_after', $from->format("Y-m-d 00:00:00"))
			->order_by('start_date', "ASC")
			->per_page($num)
			->page(1)
			->all();

		return $events;
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
}