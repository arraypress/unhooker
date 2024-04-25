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

namespace ArrayPress\Utils;

use function add_action;
use function call_user_func;
use function remove_filter;

if ( ! class_exists( 'Unhooker' ) ) :

	/**
	 * Class Unhooker
	 *
	 * Handles the removal of actions from WordPress hooks in a structured manner, with the option to set conditions.
	 */
	class Unhooker {
		/**
		 * @var array List of actions to remove.
		 */
		private array $actions = [];

		/**
		 * @var int Default priority for action removal if not specified in the add method.
		 */
		private int $default_priority;

		/**
		 * @var callable|null Global condition that must be met for any action to be removed.
		 */
		private $global_condition;

		/**
		 * @var string|null The specific WordPress hook on which to remove actions.
		 */
		private ?string $hook;

		/**
		 * * @var int Priority for the hook when adding actions to WordPress.
		 */
		private int $hook_priority;

		/**
		 * @var array Stores the results of each removal attempt.
		 */
		private array $removal_results = [];

		/**
		 * Constructor.
		 *
		 * Optionally specifies a WordPress hook, default priority, and global condition under which the actions should be removed.
		 *
		 * @param int           $default_priority Default priority for all removals, default is 10.
		 * @param callable|null $global_condition Global condition that must be met to perform removals.
		 * @param string|null   $hook             The hook to bind the removal to, default is null.
		 * @param int           $hook_priority    Priority with which the removals are executed on the hook, default is 10.
		 */
		public function __construct( int $default_priority = 10, ?callable $global_condition = null, ?string $hook = null, int $hook_priority = 10 ) {
			$this->default_priority = $default_priority;
			$this->global_condition = $global_condition;
			$this->hook             = $hook;
			$this->hook_priority    = $hook_priority;
		}

		/**
		 * Adds an action to the queue for removal, with an optional local condition.
		 *
		 * @param string        $hook      The name of the action to remove.
		 * @param callable      $callback  The function to remove from the action.
		 * @param int|null      $priority  The priority of the function (optional).
		 * @param callable|null $condition A local conditional callback that must return true to remove the action.
		 */
		public function add( string $hook, callable $callback, ?int $priority = null, ?callable $condition = null ): void {
			$this->actions[] = [
				'hook'      => $hook,
				'callback'  => $callback,
				'priority'  => $priority ?? $this->default_priority,
				'condition' => $condition
			];
		}

		/**
		 * Sets a global condition that must be met for any actions to be removed.
		 *
		 * @param callable $condition A global conditional callback that must return true to remove any action.
		 */
		public function set_global_condition( callable $condition ): void {
			$this->global_condition = $condition;
		}

		/**
		 * Set the default priority for new actions.
		 *
		 * @param int $priority New default priority.
		 */
		public function set_priority( int $priority ): void {
			$this->default_priority = $priority;
		}

		/**
		 * Set or change the WordPress hook and its priority to trigger action removal.
		 *
		 * @param string   $hook          The WordPress hook to which the removal process will be bound.
		 * @param int|null $hook_priority Optional. The priority at which the removals are executed on the hook. Defaults to 10.
		 */
		public function set_hook( string $hook, ?int $hook_priority = null ): void {
			$this->hook = $hook;
			if ( $hook_priority !== null ) {
				$this->hook_priority = $hook_priority;
			}
		}

		/**
		 * Performs the removal of all queued actions, respecting global and local conditions.
		 * Returns an array of the actions that were successfully removed.
		 *
		 * @return array Results of action removals.
		 */
		public function perform_removal(): array {
			$this->removal_results = []; // Reset results before each removal operation
			if ( $this->global_condition && ! call_user_func( $this->global_condition ) ) {
				return []; // If global condition fails, skip all actions.
			}

			foreach ( $this->actions as $action ) {
				if ( empty( $action['condition'] ) || call_user_func( $action['condition'] ) ) {
					$success = remove_filter( $action['hook'], $action['callback'], $action['priority'] );
					if ( $success ) {
						$this->removal_results[] = $action;
					}
				}
			}

			return $this->removal_results;
		}

		/**
		 * Commits the removal of all actions, either immediately or at a specified hook.
		 *
		 * @return array If immediate, returns results of action removals, otherwise returns an empty array.
		 */
		public function commit(): array {
			if ( $this->hook ) {
				add_action( $this->hook, [ $this, 'perform_removal' ], $this->hook_priority );

				return [];
			} else {
				return $this->perform_removal();
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
			return count( $this->actions ) === count( $this->removal_results );
		}

	}
endif;