(function (context) {
    function ApiProxy() {
    }

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

    ApiProxy.prototype.sendMessage = function (targetDevice, message, options, callback) {
        if (typeof options === 'function') {
            callback = options;
            options = undefined;
        }

        var params = [
            targetDevice,
            message
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

    function callApiMethod(methodName, methodParams, cb) {
        jQuery.post(ctn_api_proxy_obj.ajax_url, {
            _ajax_nonce: ctn_api_proxy_obj.nonce,
            action: "call_api_method",
            post_id: ctn_api_proxy_obj.post_id,
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

    context.ctnApiProxy = new ApiProxy();
})(this);