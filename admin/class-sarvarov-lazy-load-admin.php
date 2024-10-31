<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    SARVAROV_Lazy_Load
 * @subpackage SARVAROV_Lazy_Load/admin
 */
class SARVAROV_Lazy_Load_Admin {
	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The title of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_title The title of this plugin.
	 */
	protected $plugin_title;

	/**
	 * The slug of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_slug The slug of this plugin.
	 */
	protected $plugin_slug;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_version The current version of the plugin.
	 */
	protected $plugin_version;

	/**
	 * The plugin settings.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array $plugin_settings The plugin settings.
	 */
	protected $plugin_settings;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $plugin_title The title of this plugin.
	 * @param string $plugin_slug The slug of this plugin.
	 * @param string $plugin_version The version of this plugin.
	 * @param array $plugin_settings The plugin settings.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $plugin_title, $plugin_slug, $plugin_version, $plugin_settings ) {
		$this->plugin_name     = $plugin_name;
		$this->plugin_title    = $plugin_title;
		$this->plugin_slug     = $plugin_slug;
		$this->plugin_version  = $plugin_version;
		$this->plugin_settings = $plugin_settings;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/admin-styles.css', array( 'wp-color-picker' ), $this->plugin_version );
	}

	/**
	 * Register the option link for the plugins page.
	 *
	 * @param array $links Action links of the plugin.
	 *
	 * @since    1.0.0
	 */
	public function settings_link( $links = array() ) {
		$page = sprintf( '<a href="%1$s">%2$s</a>', admin_url( 'options-general.php?page=' . $this->plugin_slug . '_settings' ), esc_html__( 'Settings' ) );
		array_unshift( $links, $page );

		return $links;
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker-alpha', plugin_dir_url( __FILE__ ) . 'js/wp-color-picker-alpha.js', array( 'wp-color-picker' ) );
		wp_enqueue_script( 'hc-sticky', plugin_dir_url( __FILE__ ) . 'js/hc-sticky.js' );
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/admin-scripts.js', array(
			'jquery',
			'wp-color-picker-alpha',
			'hc-sticky'
		), $this->plugin_version, false );
	}

	/**
	 * Create menu item.
	 *
	 * @since 0.1.0
	 */
	public function add_menu_page() {
		add_options_page(
			$this->plugin_title,
			esc_html__( 'Lazy Load', $this->plugin_name ),
			'activate_plugins',
			$this->plugin_slug . '_settings',
			array( $this, 'render_plugin_admin_page' )
		);
	}

	/**
	 * Render admin page.
	 *
	 * @since 0.1.0
	 */
	public function render_plugin_admin_page() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/sections.php';
	}

	/**
	 * Wrap sections in div.
	 *
	 * @param string $page The slug name of the page whose settings sections you want to output.
	 *
	 * @since    1.0.0
	 */
	private function do_wrapped_settings_sections( $page ) {
		global $wp_settings_sections, $wp_settings_fields;

		if ( ! isset( $wp_settings_sections[ $page ] ) ) {
			return;
		}

		foreach ( (array) $wp_settings_sections[ $page ] as $section ) {
			printf( '<div id="%s" class="section">', str_replace( $this->plugin_slug . '_', '', $section['id'] ) );

			if ( $section['title'] ) {
				echo "<h2>{$section['title']}</h2>\n";
			}

			if ( $section['callback'] ) {
				call_user_func( $section['callback'], $section );
			}

			if ( ! isset( $wp_settings_fields ) || ! isset( $wp_settings_fields[ $page ] ) || ! isset( $wp_settings_fields[ $page ][ $section['id'] ] ) ) {
				continue;
			}

			echo '<table class="form-table">';
			do_settings_fields( $page, $section['id'] );
			echo '</table>';

			echo '</div>';
		}
	}

	/**
	 * Initialize admin page.
	 *
	 * @since 0.1.0
	 */
	public function admin_init() {
		$settings = $this->plugin_settings;

		register_setting(
			$this->plugin_name,
			$this->plugin_slug,
			array(
				'sanitize_callback' => array( $this, 'sanitize' )
			)
		);

		/**
		 * General section.
		 */
		add_settings_section(
			$this->plugin_slug . '_general_settings',
			esc_html__( 'General settings', $this->plugin_name ),
			'__return_false',
			$this->plugin_name
		);

		add_settings_field(
			$this->plugin_slug . '_enable',
			esc_html__( 'Enable on', $this->plugin_name ),
			function ( $args ) use ( $settings ) { ?>
                <fieldset>
                    <legend class="screen-reader-text">
                        <span><?php esc_html_e( 'Enable on', $this->plugin_name ); ?></span>
                    </legend>
					<?php
					$attr = 'enable_on_images';

					printf(
						'<label>
								<input type="checkbox" name="%1$s[%2$s]" value="1"%3$s />
								%4$s
							</label>',
						$this->plugin_slug,
						$attr,
						( isset( $settings[ $attr ] ) && (int) $settings[ $attr ] === 1 ) ? ' checked' : '',
						__( 'Images', $this->plugin_name )
					);
					?>
                    <br/>
					<?php
					$attr = 'enable_on_iframes';

					printf(
						'<label>
								<input type="checkbox" name="%1$s[%2$s]" value="1"%3$s />
								%4$s
							</label>',
						$this->plugin_slug,
						$attr,
						( isset( $settings[ $attr ] ) && (int) $settings[ $attr ] === 1 ) ? ' checked' : '',
						__( 'Iframes', $this->plugin_name )
					);
					?>
                </fieldset>

				<?php
			},
			$this->plugin_name,
			$this->plugin_slug . '_general_settings'
		);

		add_settings_field(
			$this->plugin_slug . '_preload_enable',
			esc_html__( 'Preload after page load', $this->plugin_name ),
			function ( $args ) use ( $settings ) { ?>
                <fieldset>
                    <legend class="screen-reader-text">
                        <span><?php esc_html_e( 'Preload after page load', $this->plugin_name ); ?></span>
                    </legend>
					<?php
					$attr = 'preload_enable';

					printf(
						'<label>
								<input name="%1$s[%2$s]" type="radio" value="1"%3$s /> 
								%5$s
							</label>
							<br />
							<label>
								<input name="%1$s[%2$s]" type="radio" value="0"%4$s /> 
								%6$s
							</label>',
						$this->plugin_slug,
						$attr,
						( isset( $settings[ $attr ] ) && (int) $settings[ $attr ] === 1 ) ? ' checked' : '',
						( isset( $settings[ $attr ] ) && (int) $settings[ $attr ] === 0 ) ? ' checked' : '',
						__( 'Yes' ),
						__( 'No' )
					);
					?>
                    <p class="description"><?php _e( 'Select <code>Yes</code> if you want every lazy item to be instantly loaded after page load.', $this->plugin_name ); ?></p>
                </fieldset>

				<?php
			},
			$this->plugin_name,
			$this->plugin_slug . '_general_settings'
		);

		add_settings_field(
			$this->plugin_slug . '_expand_value',
			esc_html__( 'Expand value', $this->plugin_name ),
			function ( $args ) use ( $settings ) { ?>
                <fieldset>
                    <legend class="screen-reader-text">
                        <span><?php esc_html_e( 'Expand value', $this->plugin_name ); ?></span>
                    </legend>
					<?php
					$attr = 'expand_value';

					printf(
						'<input name="%1$s[%2$s]" type="number" step="1" value="%3$s" class="small-text" placeholder="auto">
							%4$s',
						$this->plugin_slug,
						$attr,
						$settings[ $attr ],
						__( 'px' )
					);
					?>
                    <p class="description"><?php _e( 'This option expands the calculated visual viewport area in all directions, so that elements can be loaded before they become visible.', $this->plugin_name ); ?></p>
                    <p class="description">
                        <small><?php _e( 'Set to empty and it will be automatically calculated depending on the viewport size of the device. Set to <code>0</code> if you want lazy content to be loaded only if it are inside the viewport.', $this->plugin_name ); ?></small>
                    </p>
                </fieldset>

				<?php
			},
			$this->plugin_name,
			$this->plugin_slug . '_general_settings'
		);

		add_settings_field(
			$this->plugin_slug . '_placeholder_color',
			esc_html__( 'Placeholder color', $this->plugin_name ),
			function ( $args ) use ( $settings ) { ?>
                <fieldset>
                    <legend class="screen-reader-text">
                        <span><?php esc_html_e( 'Placeholder color', $this->plugin_name ); ?></span>
                    </legend>
					<?php
					$attr = 'placeholder_color';

					printf(
						'<input name="%1$s[%2$s]" type="text" class="color-picker" data-alpha="true" value="%3$s" />',
						$this->plugin_slug,
						$attr,
						$settings[ $attr ]

					);
					?>
                    <p class="description"><?php printf( __( 'Click on <code>%s</code> or set transparent to <code>0</code> if you want to make placeholder transparent.', $this->plugin_name ), __( 'Clear' ) ); ?></p>
                    <p class="description">
                        <small><?php printf( '%s: <code>rgba(0, 0, 0, 0.05)</code>', __( 'Default' ) ); ?></small></p>
                </fieldset>

				<?php
			},
			$this->plugin_name,
			$this->plugin_slug . '_general_settings'
		);

		/**
		 * Lazy images settings section.
		 */
		add_settings_section(
			$this->plugin_slug . '_images_settings',
			esc_html__( 'Lazy images settings', $this->plugin_name ),
			'__return_false',
			$this->plugin_name
		);

		add_settings_field(
			$this->plugin_slug . '_image_transition',
			esc_html__( 'Transition', $this->plugin_name ),
			function ( $args ) use ( $settings ) { ?>
                <fieldset class="transition-settings">
                    <legend class="screen-reader-text">
                        <span><?php esc_html_e( 'Transition', $this->plugin_name ); ?></span>
                    </legend>
					<?php
					$attr = 'image_transition_time';

					printf(
						'<label>
								<span>%1$s</span>
								<input name="%2$s[%3$s]" type="number" step="1" min="0" value="%4$s" class="small-text" />
								%5$s
							</label>',
						__( 'Duration', $this->plugin_name ),
						$this->plugin_slug,
						$attr,
						(int) $settings[ $attr ],
						__( 'ms' )
					);
					?>
                    <br/>
					<?php
					$attr = 'image_transition_delay';

					printf(
						'<label>
								<span>%1$s</span>
								<input name="%2$s[%3$s]" type="number" step="1" min="0" value="%4$s" class="small-text" />
								%5$s
							</label>',
						__( 'Delay', $this->plugin_name ),
						$this->plugin_slug,
						$attr,
						(int) $settings[ $attr ],
						__( 'ms' )
					);
					?>
                    <br/>
					<?php
					$attr = 'image_transition_effect';

					printf(
						'<label>
								<span>%1$s</span>
								<select name="%2$s[%3$s]" class="postform">
									%4$s
								</select>
							</label>',
						__( 'Effect', $this->plugin_name ),
						$this->plugin_slug,
						$attr,
						$this->get_transition_options( true, $settings[ $attr ] )
					);
					?>
                    <p class="description"><?php _e( 'Set the duration to <code>0</code> if you want to disable the transition completely.', $this->plugin_name ); ?></p>
                </fieldset>

				<?php
			},
			$this->plugin_name,
			$this->plugin_slug . '_images_settings'
		);

		add_settings_field(
			$this->plugin_slug . '_image_average_color_bg_enable',
			esc_html__( 'Average color placeholder', $this->plugin_name ),
			function ( $args ) use ( $settings ) { ?>
                <fieldset>
                    <legend class="screen-reader-text">
                        <span><?php esc_html_e( 'Average color placeholder', $this->plugin_name ); ?></span>
                    </legend>
					<?php
					$attr = 'image_average_color_bg_enable';

					printf(
						'<p>
								<label>
									<input name="%1$s[%2$s]" type="radio" value="1"%3$s /> 
									%5$s
								</label>
								<br />
								<label>
									<input name="%1$s[%2$s]" type="radio" value="0"%4$s /> 
									%6$s
								</label>
							</p>',
						$this->plugin_slug,
						$attr,
						( isset( $settings[ $attr ] ) && (int) $settings[ $attr ] === 1 ) ? ' checked' : '',
						( isset( $settings[ $attr ] ) && (int) $settings[ $attr ] === 0 ) ? ' checked' : '',
						__( 'Yes' ),
						__( 'No' )
					);
					?>
                </fieldset>

				<?php
			},
			$this->plugin_name,
			$this->plugin_slug . '_images_settings'
		);

		add_settings_field(
			$this->plugin_slug . '_lqip_enable',
			esc_html__( 'LQIP technologue', $this->plugin_name ),
			function ( $args ) use ( $settings ) { ?>
                <fieldset>
                    <legend class="screen-reader-text">
                        <span><?php esc_html_e( 'LQIP technologue enable', $this->plugin_name ); ?></span>
                    </legend>
					<?php
					$attr = 'lqip_enable';

					printf(
						'<label>
								<input name="%1$s[%2$s]" type="radio" value="1"%3$s /> 
								%5$s
							</label>
							<br />
							<label>
								<input name="%1$s[%2$s]" type="radio" value="0"%4$s /> 
								%6$s
							</label>',
						$this->plugin_slug,
						$attr,
						( isset( $settings[ $attr ] ) && (int) $settings[ $attr ] === 1 ) ? ' checked' : '',
						( isset( $settings[ $attr ] ) && (int) $settings[ $attr ] === 0 ) ? ' checked' : '',
						__( 'Yes' ),
						__( 'No' )
					);
					?>
                    <p class="description"><?php _e( '<span style="color: red;">Warning!</span> LQIP will only work on newly uploaded images. If you want to make it work with existing images, you need to regenerate <code>lqip</code> image size using a third-party plugin like <a href="https://wordpress.org/plugins/regenerate-thumbnails/" target="_blank">Regenerate Thumbnails</a>.', $this->plugin_name ); ?></p>
                </fieldset>

				<?php
			},
			$this->plugin_name,
			$this->plugin_slug . '_images_settings'
		);

		add_settings_field(
			$this->plugin_slug . '_lqip_size',
			esc_html__( 'LQIP size', $this->plugin_name ),
			function ( $args ) use ( $settings ) { ?>
                <fieldset class="lqip-child">
                    <legend class="screen-reader-text">
                        <span><?php esc_html_e( 'LQIP size', $this->plugin_name ); ?></span>
                    </legend>
					<?php
					$attr = 'lqip_size';

					printf(
						'<input name="%1$s[%2$s]" type="number" step="1" min="15" max="100" value="%3$s" class="small-text" placeholder="33">
							%4$s',
						$this->plugin_slug,
						$attr,
						(int) $settings[ $attr ],
						__( 'px' )
					);
					?>
                </fieldset>

				<?php
			},
			$this->plugin_name,
			$this->plugin_slug . '_images_settings'
		);

		add_settings_field(
			$this->plugin_slug . '_lqip_blur_radius',
			esc_html__( 'LQIP blur radius', $this->plugin_name ),
			function ( $args ) use ( $settings ) { ?>
                <fieldset class="lqip-child">
                    <legend class="screen-reader-text">
                        <span><?php esc_html_e( 'LQIP blur radius', $this->plugin_name ); ?></span>
                    </legend>
					<?php
					$attr = 'lqip_blur_radius';

					printf(
						'<input name="%1$s[%2$s]" type="number" step="1" min="0" max="50" value="%3$s" class="small-text" placeholder="20">
							%4$s',
						$this->plugin_slug,
						$attr,
						(int) $settings[ $attr ],
						__( 'px' )
					);
					?>
                </fieldset>

				<?php
			},
			$this->plugin_name,
			$this->plugin_slug . '_images_settings'
		);

		add_settings_field(
			$this->plugin_slug . '_lqip_base64_enable',
			esc_html__( 'Encode LQIP to base64', $this->plugin_name ),
			function ( $args ) use ( $settings ) { ?>
                <fieldset class="lqip-child">
                    <legend class="screen-reader-text">
                        <span><?php esc_html_e( 'Encode LQIP to base64', $this->plugin_name ); ?></span>
                    </legend>
					<?php
					$attr = 'lqip_base64_enable';

					printf(
						'<p>
								<label>
									<input name="%1$s[%2$s]" type="radio" value="1"%3$s /> 
									%5$s
								</label>
								<br />
								<label>
									<input name="%1$s[%2$s]" type="radio" value="0"%4$s /> 
									%6$s
								</label>
							</p>',
						$this->plugin_slug,
						$attr,
						( isset( $settings[ $attr ] ) && (int) $settings[ $attr ] === 1 ) ? ' checked' : '',
						( isset( $settings[ $attr ] ) && (int) $settings[ $attr ] === 0 ) ? ' checked' : '',
						__( 'Yes' ),
						__( 'No' )
					);
					?>
                </fieldset>

				<?php
			},
			$this->plugin_name,
			$this->plugin_slug . '_images_settings'
		);

		add_settings_field(
			$this->plugin_slug . '_image_lqip_transition',
			esc_html__( 'LQIP transition', $this->plugin_name ),
			function ( $args ) use ( $settings ) { ?>
                <fieldset class="lqip-child transition-settings">
                    <legend class="screen-reader-text">
                        <span><?php esc_html_e( 'LQIP transition', $this->plugin_name ); ?></span>
                    </legend>
					<?php
					$attr = 'image_lqip_transition_time';

					printf(
						'<label>
								<span>%1$s</span>
								<input name="%2$s[%3$s]" type="number" step="1" min="0" value="%4$s" class="small-text" />
								%5$s
							</label>',
						__( 'Duration', $this->plugin_name ),
						$this->plugin_slug,
						$attr,
						(int) $settings[ $attr ],
						__( 'ms' )
					);
					?>
                    <br/>
					<?php
					$attr = 'image_lqip_transition_delay';

					printf(
						'<label>
								<span>%1$s</span>
								<input name="%2$s[%3$s]" type="number" step="1" min="0" value="%4$s" class="small-text" />
								%5$s
							</label>',
						__( 'Delay', $this->plugin_name ),
						$this->plugin_slug,
						$attr,
						(int) $settings[ $attr ],
						__( 'ms' )
					);
					?>
                    <br/>
					<?php
					$attr = 'image_lqip_transition_effect';

					printf(
						'<label>
								<span>%1$s</span>
								<select name="%2$s[%3$s]" class="postform">
									%4$s
								</select>
							</label>',
						__( 'Effect', $this->plugin_name ),
						$this->plugin_slug,
						$attr,
						$this->get_transition_options( true, $settings[ $attr ] )
					);
					?>
                    <p class="description"><?php _e( 'Set the duration to <code>0</code> if you want to disable the transition completely.', $this->plugin_name ); ?></p>
                </fieldset>

				<?php
			},
			$this->plugin_name,
			$this->plugin_slug . '_images_settings'
		);

		/**
		 * Lazy iframes settings section.
		 */
		add_settings_section(
			$this->plugin_slug . '_iframes_settings',
			esc_html__( 'Lazy iframes settings', $this->plugin_name ),
			'__return_false',
			$this->plugin_name
		);

		add_settings_field(
			$this->plugin_slug . '_iframe_transition',
			esc_html__( 'Transition', $this->plugin_name ),
			function ( $args ) use ( $settings ) { ?>
                <fieldset class="transition-settings">
                    <legend class="screen-reader-text">
                        <span><?php esc_html_e( 'Transition', $this->plugin_name ); ?></span>
                    </legend>
					<?php
					$attr = 'iframe_transition_time';

					printf(
						'<label>
								<span>%1$s</span>
								<input name="%2$s[%3$s]" type="number" step="1" min="0" value="%4$s" class="small-text" />
								%5$s
							</label>',
						__( 'Duration', $this->plugin_name ),
						$this->plugin_slug,
						$attr,
						(int) $settings[ $attr ],
						__( 'ms' )
					);
					?>
                    <br/>
					<?php
					$attr = 'iframe_transition_delay';

					printf(
						'<label>
								<span>%1$s</span>
								<input name="%2$s[%3$s]" type="number" step="1" min="0" value="%4$s" class="small-text" />
								%5$s
							</label>',
						__( 'Delay', $this->plugin_name ),
						$this->plugin_slug,
						$attr,
						(int) $settings[ $attr ],
						__( 'ms' )
					);
					?>
                    <br/>
					<?php
					$attr = 'iframe_transition_effect';

					printf(
						'<label>
								<span>%1$s</span>
								<select name="%2$s[%3$s]" class="postform">
									%4$s
								</select>
							</label>',
						__( 'Effect', $this->plugin_name ),
						$this->plugin_slug,
						$attr,
						$this->get_transition_options( true, $settings[ $attr ] )
					);
					?>
                    <p class="description"><?php _e( 'Set the duration to <code>0</code> if you want to disable the transition completely.', $this->plugin_name ); ?></p>
                </fieldset>

				<?php
			},
			$this->plugin_name,
			$this->plugin_slug . '_iframes_settings'
		);

		add_settings_field(
			$this->plugin_slug . '_iframe_average_color_bg_enable',
			esc_html__( 'Average color placeholder', $this->plugin_name ),
			function ( $args ) use ( $settings ) { ?>
                <fieldset>
                    <legend class="screen-reader-text">
                        <span><?php esc_html_e( 'Average color placeholder', $this->plugin_name ); ?></span>
                    </legend>
					<?php
					$attr = 'iframe_average_color_bg_enable';

					printf(
						'<p>
								<label>
									<input name="%1$s[%2$s]" type="radio" value="1"%3$s /> 
									%5$s
								</label>
								<br />
								<label>
									<input name="%1$s[%2$s]" type="radio" value="0"%4$s /> 
									%6$s
								</label>
							</p>',
						$this->plugin_slug,
						$attr,
						( isset( $settings[ $attr ] ) && (int) $settings[ $attr ] === 1 ) ? ' checked' : '',
						( isset( $settings[ $attr ] ) && (int) $settings[ $attr ] === 0 ) ? ' checked' : '',
						__( 'Yes' ),
						__( 'No' )
					);
					?>
                    <p class="description"><?php _e( '<span style="color: orange;">Beta!</span> At the moment working only on YouTube and Vimeo videos.', $this->plugin_name ); ?></p>
                </fieldset>

				<?php
			},
			$this->plugin_name,
			$this->plugin_slug . '_iframes_settings'
		);

		/**
		 * Advanced settings section.
		 */
		add_settings_section(
			$this->plugin_slug . '_advanced_settings',
			esc_html__( 'Advanced settings', $this->plugin_name ),
			'__return_false',
			$this->plugin_name
		);

		add_settings_field(
			$this->plugin_slug . '_cache_enable',
			esc_html__( 'Cache enable', $this->plugin_name ),
			function ( $args ) use ( $settings ) { ?>
                <fieldset>
                    <legend class="screen-reader-text">
                        <span><?php esc_html_e( 'Cache enable', $this->plugin_name ); ?></span>
                    </legend>
					<?php
					$attr = 'cache_enable';

					printf(
						'<label>
								<input name="%1$s[%2$s]" type="radio" value="1"%3$s /> 
								%5$s
							</label>
							<br />
							<label>
								<input name="%1$s[%2$s]" type="radio" value="0"%4$s /> 
								%6$s
							</label>',
						$this->plugin_slug,
						$attr,
						( isset( $settings[ $attr ] ) && (int) $settings[ $attr ] === 1 ) ? ' checked' : '',
						( isset( $settings[ $attr ] ) && (int) $settings[ $attr ] === 0 ) ? ' checked' : '',
						__( 'Yes' ),
						__( 'No' )
					);
					?>
                    <p class="description"><?php _e( '<span style="color: red;">Warning!</span> Turning it off will increase loading time of your blog.', $this->plugin_name ); ?></p>
                </fieldset>

				<?php
			},
			$this->plugin_name,
			$this->plugin_slug . '_advanced_settings'
		);

		add_settings_field(
			$this->plugin_slug . '_noscript_enable',
			esc_html__( 'No javascript support', $this->plugin_name ),
			function ( $args ) use ( $settings ) { ?>
                <fieldset>
                    <legend class="screen-reader-text">
                        <span><?php esc_html_e( 'No javascript support', $this->plugin_name ); ?></span>
                    </legend>
					<?php
					$attr = 'noscript_enable';

					printf(
						'<label>
								<input name="%1$s[%2$s]" type="radio" value="1"%3$s /> 
								%5$s
							</label>
							<br />
							<label>
								<input name="%1$s[%2$s]" type="radio" value="0"%4$s /> 
								%6$s
							</label>',
						$this->plugin_slug,
						$attr,
						( isset( $settings[ $attr ] ) && (int) $settings[ $attr ] === 1 ) ? ' checked' : '',
						( isset( $settings[ $attr ] ) && (int) $settings[ $attr ] === 0 ) ? ' checked' : '',
						__( 'Yes' ),
						__( 'No' )
					);
					?>
                    <p class="description"><?php _e( 'This option will add a <code>&lt;noscript&gt;</code> tag that will display the element even if the user has disabled JavaScript.', $this->plugin_name ); ?></p>
                </fieldset>

				<?php
			},
			$this->plugin_name,
			$this->plugin_slug . '_advanced_settings'
		);

		add_settings_field(
			$this->plugin_slug . '_placeholders_disable',
			esc_html__( 'Completely disable placeholders', $this->plugin_name ),
			function ( $args ) use ( $settings ) { ?>
                <fieldset>
                    <legend class="screen-reader-text">
                        <span><?php esc_html_e( 'Completely disable placeholders', $this->plugin_name ); ?></span>
                    </legend>
					<?php
					$attr = 'placeholders_disable';

					printf(
						'<label>
								<input name="%1$s[%2$s]" type="radio" value="1"%3$s /> 
								%5$s
							</label>
							<br />
							<label>
								<input name="%1$s[%2$s]" type="radio" value="0"%4$s /> 
								%6$s
							</label>',
						$this->plugin_slug,
						$attr,
						( isset( $settings[ $attr ] ) && (int) $settings[ $attr ] === 1 ) ? ' checked' : '',
						( isset( $settings[ $attr ] ) && (int) $settings[ $attr ] === 0 ) ? ' checked' : '',
						__( 'Yes' ),
						__( 'No' )
					);
					?>
                </fieldset>

				<?php
			},
			$this->plugin_name,
			$this->plugin_slug . '_advanced_settings'
		);

		add_settings_field(
			$this->plugin_slug . '_custom_settings',
			esc_html__( 'Custom settings', $this->plugin_name ),
			function ( $args ) use ( $settings ) { ?>
                <fieldset>
                    <legend class="screen-reader-text">
                        <span><?php esc_html_e( 'Custom settings', $this->plugin_name ); ?></span>
                    </legend>
					<?php
					$attr = 'custom_settings';

					printf(
						'<textarea name="%1$s[%2$s]" class="large-text code" rows="3" placeholder="window.lazySizesConfig.loadMode = 2;%3$swindow.lazySizesConfig.throttleDelay = 125;">%4$s</textarea>',
						$this->plugin_slug,
						$attr,
						PHP_EOL,
						$settings[ $attr ]
					);
					?>
                    <p class="description"><?php _e( 'This plugin uses the <a href="https://github.com/aFarkas/lazysizes" target="_blank">Lazysizes</a> library. You can read its documentation and put here any options you want.', $this->plugin_name ); ?></p>
                </fieldset>

				<?php
			},
			$this->plugin_name,
			$this->plugin_slug . '_advanced_settings'
		);
	}

	/**
	 * Sanitize option value.
	 *
	 * @param string $settings Potentially dangerous data.
	 *
	 * @since    1.0.0
	 */
	public function sanitize( $settings ) {
		// sanitize checkboxes
		$checkboxes = array(
			'enable_on_images',
			'enable_on_iframes'
		);

		foreach ( $checkboxes as $c ) {
			$settings[ $c ] = ( isset( $settings[ $c ] ) ? true : false );
		}

		// sanitize ratio boxes
		$ratioboxes = array(
			'image_average_color_bg_enable',
			'iframe_average_color_bg_enable',
			'lqip_enable',
			'preload_enable',
			'noscript_enable',
			'lqip_base64_enable',
			'cache_enable',
			'placeholders_disable'
		);

		foreach ( $ratioboxes as $r ) {

			if ( ! isset( $settings[ $r ] ) ) {
				continue;
			}

			if ( isset( $settings[ $r ] ) && (int) $settings[ $r ] === 1 ) {
				$settings[ $r ] = true;
			} else {
				$settings[ $r ] = false;
			}
		}

		// sanitize number inputs
		$numberinputs = array(
			'expand_value',
			'image_transition_time',
			'image_transition_delay',
			'image_lqip_transition_time',
			'image_lqip_transition_delay',
			'iframe_transition_time',
			'iframe_transition_delay',
			'lqip_blur_radius',
			'lqip_size'
		);

		foreach ( $numberinputs as $n ) {

			if ( ! isset( $settings[ $n ] ) ) {
				continue;
			}

			if ( $n !== 'expand_value' || $n === 'expand_value' && $settings[ $n ] != '' ) {
				$settings[ $n ] = (int) $settings[ $n ];
			}

			if ( $n === 'lqip_size' && ( $settings[ $n ] > 15 || $settings[ $n ] < 100 ) ) {
				$settings[ $n ] = 33;
			} elseif ( $n === 'lqip_blur_radius' && ( $settings[ $n ] > 0 || $settings[ $n ] < 50 ) ) {
				$settings[ $n ] = 20;
			}
		}

		// sanitize color inputs
		$color_inputs = array(
			'placeholder_color'
		);

		foreach ( $color_inputs as $c ) {
			if ( ! isset( $settings[ $c ] ) ) {
				continue;
			}

			$settings[ $r ] = rtrim( $settings[ $r ], ';' );
		}

		// sanitize transition selects
		$transition_selects = array(
			'image_transition_effect',
			'image_lqip_transition_effect',
			'iframe_transition_effect'
		);

		$aviable_transitions = $this->get_transition_options();

		foreach ( $transition_selects as $t ) {

			if ( ! isset( $settings[ $t ] ) ) {
				continue;
			}

			if ( ! in_array( $settings[ $t ], $aviable_transitions ) ) {
				$settings[ $t ] = $aviable_transitions[0];
			}
		}

		delete_transient( 'sarvarov_lazy_load_dynamic_js' );
		delete_transient( 'sarvarov_lazy_load_dynamic_css' );

		return apply_filters( 'sarvarov_lazy_load_admin_sanitize_settings', $settings );
	}

	/**
	 * Get allowed transition options.
	 *
	 * @param bool $echo_in_html If true - function will return <option ... > for <select> tag.
	 * @param bool $default_value Will add `selected` if match.
	 *
	 * @since     1.0.0
	 */
	private function get_transition_options( $echo_in_html = false, $default_value = '' ) {
		$allowed_transition_effects = array(
			'ease',
			'ease-in',
			'ease-out',
			'ease-in-out',
			'linear'
		);

		$allowed_transition_effects = apply_filters( 'sarvarov_lazy_load_allowed_transition_effects', $allowed_transition_effects );

		if ( ! $echo_in_html ) {
			return $allowed_transition_effects;
		}

		$allowed_transition_effects_output = '';

		foreach ( $allowed_transition_effects as $effect ) {
			$allowed_transition_effects_output .= sprintf( '<option%1$s>%2$s</option>', ( $default_value == $effect ? ' selected' : '' ), $effect );
		}

		return $allowed_transition_effects_output;
	}

	/**
	 * Outputs the plugin sidebar.
	 *
	 * @since 1.0.0
	 */
	private function sidebar() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/sidebar.php';
	}
}
