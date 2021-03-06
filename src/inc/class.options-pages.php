<?php

if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 404 Not Found' );
	exit( 'You shall not pass' );
}

class WP_Advanced_Revisions_Options_Page {
	/**
	 * Init the Admin menu entry.
	 *
	 * @since 0.1.0
	 */
	public static function add_admin_menu(): void {
		add_options_page(
			esc_html__( 'WP Advanced Revisions', 'wp-advanced-revisions' ),
			esc_html__( 'WP Advanced Revisions', 'wp-advanced-revisions' ),
			'manage_options',
			'wp-advanced-revisions',
			[ __CLASS__, 'page_layout' ]
		);
	}

	/**
	 * Init the options page.
	 *
	 * @since 0.1.0
	 */
	public static function init_settings(): void {
		$post_types = get_post_types();
		$options    = get_option( 'wp_advanced_revisions' );

		register_setting(
			'wp_advanced_revisions_group',
			'wp_advanced_revisions',
			[
				'sanitize_callback' => [ __CLASS__, 'sanitize_callback' ],
			]
		);

		add_settings_section(
			'wp_advanced_revisions_global_settings',
			__( 'Revisions', 'wp-advanced-revisions' ),
			false,
			'wp_advanced_revisions'
		);

		$args = [
			'value' => $options['global'],
		];

		add_settings_field(
			'global_settings',
			__( 'Global number of revisions', 'wp-advanced-revisions' ),
			[ __CLASS__, 'render_global_field' ],
			'wp_advanced_revisions',
			'wp_advanced_revisions_global_settings',
			$args
		);

		add_settings_section(
			'wp_advanced_revisions_post_type_settings',
			__( 'Post type Revisions', 'wp-advanced-revisions' ),
			false,
			'wp_advanced_revisions'
		);


		/**
		 * Filters the the post types that shall not be be editable on the options page.
		 *
		 * @param string[] $post_types Array Post type IDs that will not be visible on the options page.
		 *
		 * @since 0.1.0
		 */
		$no_revisions_for = apply_filters(
			'wp_advanced_revisions_no_revisions',
			[
				'attachment',
				'revision',
				'nav_menu_item',
				'customize_changeset',
				'oembed_cache',
				'user_request',
			]
		);

		foreach ( $post_types as $post_type ) {
			// If the current post type is blocked for revisions, skip and go on
			if ( in_array( $post_type, $no_revisions_for, true ) ) {
				continue;
			}

			$post_type_object = get_post_type_object( $post_type );

			if ( ! $post_type_object instanceof WP_Post_Type ) {
				continue;
			}

			$args = [
				'key'    => $post_type,
 				'value'  => $options[ $post_type ]['count'] ?? '',
				'status' => $options[ $post_type ]['status'] ?? '',
			];

			add_settings_field(
				$post_type,
				$post_type_object->label,
				[ __CLASS__, 'render_post_type_field' ],
				'wp_advanced_revisions',
				'wp_advanced_revisions_post_type_settings',
				$args
			);
		}

	}

	/**
	 * Callback to display the options page
	 *
	 * @since 0.1.0
	 */
	public static function page_layout(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-advanced-revisions' ) );
		}

		echo '<div class="wrap">' . "\n";
		echo '	<h1>' . get_admin_page_title() . '</h1>' . "\n";
		echo '	<form action="options.php" method="post">' . "\n";

		settings_fields( 'wp_advanced_revisions_group' );
		do_settings_sections( 'wp_advanced_revisions' );
		submit_button();

		echo '	</form>' . "\n";
		echo '</div>' . "\n";
	}

	/**
	 * Callback to display the settings fields to edit the settings for 'WP_POST_REVISIONS'
	 *
	 * @param array $args Arguments for the input fields
	 *
	 * @since 0.1.0
	 */
	public static function render_global_field( $args ): void {
		if ( WP_POST_REVISIONS_DEFINED ) {
			if ( WP_POST_REVISIONS === true || WP_POST_REVISIONS === -1 ) {
				$revisions = __( 'Enabled', 'wp-advanced-revisions' );
			} elseif ( WP_POST_REVISIONS === 0 ) {
				$revisions = __( 'Disabled', 'wp-advanced-revisions' );
			} else {
				$revisions = (int) WP_POST_REVISIONS;

				$revisions = sprintf(
					/* translators: %d is the number of revisions. */
					_n( '%d revision allowed', '%d revisions allowed', $revisions, 'wp-advanced-revisions' ),
					$revisions
				);
			}

			echo '<p class="description">' . __( '"WP_POST_REVISIONS" is defined.', 'wp-advanced-revisions' ) . '<br>' . $revisions . '</p>';
			return;
		}


		echo '<input type="number" name="wp_advanced_revisions[global]" class="regular-text global_revisions" value="' . esc_attr( $args['value'] ) . '">';
		echo '<p class="description">' . __( 'Global number of revisions. Will set "WP_POST_REVISIONS", -1 or empty for infinite, 0 for disable, any integer for any other number', 'wp-advanced-revisions' ) . '</p>';
	}

	/**
	 * Render the input fields for a post type.
	 *
	 * @param array $args Arguments for the input fields.
	 *
	 * @since 0.1.0
	 */
	public static function render_post_type_field( $args ): void {
		echo '<input type="number" name="wp_advanced_revisions[' . esc_attr( $args['key'] ) . '][count]" class="regular-text bla_field" value="' . esc_attr( $args['value'] ) . '">';
		echo '<p class="description">' . __( 'Number of revisions, -1 for infinite, 0 for disable, leave empty for default', 'wp-advanced-revisions' ) . '</p>';

		$status = post_type_supports( $args['key'], 'revisions' ) ? __( 'Enabled', 'wp-advanced-revisions' ) : __( 'Disabled', 'wp-advanced-revisions' );

		$options = [
			'on'  => __( 'Enabled', 'wp-advanced-revisions' ),
			''    => sprintf( __( '%s (Default)', 'wp-advanced-revisions' ), $status ),
			'off' => __( 'Disabled', 'wp-advanced-revisions' ),
		];

		if ( post_type_supports( $args['key'], 'revisions' ) ) {
			unset( $options['on'] );
		} else {
			unset( $options['off'] );
		}

		foreach ( $options as $value => $label ) {
			echo '<input type="radio" name="wp_advanced_revisions[' . esc_attr( $args['key'] ) . '][status]" class="status_field" value="' . esc_attr( $value ) . '" ' . checked( $args['status'], $value, false ) . '> ' . $label . '<br>';
		}

		echo '<p class="description">' . __( 'Overwrite the default setting for revisions of this post type', 'wp-advanced-revisions' ) . '</p>';
	}

	/**
	 * Sanitize the data from the options page.
	 *
	 * @param array $input Input of the options page
	 *
	 * @return array
	 *
	 * @since 0.1.0
	 */
	public static function sanitize_callback( $input ) {
		//TODO add validation
		//var_dump( $input );
		return $input;
	}
}
