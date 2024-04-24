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

// Use the Slurp class from the ArrayPress\Utils namespace.
Use ArrayPress\Utils;
```

#### Removing Actions

You can remove actions dynamically with the `remove_actions` function. This function supports both simple and detailed configurations:

```php
// Using the remove_actions function to manage hooks conditionally
remove_actions([
    [ 'tag' => 'wp_head', 'function' => 'wp_generator', 'priority' => 10 ],
    [ 'tag' => 'init', 'function' => 'disable_wp_cron', 'priority' => 10, 'condition' => function() { return defined('DISABLE_WP_CRON'); } ]
]);

// Simple format
remove_actions( [
    'admin_init' => 'redirect_non_admin_users',
    'wp_footer' => 'inject_footer_script'
] );

// Using remove_actions with a global priority
remove_actions([
    [ 'tag' => 'wp_head', 'function' => 'remove_wp_version' ],  // Default global priority applied
    [ 'tag' => 'wp_footer', 'function' => 'footer_custom_code', 'priority' => 5 ]  // Specific priority overriding the global
], 10);  // Global priority set to 10

// Using remove_actions with a global conditional callback
remove_actions([
    [ 'tag' => 'admin_bar_init', 'function' => 'remove_admin_bar' ],  // Global condition applied
    [ 'tag' => 'init', 'function' => 'custom_plugin_init', 'condition' => function() { return current_user_can('manage_options'); } ]  // Specific local condition
], 10, function() { return !is_admin(); });  // Global condition to apply only on the frontend
```

#### Setting Filters

Similarly, you can set filters using the `set_filters` function:

```php
// Dynamically set filters with optional conditions
set_filters([
    [ 'tag' => 'show_admin_bar', 'value' => false, 'condition' => function() { return !current_user_can('edit_posts'); } ],
    'auto_update_plugin' => true  // Always apply this filter
]);
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