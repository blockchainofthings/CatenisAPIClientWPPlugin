=== Catenis API Client for WordPress ===
Contributors: catenisadmin
Tags: Catenis, Catenis Enterprise API, Blockchain of Things, blockchain
Requires at least: 4.0
Tested up to: 5.0.2
Requires PHP: 5.6
Stable tag: 2.0.0
License: MIT

Provides a way to use the Catenis Enterprise services from within WordPress

== Description ==

Catenis API Client for WordPress enables (JavaScript) code on WordPress pages to interact with the Catenis Enterprise API.

= Enabling the Catenis API client =

To enable the Catenis API client for a given WordPress page, go to the page's edit page, and look for a section (meta box) named "Catenis API Client" below the page's main editing panel. Make sure the section is expanded, and check the `Load Catenis API Client` checkbox.

You can then choose to override the global settings used for instantiating the Catenis API client on that given page, like using a different device ID and its associated API access secret. Otherwise, whatever is configured in the plugin's global settings -- configured under "Settings" | "Catenis API Client" -- is going to be used.

= Using the Catenis API client =

Once enabled, a global JavaScript variable named `ctnApiClient` is made available on the page. That variable holds the instantiated Catenis API client object.

Use the `ctnApiClient` variable to call the Catenis Enterprise API methods by invoking the corresponding method on that object.

For a reference of the available methods, please refer to the [Catenis API JavaScript Client](https://github.com/blockchainofthings/CatenisAPIClientJS) as it is functionally identical to the Catenis API Client for WordPress, except for notifications support and error handling.

= Notifications support =

Unlike the Catenis API JavaScript client, notifications from the Catenis system are handled directly from the Catenis API client object.

Please refer to the "Receiving Notifications" section below for detailed information on how to receive Catenis notifications from within WordPress pages.

= Error handling =

Errors that take place while calling the Catenis API methods are returned as standard JavaScript Error objects.

== Installation ==

1. Upload the plugin files to the "/wp-content/plugins/" directory.
1. Activate the plugin through the "Plugins" menu in WordPress.
1. Go to "Settings" | "Catenis API Client" to configure the global settings for instantiating the Catenis API client.
1. A meta box named "Catenis API Client" will be available on every WordPress page's edit page. Use it to make the Catenis API client available from a given page, and optionally configure custom settings for instantiating the Catenis API client for that given page.

Please refer to [Catenis Enterprise API documentation](https://www.catenis.com/docs/api) for further information on accessing the Catenis Enterprise API.

== Frequently Asked Questions ==

= What client options settings should I use to connect with the Catenis Enterprise API sandbox environment? =

When doing it on the plugin's global settings ("Settings" | "Catenis API Client"), just leave all fields of the "Client Options" section blank.

However, when doing it on the "Catenis API Client" meta box on a WordPress page's edit page, use the following settings to make sure that all client options fields of the plugin's global settings are properly overridden:
- Host: `catenis.io`
- Environment: `sandbox`
- Secure Connection: `On`

== Screenshots ==

1. The plugin's global settings menu

2. The "Catenis API Client" meta box on a WordPress page's edit page.

== Changelog ==

= 2.0.0 =
* Update Catenis API client for PHP to its latest version (2.1.1), which targets version 0.7 of the Catenis Enterprise API.
* Changes to accommodate changes introduced by the new version of the Catenis API client for PHP, including: a) change in the interface of the Send Message API method; and b) addition of new Retrieve Message Progress API method.

= 1.1.2 =
* Internal adjustments to usage of WP Heartbeat API.

= 1.1.1 =
* Fix issue with deleting plugin's data when plugin is uninstalled from multi-site WordPress environments.

= 1.1.0 =
* Add support for Catenis notifications.
* **WARNING**: this version only works on Unix-like OS's like Linux and macOS. It does not work on Windows.

= 1.0.0 =
* Initial working version. Exposes all Catenis API methods (as of version 0.6 of the Catenis API), but does not include support for notifications.

== Upgrade Notice ==

= 2.0.0 =
Upgrade to this version to take advantage of the new features found in version 0.7 of the Catenis Enterprise API.

= 1.1.2 =
All users are advised to upgrade to this version.

= 1.1.1 =
Upgrade to this version if using the plugin in a multi-site WordPress environment.

= 1.1.0 =
All users are advised to upgrade to this version even if not planning to use notifications since it also adds several enhancements and fixes to the basic functionality.

== Receiving Notifications ==

= Add listeners =

Add event listeners to monitor activity on notification channels.

`
ctnApiClient.on('comm-error', function (error) {
    // Error communicating with Catenis notification process
});

ctnApiClient.on('notify-channel-opened', function (eventName, success, error) {
    if (success) {
        // Notification channel successfully open
    }
    else {
        // Error establishing underlying WebSocket connection
    }
});

ctnApiClient.on('notify-channel-error', function (eventName, error) {
    // Error in the underlying WebSocket connection
});

ctnApiClient.on('notify-channel-closed', function (eventName, code, reason) {
    // Underlying WebSocket connection has been closed
});

ctnApiClient.on('notification', function (eventName, eventData) {
    // Received notification
});
`

> **Note**: except for the `comm-error` event, the first argument of the event listener functions -- named `eventName` -- identifies the Catenis notification event for which that event applies.

= Open notification channel =

Open a notification channel for a given Catenis notification event.

`
ctnApiClient.openNotifyChannel(eventName, function (error) {
    if (err) {
        // Error from calling method
    }
});
`

= Close notification channel =

Close a notification channel to stop receiving notifications for that given Catenis notification event.

`
ctnApiClient.closeNotifyChannel(eventName, function (error) {
    if (err) {
        // Error from calling method
    }
});
`
