define(['jquery'], function($)
{
    'use strict';

    var mageJsComponent = function()
    {
        const helper = {
            logDebug: function(message, object) {

                if (window.IsDebug === undefined || window.IsDebug === false) {
                    return;
                };
        
                if (message) {
                    console.log(message);
                };
        
                if (object) {
                    console.log(object);
                };
            },
            hideLockShroud: function() {
                document.getElementById('customers-canvas__shroud').style.display = 'none';
            },
            hideProductInfo: function() {
                document.getElementsByClassName('product-info-main').item(0).style.display = 'none';
                document.getElementsByClassName('product media').item(0).style.display = 'none';
                this.logDebug("Product info was hidden");
            },
            handleErrors(response) {
                if (!response.ok) {
                    throw Error(response.statusText);
                }
                return response;
            },
            async post(url, bodyData = "", shouldReturnData = false) {

                let encodedBody = this.JSON_to_URLEncoded(bodyData);
        
                return await fetch(url, {
                        method: 'POST',
                        body: encodedBody,
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        }
                    })
                    .then(this.handleErrors)
                    .then(response => {
                        if (shouldReturnData === true) {
                            let data = response.json();
                            this.logDebug("Response data is:", data);
                            return data;
                        }
                    })
                    .catch(error => console.error(error));
            },
            getFormKey() {
                const inputs = $('#product_addtocart_form > input[name="form_key"]');
                if (inputs.length) {
                    this.logDebug("FormKey is:", inputs[0].value);
                    return  inputs[0].value;
                } else {
                    console.error('Form key is not found.');
                }
            },
            buildProject(detail) {
        
                let hidden = detail.properties._hidden;
                hidden.snapshot = "";
                
                let project = {
                    _stateId: detail.properties._stateId,
                    _userId: detail.properties._userId,
                    _hidden: hidden
                }
        
                return project;
            },
            buildSubmitRequestBody(detail) {
                
                let project = this.buildProject(detail);
                
                let submitRequestBody = {
                    productId: detail.originalProductId,
                    projectJson: JSON.stringify(project),
                    imagesJson: JSON.stringify(detail.properties._hidden.images),
                    quantity: detail.quantity,
                    form_key: this.getFormKey(),
                    optionBasedProductSku: detail.sku
                };
        
                this.logDebug("SubmitRequestBody is:", submitRequestBody);
        
                return submitRequestBody;
            },
            buildSubmitUrl(config) {
                
                let submitUrl = new URL(config.pluginSettings.magentoBaseUrl + config.pluginSettings.addToCartUrl);
                this.logDebug("Submit url is:", submitUrl.href);
                return submitUrl.href;
            },
            JSON_to_URLEncoded(element, key = null, list = []) {
                list = list || [];
                if (typeof (element) === "object") {
                    for (let idx in element) {
                        if (element.hasOwnProperty(idx))
                            this.JSON_to_URLEncoded(element[idx], key ? key + "[" + idx + "]" : idx, list);
                    }
                } else {
                    list.push(key + "=" + encodeURIComponent(element));
                }
                return list.join("&");
            },
            async submitItem(event, config) {
                
                let submitUrl = this.buildSubmitUrl(config);
        
                let submitRequestBody = this.buildSubmitRequestBody(event.detail);
                
                await this.post(submitUrl, submitRequestBody);
            },
            redirectToCart: function(config) {
        
                let redirectToCartAfterAdd = config.pluginSettings.redirectToCartAfterAdd;
                
                if (!redirectToCartAfterAdd){
                    redirectToCartAfterAdd = true;
                }
        
                this.logDebug("RedirectToCartAfterAdd is:", redirectToCartAfterAdd);
        
                if (redirectToCartAfterAdd === 'false') {
                    return;
                }
        
                let cartUrl = config.pluginSettings.magentoBaseUrl + "checkout/cart/"
                this.logDebug("CartUrl is:", cartUrl);
        
                window.location.href = cartUrl;
            },
            async get(url) {
        
                return await fetch(url, {
                        method: 'GET'
                    })
                    .then(this.handleErrors)
                    .then(response => {
                        let data = response.json();
                        this.logDebug("Response data is:", data);
                        return data;
                    })
                    .catch(error => console.error(error));
            },
            async getSimpleEditorUrlFromBO(config) {
        
                let backOfficeUrl = config.pluginSettings.customersCanvasBaseUrl;
                let tenantId = config.commonSettings.tenantId;
                let productId = config.productModel.id;
                let storefrontId = config.commonSettings.storefrontId;
        
                let getIntegrationFromBoUrl = new URL(backOfficeUrl + "api/v1/tenants/" + tenantId + "/integrations/" + productId);
                getIntegrationFromBoUrl.searchParams.append("storefrontId", storefrontId);
        
                this.logDebug("GetIntegrationBoUrl is :", getIntegrationFromBoUrl.href);
        
                let data = await this.get(getIntegrationFromBoUrl);
        
                return this.ensureUrlWithSlash(data.result.simpleEditorUrl);
            },
            async setSimpleEditorScriptSources(config) {

                let simpleEditorUrl = await this.getSimpleEditorUrlFromBO(config);
                this.setSimpleEditorStyleSource(simpleEditorUrl);
                this.setSimpleEditorScriptSource(simpleEditorUrl);
            },
            setSimpleEditorStyleSource(simpleEditorUrl) {
                const simpleEditorStyleUrl = simpleEditorUrl + 'styles.css';
                this.addCss(simpleEditorStyleUrl, 'se-style');
            },
            async setSimpleEditorScriptSource(simpleEditorUrl) {
                const simpleEditorScriptUrl = simpleEditorUrl + 'editor.js';
                document.getElementById('se-source').setAttribute('src', simpleEditorScriptUrl);
            },
            addCss(cssUrl, cssId) {
                if (!document.getElementById(cssId))
                {
                    const head  = document.getElementsByTagName('head')[0];
                    const link  = document.createElement('link');
                    link.id   = cssId;
                    link.href = cssUrl.toString();
                    link.rel  = 'stylesheet';
                    link.type = 'text/css';
                    link.media = 'all';
                    head.appendChild(link);
                }
            },
            ensureUrlWithSlash(url) {
                url += !url.endsWith('/') ? '/' : '';
                return url;
            }
        };
        return helper;
    };

    return mageJsComponent;
});