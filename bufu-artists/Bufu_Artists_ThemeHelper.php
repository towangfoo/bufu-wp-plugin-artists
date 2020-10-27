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
}