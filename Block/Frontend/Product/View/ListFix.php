<?php

namespace Aurigma\CustomersCanvas\Block\Frontend\Product\View;

use \Magento\Catalog\Block\Product\ListProduct;
use \Magento\Catalog\Block\Product\Context;
use \Magento\Framework\Data\Helper\PostHelper;
use \Magento\Catalog\Model\Layer\Resolver;
use \Magento\Catalog\Api\CategoryRepositoryInterface;
use \Magento\Framework\Url\Helper\Data;
use \Magento\Framework\App\ObjectManager;
use \Magento\Catalog\Helper\Output as OutputHelper;

use \Psr\Log\LoggerInterface;

use Aurigma\CustomersCanvas\Setup\InstallData;

/**
 * Helps to prepare data for removing Add to cart button for catalog list for personalize products
 */
class ListFix extends ListProduct
{
    /**
     * Block template File
     *
     * @var string
     */
    protected $_template = 'Aurigma_CustomersCanvas::product/view/list-fix-data.phtml';

    protected $_logger;

    public function __construct(
        Context $context,
        PostHelper $postDataHelper,
        Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        Data $urlHelper,
        LoggerInterface $logger,
        array $data = [],
        ?OutputHelper $outputHelper = null
    ) {
        $this->_catalogLayer = $layerResolver->get();
        $this->_postDataHelper = $postDataHelper;
        $this->categoryRepository = $categoryRepository;
        $this->urlHelper = $urlHelper;
        $data['outputHelper'] = $outputHelper ?? ObjectManager::getInstance()->get(OutputHelper::class);

        $this->_logger = $logger;

        parent::__construct(
            $context,
            $postDataHelper,
            $layerResolver,
            $categoryRepository,
            $urlHelper,
            $data,
            $outputHelper
        );
    }
    
    /**
     * Creates list of integrated products, which loaded on page
     */
    public function getIntegratedProductList()
    {
        $result = [];
        $productCollection = $this->getLoadedProductCollection();
        foreach ($productCollection as $product) {
            if ($this->isIntegratedProduct($product)) {
                $result[] = $product->getSku();
            }
        }
        return $result;
    }

    /**
     * Checks integrated mark of product
     */
    private function isIntegratedProduct($product)
    {
        return $product->getData(InstallData::INTEGRATED_ATTRIBUTE) == 1;
    }
}
?>