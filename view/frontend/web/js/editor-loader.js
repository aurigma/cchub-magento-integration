define([
    'jquery',
    'Aurigma_CustomersCanvas/js/editor-view-helper',
    'Aurigma_CustomersCanvas/js/query-string-helper'
], function($, editorHelperFactory, queryHelperFactory)
{
    'use strict';

    const queryHelper = queryHelperFactory();
    const editorHelper = editorHelperFactory();

    async function restoreEditionOnce(ccInPageEditorMode, config) {
        const queryParams = queryHelper.getQueryParameters();
        await editorHelper.restoreEditionOnce(queryParams, ccInPageEditorMode, config);
        queryHelper.cleanUrl();
    }

    function isReturned() {
        return queryHelper.isParamExists('snapshot');
    }

    var mageJsComponent = async function(config, node)
    {       
        window.__customersCanvas_stepInited = false;
        $('#customers-canvas-editor-parent').on('stepInited', () => { 
            window.__customersCanvas_stepInited = true;
        });

        const ccInPageEditorMode = !config.pluginSettings.popupMode;

        $('#customers-canvas-closebtn').click(() => 
            { 
                const result = window.confirm($.mage.__('When you close the window, all unsaved data will be lost!'));
                if (result) {
                    editorHelper.hideEditor(ccInPageEditorMode);
                }
            }
        );

        $(document).ready( (event) => {

            const isRestored = isReturned();

            if (!isRestored) {
                editorHelper.hideLockShroud();
            }
    
            if (isRestored) {
                restoreEditionOnce(ccInPageEditorMode, config);
            }

        });
    };

    return mageJsComponent;
});