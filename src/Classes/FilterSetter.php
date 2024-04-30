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

namespace ArrayPress\Utils\Unhooker\Classes;

use ArrayPress\Utils\Unhooker\Traits\Hooks;
use function add_filter;
use function call_user_func;

if ( ! class_exists( 'FilterSetter' ) ) :

	/**
	 * Class FilterSetter
	 *
	 * Simplifies the process of setting filters in WordPress to return specific boolean values, with conditional application.
	 */
	class FilterSetter {
		use Hooks;

		/**
		 * Constructor.
		 *
		 * Initializes the filter settings based on an optional global condition, hook, and priority.
		 *
		 * @param string|null   $hook             The hook to bind the filter application to, default is null.
		 * @param int           $hook_priority    Priority with which the filters are executed on the hook, default is 10.
		 * @param callable|null $global_condition Global condition that must be met to apply any filter.
		 */
		public function __construct( ?string $hook = null, int $hook_priority = 10, ?callable $global_condition = null ) {
			$this->hook             = $hook;
			$this->hook_priority    = $hook_priority;
			$this->global_condition = $global_condition;
		}

		/**
		 * Adds a filter to the list with an optional condition.
		 *
		 * @param string        $hook      The name of the filter to apply.
		 * @param bool          $value     The boolean value the filter should return.
		 * @param callable|null $condition A local conditional callback that must return true to apply the filter.
		 */
		public function add( string $hook, bool $value, ?callable $condition = null ): void {
			$this->hooks[] = [
				'hook'      => $hook,
				'value'     => $value,
				'condition' => $condition
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
						add_filter( $hook['hook'], $hook['value'] ? '__return_true' : '__return_false' );
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