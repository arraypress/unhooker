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

namespace ArrayPress\Utils\Classes;

use ArrayPress\Utils\Unhooker\Traits\Hooks;
use ArrayPress\Utils\Unhooker\Traits\Results;
use function call_user_func;
use function remove_filter;

if ( ! class_exists( 'Unhooker' ) ) :

	/**
	 * Class Unhooker
	 *
	 * Handles the removal of actions from WordPress hooks in a structured manner, with the option to set conditions.
	 */
	class Unhooker {
		use Hooks, Results;

		/**
		 * Constructor.
		 *
		 * Optionally specifies a WordPress hook, default priority, and global condition under which the actions should be removed.
		 *
		 * @param string|null   $hook             The hook to bind the removal to, default is null.
		 * @param int           $hook_priority    Priority with which the removals are executed on the hook, default is 10.
		 * @param callable|null $global_condition Global condition that must be met to perform removals.
		 * @param int           $default_priority Default priority for all removals, default is 10.
		 */
		public function __construct( ?string $hook = null, int $hook_priority = 10, ?callable $global_condition = null, int $default_priority = 10 ) {
			$this->hook             = $hook;
			$this->hook_priority    = $hook_priority;
			$this->global_condition = $global_condition;
			$this->default_priority = $default_priority;
		}

		/**
		 * Performs the removal of all queued actions, respecting global and local conditions.
		 * Returns an array of the actions that were successfully removed.
		 *
		 * @return void
		 */
		public function perform_operations(): void {
			if ( ! $this->is_global_condition_met() ) {
				return;
			}

			if ( $this->hooks ) {
				foreach ( $this->hooks as $hook ) {
					if ( empty( $hook['condition'] ) || call_user_func( $hook['condition'] ) ) {
						$success = remove_filter( $hook['hook'], $hook['function'], $hook['priority'] );
						if ( $success ) {
							$this->removal_results[] = $hook;
						}
					}
				}
			}
		}

		/**
		 * Destructor.
		 *
		 * Ensures that any pending removals are committed.
		 */
		public function __destruct() {
			$this->commit();
		}

	}
endif;