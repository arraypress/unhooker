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
use function remove_action;

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
		 * @var string|null The specific WordPress hook on which to remove actions.
		 */
		private ?string $hook;

		/**
		 * @var int Default priority for action removal if not specified in the add method.
		 */
		private int $default_priority;

		/**
		 * @var callable|null Global condition that must be met for any action to be removed.
		 */
		private $global_condition;

		/**
		 * @var array Stores the results of each removal attempt.
		 */
		private array $removal_results = [];

		/**
		 * Constructor.
		 *
		 * Optionally specifies a WordPress hook and default priority on which the actions should be removed.
		 *
		 * @param string|null $hook     The hook to bind the removal to.
		 * @param int         $priority Default priority for all removals.
		 */
		public function __construct( ?string $hook = null, int $priority = 10, ?callable $condition = null ) {
			$this->hook             = $hook;
			$this->default_priority = $priority;
			$this->global_condition = $condition;
		}

		/**
		 * Adds an action to the queue for removal, with an optional local condition.
		 *
		 * @param string        $tag                The name of the action to remove.
		 * @param callable      $function_to_remove The function to remove from the action.
		 * @param int|null      $priority           The priority of the function (optional).
		 * @param callable|null $condition          A local conditional callback that must return true to remove the action.
		 */
		public function add( string $tag, callable $function_to_remove, ?int $priority = null, ?callable $condition = null ): void {
			$this->actions[] = [
				'tag'       => $tag,
				'function'  => $function_to_remove,
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
		 * Set or change the hook to trigger action removal.
		 *
		 * @param string $hook New hook to bind the removal process to.
		 */
		public function set_hook( string $hook ): void {
			$this->hook = $hook;
		}

		/**
		 * Performs the removal of all queued actions, respecting global and local conditions.
		 * Returns an array of the actions that were successfully removed.
		 *
		 * @return array Results of action removals.
		 */
		private function perform_removal(): array {
			$this->removal_results = []; // Reset results before each removal operation
			if ( $this->global_condition && ! call_user_func( $this->global_condition ) ) {
				return []; // If global condition fails, skip all actions.
			}

			foreach ( $this->actions as $action ) {
				if ( empty( $action['condition'] ) || call_user_func( $action['condition'] ) ) {
					$success = remove_action( $action['tag'], $action['function'], $action['priority'] );
					if ( $success ) {
						$this->removal_results[] = [
							'tag'      => $action['tag'],
							'function' => $action['function'],
							'priority' => $action['priority'],
							'removed'  => $success
						];
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
				add_action( $this->hook, [ $this, 'perform_removal' ] );

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

	}
endif;