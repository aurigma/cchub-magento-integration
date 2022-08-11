define([
    'jquery',
    'Aurigma_CustomersCanvas/js/editor-view-helper',
    'Aurigma_CustomersCanvas/js/query-string-helper'
], function($, editorHelperFactory, queryHelperFactory)
{
    'use strict';

    const queryHelper = queryHelperFactory();
    const editorHelper = editorHelperFactory();

    async function restoreEditionOnce(editorMode, config) {
        const queryParams = queryHelper.getQueryParameters();
        await editorHelper.restoreEditionOnce(queryParams, editorMode, config);
        queryHelper.cleanUrl();
    }

    function isReturned() {
        return queryHelper.isParamExists('snapshot');
    }

    var mageJsComponent = async function(config, node)
    {       
        window.__customersCanvas_stepInited = false;
        $('#customers-canvas__editor-parent').on('stepInited', () => { 
            window.__customersCanvas_stepInited = true;
        });

        const editorMode = config.pluginSettings.editorMode;

        $('#customers-canvas__close-btn').click(() => 
            { 
                const result = window.confirm($.mage.__('When you close the window, all unsaved data will be lost!'));
                if (result) {
                    editorHelper.hideEditor(editorMode);
                }
            }
        );

        $(document).ready( (event) => {

            const isRestored = isReturned();

            if (!isRestored) {
                editorHelper.hideLockShroud();
            }
    
            if (isRestored) {
                restoreEditionOnce(editorMode, config);
            }

        });
    };

    return mageJsComponent;
});