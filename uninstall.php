<?php
/**
 * Rext AI Uninstall
 *
 * Fires when the plugin is uninstalled.
 * Removes all plugin data from the database.
 *
 * @package Rext_AI
 * @since 1.0.0
 */

// Exit if not called by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Clean up all plugin data.
 */
function rext_ai_uninstall() {
	global $wpdb;

	// Delete plugin options.
	$options = array(
		'rext_ai_api_key',
		'rext_ai_enabled',
		'rext_ai_permissions',
		'rext_ai_last_connected',
	);

	foreach ( $options as $option ) {
		delete_option( $option );
	}

	// Drop custom database tables.
	$tables = array(
		$wpdb->prefix . 'rext_ai_logs',
		$wpdb->prefix . 'rext_ai_posts',
	);

	foreach ( $tables as $table ) {
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $table ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
	}

	// Delete all post meta with _rext_ai_ prefix.
	$wpdb->query( 'DELETE FROM ' . $wpdb->postmeta . " WHERE meta_key LIKE '_rext_ai_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	// Clear any transients.
	$wpdb->query( 'DELETE FROM ' . $wpdb->options . " WHERE option_name LIKE '_transient_rext_ai_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( 'DELETE FROM ' . $wpdb->options . " WHERE option_name LIKE '_transient_timeout_rext_ai_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	// For multisite, clean up network-wide if needed.
	if ( is_multisite() ) {
		$sites = get_sites();
		foreach ( $sites as $site ) {
			switch_to_blog( $site->blog_id );

			// Delete options for this site.
			foreach ( $options as $option ) {
				delete_option( $option );
			}

			// Drop tables for this site.
			$site_tables = array(
				$wpdb->prefix . 'rext_ai_logs',
				$wpdb->prefix . 'rext_ai_posts',
			);

			foreach ( $site_tables as $table ) {
				$wpdb->query( 'DROP TABLE IF EXISTS ' . $table ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
			}

			// Delete post meta.
			$wpdb->query( 'DELETE FROM ' . $wpdb->postmeta . " WHERE meta_key LIKE '_rext_ai_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			restore_current_blog();
		}
	}
}

// Run uninstall.
rext_ai_uninstall();
