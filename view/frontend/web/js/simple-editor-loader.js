define([
    'jquery',
    'Aurigma_CustomersCanvas/js/simple-editor-view-helper'
], function($, editorHelperFactory)
{
    'use strict';

    const editorHelper = editorHelperFactory();
    console.log(editorHelper);

    var mageJsComponent = async function(config, node)
    {
        $(document).ready((event) => {
            
            // set true to log debug info to console
            window.IsDebug = false;
            editorHelper.logDebug("Load event was fired");

            editorHelper.logDebug("Config is:", config);

            editorHelper.hideProductInfo();

            const editorSourceLoading = editorHelper.setSimpleEditorScriptSources(config);

            const simpleEditor = document.getElementsByTagName("au-simple-editor").item(0);
            editorHelper.logDebug("Simple editor is:", simpleEditor);

            document.getElementById('se-source').addEventListener('load', function() {
                editorHelper.hideLockShroud();
                simpleEditor.setEditorConfig({
                    backOfficeUrl: config.pluginSettings.customersCanvasBaseUrl,
                    tenantId: config.commonSettings.tenantId,
                    product: { id: config.productModel.id },
                    user: { id: config.userInfo.id  },
                    ecommerceSystemId: config.commonSettings.storefrontId
                });
            })

            $(document).on("editorloaded", () => {
                editorHelper.logDebug("Editor loaded");
                // editorHelper.hideLockShroud();
            });

            simpleEditor.addEventListener("addtocart", async (e) => {
                editorHelper.logDebug("Submit event is:", e);
                await editorHelper.submitItem(e, config);
                editorHelper.redirectToCart(config);
            });

        });
    };

    return mageJsComponent;
});