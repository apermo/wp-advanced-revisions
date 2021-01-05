<?php
/**
 * Plugin Name:       WP Advanced Revisions
 * Description:       Allow to maintain the revisions for each post type via WordPress options page
 * Version:           0.1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Christoph Daum & Joshua Schmidtke
 * Author URI:        TBD
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 404 Not Found' );
	exit( 'You shall not pass' );
}


class WP_Advanced_Revisions {

	/**
	 * Plugin Options
	 *
	 * @var array
	 * @since 0.1.0
	 */
	private static $options = [];

	/**
	 * Init function, called on 'plugins_loaded' to be early enough to define the constant 'WP_POST_REVISIONS'
	 *
	 * @since 0.1.0
	 */
	public static function init(): void {
		self::$options = get_option( 'wp_advanced_revisions' );

		add_action( 'init', [ __CLASS__, 'init_revisions' ], 99 );

		if ( is_admin() ) {
			define( 'WP_POST_REVISIONS_DEFINED', defined( 'WP_POST_REVISIONS' ) );
			require_once 'inc/class.options-pages.php';

			add_action( 'admin_menu', [ 'WP_Advanced_Revisions_Options_Page', 'add_admin_menu' ] );
			add_action( 'admin_init', [ 'WP_Advanced_Revisions_Options_Page', 'init_settings' ] );
		}

		add_filter( 'wp_revisions_to_keep', [ __CLASS__, 'revisions_to_keep_per_post_type' ], 10, 2 );

		if ( ! defined( 'WP_POST_REVISIONS' ) && isset( self::$options['global'] ) ) {
			define( 'WP_POST_REVISIONS', (int) self::$options['global'] );
		}
	}


	/**
	 * Called on 'init' with priority 99, hopefully late enough to be able to overwrite any settings for revisions
	 *
	 * @since 0.1.0
	 */
	public static function init_revisions(): void {
		if ( strpos( $_SERVER['REQUEST_URI'], '/wp-admin/options-general.php?page=wp-advanced-revisions' ) !== false ) {
			return;
		}

		foreach ( self::$options as $key => $settings ) {
			if ( $key === 'global' ) {
				continue;
			}

			if ( isset( $settings['status'] ) ) {
				switch ( $settings['status'] ) {
					case 'on':
						add_post_type_support( $key, 'revisions' );
						break;
					case 'off':
						remove_post_type_support( $key, 'revisions' );
						break;
				}
			}
		}
	}

	/**
	 * Called by filter 'wp_revisions_to_keep' to limit the revisions.
	 *
	 * @param int     $num  Number of revisions to keep.
	 * @param WP_Post $post The WordPress post object.
	 *
	 * @return int
	 *
	 * @since 0.1.0
	 */
	public static function revisions_to_keep_per_post_type( int $num, WP_Post $post ): int {
		if ( ! post_type_supports( $post->post_type, 'revisions' ) ) {
			return $num;
		}

		// If there is a limited revision for the post type, return that, otherwise return the original value that was being sent from the filter.
		return self::$options[ $post->post_type ]['count'] ?? $num;
	}
}

add_action( 'plugins_loaded', [ 'WP_Advanced_Revisions', 'init' ] );
