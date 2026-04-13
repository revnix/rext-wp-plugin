<?php
/**
 * Rext AI Plugin Global Functions
 *
 * @package Rext_AI
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the main instance of Rext_AI.
 *
 * @return Rext_AI
 */
function rext_ai() {
	return Rext_AI::instance();
}
