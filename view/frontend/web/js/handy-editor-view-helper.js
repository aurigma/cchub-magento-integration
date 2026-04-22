define([
    'jquery'
], function ($) {
    'use strict';

    var mageJsComponent = function () {
        const helper = {
            defaultWorkflowComponent: 'handy-editor',

            getMarker: function () {
                return document.getElementById('customers-canvas__handy-editor-marker');
            },

            getEditor: function () {
                return document.getElementById('fullscreen-editor');
            },

            getOverlay: function () {
                return document.getElementById('fullscreen-editor-overlay');
            },

            showOverlay: function () {
                const overlay = this.getOverlay();
                const editor = this.getEditor();

                if (overlay) {
                    overlay.style.display = 'flex';
                }

                if (editor) {
                    editor.style.display = 'block';
                    editor.style.visibility = 'visible';
                }
            },

            hideOverlay: function () {
                const overlay = this.getOverlay();

                if (overlay) {
                    overlay.style.display = 'none';
                }
            },

            removeEditorArtifacts: function () {
                const dnd = document.getElementById('ccDndDiv');
                if (dnd && dnd.parentNode) {
                    dnd.parentNode.removeChild(dnd);
                }

                document.querySelectorAll('.eui-context-menu__container').forEach(function (node) {
                    if (node.parentNode) {
                        node.parentNode.removeChild(node);
                    }
                });

                document.querySelectorAll('.cdk-overlay-container').forEach(function (node) {
                    if (node.parentNode) {
                        node.parentNode.removeChild(node);
                    }
                });
            },

            closeEditor: function () {
                const editor = this.getEditor();

                if (editor) {
                    editor.style.display = 'none';
                    editor.style.visibility = 'hidden';
                }

                this.hideOverlay();
                this.removeEditorArtifacts();
            },

            getStoredConfigPromise: function (marker) {
                const markerNode = marker || this.getMarker();

                if (!markerNode) {
                    return Promise.reject(new Error('Handy marker is not found.'));
                }

                const markerElement = $(markerNode);
                const configPromise = markerElement.data('handyEditorConfigPromise');

                if (configPromise && typeof configPromise.then === 'function') {
                    return configPromise;
                }

                const config = markerElement.data('handyEditorConfig');

                if (config) {
                    return Promise.resolve(config);
                }

                return Promise.reject(new Error('Handy config is not ready yet.'));
            },

            ensureUrlWithSlash: function (url) {
                if (!url) {
                    return '';
                }

                return url.endsWith('/') ? url : url + '/';
            },

            addCss: function (cssUrl, cssId) {
                if (!cssUrl || document.getElementById(cssId)) {
                    return;
                }

                const head = document.getElementsByTagName('head')[0];
                const link = document.createElement('link');

                link.id = cssId;
                link.href = cssUrl;
                link.rel = 'stylesheet';
                link.type = 'text/css';
                link.media = 'all';

                head.appendChild(link);
            },

            handleErrors: function (response) {
                if (!response.ok) {
                    throw new Error(response.status + ' ' + response.statusText);
                }

                return response;
            },

            get: async function (url) {
                const response = await fetch(url, {
                    method: 'GET',
                    credentials: 'same-origin'
                });

                this.handleErrors(response);

                return response.json();
            },

            post: async function (url, bodyData) {
                const response = await fetch(url, {
                    method: 'POST',
                    body: this.JSON_to_URLEncoded(bodyData),
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    credentials: 'same-origin'
                });

                this.handleErrors(response);

                return response.json();
            },

            getIntegrationInfo: async function (config) {
                const baseUrl = this.ensureUrlWithSlash(config.pluginSettings.customersCanvasBaseUrl);
                const integrationUrl = new URL(
                    'api/v1/tenants/' + config.commonSettings.tenantId + '/integrations/' + config.productModel.id,
                    baseUrl
                );

                integrationUrl.searchParams.append('storefrontId', config.commonSettings.storefrontId);

                const response = await this.get(integrationUrl.href);

                if (response && response.success && response.result) {
                    return response.result;
                }

                if (response && response.result) {
                    return response.result;
                }

                if (response && response.config) {
                    return response;
                }

                throw new Error('Handy integration response is empty.');
            },

            parseWorkflowConfig: function (rawConfig) {
                let parsedConfig = rawConfig;

                if (typeof parsedConfig === 'string') {
                    parsedConfig = JSON.parse(parsedConfig);
                }

                if (parsedConfig && parsedConfig.config) {
                    parsedConfig = parsedConfig.config;
                }

                if (parsedConfig && parsedConfig.personalizationWorkflow) {
                    parsedConfig = parsedConfig.personalizationWorkflow;
                }

                if (Array.isArray(parsedConfig)) {
                    return parsedConfig[0] || null;
                }

                return parsedConfig || null;
            },

            getWorkflowComponent: function (workflowConfig) {
                if (workflowConfig && workflowConfig.component) {
                    return workflowConfig.component;
                }

                return this.defaultWorkflowComponent;
            },

            loadScriptSource: function (scriptUrl) {
                const script = document.getElementById('he-source');

                if (!script) {
                    return Promise.reject(new Error('Handy script placeholder is not found.'));
                }

                if (script.getAttribute('src') === scriptUrl && script.dataset.loaded === 'true') {
                    return Promise.resolve();
                }

                return new Promise(function (resolve, reject) {
                    script.onload = function () {
                        script.dataset.loaded = 'true';
                        resolve();
                    };

                    script.onerror = function () {
                        script.dataset.loaded = 'false';
                        reject(new Error('Failed to load Handy script.'));
                    };

                    script.dataset.loaded = 'false';
                    script.setAttribute('src', scriptUrl);
                });
            },

            loadWorkflowElementsAssets: async function (integrationInfo) {
                const workflowConfig = this.parseWorkflowConfig(integrationInfo.config);

                if (!workflowConfig) {
                    throw new Error('Handy workflow config is missing in integration info.');
                }

                if (!integrationInfo.workflowElementsUrl) {
                    throw new Error('workflowElementsUrl is missing in integration info.');
                }

                const workflowElementsUrl = this.ensureUrlWithSlash(integrationInfo.workflowElementsUrl);
                const component = this.getWorkflowComponent(workflowConfig);
                const componentUrl = workflowElementsUrl + component + '/';

                this.addCss(componentUrl + 'styles.css', 'he-style');
                await this.loadScriptSource(componentUrl + 'index.js');

                return workflowConfig;
            },

            getQuantity: function (formData) {
                const quantityField = formData.find(function (field) {
                    return field.name === 'qty';
                });

                return quantityField && quantityField.value ? quantityField.value : 1;
            },

            getFormFieldValue: function (formData, fieldName) {
                if (!Array.isArray(formData) || !fieldName) {
                    return null;
                }

                const field = formData.find(function (entry) {
                    return entry.name === fieldName;
                });

                return field && field.value ? String(field.value) : null;
            },

            getSelectedConfigurableProductId: function (formData) {
                const selectedId = this.getFormFieldValue(formData, 'selected_configurable_option');

                return selectedId && selectedId !== '0' ? selectedId : null;
            },

            JSON_to_URLEncoded: function (element, key, list) {
                const items = list || [];

                if (typeof element === 'object' && element !== null) {
                    Object.keys(element).forEach((idx) => {
                        this.JSON_to_URLEncoded(element[idx], key ? key + '[' + idx + ']' : idx, items);
                    });
                } else {
                    items.push(key + '=' + encodeURIComponent(element));
                }

                return items.join('&');
            },

            getFormKey: function () {
                const inputs = $('#product_addtocart_form > input[name="form_key"]');

                if (inputs.length) {
                    return inputs[0].value;
                }

                throw new Error('Form key is not found.');
            },

            resolveSku: function (config, formData) {
                const selectedProductId = this.getSelectedConfigurableProductId(formData);
                const variantSkuMap = config && config.productModel ? config.productModel.variantSkuMap : null;

                if (selectedProductId && variantSkuMap && variantSkuMap[selectedProductId]) {
                    return variantSkuMap[selectedProductId];
                }

                if (config && config.productModel && config.productModel.sku) {
                    return config.productModel.sku;
                }

                return null;
            },

            buildInput: function (config, formData) {
                const quantity = this.getQuantity(formData);
                const input = {
                    productReferenceId: config.productModel.id,
                    quantity: quantity
                };

                const sku = this.resolveSku(config, formData);
                if (sku) {
                    input.sku = sku;
                }

                return input;
            },

            buildEditorConfig: function (config, integrationInfo, workflowConfig, formData) {
                return $.extend(true, {}, workflowConfig, {
                    integration: {
                        cchubApiGatewayUrl: integrationInfo.apiGatewayUrl || '',
                        cchubApiUrl: config.pluginSettings.customersCanvasBaseUrl,
                        assetStorageUrl: integrationInfo.assetStorageUrl || '',
                        assetProcessorUrl: integrationInfo.assetProcessorUrl || '',
                        tenantId: config.commonSettings.tenantId,
                        storefrontId: config.commonSettings.storefrontId,
                        quantity: this.getQuantity(formData),
                        user: {
                            id: config.userInfo.id,
                            token: config.userInfo.token
                        }
                    },
                    product: {
                        externalProductId: String(config.productModel.id)
                    },
                    input: this.buildInput(config, formData)
                });
            },

            buildProject: function (detail) {
                const hidden = detail.properties && detail.properties._hidden ? detail.properties._hidden : {};
                hidden.snapshot = '';

                return {
                    _stateId: detail.properties ? detail.properties._stateId : undefined,
                    _userId: detail.properties ? detail.properties._userId : undefined,
                    _hidden: hidden
                };
            },

            buildSubmitUrl: function (config) {
                return new URL(config.pluginSettings.addToCartUrl, config.pluginSettings.magentoBaseUrl).href;
            },

            buildSubmitRequestBody: function (detail, config) {
                const project = this.buildProject(detail);

                return {
                    productId: detail.originalProductId || config.productModel.id,
                    projectJson: JSON.stringify(project),
                    imagesJson: JSON.stringify(project._hidden.images || []),
                    quantity: detail.quantity || 1,
                    form_key: this.getFormKey(),
                    optionBasedProductSku: detail.sku || config.selectedFormSku || this.resolveSku(config) || ''
                };
            },

            submitItem: async function (detail, config) {
                const submitUrl = this.buildSubmitUrl(config);
                const submitRequestBody = this.buildSubmitRequestBody(detail, config);

                return this.post(submitUrl, submitRequestBody);
            },

            bindEditorEvents: function (editor, config, onRequestSuccessHandler, onRequestErrorHandler) {
                if (!editor) {
                    return;
                }

                if (editor.__customersCanvasHandyHandler) {
                    editor.removeEventListener('addtocart', editor.__customersCanvasHandyHandler);
                }

                editor.__customersCanvasHandyHandler = async (event) => {
                    try {
                        const response = await this.submitItem(event.detail, config);

                        if (typeof onRequestSuccessHandler === 'function') {
                            onRequestSuccessHandler(response || {});
                        }
                    } catch (error) {
                        this.closeEditor();
                        console.error('[CustomersCanvas][HandyEditor] failed to submit editor result.', error);

                        if (typeof onRequestErrorHandler === 'function') {
                            onRequestErrorHandler({ error: error });
                        }
                    }
                };

                editor.addEventListener('addtocart', editor.__customersCanvasHandyHandler);
            },

            openEditor: async function (form, onRequestSuccessHandler, onRequestErrorHandler) {
                try {
                    const marker = this.getMarker();
                    const config = await this.getStoredConfigPromise(marker);

                    if (!config || !config.userInfo || !config.userInfo.token) {
                        console.warn('[CustomersCanvas][HandyEditor] token is empty. Abort editor bootstrap.');
                        return false;
                    }

                    const integrationInfo = await this.getIntegrationInfo(config);
                    const workflowConfig = await this.loadWorkflowElementsAssets(integrationInfo);
                    const editor = this.getEditor();

                    if (!editor) {
                        console.error('[CustomersCanvas][HandyEditor] editor element is not found.');
                        return false;
                    }

                    const formData = $(form).serializeArray();
                    config.selectedFormSku = this.resolveSku(config, formData);
                    const editorConfig = this.buildEditorConfig(config, integrationInfo, workflowConfig, formData);

                    this.bindEditorEvents(editor, config, onRequestSuccessHandler, onRequestErrorHandler);
                    this.showOverlay();
                    console.log('[CustomersCanvas][HandyEditor] opening editor with integration info:', integrationInfo);
                    editor.init(editorConfig);
                    return true;
                } catch (error) {
                    this.hideOverlay();
                    console.error('[CustomersCanvas][HandyEditor] failed to open editor.', error);
                    return false;
                }
            }
        };

        return helper;
    };

    return mageJsComponent;
});