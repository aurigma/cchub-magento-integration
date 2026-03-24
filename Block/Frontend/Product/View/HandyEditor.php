<?php

namespace Aurigma\CustomersCanvas\Block\Frontend\Product\View;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;

use Aurigma\CustomersCanvas\Api\PluginSettingsManager;
use Aurigma\CustomersCanvas\Api\StorefrontUsersService;
use Aurigma\CustomersCanvas\Helper\EditorFamilyResolver;
use Aurigma\CustomersCanvas\Plugin\Session\CustomerSessionContext;
use Aurigma\CustomersCanvas\Setup\InstallData;

class HandyEditor extends Template
{
    /**
     * Block template File
     *
     * @var string
     */
    protected $_template = 'Aurigma_CustomersCanvas::product/view/handy-editor.phtml';

    /**
     * @var Product
     */
    protected $_product = null;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager = null;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * @var Aurigma\CustomersCanvas\Api\Data\PluginSettings
     */
    protected $settings;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StorefrontUsersService
     */
    protected $storefrontUsersService;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Aurigma\CustomersCanvas\Api\PluginSettingsManager $settingManager
     * @param \Aurigma\CustomersCanvas\Api\StorefrontUsersService $storefrontUsersService
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        StoreManagerInterface $storeManager,
        HttpContext $httpContext,
        PluginSettingsManager $settingManager,
        StorefrontUsersService $storefrontUsersService,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->storeManager = $storeManager;
        $this->httpContext = $httpContext;
        $this->scopeConfig = $scopeConfig;
        $this->settings = $settingManager->getSettings(ScopeInterface::SCOPE_STORE);
        $this->storefrontUsersService = $storefrontUsersService;
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
    public function isProductForHandy()
    {
        $editorFamilyValue = $this->getProduct()->getData(InstallData::EDITOR_FAMILY_ATTRIBUTE);
        return EditorFamilyResolver::isHandy($editorFamilyValue);
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
     * Returns map of configurable child product IDs to child SKUs.
     *
     * @return array<string, string>
     */
    public function getVariantSkuMap()
    {
        $product = $this->getProduct();
        if (!$product || $product->getTypeId() !== ConfigurableType::TYPE_CODE) {
            return [];
        }

        $result = [];
        $children = $product->getTypeInstance()->getUsedProducts($product);
        foreach ($children as $child) {
            $result[(string) $child->getId()] = (string) $child->getSku();
        }

        return $result;
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
     * @return string
     */
    public function getStorefrontUserToken()
    {
        $storefrontUserId = $this->getModifyCustomerId();
        if (empty($storefrontUserId)) {
            return '';
        }

        return $this->storefrontUsersService->getCcHubTokenForStorefrontUser($storefrontUserId);
    }

    /**
     * @return string
     */
    public function getStorefrontUserTokenUrl()
    {
        return $this->getUrl('aurigma_customers_canvas/token/get');
    }

    /**
     * @return string
     */
    public function getEditorMode()
    {
        return $this->settings->getEditorMode();
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
