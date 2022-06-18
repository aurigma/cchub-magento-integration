define(['jquery'], function($)
{
    'use strict';

    var mageJsComponent = function()
    {
        const helper = {
            hideLockShroud: function() {
                $('#customers-canvas-shroud').hide();
            },
            showLockShroud: function() {
                $('#customers-canvas-shroud').show();
            },
            showEditor: function(ccInPageEditorMode) {
                this.hideLockShroud();
                if (ccInPageEditorMode) {
                    $('.product-info-main').hide();
                    $('.product.media').hide();
        
                    $('#customers-canvas-container').css('position', 'static');
                } else {
                    if (window.auWizard && window.auWizard.scope.driver) {
                        window.auWizard.showEditorPopup();
                    } else {
                        $('#customers-canvas-editor-parent').one('load', function() {
                            window.auWizard.showEditorPopup();
                        });
                    }
                }
            },
            hideEditor: function(ccInPageEditorMode) {
                if (ccInPageEditorMode) {
                    $('#customers-canvas-container').css('position', 'fixed');
                    $('.product-info-main').show();
                    $('.product.media').show();
                } else {
                    auWizard.closeEditorPopup(true);
                }
            },
            async loadEditor(formData, settings, restoreState, disableSubmitButton, activateSubmitButton, onRequestSuccessHandler, onRequestErrorHandler) {
                const ccInPageEditorMode = !settings.pluginSettings.popupMode;
                const self = this;
                if (!ccInPageEditorMode) {
                    disableSubmitButton();
                }
                window.cc_formDataForDriver = formData;
                await this.editorInitialization(settings, restoreState, formData, onRequestSuccessHandler, onRequestErrorHandler, ccInPageEditorMode);
                
                
                if (window.__customersCanvas_stepInited) {
                    this.updateDriverQuantity(formData);
                    this.showEditor(ccInPageEditorMode);
                    activateSubmitButton();
                } else {
                    $('#customers-canvas-editor-parent').one('stepInited', function() {
                        setTimeout(() => {
                            self.updateDriverQuantity(formData);
                            self.showEditor(ccInPageEditorMode);
                            activateSubmitButton();
                        }, 0);
                    });
                }
            },
            async editorInitialization(settings, restoreState, formData, onRequestSuccessHandler, onRequestErrorHandler, ccInPageEditorMode) {
                const self = this;
                const initData = await this.preloadEditor(settings);

                const storefront = initData.storefront;
                const data = initData.data;
                const userInfo = initData.userInfo;
        
                const container = document.querySelector("#customers-canvas-editor-parent");
        
                const productModel = settings.productModel;
                const orderRestoreData = !!restoreState ? restoreState : null;
                
                this.setOptionsToModel(formData, productModel);

                window.__customersCanvas_stepInited = false;
                
                const qwe = await storefront.loadEditor(container, data, userInfo, productModel, orderRestoreData);
                let driver = window.auWizard.scope.driver;
                if (driver) {
                    driver.orders.current.onSubmitted.subscribe(async function (order, data) {
                        if (data.err) {
                            onRequestErrorHandler(data.response)
                        } else {
                            onRequestSuccessHandler(data.response);
                        }
                        self.hideEditor(ccInPageEditorMode);
                    });     
                }
            },
            async restoreEditionOnce(queryParams, ccInPageEditorMode, settings) {

                const formDataArray = this.getFormDataFromParamsObj(queryParams);
                const restoreState = this.getRestoreDataFromParamsObj(queryParams);

                formDataArray.push(this.getInputArrayElement('form_key'));
                formDataArray.push(this.getInputArrayElement('related_product'));
                formDataArray.push(this.getInputArrayElement('selected_configurable_option'));

                await this.loadEditor(
                    formDataArray, 
                    settings, 
                    restoreState, 
                    () => {}, 
                    () => {}, 
                    (res) => { console.log(res) }, 
                    (res) => { console.log(res) }, 
                    ccInPageEditorMode, 
                    true); // always goes to cart
                this.showEditor(ccInPageEditorMode);
            },
            updateDriverQuantity(formData) {
                let driver = window.auWizard.scope.driver;
                driver.cart.lineItems[0].quantity = this.getQuantity(formData);
            },
            setOptionsToModel(formData, productModel) {
                const formOptions = formData.filter( x => { return x['name'].startsWith('options['); });
                formOptions.forEach(formOption => {
                    try {
                        const optionId = formOption['name'].match(/\d+/)[0];
                        const optionValue = formOption['value'];
                        
                        const option = productModel.options.find( x => x.option_id === optionId);
                        option.values.forEach( value => {
                            if (value.option_type_id === optionValue) {
                                value.preselected = true;
                            } else {
                                value.preselected = false;
                            }
                        });
                    } catch (ex) {
                        console.error('Unable to preselect option', ex);
                    }
                });
            },
            getQuantity(formData) {
                let quantity = 1;
                
                const quantityParam = formData.find(x => { return x['name'] === 'qty'});
                if (quantityParam) {
                    quantity = quantityParam['value'];
                }
        
                return quantity;
            },
            getFormDataFromParamsObj(params) {
                const result = [];
                for(let key in params) {
                    if (key === 'snapshot' || key === 'cartItemId' || key === 'stateId') {
                        continue;
                    }

                    let newKey = key;
                    let newValue = params[key];

                    if (key.substring(0, 7) === 'option_') {
                        newKey = key.replace('option_', 'options[') + ']';
                    }

                    if (key === 'quantity') {
                        newKey = 'qty';
                        newValue = Number.parseInt(params[key]);
                    }

                    result.push({
                        name: newKey,
                        value: newValue,
                    });
                }
                return result;
            },
            getRestoreDataFromParamsObj(params) {
                const orderRestoreData = {
                    state: params['stateId'],
                    snapshot: params['snapshot'],
                    key: params['cartItemId'],
                };
                return orderRestoreData;
            },
            getInputArrayElement(name) {
                const inputs = $('#product_addtocart_form > input[name="' +  name + '"');
                if (inputs.length) {
                    return {
                        name: inputs[0].name,
                        value: inputs[0].value,
                    };
                } else {
                    console.error('Form key is not found.');
                }
            },
            async preloadEditor(settings) {
                const userInfo = settings.userInfo;
        
                const storefront = new Aurigma.BackOffice({
                    tenantId: settings.commonSettings.tenantId,
                    backOfficeUrl: settings.pluginSettings.customersCanvasBaseUrl,
                    pluginSettings: settings.pluginSettings
                });
        
                
                const data = await storefront.templates.findByProduct(settings.productModel.id);
                if (settings.pluginSettings.popupMode) {
                    const config = JSON.parse(data.config);
                    config.displayInPopup = true;
                    data.config = JSON.stringify(config);
                }

                //data.uiFrameworkUrl = "http://localhost/uif/editor";
        
                return { storefront: storefront, data: data, userInfo: userInfo, pluginSettings: settings.pluginSettings };
            },
        };
        return helper;
    };

    return mageJsComponent;
});