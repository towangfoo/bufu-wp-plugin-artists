<?php

/**
 * Widget to search within artists posts.
 */
class Bufu_Widget_PostArchive extends WP_Widget
{
	function __construct() {
		parent::__construct(
			// Base ID of your widget
			'bufu_post_archive',

			// Widget name will appear in UI
			__('Configurable Post Archive', 'bufu-artists'),

			// Widget description
			array( 'description' => __( 'Show a post archive with various options', 'bufu-artists' ), )
		);
	}

	/**
     * Echoes the widget content.
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance )
    {
		$title = apply_filters( 'widget_title', $instance['title'] );

		$type         = $instance['type'];
		$showCounters = ($instance['counters'] === 'yes');
		$postType     = isset($instance['post_type']) ? $instance['post_type'] : 'post';

        echo $args['before_widget'];

        if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];

		echo '<div class="widget-post-archive">';

		echo '<ul class="nav flex-column">';
		echo wp_get_archives([
            'type'            => $type,
			'post_type'       => $postType,
            'show_post_count' => $showCounters,
            'echo'            => false,
            'format'          => 'custom',
            'before'          => '<li class="nav-item">',
            'after'           => '</li>',
        ]);
		echo '</ul>';

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
			$title = __('Post Archive', 'bufu-artists' );
		}

		if ( isset( $instance[ 'post_type' ] ) ) {
			$postType = $instance[ 'post_type' ];
		}
		else {
			$postType = 'post';
		}

		if ( isset( $instance[ 'type' ] ) ) {
			$type = $instance[ 'type' ];
		}
		else {
			$type = 'monthly';
		}

		if ( isset( $instance[ 'counters' ] ) ) {
			$counters = $instance[ 'counters' ];
		}
		else {
			$counters = 'yes';
		}

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'bufu-artists' ); ?>:</label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>

        <p>
            <label for="<?php echo $this->get_field_id( 'post_type' ); ?>"><?php _e( 'Post Type', 'bufu-artists' ); ?>:</label>
            <input id="<?php echo $this->get_field_id( 'post_type' ); ?>" name="<?php echo $this->get_field_name( 'post_type' ); ?>" type="text" value="<?php echo esc_attr( $postType ); ?>" />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id( 'type' ); ?>"><?php _e( 'Type', 'bufu-artists' ); ?>:</label>
            <select id="<?php echo $this->get_field_id( 'type' ); ?>" name="<?php echo $this->get_field_name( 'type' ); ?>">
                <option value="yearly"<?php if ($type === 'yearly') echo ' selected="selected"' ?>><?php _e('Yearly', 'bufu-artists') ?></option>
                <option value="monthly"<?php if ($type === 'monthly') echo ' selected="selected"' ?>><?php _e('Monthly', 'bufu-artists') ?></option>
                <option value="weekly"<?php if ($type === 'weekly') echo ' selected="selected"' ?>><?php _e('Weekly', 'bufu-artists') ?></option>
                <option value="postbypost"<?php if ($type === 'postbypost') echo ' selected="selected"' ?>><?php _e('Posts ordered by date', 'bufu-artists') ?></option>
                <option value="alpha"<?php if ($type === 'alpha') echo ' selected="selected"' ?>><?php _e('Posts ordered by title', 'bufu-artists') ?></option>
            </select>
        </p>

        <p>
            <input class="checkbox" type="checkbox"<?php if ($counters === 'yes') echo ' checked="checked"' ?> id="<?php echo $this->get_field_id( 'counters' ) ?>" name="<?php echo $this->get_field_name( 'counters' ) ?>" value="yes">
            <label for="<?php echo $this->get_field_id( 'counters' ); ?>"><?php _e( 'Include post count', 'bufu-artists' ); ?></label>
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
		$knownTypes = ['weekly', 'monthly', 'yearly', 'postbypost', 'alpha'];

		$instance = array();
		$instance['title']    = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['post_type'] = ( ! empty( $new_instance['post_type'] ) ) ? $new_instance['post_type'] : 'post';
		$instance['type']     = ( ! empty( $new_instance['type'] && in_array($new_instance['type'], $knownTypes) ) ) ? $new_instance['type'] : 'monthly';
		$instance['counters'] = ( ! empty( $new_instance['counters'] )  && $new_instance['counters'] === 'yes' ) ? 'yes' : 'no';
		return $instance;
	}
}