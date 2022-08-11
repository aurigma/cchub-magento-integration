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
use \Magento\Customer\Model\Context as ContextAuth;
use \Magento\Catalog\Model\Product\Option;
use \Psr\Log\LoggerInterface;

use Aurigma\CustomersCanvas\Api\PluginSettingsManager;
use Aurigma\CustomersCanvas\Model\Config\Source\EditorMode;
use Aurigma\CustomersCanvas\Setup\InstallData;
use Aurigma\CustomersCanvas\Plugin\Session\CustomerSessionContext;
use Aurigma\CustomersCanvas\Observer\Cart\CheckoutCartAdd;

class Editor extends Template
{
    /**
     * Block template File
     *
     * @var string
     */
    protected $_template = 'Aurigma_CustomersCanvas::product/view/editor.phtml';

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
     * @var Resolver
     */
    private $localeResolver;

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
     * @return int
     */
    public function getProductId()
    {
        return $this->getProduct()->getId();
    }

    /**
     * @return string
     */
    public function getProductSku()
    {
        return $this->getProduct()->getSku();
    }

    /**
     * @return string
     */
    public function getProductName()
    {
        return $this->getProduct()->getName();
    }

    /**
     * @return float
     */
    public function getProductPrice()
    {
        return $this->getProduct()->getPrice();
    }

    /**
     * @return string
     */
    public function getProductType()
    {
        return $this->getProduct()->getTypeId();
    }

    public function getProductOptions() 
    {
        $resultOptions = [];
        $customOptions = $this->optionLoader->getProductOptionCollection($this->getProduct());

        foreach ($customOptions as $option) {
            $newOption = (object)(array) $option->toArray();
            $newOption->values = [];
            $values = $option->getValues();
            if ($values) {
                foreach ($option->getValues() as $value) {
                    $newOption->values[] = $value->getData();
                }
            }
            
            $resultOptions[] = $newOption;
        }
             
        return $resultOptions;
    }

    public function getProductAttributes() 
    {  
        $resultAttributes = [];
        $attributes = $this->getProduct()->getAttributes();
        foreach ($attributes as $attribute) {
            $newAttribute = new \stdClass();
            $newAttribute->attribute_code = $attribute->getAttributeCode();
            $newAttribute->value = $this->getProduct()->getData($newAttribute->attribute_code);
            if ($newAttribute->value) {
                $resultAttributes[] = $newAttribute;
            }
        }
        return $resultAttributes;
    }

    /**
     * @return bool
     */
    public function isPopupMode()
    {
        return $this->settings->getEditorMode() == EditorMode::POPUP_VALUE;
    }

    /**
     * @return string
     */
    public function getEditorMode()
    {
       return $this->settings->getEditorMode();
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
    public function getAssetStorageUrl()
    {
        return $this->settings->getAssetStorageUrl();
    }

    /**
     * @return string
     */
    public function getAssetProcessorUrl()
    {
        return $this->settings->getAssetProcessorUrl();
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
    public function getBaseUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }

    /**
     * @return string
     */
    public function getProjectOptionKey()
    {
        return CheckoutCartAdd::BACKOFFICE_OPTION_NAME;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        $currentLocaleCode = $this->localeResolver->getLocale();
        $languageCode = strstr($currentLocaleCode, '_', true);
        return $languageCode;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->storeManager->getStore()->getCurrentCurrency()->getCode();
    }

    /**
     * @return int
     */
    public function getEcommerceSystemType()
    {
        return 6; // This is magento type code in backOffice system
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
    public function getAddToCartUpdateUrl()
    {
        return 'aurigma_customers_canvas/cart/update?isAjax=true';
    }

    /**
     * @return string
     */
    public function getUpdatePriceUrl()
    {
        return 'customers-canvas/update-price'; // заглушка
    }

    /**
     * @return int | string(guid)
     */
    public function getCustomerId() 
    {
        return $this->httpContext->getValue(CustomerSessionContext::CUSTOMER_ID_KEY);
    }

    /**
     * @return string
     */
    public function getModifyCustomerId() 
    {
        return $this->httpContext->getValue(CustomerSessionContext::MODIFY_CUSTOMER_ID_KEY);
    }

    /**
     * @return string
     */
    public function getCustomerName() 
    {
        return $this->httpContext->getValue(CustomerSessionContext::CUSTOMER_NAME_KEY);
    }

    /**
     * @return string
     */
    public function getCustomerEmail() 
    {
        return $this->httpContext->getValue(CustomerSessionContext::CUSTOMER_EMAIL_KEY);
    }

    /**
     * @return boolean
     */
    public function isLoggedIn() 
    {
        return $this->httpContext->getValue(ContextAuth::CONTEXT_AUTH);
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

    private function getLogContext(string $methodName) 
    {
        return array('class' => get_class($this), 'method' => $methodName);
    }
}

?>