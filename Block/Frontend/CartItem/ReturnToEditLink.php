<?php

namespace Aurigma\CustomersCanvas\Block\Frontend\CartItem;

use \Magento\Catalog\Api\ProductRepositoryInterface;
use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;
use \Magento\Store\Model\StoreManagerInterface;
use \Psr\Log\LoggerInterface;

use Aurigma\CustomersCanvas\Model\BackofficeProjectFactory;
use Aurigma\CustomersCanvas\Helper\BackOfficeProjectHelper;
use Aurigma\CustomersCanvas\Observer\Cart\CheckoutCartAdd;

/**
 * ReturnToEditLink
 */
class ReturnToEditLink extends Template
{
    protected $backOfficeProjectFactory;
    protected $projectHelper;
    protected $productRepository;
    protected $storeManager;

    protected $_logger;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
    */
    public function __construct(
        Context $context, 
        BackofficeProjectFactory $backOfficeProjectFactory,
        BackOfficeProjectHelper $projectHelper,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger, 
        array $data = []) 
    {
        $this->backOfficeProjectFactory = $backOfficeProjectFactory;
        $this->projectHelper = $projectHelper;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;

        $this->_logger = $logger;
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
    }

    public function isPersonalized()
    {
        $item = $this->getItem();
        $itemOption = $item->getOptionByCode(CheckoutCartAdd::BACKOFFICE_OPTION_NAME);
        if (!empty($itemOption)) {
            return true;
        } else {
            return false;
        }
    }

    public function isSnapshotEmpty()
    {
        $item = $this->getItem();
        $project = $this->getBackOfficeProjectForItem($item);
        $snapshot = $this->projectHelper->getSnapshotFromProject($project);
        if (empty($snapshot)) {
            return true;
        } else {
            return false;
        }
    }

    public function isOptionBasedProduct()
    {
        $item = $this->getItem();
        $itemOption = $item->getOptionByCode(CheckoutCartAdd::ORIGINAL_PRODUCT_NAME);
        if (!empty($itemOption)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return additional information data
     */
    public function getReturnToEditLink()
    {
        return $this->buildReturnLink();
    }

    /**
     * @return additional information data
     */
    public function getItemId()
    {
        $item = $this->getItem();
        return $item->getId();
    }

    private function buildReturnLink()
    {
        $resultLink = '';
        $item = $this->getItem();
        $product = $item->getProduct();
        $resultLink = $this->getProductUrl($product);
        $queryArray = $this->getQueryArrayForItem($item);

        return $resultLink . '?' . http_build_query($queryArray);
    }

    private function getProductUrl($product): string
    {
        $resultLink = $product->getProductUrl();
        if ($this->isOptionBasedProduct())
        {
            $item = $this->getItem();
            $itemOption = $item->getOptionByCode(CheckoutCartAdd::ORIGINAL_PRODUCT_NAME);
            $originalProductId = $itemOption->getValue();

            $storeId = $this->storeManager->getStore()->getId();
            $originalProduct = $this->productRepository->getById($originalProductId, false, $storeId);

            $resultLink = $originalProduct->getProductUrl();
        }
        return $resultLink;
    }

    private function getQueryArrayForItem($item)
    {
        $project = $this->getBackOfficeProjectForItem($item);

        $snapshot = $this->projectHelper->getSnapshotFromProject($project);
        $stateId = $this->projectHelper->getStateIdFromProject($project);
        $queryArray = array(
            'snapshot' => $snapshot,
            'cartItemId' => $item->getId(),
            'quantity' => $item->getQty(),
            'stateId' => $stateId,
        );

        $options = $item->getOptions();
        foreach ($options as $option) {
            $code = $option->getCode();
            if ($code !== 'option_ids' && substr($code, 0, 7) === 'option_') {
                $queryArray[$option->getCode()] = $option->getValue();
            }
        }

        return $queryArray;
    }

    private function getBackOfficeProjectForItem($item)
    {
        $itemOption = $item->getOptionByCode(CheckoutCartAdd::BACKOFFICE_OPTION_NAME);
        $projectKey = $itemOption->getValue();

        $backOfficeProjects = $this->backOfficeProjectFactory->create();
        $projectCollection = $backOfficeProjects->getCollection()->addFieldToFilter('project_key', ['eq' => $projectKey]);

        if($projectCollection->getSize()) {
            return $projectCollection->getFirstItem();
        } else {
            $this->_logger->debug("BackOffice project with key $projectKey was not found in db.", $this->getLogContext(__METHOD__));
            return;
        }
    }

    private function getLogContext(string $methodName) 
    {
        return array('class' => get_class($this), 'method' => $methodName);
    }
}

?>