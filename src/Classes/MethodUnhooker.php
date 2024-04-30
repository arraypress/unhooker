<?php
/**
 * ClassUnhooker Class
 *
 * Facilitates the dynamic removal of WordPress actions and filters based on class methods,
 * supporting conditional logic to control hook management within themes and plugins effectively.
 *
 * Usage:
 * - Add hooks for removal: `$hookRemover->add('init', 'ClassName', 'methodName');`
 * - Remove multiple hooks with a single commit: `$hookRemover->commit();`
 *
 * @package     ArrayPress/Unhooker
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\Utils\Unhooker\Classes;

use ArrayPress\Utils\Unhooker\Traits\Hooks;
use ArrayPress\Utils\Unhooker\Traits\Results;
use WP_Hook;
use function is_array;

if ( ! class_exists( 'MethodUnhooker' ) ) :

	class MethodUnhooker {
		use Hooks, Results;

		/**
		 * @var bool Whether to match class names strictly.
		 */
		private bool $strict_matching;

		/**
		 * @var bool Whether class name matching is case-sensitive.
		 */
		private bool $case_sensitive;

		/**
		 * Constructor.
		 *
		 * Initializes the hook remover with options for strict and case-sensitive class name matching.
		 *
		 * @param string|null   $hook             The hook to bind the filter application to, default is null.
		 * @param int           $hook_priority    Priority with which the filters are executed on the hook, default is 10.
		 * @param callable|null $global_condition Global condition that must be met to apply any filter.
		 * @param int           $default_priority Default priority for all removals, default is 10.
		 * @param bool          $strict_matching  Whether to strictly match class names. Default is false.
		 * @param bool          $case_sensitive   Whether to match class names with case sensitivity. Default is false.
		 */
		public function __construct( ?string $hook = null, int $hook_priority = 10, ?callable $global_condition = null, int $default_priority = 10, bool $strict_matching = false, bool $case_sensitive = false ) {
			$this->hook             = $hook;
			$this->hook_priority    = $hook_priority;
			$this->global_condition = $global_condition;
			$this->default_priority = $default_priority;
			$this->strict_matching  = $strict_matching;
			$this->case_sensitive   = $case_sensitive;
		}

		/**
		 * Sets whether class names should be matched strictly.
		 *
		 * If true, the class name must be an exact match. If false, the class name must be a substring of the full class name.
		 *
		 * @param bool $strict_matching Specify true for strict matching.
		 */
		public function set_strict_matching( bool $strict_matching ): void {
			$this->strict_matching = $strict_matching;
		}

		/**
		 * Sets the case sensitivity for class name matching.
		 *
		 * @param bool $case_sensitive Specify true to enable case-sensitive matching.
		 */
		public function set_case_sensitive( bool $case_sensitive ): void {
			$this->case_sensitive = $case_sensitive;
		}

		/**
		 * Adds a hook to the queue for removal.
		 *
		 * @param string        $hook        The name of the action or filter to remove.
		 * @param string        $class_name  The class name where the method is defined.
		 * @param string        $method_name The method name attached to the hook.
		 * @param int           $priority    Optional. The priority of the hook. Default is 10.
		 * @param callable|null $condition   A local conditional callback that must return true to apply the filter.
		 */
		public function add( string $hook, string $class_name, string $method_name, int $priority = 10, ?callable $condition = null ): void {
			$this->hooks[] = [
				'hook'        => $hook,
				'class_name'  => $class_name,
				'method_name' => $method_name,
				'priority'    => $priority,
				'condition'   => $condition
			];
		}


		/**
		 * Internal method to apply filters according to the specified configurations.
		 */
		public function perform_operations(): void {
			if ( ! $this->is_global_condition_met() ) {
				return;
			}

			if ( $this->hooks ) {
				foreach ( $this->hooks as $hook ) {
					if ( empty( $hook['condition'] ) || call_user_func( $hook['condition'] ) ) {
						$success = $this->remove_hook( $hook['hook'], $hook['class_name'], $hook['method_name'], $hook['priority'] );
						if ( $success ) {
							$this->removal_results[] = $hook;
						}
					}
				}
			}
		}

		/**
		 * Remove a specified hook.
		 *
		 * @param string $hook        The hook tag.
		 * @param string $class_name  The class name.
		 * @param string $method_name The method name in the class.
		 * @param int    $priority    Priority of the hook.
		 *
		 * @return bool True if the hook was successfully removed, false otherwise.
		 */
		private function remove_hook( string $hook, string $class_name, string $method_name, int $priority ): bool {
			global $wp_filter;
			if ( ! isset( $wp_filter[ $hook ] ) ) {
				return false; // Hook doesn't exist
			}

			$hook = $wp_filter[ $hook ];
			if ( ! ( $hook instanceof WP_Hook ) ) {
				return false;
			}

			$removed = false;
			if ( isset( $hook->callbacks[ $priority ] ) ) {
				foreach ( $hook->callbacks[ $priority ] as $callback ) {
					if ( is_array( $callback['function'] ) && $this->compare_class_name( $callback['function'][0], $class_name ) && $callback['function'][1] === $method_name ) {
						$hook->remove_filter( $hook, $callback['function'], $priority );
						$removed = true;
					}
				}
			}

			return $removed;
		}

		/**
		 * Compares the class name of an object to a specified class name based on the configured matching settings.
		 *
		 * @param object $object     The object whose class name is to be compared.
		 * @param string $class_name The class name to match against.
		 *
		 * @return bool Returns true if the class names match according to the configured settings.
		 */
		private function compare_class_name( $object, string $class_name ): bool {
			$actual_class_name = is_object( $object ) ? get_class( $object ) : $object;
			if ( $this->strict_matching ) {
				if ( $this->case_sensitive ) {
					return $actual_class_name === $class_name;
				} else {
					return strtolower( $actual_class_name ) === strtolower( $class_name );
				}
			} else {
				if ( $this->case_sensitive ) {
					return strpos( $actual_class_name, $class_name ) !== false;
				} else {
					return stripos( $actual_class_name, $class_name ) !== false;
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
