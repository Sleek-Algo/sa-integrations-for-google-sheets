# SA Integrations For Google Sheets

## Description

This plugin connects your WordPress website with Google Sheets, enabling automatic synchronization of form submissions and WooCommerce order data.

## Version 1.0.0

**Prerequisites**
- PHP => 5.6

### 🎉 Free Version - Features
* Seamless integration with the following plugins:
    * WooCommerce
    * Contact form 7
    * wpforms
    * Gravity Forms
* Allow user to Create, edit, and delete a single integration at a time.
* Field mapping feature to map spreadsheet columns with source data fields for streamlined data synchronization.


### 🌟 Premium Version - Features: 🎯
* All features in the free version and the following
* Support for creating and managing multiple integrations simultaneously.
* Enhanced integration with WooCommerce to support custom meta data maping in the integrations.
* Enhanced compatibility with forms plugins (Contact Form 7, WPForms, Gravity Forms) to support repeated fields.
* Advanced field mapping capabilities to ensure seamless data flow between WordPress and Google Sheets.
* Automatic updates to Google Sheets when WooCommerce orders or form submissions are modified.

Get the [premium version](https://www.sleekalgo.com/sa-integrations-for-google-sheets/) now 🚀.


### Documentation 📚
Discover how to use the plugin with our detailed and user-friendly [documentation](https://www.sleekalgo.com/sa-integrations-for-google-sheets/#installation-guide).

### 🌐 Translation Ready 🤩
*SA Integrations For Google Sheets* is compatible with Loco Translate, WPML, Polylang, TranslatePress, Weglot, and more. To contribute, add a new language via translate.wordpress.org.

### ⏩ Use of Third-Party Libraries 🛠️ 
The *SA Integrations For Google Sheets* plugin has been built using the following third-party libraries to enhance functionality and user experience:
- [OAuth 2.0 Protocol](https://developers.google.com/identity/protocols/oauth2) - This plugin utilizes the OAuth 2.0 protocol to access Google APIs. It requests an access token from the Google Authorization Server using the client credentials of the connected Google account. The token is extracted from the server's response and included in each Google API request to ensure secure and authorized access.
- [PHP-JWT](https://github.com/firebase/php-jwt) - The plugin uses the PHP-JWT library to encode the request body for generating Google API access tokens as JSON Web Tokens (JWT). This approach adheres to [Google's recommended security practices](https://developers.google.com/identity/protocols/oauth2#:~:text=We%20strongly%20encourage%20you%20to%20use%20a%20library%20to%20perform%20these%20tasks) for enhanced security.
- [Google Drive API V3](https://developers.google.com/drive/api/reference/rest/v3) – This API is used to access the connected Google account and retrieve the list of created sheets. These sheets are then made available for feed management, enabling seamless data synchronization between the site and Google Sheets.
- [Google Sheets API V4](https://developers.google.com/sheets/api/reference/rest) – This API is used for reading and writing spreadsheet data. The mapped spreadsheet is automatically updated through the created feed map whenever new data is received at the source, ensuring continuous synchronization between the feed source and Google Sheets.
- [Mozart](https://github.com/coenjacobs/mozart) - To prevent conflicts with other plugins, this plugin incorporates the Mozart PHP Composer package as devlopment dependency. Mozart wraps all third-party Composer dependencies into the plugin's own namespace, ensuring compatibility even when multiple versions of the same package are in use.
- [React.js](https://react.dev/) – React.js is used to manage the plugin’s admin interface components for a dynamic and responsive user experience.
- [Ant Design](https://ant.design/) – We used Ant Design and [Ant Design ProComponents](https://procomponents.ant.design/en-US) to create a polished and intuitive UI for the plugin's admin interfaces.
- [WordPress Scripts](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/) – WordPress Scripts manage the plugin’s build system, as the admin interface is developed in React’s JSX syntax, with builds generated via WordPress's robust tooling.

### 😍 Useful Links 📌
* [Documentation](https://www.sleekalgo.com/sa-integrations-for-google-sheets/#installation-guide)
* [Support Forum](https://wordpress.org/support/plugin/sa-integrations-for-google-sheets/)
* [Translations](https://translate.wordpress.org/projects/wp-plugins/sa-integrations-for-google-sheets/)

### Become a Contributor 👨🏻‍💻
*SA Integrations For Google Sheets* is an open-source project, and we welcome contributors to be part of our vibrant community! Help us improve the plugin and make it even better.

### 🤝 Support 👀
We offers full support on the WordPress.org [Forum](https://wordpress.org/support/plugin/sa-integrations-for-google-sheets/). Before starting a new thread, please check available [documentation](https://www.sleekalgo.com/sa-integrations-for-google-sheets/#installation-guide) and other support threads. Leave a clear and concise description of your issue, and we will respond as soon as possible.


## Installation
1. Upload the plugin files to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.

## Development Environment Setup

### PHP Setup

Install PHP dependencies by running:
```bash
composer run setup-development
```

Check for WP Coding Standards errors with:
```bash
composer run wpcs
```

Fix any fixable WP Coding Standards errors by running:
```bash
composer run wpcs:fix
```

### Build Setup

Install NPM dependencies with:
```bash
npm install
```

Watch for changes and automatically rebuild CSS and JS assets:
```bash
npm run start
```

Format component files:
```bash
npm run format
```

Update the plugin translation files using WP-CLI (requires terminal access to WP-CLI):
```bash
npm run translate
```

Generate the final build of assets:
```bash
npm run build
```

Generate a development version build:
```bash
npm run build:development
```

Generate a production version build:
```bash
npm run build:production
```

Create a plugin zip file (located in sahcfwc-backups under the wp installation folder, with separate folders for development and production versions):
```bash
npm run build:zip
```

## Changelog

[See all version changelogs](CHANGELOG.md)