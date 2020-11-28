<?php

/**
 * Widget to show events related to an artist.
 */
class Bufu_Widget_ArtistsWall extends WP_Widget
{
    /**
     * @var Bufu_Artists_ThemeHelper
     */
    private $themeHelper;

	function __construct() {
		parent::__construct(
			// Base ID of your widget
			'bufu_artists_wall',

			// Widget name will appear in UI
			__('Artists Wall', 'bufu-artists'),

			// Widget description
			array( 'description' => __( 'Show a wall of artist\'s thumbnails linked to their profile page.', 'bufu-artists' ), )
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
	public function widget( $args, $instance )
    {
		$showThumbnailPlaceholder = (array_key_exists('placeholders', $instance) && $instance['placeholders'] === 'yes');
		$showRandomOrder = (array_key_exists('random', $instance) && $instance['random'] === 'yes');

		$artists = $this->themeHelper->loadAllVisibleArtists( $showRandomOrder );

		if (count($artists) < 1) {
		    return;
        }

		$title = apply_filters( 'widget_title', $instance['title'] );

		$noThumbnail = '<span class="no-thumbnail-available"><span></span></span>';

        echo $args['before_widget'];

        if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];

		echo '<div class="widget-artists-wall">';
		echo '<div class="row">';

		$i = 0;
		foreach ($artists as $artist) {
		    /** @var $artist WP_Post */
		    $artistName = $artist->post_title;
		    $artistUrl = get_permalink( $artist );
		    $thumbnail = get_the_post_thumbnail( $artist, [150, 150] );

		    if ( !$showThumbnailPlaceholder && !$thumbnail ) {
		        continue;
            }

			if ( $i > 0 && $i % 4 === 0 ) {
				echo '</div>';
				echo '<div class="row">';
            }

            echo '<div class="col-3">';
			echo '<a href="'. $artistUrl .'" title="'. esc_attr( $artistName ) .'">';
			if ( $thumbnail ) {
			    echo $thumbnail;
            }
            else {
			    echo $noThumbnail;
            }
			echo '</a>';
			echo '</div>';

		    $i++;
		}

		echo '</div>';
		echo '</div>';

		echo $args['after_widget'];
	}

	/**
     * Render widget in admin.
     * @param array $instance
     * @return void
     */
	public function form( $instance )
    {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = _n('Artist', 'Artists', 2, 'bufu-artists' );
		}

		$checkedPlaceholders = (isset($instance['placeholders']) && $instance['placeholders'] === 'yes') ? ' checked="checked"' : '';
		$checkedRandom       = (isset($instance['random']) && $instance['random'] === 'yes') ? ' checked="checked"' : '';

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
        <p>
            <input class="checkbox" type="checkbox" id="<?php echo $this->get_field_id( 'placeholders' ); ?>" name="<?php echo $this->get_field_name( 'placeholders' ); ?>" value="yes"<?= $checkedPlaceholders ?>>
            <label for="<?php echo $this->get_field_id( 'placeholders' ); ?>"><?php _e( 'Show placeholder when there is no thumbnail', 'bufu-artists' ); ?></label>
            <br/>
            <input class="checkbox" type="checkbox" id="<?php echo $this->get_field_id( 'random' ); ?>" name="<?php echo $this->get_field_name( 'random' ); ?>" value="yes"<?= $checkedRandom ?>>
            <label for="<?php echo $this->get_field_id( 'random' ); ?>"><?php _e( 'Show artists in random order', 'bufu-artists' ); ?></label>
        </p>
		<?php
	}

	/**
     * Update widget settings
     * @param array $new_instance
     * @param array $old_instance
     * @return array
     */
	public function update( $new_instance, $old_instance )
    {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['placeholders'] = ( ! empty( $new_instance['placeholders'] ) ) ? strip_tags( $new_instance['placeholders'] ) : 'no';
		$instance['random'] = ( ! empty( $new_instance['random'] ) ) ? strip_tags( $new_instance['random'] ) : 'no';
		return $instance;
	}
}