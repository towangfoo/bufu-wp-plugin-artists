<?php

require_once 'Bufu_Widget_ThemeHelperInterface.php';

/**
 * Widget to show events related to an artist.
 */
class Bufu_Widget_EventsByArtist extends WP_Widget implements Bufu_Widget_ThemeHelperInterface
{
    /**
     * @var Bufu_Artists_ThemeHelper
     */
    private $themeHelper;

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
	 * @param Bufu_Artists_ThemeHelper $themeHelper
	 */
	public function setThemeHelper(Bufu_Artists_ThemeHelper $themeHelper)
	{
        $this->themeHelper = $themeHelper;
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

        $hasMoreEvents = false;
		$events = $this->themeHelper->loadNextConcerts($artist, $args['limit'] + 1);

		$num = count($events);
		if ( $num < 1 ) {
		    return;
        }
        else if ( $num > $args['limit'] ) {
		    $hasMoreEvents = true;
		    $events = current(array_chunk($events, $args['limit'])); // use only the first n items
        }

		$title = apply_filters( 'widget_title', $instance['title'] );

        echo $args['before_widget'];

        if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];

		echo '<ul class="nav flex-column">';

		$dateFormat = get_option( 'date_format' );
		$tz = get_option( 'timezone_string' );

		foreach ( $events as $event ) {
			$startDate = new \DateTime( $event->start_date, new \DateTimeZone($tz) );
			$dateFormatted =  wp_date( $dateFormat, $startDate->getTimestamp() );
			$venue = $event->venues[0];
			$eventUrl = get_permalink( $event );
			$ticketUrl = tribe_get_event_website_url( $event );

			echo '<li class="nav-item">';
			echo '<a href="'. $eventUrl .'">';
			echo '<span class="date">'. $dateFormatted .'</span>';
			echo '<span class="city">'. esc_html( $venue->city ) .'</span>';
			echo '</a>';
			if ( $ticketUrl ) {
				echo '<a href="'. $ticketUrl .'" target="_blank" class="tickets">'. __("Order tickets", 'bufu-artists') .'</a>';
			}
			echo '</li>';
		}

		if ( $hasMoreEvents ) {
		    $eventsListBaseUrl = tribe_get_listview_link();
			$eventsListUrl  = "{$eventsListBaseUrl}?tribe_bufu_artist_filter%5B0%5D={$artist->ID}";

			echo '<li class="nav-item">';
			echo '<a href="'. $eventsListUrl .'">';
			echo '<span class="show-more">'. __("Show more events", 'bufu-artists') .' &raquo;</span>';
			echo '</a>';
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