<?php
/**
 * Unhooker Functions
 *
 * @package     ArrayPress/Unhooker
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\Utils\Unhooker;

use ArrayPress\Utils\Unhooker\Classes\FilterSetter;
use ArrayPress\Utils\Unhooker\Classes\MethodUnhooker;
use ArrayPress\Utils\Unhooker\Classes\Unhooker;

use Exception;
use InvalidArgumentException;
use function call_user_func;
use function is_array;
use function is_bool;
use function is_callable;
use function is_int;
use function is_string;

if ( ! function_exists( 'remove_filters' ) ) {
	/**
	 * Helper function to easily remove specified actions and filters.
	 *
	 * This function supports a flexible definition of actions and filters to be removed and can handle these on a specified hook
	 * with a defined priority. It utilizes the Unhooker class for the actual removal process, allowing for both immediate
	 * and delayed removal based on conditions.
	 *
	 * @param array         $hooks             Array of filters to remove. Can be an associative array where keys are 'tags' and values can be
	 *                                         either 'functions' directly or an array including 'function', 'priority', and 'condition'.
	 * @param string|null   $hook              The WordPress hook on which the removal should be executed, null for immediate removal.
	 * @param int           $hook_priority     The priority at which the removal function should be executed on the hook.
	 * @param int           $default_priority  Default priority if not specifically provided for an action.
	 * @param callable|null $global_condition  A global condition that must be true to execute the removal.
	 * @param callable|null $error_callback    A callback function for error handling.
	 *
	 * @return Unhooker|null The initialized Unhooker instance or null on failure.
	 */
	function remove_filters( array $hooks, ?string $hook = null, int $hook_priority = 10, ?callable $global_condition = null, int $default_priority = 10, ?callable $error_callback = null ): ?Unhooker {
		try {
			$unhooker = new Unhooker( $hook, $hook_priority, $global_condition, $default_priority );

			foreach ( $hooks as $key => $value ) {
				if ( is_int( $key ) && is_array( $value ) ) {
					if ( ! isset( $value['hook'], $value['callback'] ) || ! is_callable( $value['callback'] ) ) {
						if ( $error_callback && is_callable( $error_callback ) ) {
							call_user_func( $error_callback, new InvalidArgumentException( "Invalid tag or function" ) );
						}
						continue;
					}
					$hook_name          = $value['hook'];
					$function_to_remove = $value['callback'];
					$priority           = $value['priority'] ?? $default_priority;
					$condition          = $value['condition'] ?? null;
				} elseif ( is_string( $key ) && is_string( $value ) ) {
					$hook_name          = $key;
					$function_to_remove = $value;
					$priority           = $default_priority;  // Use the default priority
					$condition          = null;  // No specific condition
				} else {
					continue;  // Skip malformed entries
				}

				// Check if the function to remove is callable
				if ( ! is_callable( $function_to_remove, true ) ) {
					if ( $error_callback && is_callable( $error_callback ) ) {
						call_user_func( $error_callback, new InvalidArgumentException( "Function to remove is not callable: $function_to_remove" ) );
					}
					continue; // Skip this entry
				}

				$unhooker->add( $hook_name, $function_to_remove, $priority, $condition );
			}

			$unhooker->commit();

			return $unhooker;
		} catch ( Exception $e ) {
			if ( $error_callback && is_callable( $error_callback ) ) {
				call_user_func( $error_callback, $e );
			}

			return null;
		}
	}
}

if ( ! function_exists( 'remove_actions' ) ) {
	/**
	 * Helper function to easily remove specified actions.
	 *
	 * This function supports a flexible definition of actions to be removed and can handle these on a specified hook
	 * with a defined priority. It utilizes the Unhooker class for the actual removal process, allowing for both immediate
	 * and delayed removal based on conditions.
	 *
	 * @param array         $hooks            Array of actions to remove. Can be an associative array where keys are 'tags' and values can be
	 *                                        either 'functions' directly or an array including 'callback', 'priority', and 'condition'.
	 * @param string|null   $hook             The WordPress hook on which the removal should be executed, null for immediate removal.
	 * @param int           $hook_priority    The priority at which the removal function should be executed on the hook.
	 * @param callable|null $global_condition A global condition that must be true to execute the removal.
	 * @param int           $default_priority Default priority if not specifically provided for a filter.
	 * @param callable|null $error_callback   A callback function for error handling.
	 *
	 * @return Unhooker|null The initialized Unhooker instance or null on failure.
	 */
	function remove_actions( array $hooks, ?string $hook = null, int $hook_priority = 10, ?callable $global_condition = null, int $default_priority = 10, ?callable $error_callback = null ): ?Unhooker {
		return remove_filters( $hooks, $hook, $hook_priority, $global_condition, $default_priority, $error_callback );
	}
}

if ( ! function_exists( 'set_filters' ) ) {
	/**
	 * Function to instantiate the FilterSetter class and set specified filters.
	 *
	 * Supports multiple input formats for defining filters. It can apply filters on a specific WordPress hook with a defined priority.
	 *
	 * @param array         $hooks            Array of filter information or associative array where keys are 'tags' and values are 'values'.
	 * @param string|null   $hook             The WordPress hook on which the filter should be applied, null for immediate application.
	 * @param int           $hook_priority    The priority at which the filter function should be executed on the hook.
	 * @param callable|null $global_condition A global condition that must be true to apply any filter.
	 * @param callable|null $error_callback   A callback function for error handling.
	 *
	 * @return FilterSetter|null The initialized FilterSetter instance or null on failure.
	 */
	function set_filters( array $hooks, ?string $hook = null, int $hook_priority = 10, ?callable $global_condition = null, ?callable $error_callback = null ): ?FilterSetter {
		try {
			$filterSetter = new FilterSetter( $hook, $hook_priority, $global_condition );

			foreach ( $hooks as $key => $value ) {
				if ( is_int( $key ) && is_array( $value ) ) {
					if ( ! isset( $value['hook'], $value['value'] ) || ! is_bool( $value['value'] ) ) {
						if ( $error_callback && is_callable( $error_callback ) ) {
							call_user_func( $error_callback, new InvalidArgumentException( "Invalid tag or value" ) );
						}
						continue;
					}
					$hook_name    = $value['hook'];
					$filter_value = $value['value'];
					$condition    = $value['condition'] ?? null;
				} elseif ( is_string( $key ) && is_bool( $value ) ) {
					$hook_name    = $key;
					$filter_value = $value;
					$condition    = null;
				} else {
					continue;
				}

				if ( null !== $condition && ! is_callable( $condition, true ) ) {
					if ( $error_callback && is_callable( $error_callback ) ) {
						call_user_func( $error_callback, new InvalidArgumentException( "Condition function is not callable: " . print_r( $condition, true ) ) );
					}
					continue;
				}

				$filterSetter->add( $hook_name, $filter_value, $condition );
			}

			$filterSetter->commit();

			return $filterSetter;
		} catch ( Exception $e ) {
			if ( $error_callback && is_callable( $error_callback ) ) {
				call_user_func( $error_callback, $e );
			}

			return null;
		}
	}
}

if ( ! function_exists( 'remove_class_hooks' ) ) {
	/**
	 * Helper function to remove specified actions or filters tied to class methods.
	 *
	 * This function supports a detailed definition of hooks to be removed and can handle these on a specified priority.
	 * It utilizes the MethodUnhooker class for the actual removal process, allowing for both immediate and delayed removal based on conditions.
	 *
	 * @param array         $hooks            Array of hooks to remove. Each entry must include 'hook', 'class_name', and 'method_name' keys, with an optional 'priority', 'condition', and 'hook_specific' for binding removal to a specific action.
	 * @param string|null   $hook             The WordPress hook to bind the removal process to, null for immediate execution.
	 * @param int           $hook_priority    The priority at which the hook is executed.
	 * @param callable|null $global_condition A global condition that must be met to apply any removal.
	 * @param int           $default_priority Default priority if not specifically provided for a filter.
	 * @param bool          $strict_matching  Whether to strictly match class names. Default is true.
	 * @param bool          $case_sensitive   Whether class name matching is case-sensitive. Default is false.
	 * @param callable|null $error_callback   A callback function for error handling.
	 *
	 * @return MethodUnhooker|null The initialized MethodUnhooker instance or null on failure.
	 */
	function remove_class_hooks( array $hooks, ?string $hook = null, int $hook_priority = 10, ?callable $global_condition = null, int $default_priority = 10, bool $strict_matching = false, bool $case_sensitive = false, ?callable $error_callback = null ): ?MethodUnhooker {
		try {
			$hookRemover = new MethodUnhooker( $hook, $hook_priority, $global_condition, $default_priority, $strict_matching, $case_sensitive );

			foreach ( $hooks as $hookDetail ) {
				if ( ! isset( $hookDetail['hook'], $hookDetail['class_name'], $hookDetail['method_name'] ) ) {
					if ( $error_callback ) {
						call_user_func( $error_callback, new InvalidArgumentException( "Missing necessary parameters to remove a hook." ) );
					}
					continue;
				}

				$tag         = $hookDetail['hook'];
				$class_name  = $hookDetail['class_name'];
				$method_name = $hookDetail['method_name'];
				$priority    = $hookDetail['priority'] ?? 10;
				$condition   = $hookDetail['condition'] ?? null;

				$hookRemover->add( $tag, $class_name, $method_name, $priority, $condition );
			}

			$hookRemover->commit();

			return $hookRemover;
		} catch ( Exception $e ) {
			if ( $error_callback ) {
				call_user_func( $error_callback, $e );
			}

			return null;
		}
	}
}