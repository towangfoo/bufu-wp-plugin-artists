<?php

/**
 * Widget to search within artists posts.
 */
class Bufu_Widget_ArtistsSearch extends WP_Widget
{
    /**
     * @var Bufu_Artists_ThemeHelper
     */
    private $themeHelper;

	function __construct() {
		parent::__construct(
			// Base ID of your widget
			'bufu_artists_search',

			// Widget name will appear in UI
			__('Search in artists', 'bufu-artists'),

			// Widget description
			array( 'description' => __( 'Search only within artist posts', 'bufu-artists' ), )
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
		$title = apply_filters( 'widget_title', $instance['title'] );
		$query = get_query_var('s', '');

		$placeholderString = $instance['placeholder'];
		$btnText = __('Search', 'bufu-artists' );

		// @FIXME: hard-coded URI
		$artistListURI = '/kuenstler/';

        echo $args['before_widget'];

        if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];

		echo '<div class="widget-artists-search">';

		echo '<form role="search" method="get" class="search-form" action="'. $artistListURI .'">';
        echo '<label>';
        echo '<input type="search" class="search-field form-control" placeholder="'. $placeholderString .'" value="'. $query .'" name="s" title="'. $title .'">';
        echo '</label>';
        echo '<input type="submit" class="search-submit btn btn-default" value="'. $btnText .'">';
		echo '</form>';

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
			$title = __('Search in artists', 'bufu-artists' );
		}

		if ( isset( $instance[ 'placeholder' ] ) ) {
			$placeholder = $instance[ 'placeholder' ];
		}
		else {
			$placeholder = __('Search in artists', 'bufu-artists' );
		}

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'bufu-artists' ); ?>:</label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
        <p>
            <label for="<?php echo $this->get_field_id( 'placeholder' ); ?>"><?php _e( 'Placeholder', 'bufu-artists' ); ?>:</label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'placeholder' ); ?>" name="<?php echo $this->get_field_name( 'placeholder' ); ?>" type="text" value="<?php echo esc_attr( $placeholder ); ?>" />
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
		$instance['placeholder'] = ( ! empty( $new_instance['placeholder'] ) ) ? strip_tags( $new_instance['placeholder'] ) : '';
		return $instance;
	}
}