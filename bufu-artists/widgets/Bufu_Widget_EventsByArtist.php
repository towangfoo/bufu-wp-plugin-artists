<?php

/**
 * Widget to show events related to an artist.
 */
class Bufu_Widget_EventsByArtist extends WP_Widget
{
	function __construct() {
		parent::__construct(
			// Base ID of your widget
			'bufu_artists_events',

			// Widget name will appear in UI
			__('Events by Artist', 'bufu-artists'),

			// Widget description
			array( 'description' => __( 'Show upcoming events of the current artist.', 'bufu-artists' ), )
		);
	}

	/**
     * Echoes the widget content.
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		$artist = get_post();
		if ( !$artist || $artist->post_type !== Bufu_Artists::$postTypeNameArtist) {
		    return;
        }

		if ( !array_key_exists('limit', $args) ) {
			$args['limit'] = 10;
        }

		// query tribe_events plugin API
		$from = new DateTime();
		$events = tribe_events()
			->where('status', 'publish')
			->where('starts_after', $from->format("Y-m-d 00:00:00"))
			->where('meta_equals', 'bufu_artist_selectArtist', $artist->ID ) // works despite the obvious API inconsistency
			->order_by('start_date', "ASC")
			->per_page($args['limit'])
			->page(1)
			->all();

		if (count($events) < 1) {
		    return;
        }

		$title = apply_filters( 'widget_title', $instance['title'] );

        echo $args['before_widget'];

        if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];

		echo '<ul class="nav flex-column">';

		$dateFormat = get_option( 'date_format' );

		foreach ($events as $event) {
			/** @var $startDate Tribe__Date_Utils */
			$startDate = $event->dates->start;
			$venue = $event->venues[0];
			$ticketUrl = tribe_get_event_website_url( $event );

			echo '<li class="nav-item">';
			echo '<span class="date">'. $startDate->format($dateFormat) .'</span>';
			echo '<span class="city">'. esc_html( $venue->city ) .'</span>';
			if ( $ticketUrl ) {
				echo '<a href="'. $ticketUrl .'" target="_blank" class="tickets">'. __("Order tickets", 'bufu-theme') .'</a>';
			}
			echo '</li>';
		}

		echo '</ul>';

		echo $args['after_widget'];
	}

	/**
     * Render widget in admin.
     * @param array $instance
     * @return void
     */
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'Events', 'bufu-artists' );
		}

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php
	}

	/**
     * Update widget settings
     * @param array $new_instance
     * @param array $old_instance
     * @return array
     */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		return $instance;
	}
}