# Catenis API Client for WordPress

Provides a way to use the Catenis Enterprise services from within WordPress.

This release (2.1.0) uses version 4.0 of the Catenis API PHP client library and targets version 0.9 of the Catenis Enterprise API.

## Description

Catenis API Client for WordPress enables (JavaScript) code on WordPress pages to interact with the Catenis Enterprise API.

### Enabling the Catenis API client

To enable the Catenis API client for a given WordPress page, go to the page's edit page and look for a section (meta box) named "Catenis API Client" below the page's main editing panel. Make sure the section is expanded, and check the `Load Catenis API Client` checkbox.

You can then choose to override the global settings used for instantiating the Catenis API client on that given page, like using a different device ID and its associated API access secret. Otherwise, whatever is configured in the plugin's global settings -- configured under "Settings" | "Catenis API Client" -- is going to be used.

### Using the Catenis API client

Once enabled, a global JavaScript variable named `ctnApiProxy` is made available on the page. That variable holds an object that functions as a proxy to the instantiated Catenis API client.

Use the *ctnApiProxy* variable to call the Catenis Enterprise API methods by invoking the corresponding method on that object.

For a reference of the available methods, please refer to the [Catenis API JavaScript Client](https://github.com/blockchainofthings/CatenisAPIClientJS) as it is functionally identical to the Catenis API Client for WordPress, except for notifications support and error handling.

### Notifications support

The notification feature on Catenis API Client for WordPress is almost identical to the one found on the Catenis API JavaScript client. The two noticeable differences are:

1. The Catenis API client object can emit a `comm-error` event.
1. The `open` event emitted by the WebSocket notification channel object may return an error.

Please refer to the "Receiving Notifications" section below for detailed information on how to receive Catenis notifications from within WordPress pages.

### Error handling

Errors that take place while calling the Catenis API methods are returned as standard JavaScript Error objects.

## Installation

### System requirements

The PHP executable should be in the system PATH so that the plugin can spawn the process used to handle notifications.

### Installation procedure

1. Upload the plugin files to the "/wp-content/plugins/" directory.
1. Activate the plugin through the "Plugins" menu in WordPress.
1. Go to "Settings" | "Catenis API Client" to configure the global settings for instantiating the Catenis API client.
1. A meta box named "Catenis API Client" will be available on every WordPress page's edit page. Use it to make the Catenis API client available from a given page, and optionally configure custom settings for instantiating the Catenis API client for that given page.

Please refer to [Catenis Enterprise API documentation](https://www.catenis.com/docs/api) for further information on accessing the Catenis Enterprise API.

## Frequently Asked Questions

### What client options settings should I use to connect with the Catenis Enterprise API sandbox environment? =

When doing it on the plugin's global settings ("Settings" | "Catenis API Client"), just leave all fields of the "Client Options" section blank.

However, when doing it on the "Catenis API Client" meta box on a WordPress page's edit page, use the following settings to make sure that all client options fields of the plugin's global settings are properly overridden:
- Host: `catenis.io`
- Environment: `sandbox`
- Secure Connection: `On`

## Receiving Notifications

### Instantiate WebSocket notification channel object

Create a WebSocket notification channel for a given Catenis notification event.

```javascript
var wsNotifyChannel = ctnApiProxy.createWsNotifyChannel(eventName);
```

### Add listeners

Add event listeners to monitor activity on the notification channel.

```javascript
ctnApiProxy.on('comm-error', function (error) {
    // Error communicating with Catenis notification process
});

wsNotifyChannel.on('open', function (error) {
    if (error) {
        // Error establishing underlying WebSocket connection
    }
    else {
        // Notification channel successfully open
    }
});

wsNotifyChannel.on('error', function (error) {
    // Error in the underlying WebSocket connection
});

wsNotifyChannel.on('close', function (code, reason) {
    // Underlying WebSocket connection has been closed
});

wsNotifyChannel.on('notify', function (eventData) {
    // Received notification
});
```

> **Note**: the 'comm-error' event is emitted by the Catenis API client object while all other events are emitted by the WebSocket notification channel object.

### Open the notification channel

Open the WebSocket notification channel to start receiving notifications.

```javascript
wsNotifyChannel.open(function (error) {
    if (err) {
        // Error sending command to open notification channel
    }
});
```

### Close the notification channel

Close the WebSocket notification channel to stop receiving notifications.

```javascript
wsNotifyChannel.close(function (error) {
    if (err) {
        // Error sending command to close notification channel
    }
});
```
