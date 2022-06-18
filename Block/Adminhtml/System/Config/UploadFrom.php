<?php

namespace Aurigma\CustomersCanvas\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Widget;
use Magento\Backend\Block\Template\Context;

class UploadFrom extends Widget
{
    /**
     * Block template File
     *
     * @var string
     */
    protected $_template = 'Aurigma_CustomersCanvas::system/config/upload-form.phtml';

    /**
     * Return ajax url for upload config
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('aurigma_customers_canvas/settings/upload');
    }

    public function getFileValidateErrorMessage() 
    {
        return __('Only json files are allowed.');
    }
}

?>