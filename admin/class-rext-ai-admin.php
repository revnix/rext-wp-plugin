<?php
/**
 * Rext AI Admin Class
 *
 * Handles admin dashboard, settings, and AJAX operations.
 *
 * @package Rext_AI
 * @since 1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Rext_AI_Admin
 *
 * Manages admin interface for the Rext AI plugin.
 */
class Rext_AI_Admin {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Admin menu.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

		// Admin scripts and styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_rext_ai_regenerate_key', array( $this, 'ajax_regenerate_key' ) );
		add_action( 'wp_ajax_rext_ai_export_logs', array( $this, 'ajax_export_logs' ) );
		add_action( 'wp_ajax_rext_ai_clear_logs', array( $this, 'ajax_clear_logs' ) );

		// Settings registration.
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Admin notices.
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	/**
	 * Add admin menu pages
	 */
	public function add_admin_menu() {
		// Main menu.
		add_menu_page(
			__( 'Rext AI', 'rext-ai-publisher' ),
			__( 'Rext AI', 'rext-ai-publisher' ),
			'manage_options',
			'rext-ai-publisher',
			array( $this, 'render_settings_page' ),
			'dashicons-cloud-upload',
			80
		);

		// Settings submenu (same as main).
		add_submenu_page(
			'rext-ai-publisher',
			__( 'Settings', 'rext-ai-publisher' ),
			__( 'Settings', 'rext-ai-publisher' ),
			'manage_options',
			'rext-ai-publisher',
			array( $this, 'render_settings_page' )
		);

		// Activity Log submenu.
		add_submenu_page(
			'rext-ai-publisher',
			__( 'Activity Log', 'rext-ai-publisher' ),
			__( 'Activity Log', 'rext-ai-publisher' ),
			'manage_options',
			'rext-ai-logs',
			array( $this, 'render_logs_page' )
		);

		// Published Content submenu.
		add_submenu_page(
			'rext-ai-publisher',
			__( 'Published Content', 'rext-ai-publisher' ),
			__( 'Published Content', 'rext-ai-publisher' ),
			'manage_options',
			'rext-ai-content',
			array( $this, 'render_content_page' )
		);
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		// Only load on our pages.
		if ( strpos( $hook, 'rext-ai-publisher' ) === false ) {
			return;
		}

		// Enqueue styles.
		wp_enqueue_style(
			'rext-ai-admin',
			REXT_AI_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			REXT_AI_VERSION
		);

		// Enqueue scripts.
		wp_enqueue_script(
			'rext-ai-admin',
			REXT_AI_PLUGIN_URL . 'admin/js/admin.js',
			array( 'jquery' ),
			REXT_AI_VERSION,
			true
		);

		// Localize script.
		wp_localize_script(
			'rext-ai-admin',
			'rextAiAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'rext_ai_admin' ),
				'strings' => array(
					'confirmRegenerate' => __( 'Are you sure you want to regenerate the API key? This will invalidate the current key and disconnect any active integrations.', 'rext-ai-publisher' ),
					'regenerating'      => __( 'Regenerating...', 'rext-ai-publisher' ),
					'regenerateSuccess' => __( 'API key regenerated successfully!', 'rext-ai-publisher' ),
					'regenerateError'   => __( 'Failed to regenerate API key.', 'rext-ai-publisher' ),
					'copied'            => __( 'Copied to clipboard!', 'rext-ai-publisher' ),
					'copyFailed'        => __( 'Failed to copy.', 'rext-ai-publisher' ),
					'exporting'         => __( 'Exporting...', 'rext-ai-publisher' ),
					'confirmClearLogs'  => __( 'Are you sure you want to clear all logs? This cannot be undone.', 'rext-ai-publisher' ),
				),
			)
		);
	}

	/**
	 * Register plugin settings
	 */
	public function register_settings() {
		// Main settings.
		register_setting(
			'rext_ai_settings',
			'rext_ai_enabled',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => true,
			)
		);

		register_setting(
			'rext_ai_settings',
			'rext_ai_permissions',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_permissions' ),
				'default'           => array(
					'create_posts'      => true,
					'edit_posts'        => true,
					'delete_posts'      => false,
					'upload_media'      => true,
					'manage_categories' => true,
					'manage_tags'       => true,
				),
			)
		);
	}

	/**
	 * Sanitize permissions array
	 *
	 * @param array $input Input array.
	 * @return array Sanitized array.
	 */
	public function sanitize_permissions( $input ) {
		$defaults = array(
			'create_posts'      => false,
			'edit_posts'        => false,
			'delete_posts'      => false,
			'upload_media'      => false,
			'manage_categories' => false,
			'manage_tags'       => false,
		);

		$sanitized = array();
		foreach ( $defaults as $key => $default ) {
			$sanitized[ $key ] = isset( $input[ $key ] ) && $input[ $key ] ? true : false;
		}

		return $sanitized;
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle form submission.
		if ( isset( $_POST['submit'] ) && check_admin_referer( 'rext_ai_settings_nonce', 'rext_ai_nonce' ) ) {
			$this->save_settings();
		}

		include REXT_AI_PLUGIN_DIR . 'admin/views/settings.php';
	}

	/**
	 * Render logs page
	 */
	public function render_logs_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include REXT_AI_PLUGIN_DIR . 'admin/views/logs.php';
	}

	/**
	 * Render content page
	 */
	public function render_content_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include REXT_AI_PLUGIN_DIR . 'admin/views/content.php';
	}

	/**
	 * Save settings
	 */
	private function save_settings() {
		// Enabled toggle.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$enabled = isset( $_POST['rext_ai_enabled'] );
		update_option( 'rext_ai_enabled', $enabled );

		// Permissions - wp_unslash before sanitization.
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- sanitized in sanitize_permissions() and nonce checked in caller.
		$raw_permissions = isset( $_POST['rext_ai_permissions'] ) ? wp_unslash( $_POST['rext_ai_permissions'] ) : array();
		update_option( 'rext_ai_permissions', $this->sanitize_permissions( $raw_permissions ) );

		Rext_AI_Logger::info( 'Settings updated' );

		add_settings_error( 'rext_ai_messages', 'rext_ai_message', __( 'Settings saved.', 'rext-ai-publisher' ), 'updated' );
	}

	/**
	 * AJAX: Regenerate API key
	 */
	public function ajax_regenerate_key() {
		check_ajax_referer( 'rext_ai_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'rext-ai-publisher' ) ) );
		}

		$auth    = new Rext_AI_Auth();
		$new_key = $auth->generate_api_key();

		wp_send_json_success(
			array(
				'key'        => $new_key,
				'masked_key' => $auth->get_masked_api_key(),
				'message'    => __( 'API key regenerated successfully.', 'rext-ai-publisher' ),
			)
		);
	}

	/**
	 * AJAX: Export logs
	 */
	public function ajax_export_logs() {
		check_ajax_referer( 'rext_ai_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'rext-ai-publisher' ) ) );
		}

		$level = isset( $_POST['level'] ) ? sanitize_text_field( wp_unslash( $_POST['level'] ) ) : '';
		$csv   = Rext_AI_Logger::export_to_csv( array( 'level' => $level ) );

		wp_send_json_success(
			array(
				'csv'      => $csv,
				'filename' => 'rext-ai-logs-' . gmdate( 'Y-m-d' ) . '.csv',
			)
		);
	}

	/**
	 * AJAX: Clear logs
	 */
	public function ajax_clear_logs() {
		check_ajax_referer( 'rext_ai_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'rext-ai-publisher' ) ) );
		}

		Rext_AI_Logger::clear_all_logs();
		Rext_AI_Logger::info( 'Logs cleared by admin' );

		wp_send_json_success( array( 'message' => __( 'Logs cleared successfully.', 'rext-ai-publisher' ) ) );
	}

	/**
	 * Display admin notices
	 */
	public function admin_notices() {
		// Show settings errors.
		settings_errors( 'rext_ai_messages' );

		// Check if on our pages.
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'rext-ai-publisher' ) === false ) {
			return;
		}

		// Check if HTTPS.
		if ( ! is_ssl() ) {
			echo '<div class="notice notice-warning"><p>';
			echo '<strong>' . esc_html__( 'Rext AI:', 'rext-ai-publisher' ) . '</strong> ';
			esc_html_e( 'For security, we recommend using HTTPS for API communication.', 'rext-ai-publisher' );
			echo '</p></div>';
		}
	}

	/**
	 * Get connection status
	 *
	 * @return array Connection status info.
	 */
	public function get_connection_status() {
		$last_connected = get_option( 'rext_ai_last_connected' );
		$enabled        = get_option( 'rext_ai_enabled', true );

		$status = array(
			'enabled'        => $enabled,
			'last_connected' => $last_connected,
			'connected'      => false,
		);

		if ( $last_connected ) {
			$diff = time() - strtotime( $last_connected );
			// Consider connected if last connection was within 24 hours.
			$status['connected']            = $diff < 86400;
			$status['last_connected_human'] = human_time_diff( strtotime( $last_connected ), time() ) . ' ' . __( 'ago', 'rext-ai-publisher' );
		}

		return $status;
	}

	/**
	 * Get plugin statistics
	 *
	 * @return array Plugin statistics.
	 */
	public function get_stats() {
		global $wpdb;
		$posts_table = $wpdb->prefix . 'rext_ai_posts';

		$stats = array(
			'total_posts'     => 0,
			'published_posts' => 0,
			'draft_posts'     => 0,
		);

		// Try to get stats from cache.
		$cached_stats = wp_cache_get( 'rext_ai_dashboard_stats', 'rext_ai' );

		if ( false === $cached_stats ) {
			// Check if table exists.
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $posts_table ) ) === $posts_table ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$stats['total_posts'] = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $posts_table ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

				$stats['published_posts'] = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . $posts_table . ' WHERE status = %s', 'publish' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

				$stats['draft_posts'] = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . $posts_table . ' WHERE status = %s', 'draft' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			}
			wp_cache_set( 'rext_ai_dashboard_stats', $stats, 'rext_ai', 300 );
		} else {
			$stats = $cached_stats;
		}

		// Add log stats.
		$log_stats           = Rext_AI_Logger::get_stats();
		$stats['total_logs'] = $log_stats['total'];
		$stats['logs_today'] = $log_stats['today'];

		return $stats;
	}
}
