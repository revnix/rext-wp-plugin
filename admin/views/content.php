<?php
/**
 * Published Content Page View
 *
 * @package Rext_AI
 * @since 1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$rext_ai_posts_table = $wpdb->prefix . 'rext_ai_posts';

// Get filter parameters.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$rext_ai_current_status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$rext_ai_current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
$rext_ai_per_page     = 20;

// Build query.
$rext_ai_where  = array( '1=1' );
$rext_ai_values = array();

if ( ! empty( $rext_ai_current_status ) ) {
	$rext_ai_where[]  = 'r.status = %s';
	$rext_ai_values[] = $rext_ai_current_status;
}

$rext_ai_where_clause = implode( ' AND ', $rext_ai_where );
$rext_ai_offset       = ( $rext_ai_current_page - 1 ) * $rext_ai_per_page;

$rext_ai_count_sql = 'SELECT COUNT(*) FROM ' . $rext_ai_posts_table . ' r WHERE ' . $rext_ai_where_clause;
if ( ! empty( $rext_ai_values ) ) {
	$rext_ai_count_query = $wpdb->prepare( $rext_ai_count_sql, $rext_ai_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
} else {
	$rext_ai_count_query = $rext_ai_count_sql;
}
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
$rext_ai_total_posts = (int) $wpdb->get_var( $rext_ai_count_query );
$rext_ai_total_pages = ceil( $rext_ai_total_posts / $rext_ai_per_page );

$rext_ai_query_sql = 'SELECT r.*, p.post_title, p.post_status as wp_status
          FROM ' . $rext_ai_posts_table . ' r
          LEFT JOIN ' . $wpdb->posts . ' p ON r.wp_post_id = p.ID
          WHERE ' . $rext_ai_where_clause . '
          ORDER BY r.created_at DESC
          LIMIT %d OFFSET %d';
$rext_ai_values[]  = $rext_ai_per_page;
$rext_ai_values[]  = $rext_ai_offset;
$rext_ai_query     = $wpdb->prepare( $rext_ai_query_sql, $rext_ai_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
$rext_ai_posts = $wpdb->get_results( $rext_ai_query );

// Status colors.
$rext_ai_status_colors = array(
	'publish' => 'rext-ai-status-badge--publish',
	'draft'   => 'rext-ai-status-badge--draft',
	'pending' => 'rext-ai-status-badge--pending',
	'private' => 'rext-ai-status-badge--private',
	'future'  => 'rext-ai-status-badge--future',
	'trash'   => 'rext-ai-status-badge--trash',
	'trashed' => 'rext-ai-status-badge--trash',
	'deleted' => 'rext-ai-status-badge--deleted',
);

$rext_ai_status_counts = $wpdb->get_results( 'SELECT status, COUNT(*) as count FROM ' . $rext_ai_posts_table . ' GROUP BY status', OBJECT_K ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
?>

<div class="wrap rext-ai-admin">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<!-- Status Filters -->
	<ul class="subsubsub rext-ai-status-filters">
		<li>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=rext-ai-content' ) ); ?>"
				class="<?php echo empty( $rext_ai_current_status ) ? 'current' : ''; ?>">
				<?php esc_html_e( 'All', 'rext-ai-publisher' ); ?>
				<span class="count">(<?php echo esc_html( $rext_ai_total_posts ); ?>)</span>
			</a> |
		</li>
		<?php
		$rext_ai_statuses = array( 'publish', 'draft', 'pending', 'trashed' );
		foreach ( $rext_ai_statuses as $rext_ai_index => $rext_ai_status ) :
			$rext_ai_count = isset( $rext_ai_status_counts[ $rext_ai_status ] ) ? $rext_ai_status_counts[ $rext_ai_status ]->count : 0;
			if ( $rext_ai_count > 0 ) :
				?>
				<li>
					<a href="<?php echo esc_url( add_query_arg( 'status', $rext_ai_status, admin_url( 'admin.php?page=rext-ai-content' ) ) ); ?>"
						class="<?php echo $rext_ai_current_status === $rext_ai_status ? 'current' : ''; ?>">
						<?php echo esc_html( ucfirst( $rext_ai_status ) ); ?>
						<span class="count">(<?php echo esc_html( $rext_ai_count ); ?>)</span>
					</a>
					<?php
					if ( $rext_ai_index < count( $rext_ai_statuses ) - 1 ) :
						?>
						|<?php endif; ?>
				</li>
				<?php
			endif;
		endforeach;
		?>
	</ul>

	<!-- Content Table -->
	<div class="rext-ai-card" style="margin-top: 20px;">
		<?php if ( empty( $rext_ai_posts ) ) : ?>
			<div class="rext-ai-empty-state">
				<span class="dashicons dashicons-media-text"></span>
				<p><?php esc_html_e( 'No content published via Rext AI yet.', 'rext-ai-publisher' ); ?></p>
				<p class="description">
					<?php esc_html_e( 'Content published from Rext AI will appear here.', 'rext-ai-publisher' ); ?>
				</p>
			</div>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped rext-ai-content-table">
				<thead>
					<tr>
						<th class="column-title"><?php esc_html_e( 'Title', 'rext-ai-publisher' ); ?></th>
						<th class="column-status" style="width: 100px;"><?php esc_html_e( 'Status', 'rext-ai-publisher' ); ?></th>
						<th class="column-rext-id" style="width: 200px;"><?php esc_html_e( 'Rext ID', 'rext-ai-publisher' ); ?></th>
						<th class="column-published" style="width: 150px;"><?php esc_html_e( 'Published', 'rext-ai-publisher' ); ?></th>
						<th class="column-synced" style="width: 150px;"><?php esc_html_e( 'Last Synced', 'rext-ai-publisher' ); ?></th>
						<th class="column-actions" style="width: 100px;"><?php esc_html_e( 'Actions', 'rext-ai-publisher' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rext_ai_posts as $rext_ai_post ) : ?>
						<tr>
							<td class="column-title">
								<?php if ( $rext_ai_post->post_title ) : ?>
									<strong>
										<a href="<?php echo esc_url( get_edit_post_link( $rext_ai_post->wp_post_id ) ); ?>">
											<?php echo esc_html( $rext_ai_post->post_title ); ?>
										</a>
									</strong>
								<?php else : ?>
									<span class="rext-ai-deleted-post">
										<?php esc_html_e( '(Post Deleted)', 'rext-ai-publisher' ); ?>
									</span>
								<?php endif; ?>
							</td>
							<td class="column-status">
								<?php
								$rext_ai_display_status = $rext_ai_post->wp_status ? $rext_ai_post->wp_status : $rext_ai_post->status;
								$rext_ai_status_class   = $rext_ai_status_colors[ $rext_ai_display_status ] ?? '';
								?>
								<span class="rext-ai-status-badge <?php echo esc_attr( $rext_ai_status_class ); ?>">
									<?php echo esc_html( ucfirst( $rext_ai_display_status ) ); ?>
								</span>
							</td>
							<td class="column-rext-id">
								<code class="rext-ai-rext-id" title="<?php echo esc_attr( $rext_ai_post->rext_content_id ); ?>">
									<?php
									$rext_ai_id = $rext_ai_post->rext_content_id;
									echo esc_html( strlen( $rext_ai_id ) > 20 ? substr( $rext_ai_id, 0, 20 ) . '...' : $rext_ai_id );
									?>
								</code>
							</td>
							<td class="column-published">
								<?php if ( $rext_ai_post->published_at ) : ?>
									<span title="<?php echo esc_attr( $rext_ai_post->published_at ); ?>">
										<?php echo esc_html( human_time_diff( strtotime( $rext_ai_post->published_at ), time() ) ); ?>
										<?php esc_html_e( 'ago', 'rext-ai-publisher' ); ?>
									</span>
								<?php else : ?>
									<span class="rext-ai-not-published">—</span>
								<?php endif; ?>
							</td>
							<td class="column-synced">
								<?php if ( $rext_ai_post->last_synced_at ) : ?>
									<span title="<?php echo esc_attr( $rext_ai_post->last_synced_at ); ?>">
										<?php echo esc_html( human_time_diff( strtotime( $rext_ai_post->last_synced_at ), time() ) ); ?>
										<?php esc_html_e( 'ago', 'rext-ai-publisher' ); ?>
									</span>
								<?php else : ?>
									<span>—</span>
								<?php endif; ?>
							</td>
							<td class="column-actions">
								<?php if ( $rext_ai_post->post_title ) : ?>
									<a href="<?php echo esc_url( get_permalink( $rext_ai_post->wp_post_id ) ); ?>"
										class="button button-small"
										target="_blank"
										title="<?php esc_attr_e( 'View', 'rext-ai-publisher' ); ?>">
										<span class="dashicons dashicons-visibility"></span>
									</a>
									<a href="<?php echo esc_url( get_edit_post_link( $rext_ai_post->wp_post_id ) ); ?>"
										class="button button-small"
										title="<?php esc_attr_e( 'Edit', 'rext-ai-publisher' ); ?>">
										<span class="dashicons dashicons-edit"></span>
									</a>
								<?php else : ?>
									<span class="rext-ai-no-actions">—</span>
								<?php endif; ?>
							</td>
						</tr>
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
							esc_html__( 'Showing %1$d-%2$d of %3$d posts', 'rext-ai-publisher' ),
							(int) ( ( ( $rext_ai_current_page - 1 ) * $rext_ai_per_page ) + 1 ),
							(int) min( $rext_ai_current_page * $rext_ai_per_page, $rext_ai_total_posts ),
							(int) $rext_ai_total_posts
						);
						?>
					</span>

					<div class="rext-ai-pagination__links">
						<?php
						$rext_ai_base_url = add_query_arg(
							array(
								'page'   => 'rext-ai-content',
								'status' => $rext_ai_current_status,
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
