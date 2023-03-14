<?php

namespace Aurigma\CustomersCanvas\Block\Frontend\Product\View;

use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;
use \Magento\Framework\Registry;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Framework\Locale\Resolver;
use \Magento\Framework\App\Http\Context as HttpContext;
use \Magento\Store\Model\ScopeInterface;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Catalog\Model\Product\Option;
use \Psr\Log\LoggerInterface;

use Aurigma\CustomersCanvas\Api\PluginSettingsManager;
use Aurigma\CustomersCanvas\Setup\InstallData;
use Aurigma\CustomersCanvas\Plugin\Session\CustomerSessionContext;

class SimpleEditor extends Template
{
    /**
     * Block template File
     *
     * @var string
     */
    protected $_template = 'Aurigma_CustomersCanvas::product/view/simple-editor.phtml';

    /**
     * @var Product
     */
    protected $_product = null;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager = null;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * @var Aurigma\CustomersCanvas\Api\Data\PluginSettings
     */
    protected $settings;

    /**
     * @var Magento\Catalog\Model\Product\Option
     */
    protected $optionLoader;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    protected $_logger;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Locale\Resolver $localeResolver
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Catalog\Model\Product\Option $optionLoader
     * @param \Aurigma\CustomersCanvas\Api\PluginSettingsManager $settingManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface;
     * @param \Psr\Log\LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        StoreManagerInterface $storeManager,
        Resolver $localeResolver,
        HttpContext $httpContext,
        Option $optionLoader,
        PluginSettingsManager $settingManager,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->storeManager = $storeManager;
        $this->localeResolver = $localeResolver;
        $this->httpContext = $httpContext;
        $this->optionLoader = $optionLoader;
        $this->scopeConfig = $scopeConfig;
        $this->_logger = $logger;

        $this->settings = $settingManager->getSettings(ScopeInterface::SCOPE_STORE);
        parent::__construct($context, $data);
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        if (!$this->_product) {
            $this->_product = $this->_coreRegistry->registry('product');
        }
        return $this->_product;
    }

    /**
     * @return bool
     */
    public function isProductIntegrated()
    {
        return $this->getProduct()->getData(InstallData::INTEGRATED_ATTRIBUTE) == 1;
    }

    /**
     * @return bool
     */
    public function isProductForSe()
    {
        $editorFamilyValue = $this->getProduct()->getData(InstallData::EDITOR_FAMILY_ATTRIBUTE);
        
        if(!empty($editorFamilyValue) && $editorFamilyValue == 1){
            return true;
        }

        return false;
    }

    /**
     * @return int
     */
    public function getProductId()
    {
        return $this->getProduct()->getId();
    }

    /**
     * @return string
     */
    public function getBackOfficeUrl()
    {
        return $this->settings->getBackOfficeUrl();
    }

    /**
     * @return string
     */
    public function getTenantId()
    {
        return $this->settings->getBackOfficeTenantId();
    }

    /**
     * @return string
     */
    public function getStorefrontId()
    {
        return $this->settings->getBackOfficeStorefrontId();
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }

    /**
     * @return string
     */
    public function getAddToCartUrl()
    {
        return 'aurigma_customers_canvas/cart/add?isAjax=true';
    }

    /**
     * @return string
     */
    public function getModifyCustomerId() 
    {
        return $this->httpContext->getValue(CustomerSessionContext::MODIFY_CUSTOMER_ID_KEY);
    }

    /**
     * Whether redirect to cart enabled
     *
     * @return bool
     */
    public function isRedirectToCartEnabled()
    {
        return $this->scopeConfig->getValue('checkout/cart/redirect_to_cart', ScopeInterface::SCOPE_STORE) == 1;
    }
}

?>