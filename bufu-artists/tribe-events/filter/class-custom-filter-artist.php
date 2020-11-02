<?php

use Tribe\Events\Filterbar\Views\V2\Filters\Context_Filter;

class Artist_Custom_Filter extends Tribe__Events__Filterbar__Filter {

	use Context_Filter;

	/*
     * The type of this filter.
     * Available options are `select`, `multiselect`, `checkbox`, `radio`, `range`.
     */
	public $type = 'select';

	/**
	 * Returns the available values this filter will display on the front-end, when the user clicks on it.
	 *
	 * @return array<string,string> A map of the available values relating values to their human-readable form.
	 */
	protected function get_values() {
		$values = [];
		foreach (bufu_artists()->getArtistsSelectOptions() as $id => $name) {
			$values[] = [
				'name' => $name,
				'value' => $id,
			];
		}

		return $values;
	}

	/**
	 * Sets up the filter JOIN clause.
	 *
	 * This will be added to the running events query to add (JOIN) the tables the filter requires.
	 * Specifically, this filter will JOIN on the post meta table to use the events start time and all-day information.
	 */
	protected function setup_join_clause() {
		add_filter( 'posts_join', [ 'Tribe__Events__Query', 'posts_join' ], 10, 2 );

		global $wpdb;

		// Use a LEFT JOIN here to have `null` `post_id` results we'll be able to look up in the WHERE clause.
		$this->joinClause .= " LEFT JOIN {$wpdb->postmeta} AS bufu_artist " .
			"ON ( {$wpdb->posts}.ID = bufu_artist.post_id AND bufu_artist.meta_key = '_bufu_artists_selectArtist' )";
	}

	/**
	 * Sets up the filter WHERE clause.
	 *
	 * This will be added to the running events query to apply the matching criteria, handled by the
	 * custom filer.
	 *
	 * @throws Exception
	 */
	protected function setup_where_clause() {
		global $wpdb;

		$clause = $wpdb->prepare( 'bufu_artist.meta_value = %s', $this->currentValue );

		$this->whereClause .= ' AND (' . $clause . ')';
	}

}