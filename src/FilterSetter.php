<?php
/**
 * FilterSetter Class
 *
 * Facilitates the management of WordPress filters, enabling dynamic setting of boolean values for specified filters
 * with optional conditional logic. This class provides a powerful tool for configuring the behavior of WordPress sites
 * through simple, conditional filter settings.
 *
 * Usage:
 * - Set a simple filter to true: `$filterSetter->add( 'example_filter', true );`
 * - Conditionally apply filters: `$filterSetter->add( 'example_filter', false, function() { return is_admin(); } );`
 * - Apply global condition: `$filterSetter->set_global_condition( function() { return !is_user_logged_in(); } );`
 * - Apply all configured filters: `$filterSetter->commit();`
 *
 * @package     ArrayPress/Unhooker
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\Utils;

use function add_filter;
use function call_user_func;

if ( ! class_exists( 'FilterSetter' ) ) :

	/**
	 * Class FilterSetter
	 *
	 * Simplifies the process of setting filters in WordPress to return specific boolean values, with conditional application.
	 */
	class FilterSetter {
		/**
		 * @var array Filters to be applied with conditions.
		 */
		private array $filters = [];

		/**
		 * @var callable|null Global condition that must be met to apply any filter.
		 */
		private $global_condition = null;

		/**
		 * Constructor.
		 *
		 * Initializes the filter settings based on an associative array of filter tags with boolean values.
		 *
		 * @param array         $initialFilters Associative array of filter tags with boolean values to set them to.
		 * @param callable|null $condition      A global conditional callback that must return true to apply any filter.
		 */
		public function __construct( array $initialFilters = [], ?callable $condition = null ) {
			foreach ( $initialFilters as $tag => $value ) {
				$this->add( $tag, $value );
			}
			$this->global_condition = $condition;
		}

		/**
		 * Adds a filter to the list with an optional condition.
		 *
		 * @param string        $tag       The name of the filter to apply.
		 * @param bool          $value     The boolean value the filter should return.
		 * @param callable|null $condition A local conditional callback that must return true to apply the filter.
		 */
		public function add( string $tag, bool $value, ?callable $condition = null ): void {
			$this->filters[] = [
				'tag'       => $tag,
				'value'     => $value,
				'condition' => $condition
			];
		}

		/**
		 * Sets a global condition that must be met for any filters to be applied.
		 *
		 * @param callable $condition A global conditional callback that must return true to apply any filter.
		 */
		public function set_global_condition( callable $condition ): void {
			$this->global_condition = $condition;
		}

		/**
		 * Applies all configured filters, respecting global and local conditions.
		 *
		 * Executes each filter setting only if the global and local conditions are met.
		 */
		public function commit(): void {
			if ( $this->global_condition && ! call_user_func( $this->global_condition ) ) {
				return; // Global condition not met, skip all filters.
			}

			foreach ( $this->filters as $filter ) {
				if ( empty( $filter['condition'] ) || call_user_func( $filter['condition'] ) ) {
					if ( $filter['value'] ) {
						add_filter( $filter['tag'], '__return_true' );
					} else {
						add_filter( $filter['tag'], '__return_false' );
					}
				}
			}
		}
	}

endif;