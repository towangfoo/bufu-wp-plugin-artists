<?php

/**
 * Widget to show reviews related to an artist.
 */
class Bufu_Widget_ReviewsByArtist extends WP_Widget
{
    /**
     * @var Bufu_Artists_ThemeHelper
     */
    private $themeHelper;

	function __construct() {
		parent::__construct(
			// Base ID of your widget
			'bufu_artists_reviews',

			// Widget name will appear in UI
			__('Reviews by Artist', 'bufu-artists'),

			// Widget description
			array( 'description' => __( 'A list of reviews related to the artist.', 'bufu-artists' ), )
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
		if (! $artist || $artist->post_type !== Bufu_Artists::$postTypeNameArtist) {
		    return;
        }

		if (! array_key_exists('limit', $args) ) {
			$args['limit'] = 10;
        }

		$interviews = $this->themeHelper->loadReviews($artist, $args['limit']);

		if (count($interviews) < 1) {
		    return;
        }

		$title = apply_filters( 'widget_title', $instance['title'] );

        echo $args['before_widget'];

        if (! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];

		echo '<ul class="nav flex-column">';

		$dateFormat = get_option( 'date_format' );
		$tz = get_option( 'timezone_string' );

		foreach ($interviews as $interview) {
		    /** @var $interview WP_Post */
			$itemUrl    = get_permalink( $interview );
			$itemSource = get_post_meta( $interview->ID, '_bufu_artist_review_source' , true);

			echo '<li class="nav-item">';
			echo '<a href="'. $itemUrl .'">';
			echo '<span class="title">'. esc_html( $interview->post_title ) .'</span>';
			echo '<span class="source">'. esc_html( $itemSource ) .'</span>';
			echo '</a>';
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
			$title = _n( 'Review', 'Reviews', 2, 'bufu-artists' );
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