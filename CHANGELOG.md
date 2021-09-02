# Changelog

## [3.0.0] - 2021-09-02

### Breaking changes
- The list of current asset holders returned by the *listAssetHolders* method may now include an entry that reports the
  total asset amount that is currently migrated to foreign blockchains as per the new behavior of version 0.11 of the
  Catenis API. That entry differs from the regular ones in that the `holder` property is missing and a new boolean type
  property named `migrated`, the value of which is always `true`, is present.

### New features
- Added support for changes introduced by version 0.11 of the Catenis API: new asset export feature, including the new
  API methods Export Asset, Migrate Asset, Asset Export Outcome, Asset Migration Outcome, List Exported Assets, and
  List Asset Migrations.

## [2.2.0] - 2020-07-02

### New features
- Added support for changes introduced by version 0.10 of the Catenis API: new public API method Retrieve
 Message Origin.

## [2.1.0] - 2020-01-23

### Changes
- Updated dependency package Catenis API PHP client library to its latest version (4.0), which targets version 0.9 of the Catenis API.

### Fixes
- Added workaround to avoid that Catenis device credentials fields — *Device ID* and *API Access Secret* — be automatically filled by the web browser when editing a page. This behavior has been observed on Google Chrome 79.0 on Linux.

## [2.0.0] - 2019-08-26

### Breaking changes
- Changed interface of method *sendMessage*: parameters `message` and `targetDevice` have swapped positions.
- The object returned from a successful call to the *readMessage* method has a different structure.
- The `countExceeded` property of the object returned from a successful call to the *listMessages* method has been
 replaced with the new `hasMore` property.
- The `countExceeded` property of the object returned from a successful call to the *retrieveAssetIssuanceHistory*
 method has been replaced with the new `hasMore` property.
- Whole new (not backwards compatible) and improved notifications implementation.

### Other changes
- Updated dependency package Catenis API PHP client library to its latest version (3.0), which targets version 0.8 of the Catenis API.
- Added New *retrieveMessageProgress* method.
- Changed interface of *listMessages* method: first parameter renamed to `selector`; new parameters `limit` and `skip` added.
- Changed interface of *retrieveAssetIssuanceHistory* method: new parameters `limit` and `skip` added.

### New features
- Added support for changes introduced by version 0.7 of the Catenis API: log, send and read message in chunks.
- WebSocket notification channel object emits new `open` event.
- Added support for changes introduced by version 0.8 of the Catenis API: "pagination" (limit/skip) for API
 methods List Messages and Retrieve Asset Issuance History; new URI format for notification endpoints.
- New `Compression Threshold` settings used for instantiating the Catenis API client.

## [1.1.2] - 2019-06-08

### Fixes
- Internal adjustments to usage of WP Heartbeat API.

## [1.1.1] - 2019-01-04

### Fixes
* Fix issue with deleting plugin's data when plugin is uninstalled from multi-site WordPress environments.

## [1.1.0] - 2019-01-03

### New features
- Add support for Catenis notifications.
- **WARNING**: this version only works on Unix-like OS's like Linux and macOS. It does not work on Windows.

## [1.0.0] - 2019-12-28

### New features
- Initial working version. Exposes all Catenis API methods (as of version 0.6 of the Catenis API), but does not include support for notifications.
