define([
    'jquery'
], function ($) {
    'use strict';

    function logTokenState(config) {
        const token = config && config.userInfo ? config.userInfo.token : '';
        const tokenLength = typeof token === 'string' ? token.length : 0;
        console.log('[CustomersCanvas][HandyEditor] token length:', tokenLength);
    }

    function fetchUserToken(config) {
        if (!config || !config.userInfo || !config.userInfo.tokenUrl) {
            console.warn('[CustomersCanvas][HandyEditor] tokenUrl is missing.');
            return $.Deferred().resolve('').promise();
        }

        return $.ajax({
            url: config.userInfo.tokenUrl,
            method: 'GET',
            dataType: 'json',
            cache: false
        })
            .then(function(response) {
                if (response && response.success && response.token) {
                    config.userInfo.token = response.token;
                    return response.token;
                }

                config.userInfo.token = '';
                console.warn('[CustomersCanvas][HandyEditor] token endpoint returned empty token.', response);
                return '';
            })
            .catch(function(error) {
                config.userInfo.token = '';
                console.error('[CustomersCanvas][HandyEditor] token request failed.', error);
                return '';
            });
    }

    var mageJsComponent = function (config, node) {
        const marker = $(node);
        const configPromise = fetchUserToken(config).always(function () {
            logTokenState(config);
            marker.data('handyEditorConfig', config);
        }).then(function () {
            return config;
        });

        marker.data('handyEditorConfigPromise', configPromise);
    };

    return mageJsComponent;
});
