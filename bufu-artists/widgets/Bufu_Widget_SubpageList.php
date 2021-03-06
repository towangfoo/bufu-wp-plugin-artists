<?php

/**
 * Widget to render subpages as navigation in sidebar
 */
class Bufu_Widget_SubpageList extends WP_Widget
{
	function __construct() {
		parent::__construct(
			// Base ID of your widget
			'bufu_post_chronicles',

			// Widget name will appear in UI
			__('Subpage navigation', 'bufu-artists'),

			// Widget description
			array( 'description' => __( 'Show links to all subpages of a selected page', 'bufu-artists' ), )
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

		$parentPage = $instance['page'];

		$pages = get_pages([
            'parent'       => $parentPage,
            'sort_column'  => 'post_title',
            'sort_order'   => 'DESC',
			'hierarchical' => true,
            'post_type'    => 'page',
        ]);

        echo $args['before_widget'];

        if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];

		echo '<div class="widget-chronicles-archive">';
		echo '<ul class="nav flex-column">';

		foreach ($pages as $p) {
		    /** @var $p WP_Post */
			echo '<li class="nav-item">';
			echo '<a class="line-clamp-2" href="'. get_permalink($p) .'">'. $p->post_title .'</a>';
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
	public function form( $instance )
    {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __('Subpage navigation', 'bufu-artists' );
		}

		$page = 0;
		if ( isset( $instance[ 'page' ] ) ) {
			$page = intval($instance[ 'page' ]);
		}

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'bufu-artists' ); ?>:</label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>

        <p>
            <label for="<?php echo $this->get_field_id( 'page' ); ?>"><?php _e( 'Parent page', 'bufu-artists' ); ?>:</label>
            <?php wp_dropdown_pages( [
                'depth'    => 1,
                'child_of' => 0,
                'selected' => $page,
                'name'     => $this->get_field_name( 'page' ),
                'id'       => $this->get_field_id( 'page' ),
                'echo'     => true,
            ] ) ?>
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
		$instance['page']  = ( ! empty( $new_instance['page']  ) ) ? $new_instance['page'] : '';
		return $instance;
	}
}