<?php

namespace Apermo\WP_Advanced_Revisions;

if ( ! defined( 'ABSPATH' ) ) {
	/**
	 * Not in WordPress, bail out.
	 */
	header( 'Status: 404 Not found' );
	header( 'HTTP/1.1 404 Not found' );
	exit();
}

/**
 * Class Meta_Box
 *
 * @package Apermo\WP_Advanced_Revisions
 *
 * @since 0.2.0
 */
class Meta_Box {


	/**
	 * Init the metabox
	 *
	 * @since 0.2.0
	 */
	public static function init_metabox(): void {
		add_action( 'add_meta_boxes', [ __CLASS__, 'add_metabox' ] );
		add_action( 'save_post', [ __CLASS__, 'save_metabox' ], 10, 2 );
	}

	/**
	 * Add the metabox to all posts that support revisions
	 */
	public static function add_metabox(): void {
		$post_types = get_post_types();

		foreach ( $post_types as $key => $post_type ) {
			// Remove post types that don't support revisions.
			if ( post_type_supports( $post_type, 'revisions' ) !== true ) {
				unset( $post_types[ $key ] );
			}
		}

		add_meta_box(
			'advanced_revisions',
			__( 'WP Advanced Revisions', 'wp-advanced-revisions' ),
			[ __CLASS__, 'render_metabox' ],
			$post_types,
			'side',
			'default'
		);
	}

	/**
	 * Render callback for the the metabox
	 *
	 * @param \WP_Post $post The WordPress post object.
	 *
	 * @since 0.2.0
	 */
	public static function render_metabox( \WP_Post $post ): void {
		if ( post_type_supports( $post->post_type, 'revisions' ) !== true ) {
			return;
		}

		wp_nonce_field( 'wp_advanced_revisions_nonce_action', 'wp_advanced_revisions_nonce' );

		$wp_advanced_revisions_custom_count = get_post_meta( $post->ID, '_wp_advanced_revisions_custom_count', true );

		$post->called_from_wpar_metabox = true;
		$default_count = wp_revisions_to_keep( $post );
		$post_type = get_post_type_object( $post->post_type );

		if ( ! $post_type instanceof \WP_Post_Type ) {
			return;
		}
		?>
		<table class="form-table">
			<tr>
				<th><label for="wp_advanced_revisions_custom_count" class="wp_advanced_revisions_custom_count_label"><?php esc_html_e( 'Number of revisions', 'wp-advanced-revisions' ); ?></label></th>
				<td>
					<input type="number" id="wp_advanced_revisions_custom_count" name="wp_advanced_revisions_custom_count" class="wp_advanced_revisions_custom_count_field" value="<?php echo esc_attr( $wp_advanced_revisions_custom_count ); ?>" placeholder="<?php echo esc_attr( $default_count ); ?>">
					<p class="description"><?php esc_html_e( 'Number of revisions, -1 for infinite, 0 for disable, leave empty for default', 'wp-advanced-revisions' ); ?><br><br>
					<?php echo esc_html( sprintf( _x( 'Current default for %1$s: %2$d', '%1$s contains the post type label, %2$d the default number of revisions', 'wp-advanced-revisions' ), $post_type->label, $default_count ) ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save the content of the metabox.
	 *
	 * @param int $post_id The WordPress post id.
	 * @param \WP_Post $post The WordPress post object.
	 *
	 * @since 0.2.0
	 */
	public function save_metabox( int $post_id, \WP_Post $post ): void {
		if ( post_type_supports( $post->post_type, 'revisions' ) !== true ) {
			return;
		}

		if ( ! filter_input( INPUT_GET, 'wp_advanced_revisions_nonce' ) ) {
			return;
		}

		if ( ! wp_verify_nonce( filter_input( INPUT_GET, 'wp_advanced_revisions_nonce' ), 'wp_advanced_revisions_nonce_action' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		$updated_count = filter_input( INPUT_POST, 'wp_advanced_revisions_custom_count', FILTER_SANITIZE_NUMBER_INT );
		$updated_count = $updated_count !== '' ? $updated_count : null;

		if ( $updated_count !== null ) {
			update_post_meta( $post_id, '_wp_advanced_revisions_custom_count', $updated_count );
		} else {
			delete_post_meta( $post_id, '_wp_advanced_revisions_custom_count' );
		}
	}
}
