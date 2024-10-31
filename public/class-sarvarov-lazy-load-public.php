<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 * @package    SARVAROV_Lazy_Load
 * @subpackage SARVAROV_Lazy_Load/public
 */
class SARVAROV_Lazy_Load_Public {
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
	 * @since 1.0.0
	 */
	public function __construct( $plugin_name, $plugin_title, $plugin_slug, $plugin_version, $plugin_settings ) {
		$this->plugin_name     = $plugin_name;
		$this->plugin_title    = $plugin_title;
		$this->plugin_slug     = $plugin_slug;
		$this->plugin_version  = $plugin_version;
		$this->plugin_settings = apply_filters( 'sarvarov_lazy_load_plugin_settings', $plugin_settings );
	}

	/**
	 * Before init event.
	 *
	 * @since 1.0.0
	 */
	public function after_setup_theme() {
		// add custom image size for LQIP
		if ( isset( $this->plugin_settings['lqip_enable'] ) && (bool) $this->plugin_settings['lqip_enable'] == true ) {
			add_image_size( 'lqip', ! empty( $this->plugin_settings['lqip_size'] ) ? (int) $this->plugin_settings['lqip_size'] : 33 );
		}
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		// don't enqueue if not enabled
		$settings = $this->plugin_settings;

		if ( ( ! $settings['enable_on_images'] && ! $settings['enable_on_iframes'] ) || $settings['placeholders_disable'] ) {
			return;
		}

		// enqueue plugin style
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/sarvarov-lazy-load.min.css', array(), $this->plugin_version );

		// inline CSS
		if ( apply_filters( 'sarvarov_lazy_load_output_css', true ) ) {
			$inline_css = $this->inline_css();

			wp_add_inline_style( $this->plugin_name, $inline_css );
		}
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		// don't enqueue if not enabled
		$settings = $this->plugin_settings;
		if ( ! $settings['enable_on_images'] && ! $settings['enable_on_iframes'] ) {
			return;
		}

		// enqueue plugin script
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/sarvarov-lazy-load.min.js', array( 'jquery' ), $this->plugin_version, true );

		// load lazysizes library
		wp_enqueue_script( 'lazysizes', plugin_dir_url( __FILE__ ) . 'js/lazysizes.min.js', array(
			'jquery',
			$this->plugin_name
		), '5.1.0', true );

		if ( apply_filters( 'sarvarov_lazy_load_output_js', true ) ) {
			$inline_js = $this->inline_js();

			wp_add_inline_script( $this->plugin_name, $inline_js, 'after' );
		}
	}

	/**
	 * Add `async` to Lazysizes script.
	 *
	 * @param string $tag The `<script>` tag for the enqueued script.
	 * @param string $handle The script's registered handle.
	 *
	 * @return string|string[]
	 * @since 1.0.0
	 */
	public function add_async_to_lazysizes( $tag, $handle ) {
		if ( ( 'lazysizes' !== $handle && $this->plugin_name !== $handle ) || ! apply_filters( 'sarvarov_lazy_load_async_load_enable', true ) ) {
			return $tag;
		}

		return str_replace( ' src', ' defer="" src', $tag );
	}

	/**
	 * Remove cache after post update.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @since 1.0.0
	 */
	public function save_post( $post_id ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		$delete_meta = array( '_sarvarov_lazy_load_cache' );

		foreach ( $delete_meta as $d ) {
			delete_post_meta( $post_id, $d );
		}
	}

	/**
	 * Capture images & iframes to make them lazy.
	 *
	 * @param string $content HTML content that need to make lazy.
	 *
	 * @param array $params
	 *
	 * @return string|string[]|null
	 * @since 1.0.0
	 */
	public function lazy_process( $content = '', $params = array() ) {
		// don't lazyload for feeds, previews, mobile, admin page
		if ( ! apply_filters( 'sarvarov_lazy_load_enable', true ) || is_feed() || is_preview() || ( is_admin() && ! wp_doing_ajax() ) ) {
			return $content;
		}
		// capture images & iframes using regex
		if ( (bool) $this->plugin_settings['enable_on_images'] ) {
			$content = $this->lazy_image( $content, $params );
		}

		if ( (bool) $this->plugin_settings['enable_on_iframes'] ) {
			$content = $this->lazy_iframe( $content, $params );
		}

		return $content;
	}

    /**
     * Remove width and height attr from post thumbnail.
     *
     * @param string $content
     * @return string|string[]|null
     * @since 1.1.0
     */
    public function remove_size_atts_from_thumbnail( $content = '' ) {
        return preg_replace( '/(width|height)="\d*"\s/', '', $content );
    }

	/**
	 * Make images lazy.
	 *
	 * @param string $content HTML content that need to make lazy.
	 * @param array $params Parameters.
	 *
	 * @return string|string[]|null
	 * @since 1.0.0
	 */
	public function lazy_image( $content = '', $params = array() ) {
		if ( ! $content ) {
			return $content;
		}

		// get plugin settings
		$settings = $this->plugin_settings;

		$default_params = array(
			'image_attachment_id' => null,
			'post_id'             => get_the_ID()
		);

		$settings = array_merge(
			$default_params,
			$settings,
			$params
		);

		$cache_array = array();

		// capture images
		$content = preg_replace_callback( '#<img([^>]+?)(>(.*?)</\\1>|[\/]?>)#si', function ( $matches ) use ( $settings, &$cache_array ) {
			$settings = apply_filters( 'sarvarov_lazy_load_image_settings', $settings, $matches );

			// exclude settings
			extract( $settings, EXTR_SKIP );
			/** @var int $post_id */
			/** @var bool $placeholders_disable */
			/** @var bool $lqip_enable */
			/** @var bool $lqip_base64_enable */
			/** @var bool $cache_enable */
			/** @var bool $noscript_enable */
			/** @var array $cache_array */
			/** @var string $cache_md5 */
			/** @var string $image_average_color_bg_enable */

			// trying to get cache
			if ( is_preview() ) {
				$cache_enable = false;
			}

			if ( $cache_enable ) {
				if ( ! $cache_array ) {
					// prevent caching if post_id are null
					if ( ! $post_id ) {
						$settings['cache_enable'] = $cache_enable = false;
					} else {
						$cache_array = get_post_meta( $post_id, '_sarvarov_lazy_load_cache', true );
					}
				}

				if ( ! $cache_array || ! is_array( $cache_array ) ) {
					$cache_array = array();
				}

				$cache_md5   = substr( md5( $matches[0] ), 0, 12 );
				$options_md5 = substr( md5( json_encode( $settings, JSON_THROW_ON_ERROR ) ), 0, 12 );

				if ( isset( $cache_array[ $cache_md5 ] ) ) {
					if ( $cache_array[ $cache_md5 ] !== false ) {
						if ( isset( $cache_array[ $cache_md5 ]['cache_html'], $cache_array[ $cache_md5 ]['plugin_opt'], $cache_array[ $cache_md5 ]['plugin_ver'] ) && $cache_array[ $cache_md5 ]['plugin_opt'] === $options_md5 && $cache_array[ $cache_md5 ]['plugin_ver'] === $this->plugin_version ) {
							return $cache_array[ $cache_md5 ]['cache_html'];
						}
					} else {
						return $matches[0];
					}
				}
			}

			// matches hook
			$matches = apply_filters( 'sarvarov_lazy_load_image_matches', $matches );

			// begin
			$image_width  = null;
			$image_height = null;
			$lqip_html    = '';
			$style_atts   = '';
			$noscript_str = '';

			// get image attributes
			$image_atts = apply_filters( 'sarvarov_lazy_load_image_atts', wp_kses_hair( $matches[1], wp_allowed_protocols() ) );

			// trying to get URL of the image
			if ( empty( $image_atts['src']['value'] ) || ! empty( $image_atts['data-sarvarov-src'] ) ) {
				if ( $cache_enable ) {
					$cache_array[ $cache_md5 ] = false;
				}

				return $matches[0];
			}

			// exclude params && atts
			$image_src = esc_url( $image_atts['src']['value'] );

			// placeholder
			if ( ! $placeholders_disable ) {
				$style = array();

				// get image ID
				$image_attachment_id = apply_filters( 'sarvarov_lazy_load_attachment_id', null );

				if ( ! $image_attachment_id && ! empty( $image_atts['class']['value'] ) ) {
					$image_class = $image_atts['class']['value'];

					if ( preg_match( '/wp-image-(\d+)/i', $image_class, $image_attachment_id ) ) {
						$image_attachment_id = absint( $image_attachment_id[1] );
					} elseif ( false !== stripos( $image_class, "wp-post-image" ) ) {
						$image_attachment_id = get_post_thumbnail_id( $post_id );
					}
				}

				// get image dimensions
				list( $image_width, $image_height ) = $this->getimagesize( $image_src );

				if ( ! $image_width || ! $image_height ) {
					if ( $cache_enable ) {
						$cache_array[ $cache_md5 ] = false;
					}

					return $matches[0];
				}

				// caltulating padding-bottom to a container
				$aspect                  = round( ( $image_height / $image_width ) * 100, 2 );
				$style['padding-bottom'] = $aspect . '%';

				// get image width and height attribute
				if ( isset( $image_atts['width']['value'], $image_atts['height']['value'] ) ) {
					$style['width']  = (int) $image_atts['width']['value'] . 'px';
					$style['height'] = (int) $image_atts['height']['value'] . 'px';
					unset( $style['padding-bottom'] );
				}

				// LQIP technologue
				$_lqip_image_src = '';

				if ( $lqip_enable && $image_attachment_id && has_image_size( 'lqip' ) ) {

					$lqip_image = apply_filters( 'sarvarov_lazy_load_lqip_image', image_get_intermediate_size( $image_attachment_id, 'lqip' ), $image_attachment_id );

					if ( $lqip_image && ! empty( $lqip_image['url'] ) ) {
						$_lqip_image_src = $lqip_image_src = $lqip_image['url'];

						if ( $lqip_base64_enable ) {
							$lqip_image_src = $this->image_to_base64( $lqip_image_src );
						}

						$lqip_html = apply_filters( 'sarvarov_lazy_load_lqip_html', sprintf( '<div class="sarvarov-lazylqip"><img src="%1$s" /></div>', $lqip_image_src ) );
					}
				}

				// primary color background
				if ( $image_average_color_bg_enable ) {
					$image_to_calculate      = $_lqip_image_src ?: $image_src;
					$image_to_calculate_type = pathinfo( $image_to_calculate, PATHINFO_EXTENSION );

					if ( in_array( $image_to_calculate_type, array( 'jpg', 'jpeg' ) ) ) {
						$average_color = apply_filters( 'sarvarov_lazy_load_image_average_color_bg', $this->get_average_color( $image_to_calculate ) );

						if ( $average_color ) {
							$style['background-color'] = $average_color;
						}
					}
				}

				// remove width and height of image
				unset( $image_atts['width'], $image_atts['height'] );

				// style attributes
				if ( ! empty( $style ) ) {
					$style_atts = apply_filters( 'sarvarov_lazy_load_image_style', $this->array_to_style_atts( $style ) );
				}
			}

			// add `lazyload` class, change `src` to `data-sarvarov-src` and other things to make images lazy
			$new_atts = $image_atts;

			if ( isset( $new_atts['src'] ) ) {
				$new_atts['data-sarvarov-src'] = $new_atts['src'];
			}

			if ( isset( $new_atts['srcset'] ) ) {
				$new_atts['data-sarvarov-srcset'] = $new_atts['srcset'];
			}

			if ( isset( $new_atts['sizes'] ) ) {
				$new_atts['data-sarvarov-sizes'] = $new_atts['sizes'];
			}

			if ( ! empty( $new_atts['class']['value'] ) ) {
				$new_atts['class']['value'] = "sarvarov-lazyitem sarvarov-not-lazyloaded {$new_atts['class']['value']}";
			} else {
				$new_atts['class'] = array(
					'value' => 'sarvarov-lazyitem sarvarov-not-lazyloaded'
				);
			}

			unset( $new_atts['src'], $new_atts['srcset'], $new_atts['sizes'] );

			$new_atts_str = $this->build_attributes_string(
				apply_filters( 'sarvarov_lazy_load_image_new_atts', $new_atts, $image_src, $image_width, $image_height )
			);

			// <noscript> tag
			if ( $noscript_enable ) {
				$noscript_str = sprintf( '<noscript><img %s loading="lazy" /></noscript>', $this->build_attributes_string(
					apply_filters( 'sarvarov_lazy_load_image_noscript', $image_atts )
				) );
			}

			// output
			$output = apply_filters( 'sarvarov_lazy_load_image_container',
				sprintf( '%1$s<img %2$s />%3$s', $lqip_html, $new_atts_str, $noscript_str ),
				$image_width,
				$image_height,
				$new_atts
			);

			if ( ! $placeholders_disable ) {
				$container_atts_array = array();

				// parent container attributes
				$container_atts = $this->build_attributes_string(
					apply_filters( 'sarvarov_lazy_load_image_container_atts', $container_atts_array )
				);

				$output = apply_filters( 'sarvarov_lazy_load_image_output', sprintf( '<div class="sarvarov-lazy-image sarvarov-not-lazyloaded"%1$s>%2$s</div>', rtrim( ( $style_atts ? $style_atts . ' ' : '' ) . $container_atts ), $output ) );
			}

			if ( $cache_enable ) {
				// save to cache
				$cache_array[ $cache_md5 ] = array(
					'cache_html' => $output,
					'plugin_ver' => $this->plugin_version,
					'plugin_opt' => $options_md5
				);
			}

			return $output;
		}, $content );

		if ( count( $cache_array ) > 0 && $settings['cache_enable'] ) {
			update_post_meta( $settings['post_id'], '_sarvarov_lazy_load_cache', wp_slash( $cache_array ) );
		}

		return $content;
	}

	/**
	 * Make iframes lazy.
	 *
	 * @param string $content HTML content that need to make lazy.
	 * @param array $params Parameters.
	 *
	 * @return string|string[]|null
	 * @since 1.0.0
	 */
	public function lazy_iframe( $content = '', $params = array() ) {
		if ( ! $content ) {
			return $content;
		}

		// get plugin settings
		$settings = $this->plugin_settings;

		$default_params = array(
			'post_id' => get_the_ID()
		);

		$settings = array_merge(
			$default_params,
			$settings,
			$params
		);

		$cache_array = array();

		// capture iframes
		$content = preg_replace_callback( '#<iframe([^>]+)>.*?<\/iframe>#si', function ( $matches ) use ( $settings, &$cache_array ) {
			$settings = apply_filters( 'sarvarov_lazy_load_iframe_settings', $settings, $matches );

			// exclude settings
			extract( $settings, EXTR_SKIP );
			/** @var int $post_id */
			/** @var bool $placeholders_disable */
			/** @var bool $cache_enable */
			/** @var bool $noscript_enable */
			/** @var array $cache_array */
			/** @var string $cache_md5 */
			/** @var string $iframe_average_color_bg_enable */

			// trying to get cache
			if ( is_preview() ) {
				$cache_enable = false;
			}

			if ( $cache_enable ) {
				if ( ! $cache_array ) {
					// prevent caching if post_id are null
					if ( ! $post_id ) {
						$settings['cache_enable'] = $cache_enable = false;
					} else {
						$cache_array = get_post_meta( $post_id, '_sarvarov_lazy_load_cache', true );
					}
				}

				if ( ! $cache_array || ! is_array( $cache_array ) ) {
					$cache_array = array();
				}

				$cache_md5   = substr( md5( $matches[0] ), 0, 12 );
				$options_md5 = substr( md5( json_encode( $settings, JSON_THROW_ON_ERROR ) ), 0, 12 );

				if ( isset( $cache_array[ $cache_md5 ] ) ) {
					if ( $cache_array[ $cache_md5 ] !== false ) {
						if ( isset( $cache_array[ $cache_md5 ]['cache_html'], $cache_array[ $cache_md5 ]['plugin_opt'], $cache_array[ $cache_md5 ]['plugin_ver'] ) && $cache_array[ $cache_md5 ]['plugin_opt'] === $options_md5 && $cache_array[ $cache_md5 ]['plugin_ver'] === $this->plugin_version ) {
							return $cache_array[ $cache_md5 ]['cache_html'];
						}
					} else {
						return $matches[0];
					}
				}
			}

			// matches hook
			$matches = apply_filters( 'sarvarov_lazy_load_iframe_matches', $matches );

			// begin
			$style_atts   = '';
			$noscript_str = '';

			// get iframe attributes
			$iframe_atts = apply_filters( 'sarvarov_lazy_load_iframe_atts', wp_kses_hair( $matches[1], wp_allowed_protocols() ) );

			// placeholder
			if ( ! $placeholders_disable ) {
				$style = array();

				// trying to get URL of the iframe
				if ( empty( $iframe_atts['src']['value'] ) || ! empty( $iframe_atts['data-sarvarov-src'] ) ) {
					if ( $cache_enable ) {
						$cache_array[ $cache_md5 ] = false;
					}

					return $matches[0];
				}

				// trying to get primary color background
				if ( $iframe_average_color_bg_enable ) {
					$yt_rx             = '/^((?:https?:)?\/\/)?((?:www|m)\.)?((?:youtube\.com|youtu.be))(\/(?:[\w\-]+\?v=|embed\/|v\/)?)([\w\-]+)(\S+)?$/';
					$has_match_youtube = preg_match( $yt_rx, $iframe_atts['src']['value'], $yt_matches );

					$vm_rx           = '/(https?:\/\/)?(www\.)?(player\.)?vimeo\.com\/([a-z]*\/)*([‌​0-9]{6,11})[?]?.*/u';
					$has_match_vimeo = preg_match( $vm_rx, $iframe_atts['src']['value'], $vm_matches );

					if ( $has_match_youtube ) {
						$video_id           = $yt_matches[5];
						$image_to_calculate = sprintf( 'https://img.youtube.com/vi/%1$s/default.jpg', $video_id );
					} elseif ( $has_match_vimeo ) {
						$video_id   = $vm_matches[5];
						$video_data = unserialize( $this->file_get_contents( "http://vimeo.com/api/v2/video/$video_id.php" ) );

						if ( is_array( $video_data ) && isset( $video_data[0]['thumbnail_small'] ) ) {
							$image_to_calculate = $video_data[0]['thumbnail_small'];
						}
					}

					if ( $image_to_calculate ) {
						$image_to_calculate_type = pathinfo( $image_to_calculate, PATHINFO_EXTENSION );

						if ( in_array( $image_to_calculate_type, array( 'jpg', 'jpeg' ) ) ) {
							$average_color = apply_filters( 'sarvarov_lazy_load_iframe_average_color_bg', $this->get_average_color( $image_to_calculate ) );

							if ( $average_color ) {
								$style['background-color'] = $average_color;
							}
						}
					}
				}

				// fix if no gutenberg OR CSS of block library is not enqueued OR theme does not support `responsive-embeds`
				if ( ( ! has_blocks() || ! wp_style_is( 'wp-block-library' ) || ! current_theme_supports( 'responsive-embeds' ) ) && apply_filters( 'sarvarov_lazy_load_iframe_responsive_fix', true ) ) {
					$style['position'] = 'relative';
					$style['width']    = $iframe_atts['width']['value'] . 'px';
					$style['height']   = $iframe_atts['height']['value'] . 'px';
				}

				unset( $iframe_atts['width'], $iframe_atts['height'] );

				// style attributes
				$style_atts = apply_filters( 'sarvarov_lazy_load_iframe_style',
					$this->array_to_style_atts( $style )
				);
			}

			// add `lazyload` class, change `src` to `data-sarvarov-src` and other things to make iframes lazy
			$new_atts = $iframe_atts;

			$new_atts['data-sarvarov-src'] = $new_atts['src'];
			unset( $new_atts['src'] );

			if ( ! empty( $new_atts['class']['value'] ) ) {
				$new_atts['class']['value'] = 'sarvarov-lazyitem sarvarov-not-lazyloaded ' . $new_atts['class']['value'];
			} else {
				$new_atts['class'] = array(
					'value' => 'sarvarov-lazyitem sarvarov-not-lazyloaded'
				);
			}

			$new_atts_str = $this->build_attributes_string(
				apply_filters( 'sarvarov_lazy_load_iframe_new_atts', $new_atts )
			);

			// <noscript> tag
			if ( $noscript_enable ) {
				$noscript_str = sprintf( '<noscript><iframe %s loading="lazy"></iframe></noscript>', $this->build_attributes_string(
					apply_filters( 'sarvarov_lazy_load_iframe_noscript', $iframe_atts )
				) );
			}

			// output
			$output = apply_filters( 'sarvarov_lazy_load_iframe_container',
				sprintf( '<iframe %1$s></iframe>%2$s', $new_atts_str, $noscript_str ),
				$new_atts
			);

			if ( ! $placeholders_disable ) {
				$container_atts_array = array();

				// parent container attributes
				$container_atts = $this->build_attributes_string(
					apply_filters( 'sarvarov_lazy_load_iframe_container_atts', $container_atts_array )
				);

				$output = apply_filters( 'sarvarov_lazy_load_iframe_output', sprintf( '<div class="sarvarov-lazy-iframe sarvarov-not-lazyloaded"%1$s>%2$s</div>', rtrim( ( $style_atts ? $style_atts . ' ' : '' ) . $container_atts ), $output ) );
			}

			if ( $cache_enable ) {
				// save to cache
				$cache_array[ $cache_md5 ] = array(
					'cache_html' => $output,
					'plugin_ver' => $this->plugin_version,
					'plugin_opt' => $options_md5
				);
			}

			return $output;
		}, $content );

		if ( count( $cache_array ) > 0 && $settings['cache_enable'] ) {
			update_post_meta( $settings['post_id'], '_sarvarov_lazy_load_cache', wp_slash( $cache_array ) );
		}

		return $content;
	}

	/**
	 * Convert array of attributes to html.
	 *
	 * @param array $attributes Attributes that need to convert.
	 *
	 * @return bool|string
	 * @since 1.0.0
	 */
	private function build_attributes_string( $attributes = array() ) {
		if ( ! is_array( $attributes ) || ! $attributes ) {
			return false;
		}

		$string = array();

		foreach ( $attributes as $name => $attribute ) {
			$value = $attribute['value'];
			if ( '' === $value ) {
				$string[] = sprintf( '%s', $name );
			} else {
				$string[] = sprintf( '%s="%s"', $name, esc_attr( $value ) );
			}
		}

		return implode( ' ', $string );
	}

	/**
	 * Encode image to base64.
	 *
	 * @param string $image_url URL of the image that need to encode.
	 *
	 * @return string|void
	 * @since 1.0.0
	 */
	private function image_to_base64( $image_url ) {
		if ( ! $image_url ) {
			return;
		}

		$image = $this->file_get_contents( $image_url );

		if ( ! $image ) {
			return;
		}

		$base64_image = 'data:image/' . pathinfo( $image_url, PATHINFO_EXTENSION ) . '; base64,' . base64_encode( $image );

		return $base64_image;
	}

	/**
	 * Get average color.
	 *
	 * @param string $image_url URL of the image that color need to calculate.
	 *
	 * @return string|void
	 * @since 1.0.0
	 */
	private function get_average_color( $image_url = '' ) {
		if ( ! $image_url || ! in_array( 'gd', get_loaded_extensions(), true ) ) {
			return;
		}

		$image = @imagecreatefromstring( $this->file_get_contents( $image_url ) );

		if ( ! $image ) {
			return;
		}

		$pixel = imagecreatetruecolor( 1, 1 );

		imagecopyresampled( $pixel, $image, 0, 0, 0, 0, 1, 1, imagesx( $image ), imagesy( $image ) );

		$average_color = strtoupper( dechex( imagecolorat( $pixel, 0, 0 ) ) );

		return '#' . $average_color;
	}

	/**
	 * Output inline CSS.
	 *
	 * @since 1.0.0
	 */
	private function inline_css() {
		$settings = $this->plugin_settings;
		extract( $settings, EXTR_SKIP );

		/** @var bool $enable_on_images */
		/** @var bool $enable_on_iframes */
		/** @var bool $cache_enable */
		/** @var string $placeholder_color */
		/** @var int|string $image_transition_time */
		/** @var int|string $image_lqip_transition_time */
		/** @var int|string $image_transition_delay */
		/** @var int|string $iframe_transition_time */
		/** @var int|string $lqip_blur_radius */
		/** @var int|string $image_lqip_transition_delay */
		/** @var int|string $image_lqip_transition_delay */
		/** @var int|string $iframe_transition_delay */
		/** @var string $image_transition_effect */
		/** @var string $image_lqip_transition_effect */
		/** @var string $iframe_transition_effect */
		/** @var bool $lqip_enable */

		if ( ! $enable_on_images && ! $enable_on_iframes ) {
			return;
		}

		$inline_css = false;

		if ( $cache_enable ) {
			$inline_css = get_transient( 'sarvarov_lazy_load_dynamic_css' );
		}

		if ( false === $inline_css ) {

			$inline_css       = '';
			$inline_css_array = array();

			if ( $placeholder_color && strpos( str_replace( ' ', '', $placeholder_color ), '0)' ) === false ) {
				$inline_css_array['.sarvarov-lazy-image,.sarvarov-lazy-iframe'] = sprintf( 'background-color:%s', $placeholder_color );
			}

			if ( $enable_on_images ) {

				if ( (int) $image_transition_time > 0 ) {
					$inline_css_array['.sarvarov-lazy-image>.sarvarov-lazyitem'] = sprintf( '-webkit-transition:opacity %1$s %2$s%3$s;-o-transition:opacity %1$s %2$s%3$s;transition:opacity %1$s %2$s%3$s;', $this->transition_time_format( $image_transition_time ), $image_transition_effect, $this->transition_time_format( $image_transition_delay, true ) );
				}

				if ( $lqip_enable ) {
					$inline_css_array['.sarvarov-lazy-image>.sarvarov-lazylqip>img'] = sprintf( '-webkit-filter:blur(%1$dpx);filter:blur(%1$dpx);', (int) $lqip_blur_radius );

					if ( (int) $image_lqip_transition_time > 0 ) {
						$inline_css_array['.sarvarov-lazy-image>.sarvarov-lazylqip'] = sprintf( '-webkit-transition:opacity %1$s %2$s%3$s;-o-transition:opacity %1$s %2$s%3$s;transition:opacity %1$s %2$s%3$s;', $this->transition_time_format( $image_lqip_transition_time ), $image_lqip_transition_effect, $this->transition_time_format( $image_lqip_transition_delay, true ) );
					}
				}
			}

			if ( $enable_on_iframes ) {

				if ( (int) $iframe_transition_time > 0 ) {
					$inline_css_array['.sarvarov-lazy-iframe>.sarvarov-lazyitem'] = sprintf( '-webkit-transition:opacity %1$s %2$s%3$s;-o-transition:opacity %1$s %2$s%3$s;transition:opacity %1$s %2$s%3$s;', $this->transition_time_format( $iframe_transition_time ), $iframe_transition_effect, $this->transition_time_format( $iframe_transition_delay, true ) );
				}
			}

			foreach ( apply_filters( 'sarvarov_lazy_load_inline_css_array', $inline_css_array ) as $sel => $param ) {
				$inline_css .= sprintf( '%1$s{%2$s}', $sel, $param );
			}

			if ( $cache_enable ) {
				set_transient( 'sarvarov_lazy_load_dynamic_css', $inline_css );
			}
		}

		return $inline_css;
	}

	/**
	 * Convert milliseconds to seconds for CSS (example: 4000ms to 4s).
	 *
	 * @param int $ms The number of milliseconds.
	 * @param bool $is_delay Is delay property.
	 *
	 * @return bool|string
	 * @since 1.0.0
	 */
	private function transition_time_format( $ms = 0, $is_delay = false ) {
		$ms = (int) $ms;

		if ( $ms <= 0 && $is_delay ) {
			return false;
		} elseif ( $ms >= 1000 ) {
			$return = ( $ms / 1000 ) . 's';
		} else {
			$return = $ms . 'ms';
		}

		return ( $is_delay ? ' ' : '' ) . $return;
	}

	/**
	 * Output inline JavaScript.
	 *
	 * @since 1.0.0
	 */
	private function inline_js() {
		$settings = $this->plugin_settings;
		extract( $settings, EXTR_SKIP );
		/** @var bool $enable_on_images */
		/** @var bool $enable_on_iframes */
		/** @var bool $cache_enable */
		/** @var bool $preload_enable */
		/** @var string|int $expand_value */
		/** @var string $custom_settings */

		if ( ! $enable_on_images && ! $enable_on_iframes ) {
			return;
		}

		$inline_js = false;

		if ( $cache_enable ) {
			$inline_js = get_transient( 'sarvarov_lazy_load_dynamic_js' );
		}

		if ( false === $inline_js ) {

			$inline_js       = '';
			$inline_js_array = array();

			if ( $preload_enable ) {
				$inline_js_array['preloadAfterLoad'] = 'true';
			}

			if ( $expand_value !== '' ) {
				if ( (int) $expand_value === 0 ) {
					$expand_value = 1;
				}

				$inline_js_array['expand'] = (int) $expand_value;
			}

			if ( $inline_js_array || $custom_settings ) {
				if ( $inline_js_array ) {
					foreach ( apply_filters( 'sarvarov_lazy_load_inline_js_array', $inline_js_array ) as $param => $value ) {
						$inline_js .= sprintf( '%1$swindow.lazySizesConfig.%2$s = %3$s;', PHP_EOL, $param, $value );
					}
				}

				if ( $custom_settings ) {
					$inline_js .= $custom_settings;
				}

				if ( $inline_js ) {
					$inline_js = "window.lazySizesConfig = window.lazySizesConfig || {}; $inline_js";
				}
			}

			if ( $cache_enable ) {
				set_transient( 'sarvarov_lazy_load_dynamic_js', $inline_js );
			}
		}

		return $inline_js;
	}

	/**
	 * Convert array to CSS.
	 *
	 * @param array $css_array Array of CSS parameters.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	private function array_to_style_atts( $css_array = array() ) {
		$style_atts = ' style="';

		foreach ( $css_array as $attr => $value ) {
			$style_atts .= $attr . ': ' . $value . '; ';
		}

		$style_atts = rtrim( $style_atts ) . '"';

		return $style_atts;
	}

	/**
	 * Retrieve remote image dimensions.
	 *
	 * @param string $url URL of the image.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	private function getimagesize( $url = '' ) {
		$return_array = array(
			null,
			null
		);

		if ( ini_get( 'allow_url_fopen' ) ) {
			@list( $return_array[0], $return_array[1] ) = getimagesize( $url );
		} elseif ( in_array( 'curl', get_loaded_extensions(), true ) &&
		           in_array( 'gd', get_loaded_extensions(), true ) ) {

			// init curl and set base settings
			$curl = @curl_init( $url );
			@curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
			@curl_setopt( $curl, CURLOPT_HEADER, 0 );
			@curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, 1 );
			$data        = @curl_exec( $curl );
			$http_status = @curl_getinfo( $curl, CURLINFO_HTTP_CODE );
			@curl_close( $curl );

			if ( $data !== false && (int) $http_status >= 200 && (int) $http_status < 400 ) {
				$image = @imagecreatefromstring( $data );
				@list( $return_array[0], $return_array[1] ) = array( imagesx( $image ), imagesy( $image ) );

				imagedestroy( $image );
			}
		}

		return $return_array;
	}

	/**
	 * Retrieve URL content.
	 *
	 * @param string $url URL of the page what need to request.
	 *
	 * @return string
	 * @since 1.0.7
	 */
	private function file_get_contents( $url = '' ): string {
		$return_content = '';

		if ( ini_get( 'allow_url_fopen' ) ) {
			$return_content = @file_get_contents( $url );
		} elseif ( in_array( 'curl', get_loaded_extensions(), true ) ) {
			// init curl and set base settings
			$curl = @curl_init( $url );
			@curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
			@curl_setopt( $curl, CURLOPT_HEADER, 0 );
			@curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, 1 );
			$data        = @curl_exec( $curl );
			$http_status = @curl_getinfo( $curl, CURLINFO_HTTP_CODE );
			@curl_close( $curl );

			if ( $data !== false && (int) $http_status >= 200 && (int) $http_status < 400 ) {
				$return_content = $data;
			}
		}

		return $return_content;
	}
}
