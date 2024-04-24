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

namespace ArrayPress\Utils;

use InvalidArgumentException;
use function call_user_func;
use function is_array;
use function is_callable;
use function is_int;
use function is_string;

if ( ! function_exists( 'remove_actions' ) ) {
	/**
	 * Function to instantiate the Unhooker class and remove specified actions.
	 *
	 * Supports multiple input formats for defining actions.
	 *
	 * @param array         $actions          Array of action information or associative array where keys are 'tags' and values are 'functions'.
	 * @param int           $default_priority Default priority if not specified for an action.
	 * @param callable|null $global_condition A global condition that must be true to remove any action.
	 * @param callable|null $error_callback   A callback function for error handling.
	 *
	 * @return Unhooker|null The initialized Unhooker instance or null on failure.
	 */
	function remove_actions( array $actions, int $default_priority = 10, ?callable $global_condition = null, ?callable $error_callback = null ): ?Unhooker {
		try {
			$unhooker = new Unhooker( null, $default_priority, $global_condition );

			foreach ( $actions as $key => $value ) {
				if ( is_int( $key ) && is_array( $value ) ) {
					if ( ! isset( $value['tag'], $value['function'] ) || ! is_callable( $value['function'] ) ) {
						if ( $error_callback && is_callable( $error_callback ) ) {
							call_user_func( $error_callback, new InvalidArgumentException( "Invalid tag or function" ) );
						}
						continue;

					}
					$tag                = $value['tag'];
					$function_to_remove = $value['function'];
					$priority           = $value['priority'] ?? $default_priority;
					$condition          = $value['condition'] ?? null;
				} elseif ( is_string( $key ) && is_string( $value ) ) {
					$tag                = $key;
					$function_to_remove = $value;
					$priority           = $default_priority;  // Default priority
					$condition          = null;  // No condition
				} else {
					continue;
				}

				$unhooker->add( $tag, $function_to_remove, $priority, $condition );
			}

			$unhooker->commit();

			return $unhooker;
		} catch ( \Exception $e ) {
			if ( $error_callback && is_callable( $error_callback ) ) {
				call_user_func( $error_callback, $e );
			}

			return null;
		}
	}
}

if ( ! function_exists( 'set_filters' ) ) {
	/**
	 * Function to instantiate the FilterSetter class and set specified filters.
	 *
	 * Supports multiple input formats for defining filters.
	 *
	 * @param array         $filters          Array of filter information or associative array where keys are 'tags' and values are 'values'.
	 * @param callable|null $global_condition A global condition that must be true to apply any filter.
	 * @param callable|null $error_callback   A callback function for error handling.
	 *
	 * @return FilterSetter|null The initialized FilterSetter instance or null on failure.
	 */
	function set_filters( array $filters, ?callable $global_condition = null, ?callable $error_callback = null ): ?FilterSetter {
		try {
			$filterSetter = new FilterSetter( [], $global_condition );

			foreach ( $filters as $key => $value ) {
				if ( is_int( $key ) && is_array( $value ) ) {
					if ( ! isset( $value['tag'], $value['value'] ) || ! is_bool( $value['value'] ) ) {
						if ( $error_callback && is_callable( $error_callback ) ) {
							call_user_func( $error_callback, new InvalidArgumentException( "Invalid tag or value" ) );
						}
						continue;
					}
					$tag          = $value['tag'];
					$filter_value = $value['value'];
					$condition    = $value['condition'] ?? null;
				} elseif ( is_string( $key ) && is_bool( $value ) ) {
					$tag          = $key;
					$filter_value = $value;
					$condition    = null;  // No condition
				} else {
					continue;
				}

				$filterSetter->add( $tag, $filter_value, $condition );
			}

			$filterSetter->commit();

			return $filterSetter;
		} catch ( \Exception $e ) {
			if ( $error_callback && is_callable( $error_callback ) ) {
				call_user_func( $error_callback, $e );
			}

			return null;
		}
	}
}