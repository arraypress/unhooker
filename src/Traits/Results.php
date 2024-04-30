<?php
/**
 * Unhooker Class
 *
 * Manages the removal of WordPress actions and filters from specified hooks with
 * support for global and local conditional checks. It allows developers to
 * dynamically control the execution flow in WordPress by conditionally
 * removing actions and filters, thereby offering precise control over
 * plugin and theme behavior. This class is highly useful for complex WordPress
 * development environments where conditional logic dictates functionality.
 *
 * Usage:
 * - Basic removal: `$unhooker->add( 'init', 'callbackFunction' );`
 * - Conditional removal: `$unhooker->add( 'init', 'callbackFunction', 10, function() { return is_admin(); } );`
 * - Set global condition: `$unhooker->set_global_condition( function() { return isset($_POST['action']); } );`
 * - Commit removals at specific hook: `$unhooker->set_hook( 'wp_loaded' );`
 * - Execute and clear hooks on object destruction or manually via `$unhooker->commit();`
 *
 * @package     ArrayPress/Unhooker
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\Utils\Unhooker\Traits;

if ( ! trait_exists( 'Results' ) ) :

	trait Results {

		/**
		 * @var array Stores the results of each removal attempt.
		 */
		protected array $removal_results = [];

		/**
		 * Retrieves the results of the most recent removal operations.
		 *
		 * @return array Results of action removals.
		 */
		public function get_removal_results(): array {
			return $this->removal_results;
		}

		/**
		 * Verifies if all actions queued for removal were successfully processed.
		 *
		 * @return bool Returns true if the number of actions removed matches the number of actions queued, false otherwise.
		 */
		public function verify_results(): bool {
			if ( ! isset( $this->hooks ) ) {
				throw new \RuntimeException( "Required property \$hooks is not set or is not an array." );
			}

			return count( $this->hooks ) === count( $this->removal_results );
		}

	}
endif;