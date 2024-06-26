# Unhooker WordPress Library

The Unhooker library offers a powerful solution for dynamically managing and removing WordPress hooks with an emphasis on conditional logic. This library enables developers to effectively control WordPress actions and filters, ensuring precise customization of WordPress behavior, ideal for complex plugin or theme development.

## Features ##

* **Dynamic Hook Management:** Automatically remove or modify actions and filters from WordPress hooks.
* **Conditional Logic:** Leverage callbacks to conditionally remove actions or set filters based on custom logic.
* **Global Conditions:** Apply a global condition that affects all hook modifications.
* **Error Handling:** Includes robust error handling capabilities to gracefully manage and log issues during hook manipulation.
* **Debugging Support:** Keep track of all modifications to actions and filters, aiding in debugging and ensuring transparency.
* **Flexible Usage:** Tailored for WordPress, perfect for developers needing advanced hook control in plugins or themes.

## Minimum Requirements ##

* **PHP:** 7.4 or higher
* **WordPress:** 5.0 or higher

## Installation ##

Unhooker is a developer library, not a WordPress plugin, so it needs to be included in your WordPress project or plugin.

You can install it using Composer:

```bash
composer require arraypress/unhooker
```

#### Basic File Inclusion

```php
// Require the Composer autoloader.
require_once __DIR__ . '/vendor/autoload.php';

// Use the functions from the ArrayPress\Utils namespace.
use function ArrayPress\Utils\Unhooker\remove_actions;
use function ArrayPress\Utils\Unhooker\remove_filters;
use function ArrayPress\Utils\Unhooker\set_filters;
use function ArrayPress\Utils\Unhooker\remove_class_hooks;
```
### Removing Actions and Filters

#### Basic Removal

Remove actions or filters immediately without conditions:
```php
remove_actions( [ 'init' => 'wp_cron' ] );
remove_filters( [ 'the_content' => 'wpautop' ] );
```

### Class Method Removal

```php
// Example class to add and then remove hooks.
class Example_Class {
    public static function init_hooks() {
        add_action( 'wp_footer', [ self::class, 'footer_action' ], 10 );
    }

	public static function footer_action() {
		echo '<p>This will be removed by the Unhooker.</p>';
	}
}

// Initialize hooks by the class.
Example_Class::init_hooks();

remove_class_hooks( [
    [
        'hook'        => 'wp_footer',
        'class_name'  => 'Example_Class',
        'method_name' => 'footer_action',
    ]
], 'init' );
```

#### Advanced Removal

Remove multiple actions with one call, each with specific conditions and priorities.

```php
remove_actions( [
    [ 'hook' => 'wp_head', 'function' => 'wp_generator', 'priority' => 1 ],
    [ 'hook' => 'wp_head', 'function' => 'rel_canonical' ],
    [ 'hook' => 'wp_footer', 'function' => 'wp_print_footer_scripts', 'priority' => 20 ]
], 'wp_loaded', 15 );
```

#### Conditional and Delayed Removal

Remove an action only if a certain condition is met, and bind it to a specific hook with a custom priority:

```php
remove_actions([
    [ 'hook' => 'wp_footer', 'function' => 'wp_print_footer_scripts', 'priority' => 20 ]
], 'wp_loaded', 15, function() { return is_page( 'home' ); } );
```

### Conditional Removal

```php
remove_actions( [
    [ 'hook' => 'wp_head', 'function' => 'wp_generator' ]
    ], 'wp', 10, function() { return is_singular('download'); } );
```
### Setting Filters

#### Basic Filter Setting

Immediately apply a filter returning a boolean value:

```php
set_filters( [ 'show_admin_bar' => false ] );

// Apply multiple filters, each with specific conditions and priorities.
set_filters([
    'show_admin_bar' => [ 'value' => false, 'condition' => function() { return ! current_user_can( 'administrator' ); } ],
    'excerpt_length' => [ 'value' => 20, 'condition' => function() { return is_home(); } ]
], 'init', 20, function() {
    return is_user_logged_in();
} );
```

#### Conditionally Applying Filters on a Hook

Apply a filter only if a certain condition is met, during a specific hook:

```php
set_filters( [ 'show_admin_bar' => false ], 'init', 20, function() { return ! is_user_logged_in(); } );
```

#### Advanced Filter Setting with Error Handling

Set a filter with error handling and conditional logic:

```php
set_filters( [
    'comment_post' => true
], 'comments_open', 10, function() {
    return current_user_can( 'moderate_comments' );
}, function( $error ) {
    error_log(' Failed to set filter: ' . $error->getMessage() );
} );
```

## Contributions

Contributions to this library are highly appreciated. Raise issues on GitHub or submit pull requests for bug
fixes or new features. Share feedback and suggestions for improvements.

## License: GPLv2 or later

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public
License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.