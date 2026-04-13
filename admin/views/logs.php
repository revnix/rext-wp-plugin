<?php
/**
 * Activity Logs Page View
 *
 * @package Rext_AI
 * @since 1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get filter parameters.
$rext_ai_current_level  = isset( $_GET['level'] ) ? sanitize_text_field( wp_unslash( $_GET['level'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$rext_ai_current_action = isset( $_GET['action_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['action_filter'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$rext_ai_current_page   = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$rext_ai_per_page       = 20;

// Get logs.
$rext_ai_logs = Rext_AI_Logger::get_logs(
	array(
		'per_page' => $rext_ai_per_page,
		'page'     => $rext_ai_current_page,
		'level'    => $rext_ai_current_level,
		'action'   => $rext_ai_current_action,
	)
);

$rext_ai_total_logs = Rext_AI_Logger::get_logs_count(
	array(
		'level'  => $rext_ai_current_level,
		'action' => $rext_ai_current_action,
	)
);

$rext_ai_total_pages = ceil( $rext_ai_total_logs / $rext_ai_per_page );

// Get unique actions for filter.
$rext_ai_unique_actions = Rext_AI_Logger::get_unique_actions();

// Log level colors.
$rext_ai_level_colors = array(
	'debug'   => 'rext-ai-level--debug',
	'info'    => 'rext-ai-level--info',
	'warning' => 'rext-ai-level--warning',
	'error'   => 'rext-ai-level--error',
);
?>

<div class="wrap rext-ai-admin">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<!-- Filters and Actions -->
	<div class="rext-ai-logs-toolbar">
		<form method="get" class="rext-ai-logs-filters">
			<input type="hidden" name="page" value="rext-ai-logs">

			<select name="level" class="rext-ai-filter-select">
				<option value=""><?php esc_html_e( 'All Levels', 'rext-ai-publisher' ); ?></option>
				<option value="debug" <?php selected( $rext_ai_current_level, 'debug' ); ?>><?php esc_html_e( 'Debug', 'rext-ai-publisher' ); ?></option>
				<option value="info" <?php selected( $rext_ai_current_level, 'info' ); ?>><?php esc_html_e( 'Info', 'rext-ai-publisher' ); ?></option>
				<option value="warning" <?php selected( $rext_ai_current_level, 'warning' ); ?>><?php esc_html_e( 'Warning', 'rext-ai-publisher' ); ?></option>
				<option value="error" <?php selected( $rext_ai_current_level, 'error' ); ?>><?php esc_html_e( 'Error', 'rext-ai-publisher' ); ?></option>
			</select>

			<select name="action_filter" class="rext-ai-filter-select">
				<option value=""><?php esc_html_e( 'All Actions', 'rext-ai-publisher' ); ?></option>
				<?php foreach ( $rext_ai_unique_actions as $rext_ai_action ) : ?>
					<option value="<?php echo esc_attr( $rext_ai_action ); ?>" <?php selected( $rext_ai_current_action, $rext_ai_action ); ?>>
						<?php echo esc_html( $rext_ai_action ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'rext-ai-publisher' ); ?></button>

			<?php if ( $rext_ai_current_level || $rext_ai_current_action ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=rext-ai-logs' ) ); ?>" class="button">
					<?php esc_html_e( 'Clear Filters', 'rext-ai-publisher' ); ?>
				</a>
			<?php endif; ?>
		</form>

		<div class="rext-ai-logs-actions">
			<button type="button" class="button" id="rext-ai-export-logs">
				<span class="dashicons dashicons-download"></span>
				<?php esc_html_e( 'Export CSV', 'rext-ai-publisher' ); ?>
			</button>
			<button type="button" class="button button-secondary" id="rext-ai-clear-logs">
				<span class="dashicons dashicons-trash"></span>
				<?php esc_html_e( 'Clear Logs', 'rext-ai-publisher' ); ?>
			</button>
		</div>
	</div>

	<!-- Logs Table -->
	<div class="rext-ai-card">
		<?php if ( empty( $rext_ai_logs ) ) : ?>
			<div class="rext-ai-empty-state">
				<span class="dashicons dashicons-format-aside"></span>
				<p><?php esc_html_e( 'No activity logs found.', 'rext-ai-publisher' ); ?></p>
			</div>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped rext-ai-logs-table">
				<thead>
					<tr>
						<th class="column-time" style="width: 150px;"><?php esc_html_e( 'Time', 'rext-ai-publisher' ); ?></th>
						<th class="column-level" style="width: 80px;"><?php esc_html_e( 'Level', 'rext-ai-publisher' ); ?></th>
						<th class="column-action" style="width: 150px;"><?php esc_html_e( 'Action', 'rext-ai-publisher' ); ?></th>
						<th class="column-message"><?php esc_html_e( 'Message', 'rext-ai-publisher' ); ?></th>
						<th class="column-ip" style="width: 120px;"><?php esc_html_e( 'IP Address', 'rext-ai-publisher' ); ?></th>
						<th class="column-details" style="width: 80px;"><?php esc_html_e( 'Details', 'rext-ai-publisher' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rext_ai_logs as $rext_ai_log ) : ?>
						<tr class="rext-ai-log-row">
							<td class="column-time">
								<span class="rext-ai-log-time" title="<?php echo esc_attr( $rext_ai_log->created_at ); ?>">
									<?php echo esc_html( human_time_diff( strtotime( $rext_ai_log->created_at ), time() ) ); ?>
									<?php esc_html_e( 'ago', 'rext-ai-publisher' ); ?>
								</span>
							</td>
							<td class="column-level">
								<span class="rext-ai-level-badge <?php echo esc_attr( $rext_ai_level_colors[ $rext_ai_log->level ] ?? '' ); ?>">
									<?php echo esc_html( ucfirst( $rext_ai_log->level ) ); ?>
								</span>
							</td>
							<td class="column-action">
								<code class="rext-ai-action-code"><?php echo esc_html( $rext_ai_log->action ); ?></code>
							</td>
							<td class="column-message">
								<?php echo esc_html( $rext_ai_log->message ); ?>
							</td>
							<td class="column-ip">
								<code><?php echo esc_html( $rext_ai_log->ip_address ? $rext_ai_log->ip_address : '-' ); ?></code>
							</td>
							<td class="column-details">
								<?php if ( ! empty( $rext_ai_log->data ) ) : ?>
									<button type="button"
											class="button button-small rext-ai-toggle-data"
											data-log-id="<?php echo esc_attr( $rext_ai_log->id ); ?>">
										<span class="dashicons dashicons-arrow-down-alt2"></span>
									</button>
								<?php else : ?>
									<span class="rext-ai-no-data">-</span>
								<?php endif; ?>
							</td>
						</tr>
						<?php if ( ! empty( $rext_ai_log->data ) ) : ?>
							<tr class="rext-ai-log-data-row" id="rext-ai-data-<?php echo esc_attr( $rext_ai_log->id ); ?>" style="display: none;">
								<td colspan="6">
									<div class="rext-ai-log-data">
										<pre><?php echo esc_html( wp_json_encode( json_decode( $rext_ai_log->data ), JSON_PRETTY_PRINT ) ); ?></pre>
									</div>
								</td>
							</tr>
						<?php endif; ?>
					<?php endforeach; ?>
				</tbody>
			</table>

			<!-- Pagination -->
			<?php if ( $rext_ai_total_pages > 1 ) : ?>
				<div class="rext-ai-pagination">
					<span class="rext-ai-pagination__info">
						<?php
						printf(
							/* translators: 1: first item, 2: last item, 3: total items */
							esc_html__( 'Showing %1$d-%2$d of %3$d logs', 'rext-ai-publisher' ),
							(int) ( ( $rext_ai_current_page - 1 ) * $rext_ai_per_page ) + 1,
							(int) min( $rext_ai_current_page * $rext_ai_per_page, $rext_ai_total_logs ),
							(int) $rext_ai_total_logs
						);
						?>
					</span>

					<div class="rext-ai-pagination__links">
						<?php
						$rext_ai_base_url = add_query_arg(
							array(
								'page'          => 'rext-ai-logs',
								'level'         => $rext_ai_current_level,
								'action_filter' => $rext_ai_current_action,
							),
							admin_url( 'admin.php' )
						);

						// Previous.
						if ( $rext_ai_current_page > 1 ) :
							?>
							<a href="<?php echo esc_url( add_query_arg( 'paged', $rext_ai_current_page - 1, $rext_ai_base_url ) ); ?>" class="button">
								&laquo; <?php esc_html_e( 'Previous', 'rext-ai-publisher' ); ?>
							</a>
						<?php endif; ?>

						<span class="rext-ai-pagination__current">
							<?php
							printf(
								/* translators: 1: current page, 2: total pages */
								esc_html__( 'Page %1$d of %2$d', 'rext-ai-publisher' ),
								(int) $rext_ai_current_page,
								(int) $rext_ai_total_pages
							);
							?>
						</span>

						<?php
						// Next.
						if ( $rext_ai_current_page < $rext_ai_total_pages ) :
							?>
							<a href="<?php echo esc_url( add_query_arg( 'paged', $rext_ai_current_page + 1, $rext_ai_base_url ) ); ?>" class="button">
								<?php esc_html_e( 'Next', 'rext-ai-publisher' ); ?> &raquo;
							</a>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>

<!-- Toast Notification -->
<div id="rext-ai-toast" class="rext-ai-toast" style="display: none;">
	<span class="rext-ai-toast__message"></span>
</div>
