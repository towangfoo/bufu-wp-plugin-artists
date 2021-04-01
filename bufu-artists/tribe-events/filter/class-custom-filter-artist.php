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

		$isPastDisplayMode = 'past' === $this->context->get( 'event_display_mode', false );

		$values = [];
		foreach (bufu_artists()->getArtistsSelectOptionsHavingConcerts( !$isPastDisplayMode ) as $id => $name) {
			$values[] = [
				'name'  => $name,
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
		global $wpdb;

		$this->joinClause .= " LEFT JOIN {$wpdb->postmeta} AS bufu_artist ON {$wpdb->posts}.ID = bufu_artist.post_id";
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

		$clause1 = $wpdb->prepare( 'bufu_artist.meta_key = %s', '_bufu_artists_selectArtist' );
		$clause2 = $wpdb->prepare( 'bufu_artist.meta_value = %s', $this->currentValue );

		$this->whereClause .= ' AND (' . $clause1 . ' AND '. $clause2 .')';
	}

}