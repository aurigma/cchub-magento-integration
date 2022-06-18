<?php
namespace Aurigma\CustomersCanvas\Controller\Adminhtml\Settings;

use \Magento\Backend\App\Action;
use \Magento\Backend\App\Action\Context;
use \Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    protected $resultPageFactory = false; 

    public function __construct(Context $context, PageFactory $resultPageFactory) 
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    } 

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Aurigma_CustomersCanvas::customerscanvas');
        $resultPage->getConfig()->getTitle()->prepend(__('Settings'));
        return $resultPage;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Backend::content');
    }
}

?>