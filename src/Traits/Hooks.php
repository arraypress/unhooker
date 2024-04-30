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

if ( ! trait_exists( 'Hooks' ) ) :

	trait Hooks {
		/**
		 * @var array Stores the details of the hooks to be managed.
		 */
		protected array $hooks = [];

		/**
		 * @var callable|null Global condition that must be met to apply any hook.
		 */
		protected $global_condition;

		/**
		 * @var int Default priority for action removal if not specified in the add method.
		 */
		protected int $default_priority = 10;

		/**
		 * @var string|null The specific WordPress hook on which to apply actions or filters.
		 */
		protected ?string $hook;

		/**
		 * @var int Priority for the hook when applying actions or filters to WordPress.
		 */
		protected int $hook_priority;

		/**
		 * Sets a global condition that must be met for any hooks to be processed.
		 *
		 * @param callable $condition A global conditional callback that must return true to apply any hook.
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
		 * Set or change the WordPress hook and its priority to apply hooks.
		 *
		 * @param string   $hook          The WordPress hook to which the operations will be bound.
		 * @param int|null $hook_priority Optional. The priority at which the hooks are executed on the hook.
		 */
		public function set_hook( string $hook, ?int $hook_priority = null ): void {
			$this->hook = $hook;
			if ( $hook_priority !== null ) {
				$this->hook_priority = $hook_priority;
			}
		}

		/**
		 * Adds a hook to the queue.
		 *
		 * @param string        $hook_name         The name of the action or filter.
		 * @param mixed         $callback_function The callback function or method to be applied.
		 * @param int|null      $priority          Optional. The priority of the hook. Default is 10.
		 * @param callable|null $condition         A local conditional callback that must return true to apply the hook.
		 */
		public function add( string $hook_name, $callback_function, ?int $priority = null, ?callable $condition = null ): void {
			$this->hooks[] = [
				'hook_name' => $hook_name,
				'function'  => $callback_function,
				'priority'  => $priority ?? $this->default_priority,
				'condition' => $condition
			];
		}

		/**
		 * Commits the execution of all queued hooks.
		 */
		public function commit(): void {
			if ( $this->hook ) {
				add_action( $this->hook, [ $this, 'perform_operations' ], $this->hook_priority );
			} else {
				$this->perform_operations();
			}
		}

		/**
		 * Checks if the global condition for applying hooks is met.
		 *
		 * This method evaluates the callable set as the global condition (if any) and returns
		 * true if the condition is met, or false otherwise. It's used to determine whether
		 * the hooks should be applied based on the specified global condition.
		 *
		 * @return bool Returns true if the global condition is met, true otherwise.
		 */
		public function is_global_condition_met(): bool {
			return $this->global_condition ? call_user_func( $this->global_condition ) : true;
		}


		/**
		 * Perform operations on the hooks according to the specified configurations.
		 * This method should be implemented in each class to handle specific logic for adding, setting, or removing hooks.
		 */
		abstract protected function perform_operations(): void;
	}
endif;
