define([], function()
{
    'use strict';

    var mageJsComponent = function()
    {
        const helper = {
            getQueryParameters: function(url) {
                const params = {};
                if (!url) url = window.location.href;
                const queryIndex = url.lastIndexOf('?');
                const query = url.substring(queryIndex === -1 ? url.length : queryIndex + 1);
                const vars = query.split('&');
                for (let i = 0; i < vars.length; i++) {
                    const pair = vars[i].split('=');
                    params[pair[0]] = decodeURIComponent(pair[1]);
                }
                delete params[""];
                return params;
            },
            isParamExists: function(paramName) {
                const params = this.getQueryParameters();
                return !!params[paramName];
            },
            getUrlWithoutParam: function(paramName, sourceUrl) {
                let result = sourceUrl.split("?")[0];
                let param;
                let paramsArr = [];
                let queryString = (sourceUrl.indexOf("?") !== -1) ? sourceUrl.split("?")[1] : "";
        
                if (queryString !== "") {
                    paramsArr = queryString.split("&");
                    for (let i = paramsArr.length - 1; i >= 0; i--) {
                        param = paramsArr[i].split("=")[0];
                        if (param === paramName) {
                            paramsArr.splice(i, 1);
                        }
                    }
                    if (paramsArr.length > 0) {
                        result = result + "?" + paramsArr.join("&");
                    }
                }
                return result;
            },
            updateCurrentLocation: function(newUrl) {
                window.history.pushState('', document.title, newUrl);
            },
            cleanUrl: function() {
                let newUrl = location.protocol + '//' + location.host + location.pathname;
                if (newUrl !== window.location.href) {
                    this.updateCurrentLocation(newUrl);
                }
            }
        };       
        return helper;
    };

    return mageJsComponent;
});