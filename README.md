# TopBar Buddy - Announcement Bar, Notification Bar and Sticky Alert Bar

Contributors: rpetersen29, adeelraza_786hotmailcom, elearningevolve
Donate link: https://link.elearningevolve.com/self-pay
Requires at least: 6.0
Tested up to: 6.9
Stable tag: 1.1.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPLv2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

Display announcement bars, notification bars, and sticky top banners in WordPress with scheduling, start/end dates, and page targeting.

## Features

- **Free Date Scheduling** - Schedule banners with start and end dates using your WordPress site timezone
- **User-Friendly Interface** - Clean, intuitive settings page designed for non-technical users
- **Fully Customizable** - Colors, fonts, positioning, and custom CSS
- **Close Button** - Let users dismiss banners with GDPR-compliant cookies
- **Page Exclusions** - Hide banners on specific pages, posts, or URLs
- **Live Preview** - See your banner changes in real-time
- **Mobile Responsive** - Works perfectly on all devices
- **Theme Compatibility** - Works with popular themes including Divi, Astra, GeneratePress, and more

## Perfect For

- Sales and promotions
- Important announcements
- Maintenance notices
- Holiday messages
- Special events
- Cookie notices
- Newsletter signups

## Installation

1. Upload the plugin files to the `/wp-content/plugins/topbar-buddy` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to 'TopBar Buddy' in the WordPress admin menu to configure your banner.

## Screenshots

1. Banner settings page with live preview
2. Schedule banner with timezone display
3. Color customization options
4. Page exclusion settings
5. Custom CSS editor
6. Banner preview on frontend

## Frequently Asked Questions

### How do I schedule a banner?

Go to TopBar Buddy settings and use the "Schedule Banner" section. Set a start date/time and/or end date/time. The plugin uses your WordPress site timezone automatically.

### Can I hide the banner on specific pages?

Yes! Use the "Hide Banner On" section to exclude specific pages, posts, or URLs.

### Is the close button GDPR compliant?

Yes, the close button uses strictly necessary cookies which are GDPR compliant.

### Can I customize the banner colors?

Absolutely! Use the color pickers in the settings to customize background, text, link, and close button colors.

### Does this work on mobile?

Yes, the banners are fully responsive and work on all devices.

## Changelog

### 1.1.0
- **Divi theme compatibility** - Banner now displays correctly for all users (admin and non-admin) when using the Divi theme
- **Theme fallback** - For themes that do not support wp_body_open (e.g. Divi), the banner is output via wp_footer with fixed positioning so it always appears at the top
- **Code cleanup** - Removed unused script options and redundant code; single, consistent banner output

### 1.0.0
- Initial release of TopBar Buddy
- **Free Date Scheduling** - Schedule banners with start and end dates using WordPress site timezone
- **WYSIWYG Editor** - Rich text editor for banner content with full formatting support
- **Page Exclusions** - Hide banners on specific pages, posts, or custom URLs
- **Server-Side Timezone Handling** - All date checks use WordPress timezone

## Upgrade Notice

### 1.1.0
Divi theme compatibility and theme fallback so the banner shows for all users. Recommended upgrade.

### 1.0.0
Initial release of TopBar Buddy with free date scheduling feature.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GPLv2 or later License - see the [License URI](https://www.gnu.org/licenses/gpl-2.0.html) for details.

## Author

**eLearning Evolve**

- Website: [elearningevolve.com](https://elearningevolve.com/about/)
- WordPress.org: [TopBar Buddy](https://wordpress.org/plugins/topbar-buddy/)
