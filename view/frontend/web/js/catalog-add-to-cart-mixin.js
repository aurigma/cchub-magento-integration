define([
    'jquery',
    'mage/translate',
    'jquery/ui',
    'Aurigma_CustomersCanvas/js/editor-view-helper',
    'Magento_Catalog/js/product/view/product-ids-resolver',
    'Magento_Customer/js/customer-data'
],
function ($, $t, $ui, editorHelperFactory, idsResolver, customerData) {
    'use strict';

    const editorHelper = editorHelperFactory();

    function formDataToDictionary(formData) {
        const result = [];
        for(var pair of formData.entries()) {
            result.push({
                'name': pair[0],
                'value': pair[1],
            });
        }
        return result;
    }

    return function (target) {
        $.widget('mage.catalogAddToCart', target, {
            _create: function () {

                if (this.options.integratedList) {
                    if (this.isIntegrated(this.element.attr('data-product-sku'), this.options.integratedList)) {
                        this.removeAddForm();
                        return;
                    }
                }

                if (!this.options.customersCanvas) {
                    this._super();
                    return;
                }

                this.options.addToCartButtonTextWhileAdding = $.mage.__('Personalizing...');
                this.options.addToCartButtonTextAdded = $.mage.__('Personalized');
                this.options.addToCartButtonTextDefault = $.mage.__('Personalize');

                const button = $(this.options.addToCartButtonSelector);

                button.find('span').text(this.options.addToCartButtonTextDefault);

                this._super();
            },
            isIntegrated: function(productSku, integratedList) {
                return !!productSku && integratedList.some(sku => sku === productSku);
            },
            removeAddForm: function() {
                this.element.remove();
            },
            submitForm: function(form) {
                if (!this.options.customersCanvas) {
                    this._super(form);
                    return;
                }
                editorHelper.showLockShroud();

                const formDataArray = $(form[0]).serializeArray();

                const self = this;
                const productIds = idsResolver(form);
                const productInfo = self.options.productInfoResolver(form);

                editorHelper.loadEditor(
                    formDataArray, 
                    this.options.customersCanvas, 
                    false, 
                    () => { this.disablePersonalizeButton(form); }, 
                    () => { this.enablePersonalizeButton(form); },
                    (res) => { this.onRequestSuccessHandler(self, res, form, productIds, productInfo); },
                    (res) => { this.onRequestErrorHandler(self, res, form, productIds, productInfo); }
                );
            },
            disablePersonalizeButton: function(form) {
                const addToCartButtonTextWhileAdding = this.options.addToCartButtonTextWhileAdding || $t('Adding...');
                const addToCartButton = $(form).find(this.options.addToCartButtonSelector);

                addToCartButton.addClass(this.options.addToCartButtonDisabledClass);
                addToCartButton.find('span').text(addToCartButtonTextWhileAdding);
                addToCartButton.attr('title', addToCartButtonTextWhileAdding);
            },
            enablePersonalizeButton: function(form) {
                const addToCartButton = $(form).find(this.options.addToCartButtonSelector);
                const addToCartButtonTextDefault = this.options.addToCartButtonTextDefault || $t('Add to Cart');

                addToCartButton.removeClass(this.options.addToCartButtonDisabledClass);
                addToCartButton.find('span').text(addToCartButtonTextDefault);
                addToCartButton.attr('title', addToCartButtonTextDefault);
            },
            onRequestSuccessHandler: function(self, res, form, productIds, productInfo) {
                var eventData, parameters;

                $(document).trigger('ajax:addToCart', {
                    'sku': form.data().productSku,
                    'productIds': productIds,
                    'productInfo': productInfo,
                    'form': form,
                    'response': res
                });

                if (self.isLoaderEnabled()) {
                    $('body').trigger(self.options.processStop);
                }

                if (res.backUrl) {
                    eventData = {
                        'form': form,
                        'redirectParameters': []
                    };
                    // trigger global event, so other modules will be able add parameters to redirect url
                    $('body').trigger('catalogCategoryAddToCartRedirect', eventData);

                    if (eventData.redirectParameters.length > 0 &&
                        window.location.href.split(/[?#]/)[0] === res.backUrl
                    ) {
                        parameters = res.backUrl.split('#');
                        parameters.push(eventData.redirectParameters.join('&'));
                        res.backUrl = parameters.join('#');
                    }

                    self._redirect(res.backUrl);

                    return;
                }

                if (res.messages) {
                    $(self.options.messagesSelector).html(res.messages);
                }

                if (res.minicart) {
                    $(self.options.minicartSelector).replaceWith(res.minicart);
                    $(self.options.minicartSelector).trigger('contentUpdated');
                }

                if (res.product && res.product.statusText) {
                    $(self.options.productStatusSelector)
                        .removeClass('available')
                        .addClass('unavailable')
                        .find('span')
                        .html(res.product.statusText);
                }
                self.enableAddToCartButton(form);

                self.updateNecessaryUi();
            },
            onRequestErrorHandler: function(self, res, form, productIds, productInfo) {
                $(document).trigger('ajax:addToCart:error', {
                    'sku': form.data().productSku,
                    'productIds': productIds,
                    'productInfo': productInfo,
                    'form': form,
                    'response': res
                });

                self.updateNecessaryUi();
            },
            updateNecessaryUi() {
                var sections = ['cart', 'messages'];
                customerData.invalidate(sections);
                customerData.reload(sections, true);
            },
        });

        return $.mage.catalogAddToCart;
    };
});