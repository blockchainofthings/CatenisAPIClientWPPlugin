(function (context) {
    function ApiProxy() {
    }

    // Make ApiProxy to inherit from EventEmitter
    heir.inherit(ApiProxy, EventEmitter, true);

    ApiProxy.prototype.logMessage = function (message, options, callback) {
        if (typeof options === 'function') {
            callback = options;
            options = undefined;
        }

        var params = [
            message
        ];

        if (options) {
            params.push(options);
        }

        callApiMethod('logMessage', params, callback);
    };

    ApiProxy.prototype.sendMessage = function (message, targetDevice, options, callback) {
        if (typeof options === 'function') {
            callback = options;
            options = undefined;
        }

        var params = [
            message,
            targetDevice
        ];

        if (options) {
            params.push(options);
        }

        callApiMethod('sendMessage', params, callback);
    };

    ApiProxy.prototype.readMessage = function (messageId, encoding, callback) {
        if (typeof encoding === 'function') {
            callback = encoding;
            encoding = undefined;
        }

        var params = [
            messageId
        ];

        if (encoding) {
            params.push(encoding);
        }

        callApiMethod('readMessage', params, callback);
    };

    ApiProxy.prototype.retrieveMessageContainer = function (messageId, callback) {
        var params = [
            messageId
        ];

        callApiMethod('retrieveMessageContainer', params, callback);
    };

    ApiProxy.prototype.retrieveMessageProgress = function (messageId, callback) {
        var params = [
            messageId
        ];

        callApiMethod('retrieveMessageProgress', params, callback);
    };

    ApiProxy.prototype.listMessages = function (options, callback) {
        if (typeof options === 'function') {
            callback = options;
            options = undefined;
        }

        var params = [];

        if (options) {
            params.push(options);
        }

        callApiMethod('listMessages', params, callback);
    };

    ApiProxy.prototype.listPermissionEvents = function (callback) {
        callApiMethod('listPermissionEvents', null, callback);
    };

    ApiProxy.prototype.retrievePermissionRights = function (eventName, callback) {
        var params = [
            eventName
        ];

        callApiMethod('retrievePermissionRights', params, callback);
    };

    ApiProxy.prototype.setPermissionRights = function (eventName, rights, callback) {
        var params = [
            eventName,
            rights
        ];

        callApiMethod('setPermissionRights', params, callback);
    };

    ApiProxy.prototype.checkEffectivePermissionRight = function (eventName, deviceId, isProdUniqueId, callback) {
        if (typeof isProdUniqueId === 'function') {
            callback = isProdUniqueId;
            isProdUniqueId = undefined;
        }

        var params = [
            eventName,
            deviceId
        ];

        if (isProdUniqueId) {
            params.push(isProdUniqueId);
        }

        callApiMethod('checkEffectivePermissionRight', params, callback);
    };

    ApiProxy.prototype.listNotificationEvents = function (callback) {
        callApiMethod('listNotificationEvents', null, callback);
    };

    ApiProxy.prototype.retrieveDeviceIdentificationInfo = function (deviceId, isProdUniqueId, callback) {
        if (typeof isProdUniqueId === 'function') {
            callback = isProdUniqueId;
            isProdUniqueId = undefined;
        }

        var params = [
            deviceId
        ];

        if (isProdUniqueId) {
            params.push(isProdUniqueId);
        }

        callApiMethod('retrieveDeviceIdentificationInfo', params, callback);
    };

    ApiProxy.prototype.issueAsset = function (assetInfo, amount, holdingDevice, callback) {
        if (typeof holdingDevice === 'function') {
            callback = holdingDevice;
            holdingDevice = undefined;
        }

        var params = [
            assetInfo,
            amount
        ];

        if (holdingDevice) {
            params.push(holdingDevice);
        }

        callApiMethod('issueAsset', params, callback);
    };

    ApiProxy.prototype.reissueAsset = function (assetId, amount, holdingDevice, callback) {
        if (typeof holdingDevice === 'function') {
            callback = holdingDevice;
            holdingDevice = undefined;
        }

        var params = [
            assetId,
            amount
        ];

        if (holdingDevice) {
            params.push(holdingDevice);
        }

        callApiMethod('reissueAsset', params, callback);
    };

    ApiProxy.prototype.transferAsset = function (assetId, amount, receivingDevice, callback) {
        var params = [
            assetId,
            amount,
            receivingDevice
        ];

        callApiMethod('transferAsset', params, callback);
    };

    ApiProxy.prototype.retrieveAssetInfo = function (assetId, callback) {
        var params = [
            assetId
        ];

        callApiMethod('retrieveAssetInfo', params, callback);
    };

    ApiProxy.prototype.getAssetBalance = function (assetId, callback) {
        var params = [
            assetId
        ];

        callApiMethod('getAssetBalance', params, callback);
    };

    ApiProxy.prototype.listOwnedAssets = function (limit, skip, callback) {
        if (typeof limit === 'function') {
            callback = limit;
            limit = undefined;
            skip = undefined;
        }
        else if (typeof skip === 'function') {
            callback = skip;
            skip = undefined;
        }

        var params = [];

        if (limit) {
            params.push(limit);
        }

        if (skip) {
            params.push(skip);
        }

        callApiMethod('listOwnedAssets', params, callback);
    };

    ApiProxy.prototype.listIssuedAssets = function (limit, skip, callback) {
        if (typeof limit === 'function') {
            callback = limit;
            limit = undefined;
            skip = undefined;
        }
        else if (typeof skip === 'function') {
            callback = skip;
            skip = undefined;
        }

        var params = [];

        if (limit) {
            params.push(limit);
        }

        if (skip) {
            params.push(skip);
        }

        callApiMethod('listIssuedAssets', params, callback);
    };

    ApiProxy.prototype.retrieveAssetIssuanceHistory = function (assetId, startDate, endDate, callback) {
        if (typeof startDate === 'function') {
            callback = startDate;
            startDate = undefined;
            endDate = undefined;
        }
        else if (typeof endDate === 'function') {
            callback = endDate;
            endDate = undefined;
        }

        var params = [
            assetId
        ];

        if (startDate) {
            params.push(startDate);
        }

        if (endDate) {
            params.push(endDate);
        }

        callApiMethod('retrieveAssetIssuanceHistory', params, callback);
    };

    ApiProxy.prototype.listAssetHolders = function (assetId, limit, skip, callback) {
        if (typeof limit === 'function') {
            callback = limit;
            limit = undefined;
            skip = undefined;
        }
        else if (typeof skip === 'function') {
            callback = skip;
            skip = undefined;
        }

        var params = [
            assetId
        ];

        if (limit) {
            params.push(limit);
        }

        if (skip) {
            params.push(skip);
        }

        callApiMethod('listAssetHolders', params, callback);
    };

    ApiProxy.prototype.openNotifyChannel = function (eventName, callback) {
        openNotifyChannel(eventName, callback);
    };

    ApiProxy.prototype.closeNotifyChannel = function (eventName, callback) {
        closeNotifyChannel(eventName, callback);
    };

    function callApiMethod(methodName, methodParams, cb) {
        jQuery.post(context.ctn_api_proxy_obj.ajax_url, {
            _ajax_nonce: context.ctn_api_proxy_obj.nonce,
            action: "call_api_method",
            post_id: context.ctn_api_proxy_obj.post_id,
            client_uid: context.ctn_api_proxy_obj.client_uid,
            method_name: methodName,
            method_params: JSON.stringify(methodParams || [])
        }, function (data) {
            // Success
            cb(undefined, data.data);
        }, 'json')
        .fail(function (jqXHR, textStatus, errorThrown) {
            // Failure
            let errMessage;

            console.log('JSON response:', jqXHR.responseJSON);
            console.log('Error thrown:', errorThrown);

            if (jqXHR.status >= 100) {
                errMessage = '[' + jqXHR.status + '] - ' + (typeof jqXHR.responseJSON === 'object' && jqXHR.responseJSON !== null && jqXHR.responseJSON.data
                    ? (typeof jqXHR.responseJSON.data === 'string' ? jqXHR.responseJSON.data : (typeof jqXHR.responseJSON.data === 'object'
                    && jqXHR.responseJSON.data !== null && typeof jqXHR.responseJSON.data.message === 'string' ? jqXHR.responseJSON.data.message : jqXHR.statusText))
                    : jqXHR.statusText);
            } else {
                errMessage = 'Ajax client error' + (textStatus ? ' (' + textStatus + ')' : '') + (errorThrown ? ': ' + errorThrown : '');
            }

            // Display returned error
            console.log(errMessage);
            cb(new Error(errMessage));
        });
    }

    function openNotifyChannel(eventName, cb) {
        jQuery.post(context.ctn_api_proxy_obj.ajax_url, {
            _ajax_nonce: context.ctn_api_proxy_obj.nonce,
            action: "open_notify_channel",
            post_id: context.ctn_api_proxy_obj.post_id,
            client_uid: context.ctn_api_proxy_obj.client_uid,
            event_name: eventName
        }, function (data) {
            // Success
            cb(undefined, data.data);
        }, 'json')
        .fail(function (jqXHR, textStatus, errorThrown) {
            // Failure
            let errMessage;

            console.log('JSON response:', jqXHR.responseJSON);
            console.log('Error thrown:', errorThrown);

            if (jqXHR.status >= 100) {
                errMessage = '[' + jqXHR.status + '] - ' + (typeof jqXHR.responseJSON === 'object' && jqXHR.responseJSON !== null && jqXHR.responseJSON.data
                        && typeof jqXHR.responseJSON.data === 'string' ? jqXHR.responseJSON.data : jqXHR.statusText);
            } else {
                errMessage = 'Ajax client error' + (textStatus ? ' (' + textStatus + ')' : '') + (errorThrown ? ': ' + errorThrown : '');
            }

            // Display returned error
            console.log(errMessage);
            cb(new Error(errMessage));
        });
    }

    function closeNotifyChannel(eventName, cb) {
        jQuery.post(context.ctn_api_proxy_obj.ajax_url, {
            _ajax_nonce: context.ctn_api_proxy_obj.nonce,
            action: "close_notify_channel",
            post_id: context.ctn_api_proxy_obj.post_id,
            client_uid: context.ctn_api_proxy_obj.client_uid,
            event_name: eventName
        }, function (data) {
            // Success
            cb(undefined, data.data);
        }, 'json')
        .fail(function (jqXHR, textStatus, errorThrown) {
            // Failure
            let errMessage;

            console.log('JSON response:', jqXHR.responseJSON);
            console.log('Error thrown:', errorThrown);

            if (jqXHR.status >= 100) {
                errMessage = '[' + jqXHR.status + '] - ' + (typeof jqXHR.responseJSON === 'object' && jqXHR.responseJSON !== null && jqXHR.responseJSON.data
                && typeof jqXHR.responseJSON.data === 'string' ? jqXHR.responseJSON.data : jqXHR.statusText);
            } else {
                errMessage = 'Ajax client error' + (textStatus ? ' (' + textStatus + ')' : '') + (errorThrown ? ': ' + errorThrown : '');
            }

            // Display returned error
            console.log(errMessage);
            cb(new Error(errMessage));
        });
    }

    context.ctnApiProxy = new ApiProxy();

    jQuery(document).ready(function () {
        jQuery(document).on('heartbeat-send', function (event, data) {
            // Send client UID
            data['catenis-api-client_client_uid'] = context.ctn_api_proxy_obj.client_uid;
        });

        jQuery(document).on('heartbeat-tick', function (event, data) {
            if (!('catenis-api-client_response' in data)) {
                // No response from Catenis API Client plugin back-end. Just return
                return;
            }
            
            var ctn_api_client_response = data['catenis-api-client_response'];

            // Process returned data
            if (!('success' in ctn_api_client_response)) {
                console.error('Missing required ctn_api_client_response field from heartbeat response');
                return;
            }

            if (!ctn_api_client_response.success) {
                // Error heartbeat response. Emit error event from ApiProxy object
                context.ctnApiProxy.emitEvent('comm_error', [ctn_api_client_response.error]);
            }
            else {
                // Success heartbeat response. Look for notification process commands returned
                if ('notifyCommands' in ctn_api_client_response) {
                    ctn_api_client_response.notifyCommands.split('|').forEach(function (jsonCommand) {
                        var command = JSON.parse(jsonCommand);

                        if (command) {
                            switch (command.cmd) {
                                case 'notification':
                                    // Emit corresponding event from ApiProxy object
                                    context.ctnApiProxy.emitEvent('notification', [command.data.eventName, command.data.eventData]);
                                    break;

                                case 'notify_channel_opened':
                                    // Emit corresponding event from ApiProxy object
                                    context.ctnApiProxy.emitEvent('notify_channel_opened', [command.data.eventName, command.data.success, command.data.error]);
                                    break;

                                case 'notify_channel_error':
                                    // Emit corresponding event from ApiProxy object
                                    context.ctnApiProxy.emitEvent('notify_channel_error', [command.data.eventName, command.data.error]);
                                    break;

                                case 'notify_channel_closed':
                                    // Emit corresponding event from ApiProxy object
                                    context.ctnApiProxy.emitEvent('notify_channel_closed', [command.data.eventName, command.data.code, command.data.reason]);
                                    break;
                            }
                        }
                    });
                }
            }
        });

        jQuery(document).on('heartbeat-error', function(event, jqXHR, textStatus, error) {
            console.error('Heartbeat error: ' + (error ? error : textStatus));
        });
    });
})(this);