=== SA Integrations For Google Sheets ===
Contributors: sleekalgo
Tags: Google sheet integrations, Spreadsheets Integrations, WooCommerce Orders, Contact forms, WP Integrations
Requires at least: 5.1
Tested up to: 6.6.1
Requires PHP: 5.6
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This plugin connects your WordPress website with Google Sheets, enabling automatic synchronization of form submissions and WooCommerce order data.

== Description ==
**SA Integrations for Google Sheets** connects your WordPress site seamlessly with Google Sheets, providing automatic synchronization of data. Whether you're managing WooCommerce orders, form submissions, or other essential data, this plugin ensures your Google Sheets remain updated in real-time. Designed for flexibility and ease of use, it empowers you to map fields, manage integrations, and streamline workflows directly from your WordPress dashboard.  
Elevate your productivity and enjoy effortless data synchronization with a solution built for WordPress users who demand reliability and performance.

=== 🗝️ Key FEATURES: ===

=  🎉 Free Version - Features: =
* Seamless integration with the following plugins:
    * WooCommerce
    * Contact form 7
    * wpforms
    * Gravity Forms
* Allow user to Create, edit, and delete a single integration at a time.
* Field mapping feature to map spreadsheet columns with source data fields for streamlined data synchronization.


= 🌟 Premium Version - Features: 🎯 =
* All features in the free version and the following
* Support for creating and managing multiple integrations simultaneously.
* Enhanced integration with WooCommerce to support custom meta data maping in the integrations.
* Enhanced compatibility with forms plugins (Contact Form 7, WPForms, Gravity Forms) to support repeated fields.
* Advanced field mapping capabilities to ensure seamless data flow between WordPress and Google Sheets.
* Automatic updates to Google Sheets when WooCommerce orders or form submissions are modified.

Get the [premium version](https://www.sleekalgo.com/sa-integrations-for-google-sheets/) now 🚀.

= Documentation 📚 =
Discover how to use the plugin with our detailed and user-friendly [documentation](https://www.sleekalgo.com/sa-integrations-for-google-sheets/#installation-guide).

= 🌐 Translation Ready 🤩 =
*SA Integrations For Google Sheets* is compatible with Loco Translate, WPML, Polylang, TranslatePress, Weglot, and more. To contribute, add a new language via translate.wordpress.org.

= ⏩ Use of Third-Party Libraries 🛠️ =  
The *SA Integrations For Google Sheets* plugin has been built using the following third-party libraries to enhance functionality and user experience:
- [OAuth 2.0 Protocol](https://developers.google.com/identity/protocols/oauth2) - This plugin utilizes the OAuth 2.0 protocol to access Google APIs. It requests an access token from the Google Authorization Server using the client credentials of the connected Google account. The token is extracted from the server's response and included in each Google API request to ensure secure and authorized access.
- [PHP-JWT](https://github.com/firebase/php-jwt) - The plugin uses the PHP-JWT library to encode the request body for generating Google API access tokens as JSON Web Tokens (JWT). This approach adheres to [Google's recommended security practices](https://developers.google.com/identity/protocols/oauth2#:~:text=We%20strongly%20encourage%20you%20to%20use%20a%20library%20to%20perform%20these%20tasks) for enhanced security.
- [Google Drive API V3](https://developers.google.com/drive/api/reference/rest/v3) – This API is used to access the connected Google account and retrieve the list of created sheets. These sheets are then made available for feed management, enabling seamless data synchronization between the site and Google Sheets.
- [Google Sheets API V4](https://developers.google.com/sheets/api/reference/rest) – This API is used for reading and writing spreadsheet data. The mapped spreadsheet is automatically updated through the created feed map whenever new data is received at the source, ensuring continuous synchronization between the feed source and Google Sheets.
- [Mozart](https://github.com/coenjacobs/mozart) - To prevent conflicts with other plugins, this plugin incorporates the Mozart PHP Composer package as devlopment dependency. Mozart wraps all third-party Composer dependencies into the plugin's own namespace, ensuring compatibility even when multiple versions of the same package are in use.
- [React.js](https://react.dev/) – React.js is used to manage the plugin’s admin interface components for a dynamic and responsive user experience.
- [Ant Design](https://ant.design/) – We used Ant Design and [Ant Design ProComponents](https://procomponents.ant.design/en-US) to create a polished and intuitive UI for the plugin's admin interfaces.
- [WordPress Scripts](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/) – WordPress Scripts manage the plugin’s build system, as the admin interface is developed in React’s JSX syntax, with builds generated via WordPress's robust tooling.

= 😍 Useful Links 📌 =
* [Documentation](https://www.sleekalgo.com/sa-integrations-for-google-sheets/#installation-guide)
* [Support Forum](https://wordpress.org/support/plugin/sa-integrations-for-google-sheets/)
* [Translations](https://translate.wordpress.org/projects/wp-plugins/sa-integrations-for-google-sheets/)

= Become a Contributor 👨🏻‍💻 =
*SA Integrations For Google Sheets* is an open-source project, and we welcome contributors to be part of our vibrant community! Help us improve the plugin and make it even better - [GitHub Link](https://github.com/Sleek-Algo/sa-integrations-for-google-sheets).

== 🤝 Support 👀 ==
We offers full support on the WordPress.org [Forum](https://wordpress.org/support/plugin/sa-integrations-for-google-sheets/). Before starting a new thread, please check available [documentation](https://www.sleekalgo.com/sa-integrations-for-google-sheets/#installation-guide) and other support threads. Leave a clear and concise description of your issue, and we will respond as soon as possible.

==🐞 BUG REPORTS 📝 ==
Find a bug in *SA Integrations For Google Sheets* ? We welcome your bug reports! Please report bugs in the *SA Integrations For Google Sheets* [repository on GitHub](https://github.com/Sleek-Algo/sa-integrations-for-google-sheets). Note that GitHub is not a support forum but an efficient platform for addressing and resolving issues efficiently.

== Installation ==
* In your WordPress dashboard, choose Plugins > Add new.
* Search for the plugin with the search bar in the top right corner using the keywords "Google Sheets Integrations". A number of results may appear.
* After finding the plugin in the results, click Install Now. You can also click the plugin name to view more details about it.

For further details please check our [Installation Guide](https://www.sleekalgo.com/sa-integrations-for-google-sheets/#installation-guide)

== Upgrade Notice ==  
Upgrade to the **Premium Version** to unlock the Premium Version advanced features.
  

== Frequently Asked Questions ==
= How much integrations can i create? =
 You can just create one integration in the free version if you want to create more than one integration so please purchase the [premium version](https://www.sleekalgo.com/sa-integrations-for-google-sheets).



== Changelog ==

= 1.0.0 =
Initial release.