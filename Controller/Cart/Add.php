<?php

namespace Aurigma\CustomersCanvas\Controller\Cart;

use \Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use \Magento\Checkout\Controller\Cart as CartController;
use \Magento\Catalog\Api\ProductRepositoryInterface;
use \Magento\Checkout\Model\Cart as CustomerCart;
use \Magento\Framework\Data\Form\FormKey\Validator;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Checkout\Model\Session;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\App\Action\Context;
use \Magento\Framework\Exception\NoSuchEntityException;
use \Magento\Framework\Controller\ResultInterface;
use \Magento\Framework\Locale\ResolverInterface;
use \Magento\Framework\Json\Helper\Data;
use \Magento\Store\Model\ScopeInterface;
use \Magento\Framework\Exception\LocalizedException;
use \Magento\Framework\Escaper;
use \Psr\Log\LoggerInterface;

class Add extends CartController implements HttpPostActionInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param Validator $formKeyValidator
     * @param CustomerCart $cart
     * @param ProductRepositoryInterface $productRepository
     * @codeCoverageIgnore
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        Session $checkoutSession,
        StoreManagerInterface $storeManager,
        Validator $formKeyValidator,
        CustomerCart $cart,
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart
        );
        $this->productRepository = $productRepository;
        $this->_logger = $logger;
    }

    /**
     * Initialize product instance from request data
     *
     * @return \Magento\Catalog\Model\Product|false
     */
    protected function _initProduct($productId)
    {
        $productId = (int) $productId;
        if ($productId) {
            // $storeId = $this->_objectManager->get(StoreManagerInterface::class)->getStore()->getId();
            $storeId = $this->_storeManager->getStore()->getId();
            try {
                return $this->productRepository->getById($productId, false, $storeId);
            } catch (NoSuchEntityException $e) {
                return false;
            }
        }
        return false;
    }

    /**
     * Add product to shopping cart action
     *
     * @return ResponseInterface|ResultInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {

        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $this->messageManager->addErrorMessage(__('Your session has expired'));
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $params = $this->getRequest()->getParams();

        try {
            
            $productParams = $this->getProductParamsFromRequest($params);
            $product = $this->_initProduct($productParams['product']);
            $related = $this->getRequest()->getParam('related_product');
            /** Check product availability */
            if (!$product) {
                return $this->goBack();
            }

            $this->cart->addProduct($product, $productParams);
            if (!empty($related)) {
                $this->cart->addProductsByIds(explode(',', $related));
            }
            $this->cart->save();

            /**
             * @todo remove wishlist observer \Magento\Wishlist\Observer\AddToCart
             */
            $this->_eventManager->dispatch(
                'checkout_cart_add_product_complete',
                ['product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse()]
            );

            if (!$this->_checkoutSession->getNoCartRedirect(true)) {
                if ($this->shouldRedirectToCart()) {
                    $message = __(
                        'You added %1 to your shopping cart.',
                        $product->getName()
                    );
                    $this->messageManager->addSuccessMessage($message);
                } else {
                    $this->messageManager->addComplexSuccessMessage(
                        'addCartSuccessMessage',
                        [
                            'product_name' => $product->getName(),
                            'cart_url' => $this->getCartUrl(),
                        ]
                    );
                }
                if ($this->cart->getQuote()->getHasError()) {
                    $errors = $this->cart->getQuote()->getErrors();
                    foreach ($errors as $error) {
                        $this->messageManager->addErrorMessage($error->getText());
                    }
                }
                return $this->goBack(null, $product);
            }
        } catch (LocalizedException $e) {
            if ($this->_checkoutSession->getUseNotice(true)) {
                $this->messageManager->addNoticeMessage(
                    $this->_objectManager->get(Escaper::class)->escapeHtml($e->getMessage())
                );
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->messageManager->addErrorMessage(
                        $this->_objectManager->get(Escaper::class)->escapeHtml($message)
                    );
                }
            }

            $url = $this->_checkoutSession->getRedirectUrl(true);
            if (!$url) {
                $url = $this->_redirect->getRedirectUrl($this->getCartUrl());
            }

            return $this->goBack($url);
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('We can\'t add this item to your shopping cart right now.')
            );
            $this->_logger->critical($e);
            return $this->goBack();
        }

        return $this->getResponse();
    }

    protected function getProductParamsFromRequest($params): array 
    {
        $result = [];

        if (isset($params['quantity'])) {
            $filter = new \Zend_Filter_LocalizedToNormalized(
                ['locale' => $this->_objectManager->get(ResolverInterface::class)->getLocale()]
            );
            $result['qty'] = $filter->filter($params['quantity']);
        }

        if (isset($params['formOptions'])) {
            $formOptions = json_decode($params['formOptions']);
            foreach ($formOptions as $formOption) {
                $result[$formOption->name] = $formOption->value ?? '';
            }
        }

        if (isset($params['optionsJson'])) {
            $options = json_decode($params['optionsJson']);

            $result['options'] = [];
            foreach ($options as $option) {
                $result['options'][$option->option->id] = $this->parseOptionValues($option);
            }
        }

        $productId = $this->getRealProductId($params);
        if ($productId) {
            $result['product'] = $productId;
            $result['item'] = $productId;
        }

        return $result;
    }

    private function parseOptionValues($option) 
    {
        if (!is_array($option->value)) {
            return $option->value;
        } elseif(count($option->value) == 1) {
            return isset($option->value[0]->id) ? $option->value[0]->id : $option->value[0];
        } else {
            $valuesIds = [];
            foreach($option->value as $optionValue) {
                $valuesIds[] = $optionValue->id;
            }
            return $valuesIds;
        }
    }

    private function getRealProductId($params)
    {
        $productId = null;
        if (isset($params['productId'])) {
            $productId = $params['productId'];
        }

        if (isset($params['optionBasedProductSku']) && $params['optionBasedProductSku'] !== '') {
            $storeId = $this->_storeManager->getStore()->getId();
            try {
                $product = $this->productRepository->get($params['optionBasedProductSku'], false, $storeId);

                if ($product) {
                    $productId = $product->getId();
                }
            } catch (NoSuchEntityException $e) {
                return false;
            }
        }
        return $productId;
    }

    /**
     * Resolve response
     *
     * @param string $backUrl
     * @param \Magento\Catalog\Model\Product $product
     * @return ResponseInterface|ResultInterface
     */
    protected function goBack($backUrl = null, $product = null)
    {
        if (!$this->getRequest()->isAjax()) {
            return parent::_goBack($backUrl);
        }

        $result = [];

        if ($backUrl || $backUrl = $this->getBackUrl()) {
            $result['backUrl'] = $backUrl;
        } else {
            if ($product && !$product->getIsSalable()) {
                $result['product'] = [
                    'statusText' => __('Out of stock')
                ];
            }
        }

        $this->getResponse()->representJson($this->_objectManager->get(Data::class)->jsonEncode($result));

        return $this->getResponse();
    }

    /**
     * Returns cart url
     *
     * @return string
     */
    private function getCartUrl()
    {
        return $this->_url->getUrl('checkout/cart', ['_secure' => true]);
    }

    /**
     * Is redirect should be performed after the product was added to cart.
     *
     * @return bool
     */
    private function shouldRedirectToCart()
    {
        return $this->_scopeConfig->isSetFlag('checkout/cart/redirect_to_cart', ScopeInterface::SCOPE_STORE);
    }
}

?>