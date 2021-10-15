<?php

require_once 'Bufu_Widget_ThemeHelperInterface.php';

/**
 * Widget to show an audio playlist related to an artist.
 * Depends on the Audiotheme Cue playlist plugin.
 */
class Bufu_Widget_PlaylistByArtist extends WP_Widget implements Bufu_Widget_ThemeHelperInterface
{
    /**
     * @var Bufu_Artists_ThemeHelper
     */
    private $themeHelper;

	function __construct() {
		parent::__construct(
			// Base ID of your widget
			'bufu_artists_audioplaylist',

			// Widget name will appear in UI
			__('Artist Playlist', 'bufu-artists'),

			// Widget description
			array( 'description' => __( 'Show an audio playlist for the current artist.', 'bufu-artists' ), )
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
	    if ( !$this->checkPluginAvailable() ) {
	        if ( is_admin() || is_admin_bar_showing() ) {
				echo $this->getAlertBoxAboutMissingPlugin();
            }
            return;
        }

		$artist = get_post();
		if ( !$artist || $artist->post_type !== Bufu_Artists::$postTypeNameArtist) {
		    return;
        }

		$playlist = $this->themeHelper->getAudioPlaylistByArtist($artist);
		if ( !$playlist ) {
		    return;
        }

		$title = apply_filters( 'widget_title', $instance['title'] );

        echo $args['before_widget'];

        if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];


        cue_playlist( $playlist, [
            'template' => 'cue-playlist-artist-profile.php',
        ] );

		echo $args['after_widget'];
	}

	/**
     * Render widget in admin.
     * @param array $instance
     * @return void
     */
	public function form( $instance ) {
		if ( !$this->checkPluginAvailable() ) {
            echo $this->getAlertBoxAboutMissingPlugin();
            return;
		}

		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'Artist Playlist', 'bufu-artists' );
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

	/**
     * Whether the Audiotheme Cue plugin is available.
	 * @return bool
	 */
	private function checkPluginAvailable()
    {
        return function_exists('cue_playlist');
    }

	/**
	 * Get an alert box HTML for feedback about missing plugin.
     *@return string
	 */
    private function getAlertBoxAboutMissingPlugin()
    {
        $plugin = [
			'Audiotheme Cue',
			'https://audiotheme.com/support/cue/'
        ];

		$out = '<div class="alert alert-info">';
		$out .= sprintf(__('Missing required plugin <a href="%s" target="_blank">%s</a>. Please install and activate the plugin.', 'bufu-artists'), $plugin[1], $plugin[0]);
		$out .= '</div>';

		return $out;
    }
}