=== WP Funnel Manager ===
Contributors: manfcarlo
Tags: funnel builder, page builder, sales funnels, landing page, marketing, sales, block, blocks, block editor, gutenberg, template, templates
Tested up to: 6.8
Stable tag: 1.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Organises content into multi-step funnels.

== Description ==

Have you ever needed to arrange your content in a multi-step sequence? Then WP Funnel Manager is what you need!

WP Funnel Manager works great for sales funnels, but it's also perfect for instruction manuals, online courses, or any kind of content that users must move through sequentially.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the plugin files to the `/wp-content/plugins/wp-funnel-manager` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Funnels screen in WordPress to start building your funnels

== How to Use This Plugin ==

After installation, you will find a Funnels screen in the WordPress admin menu. You will find the familiar editing screen to create your first funnel.

Funnels are fully integrated with the block templates feature of WordPress. This means you can edit the block template of your funnel by clicking on Template within the block editor. You can also add multiple funnels that share the same block template, or create new funnel types with their own block templates.

To add more steps to a funnel, find the funnel within its funnel type listing and click on Edit Steps. Then, click on Add New to add a new step to this funnel. Again, use the familiar editing screen to add content to this step. All steps of the funnel share the same block template.

Repeat this process for each step of the funnel. Make sure you use the WordPress menu order feature to arrange the steps of your funnel in the correct order.

== What Your Users Will Experience ==

When a user opens up one of your funnels for the first time, they will see the first step of the funnel. Below the post content is a set of navigation links to access subsequent steps of the funnel.

To access subsequent steps, they must navigate the funnel step by step sequentially. For example, they can only access step 3 after first navigating to step 2.

If the user returns to the funnel later, their progress in the funnel will be remembered. If the user is signed in with an account, the progress never expires. For guests, the progress is stored for 30 days.

== Frequently Asked Questions ==

= Why did my funnels disappear after switching theme? =

WP Funnel Manager requires the template editor, which is a new feature that some themes still don't support. Suggested actions are:

* Switch back to your previous theme that supports the template editor
* Contact your theme author and request the theme to [support the template editor](https://make.wordpress.org/core/2021/06/16/introducing-the-template-editor-in-wordpress-5-8/)

Once your active theme supports the template editor, all your funnels will be automatically restored.

= Why did the plugin automatically deactivate itself? =

This is a rare issue that the majority of users will never encounter.

WP Funnel Manager requires a core function, [wp_get_current_user](https://developer.wordpress.org/reference/functions/wp_get_current_user/), to be re-defined in a certain way. Only one plugin at a time is allowed to re-define any given core function, [see this article for more explanation.](https://codex.wordpress.org/Pluggable_Functions) Therefore, if it is detected that another plugin is already re-defining this core function in its own way, WP Funnel Manager is unable to operate.

By deactivating your other plugins one by one, and attemping to re-activate WP Funnel Manager, you can determine which one of your other plugins was causing the conflict with WP Funnel Manager. Once you have found it, you will be able to activate WP Funnel Manager and any other plugin not involved in the conflict.

= How do I suggest a new feature or submit a bug report? =

Through the WordPress support forum, or on the [GitHub page here.](https://github.com/carlomanf/wp-funnel-manager/issues)

== Changelog ==

= 1.4.0 =
* New natural funnel type (#15)
* Added user roles for funnel types (#17)
* Added new post type for funnel types (#17)
* Workaround solution for core ticket #52043
* Minimum versions lifted to WordPress 6.6 and PHP 7.2

= 1.3.2 =
* Fixed the user roles, which were previously not working properly

= 1.3.1 =
* Turned off templated funnels for themes that don't support templates
* Fixed error with 1.3.0 that modified data unrelated to the plugin
* Added more tags for plugin directory

= 1.3.0 =
* Compatibility with core version 5.8 (#14)
* Upgrade software architecture in preparation for later new features (#15)
* Replaced get_posts with WP_Query

= 1.2.0 =
* New funnel types for full site editing (#11)
  * New user roles and capabilities
  * One author per funnel
  * Removed hierarchical post type
  * Retain all old behaviour for existing funnels
* Use a namespace
* Fixed more bugs with trashing funnel (#6)
* New menu icon
* Added support for custom fields
* Apply exterior theme templates to interior (#12)
* Other bug fixes and enhancements

= 1.1.0 =
* Add readme for wordpress plugin directory
* Promote first interior when exterior is trashed
* Enable creation of funnel interiors

= 1.0.5 =
* Remove interiors from admin menu
* Prevent interiors being saved without an exterior

= 1.0.4 =
* Fix funnel permalinks

= 1.0.3 =
* Register new funnel post types
* Set up post parent selector
* Add a link to view and edit funnel interiors
