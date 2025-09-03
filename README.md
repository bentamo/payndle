[![Build Status](https://www.travis-ci.org/wpugph/WordPress-Plugin-Template.svg?branch=master)](https://www.travis-ci.org/wpugph/WordPress-Plugin-Template)

WordPress Plugin Template - Payndle (Elite Barber)
=================================================

A comprehensive WordPress plugin for barbershop management with booking system, service management, and customer interface.

## Features

### Landing Page
- Modern, responsive barbershop landing page
- Service showcase with booking integration
- Professional design with customizable branding
- **Shortcode:** `[modern_barbershop_landing]`

### Service Management
- Complete business and service management panel
- Add, edit, and manage services
- Business information and contact details
- **Shortcode:** `[manager_panel]`

### Public Booking System
- Customer-facing service booking interface
- Real-time service selection and booking
- Payment method selection
- **Shortcode:** `[services_booking]`

### Booking History Management ⭐ NEW
- Comprehensive booking history view
- Advanced filtering by service, status, payment method, and date
- Booking status management (pending, confirmed, completed, cancelled)
- Payment method tracking (cash, card, GCash, PayMaya, online)
- **Shortcode:** `[booking_history]`

## Shortcodes Reference

| Shortcode | Purpose | Parameters |
|-----------|---------|------------|
| `[modern_barbershop_landing]` | Main landing page | None |
| `[manager_panel]` | Business management | None |
| `[services_booking]` | Public booking form | None |
| `[booking_history]` | Booking management | service_id, status, limit, show_filters |

### Booking History Parameters

```php
[booking_history service_id="5" status="pending" limit="50" show_filters="yes"]
```

- **service_id**: Filter by specific service ID
- **status**: Filter by booking status (pending, confirmed, completed, cancelled)
- **limit**: Maximum bookings to display (default: 20)
- **show_filters**: Show filter controls (yes/no, default: yes)

## Payment Methods Supported

- Cash Payment
- Credit/Debit Card
- GCash
- PayMaya
- Online Payment

## Database Tables

The plugin creates and manages several database tables:
- `wp_service_bookings` - Customer booking requests with payment info
- `wp_manager_services` - Available services
- `wp_manager_business` - Business information and settings

## Design Features

### Consistent Branding
- Gold accent color (#c9a74d) throughout all interfaces
- Professional dark theme elements
- Poppins and Playfair Display typography
- Responsive design for all screen sizes

### User Experience
- AJAX-powered interactions
- Real-time filtering and updates
- Mobile-friendly interface
- Accessibility considerations

## How do I use it?

You can simply copy the files out of this repo and rename everything as you need it, but to make things easier I have included a [shell script](https://github.com/hlashbrooke/WordPress-Plugin-Template/blob/master/build-plugin.sh) in this repo that will automatically copy the files to a new folder, remove all traces of the existing git repo, rename everything in the files according to your new plugin name, and initialise a new git repo in the folder if you choose to do so.

### Running the script

You can run the script just like you would run any shell script - it does not take any arguments, so you don't need to worry about that. Once you start the script it will ask for three things:

1. **Plugin name** - this must be the full name of your plugin, with correct capitalisation and spacing.
2. **Destination folder** - this will be the folder where your new plugin will be created - typically this will be your `wp-content/plugins` folder. You can provide a path that is relative to the script, or an absolute path - either will work.
3. **Include Grunt support (y/n)** - if you enter 'y' here then the Grunt files will be included in the new plugin folder.
4. **Initialise new git repo (y/n)** - if you enter 'y' here then a git repo will be initialised in the new plugin folder.

### API functions

As of v3.0 of this template, there are a few libraries built into it that will make a number of common tasks a lot easier. I will expand on these libraries in future versions.

#### Registering a new post type

Using the [post type API](https://github.com/hlashbrooke/WordPress-Plugin-Template/blob/master/includes/lib/class-wordpress-plugin-template-post-type.php) and the wrapper function from the main plugin class you can easily register new post types with one line of code. For example if you wanted to register a `listing` post type then you could do it like this:

`WordPress_Plugin_Template()->register_post_type( 'listing', __( 'Listings', 'wordpress-plugin-template' ), __( 'Listing', 'wordpress-plugin-template' ) );`

*Note that the `WordPress_Plugin_Template()` function name and the `wordpress-plugin-template` text domain will each be unique to your plugin after you have used the cloning script.*

This will register a new post type with all the standard settings. If you would like to modify the post type settings you can use the `{$post_type}_register_args` filter. See [the WordPress codex page](http://codex.wordpress.org/Function_Reference/register_post_type) for all available arguments.

#### Registering a new taxonomy

Using the [taxonomy API](https://github.com/hlashbrooke/WordPress-Plugin-Template/blob/master/includes/lib/class-wordpress-plugin-template-taxonomy.php) and the wrapper function from the main plugin class you can easily register new taxonomies with one line of code. For example if you wanted to register a `location` taxonomy that applies to the `listing` post type then you could do it like this:

`WordPress_Plugin_Template()->register_taxonomy( 'location', __( 'Locations', 'wordpress-plugin-template' ), __( 'Location', 'wordpress-plugin-template' ), 'listing' );`

*Note that the `WordPress_Plugin_Template()` function name and the `wordpress-plugin-template` text domain will each be unique to your plugin after you have used the cloning script.*

This will register a new taxonomy with all the standard settings. If you would like to modify the taxonomy settings you can use the `{$taxonomy}_register_args` filter. See [the WordPress codex page](http://codex.wordpress.org/Function_Reference/register_taxonomy) for all available arguments.

#### Defining your Settings Page Location

Using the filter {base}menu_settings you can define the placement of your settings page. Set the `location` key to `options`, `menu` or `submenu`. When using `submenu` also set the `parent_slug` key to your preferred parent menu, e.g `themes.php`. For example use the following code to let your options page display under the Appearance parent menu.

```php
$settings['location'] = 'submenu';
$settings['parent_slug'] = 'themes.php';
```

See respective codex pages for `location` option defined below:
https://codex.wordpress.org/Function_Reference/add_options_page
https://developer.wordpress.org/reference/functions/add_menu_page/
https://developer.wordpress.org/reference/functions/add_submenu_page/

#### Calling your Options

Using the [Settings API](https://github.com/hlashbrooke/WordPress-Plugin-Template/blob/master/includes/class-wordpress-plugin-template-settings.php) and the wrapper function from the main plugin class you can easily store options from the WP admin like text boxes, radio options, dropdown, etc. You can call the values by using `id` that you have set under the `settings_fields` function. For example you have the `id` - `text_field`, you can call its value by using `get_option('wpt_text_field')`. Take note that by default, this plugin is using a prefix of `wpt_` before the id that you will be calling, you can override that value by changing it under the `__construct` function `$this->base` variable;

## What does this template give me?

This template includes the following features:

+ Plugin headers as required by WordPress & WordPress.org
+ Readme.txt file as required by WordPress.org
+ Main plugin class
+ Full & minified Javascript files
+ Grunt.js support
+ Standard enqueue functions for the dashboard and the frontend
+ A library for easily registering a new post type
+ A library for easily registering a new taxonomy
+ A library for handling common admin functions (including adding meta boxes to any post type, displaying settings fields and display custom fields for posts)
+ A complete and versatile settings class like you see [here](http://www.hughlashbrooke.com/complete-versatile-options-page-class-wordpress-plugin/)
+ A .pot file to make localisation easier
+ Full text of the GPLv2 license

See the [changelog](https://github.com/hlashbrooke/WordPress-Plugin-Template/blob/master/changelog.txt) for a complete list of changes as the template develops.

## I've got an idea/fix for the template

If you would like to contribute to this template then please fork it and send a pull request. Please submit all pull requests to the `develop` branch. I'll merge the request if it fits into the goals for the template and credit you in the [changelog](https://github.com/hlashbrooke/WordPress-Plugin-Template/blob/master/changelog.txt).

## This template is amazing! How can I ever repay you?

There's no need to credit me in your code for this template, just go forth and use it to make the WordPress experience a little better.
