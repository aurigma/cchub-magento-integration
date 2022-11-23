<?php

namespace Aurigma\CustomersCanvas\Controller\Cart;

use \Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use \Magento\Checkout\Controller\Cart as CartController;
use \Magento\Checkout\Model\Cart as CustomerCart;
use \Magento\Framework\Data\Form\FormKey\Validator;
use \Magento\Catalog\Api\ProductRepositoryInterface;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Checkout\Model\Session;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\App\Action\Context;
use \Magento\Framework\Controller\ResultInterface;
use \Magento\Framework\Locale\ResolverInterface;
use \Magento\Framework\Exception\LocalizedException;
use \Magento\Framework\DataObject;
use \Magento\Framework\Escaper;
use \Psr\Log\LoggerInterface;
use \Magento\Framework\Exception\NoSuchEntityException;

use Aurigma\CustomersCanvas\Observer\Cart\CheckoutCartAdd;

class Update extends CartController implements HttpPostActionInterface
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
     * Add product to shopping cart action
     *
     * @return ResponseInterface|ResultInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        $this->_logger->debug(json_encode($this->getRequest()->getParams()));

        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $this->messageManager->addErrorMessage(__('Your session has expired'));
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $params = $this->getRequest()->getParams();

        try {
            
            $productParams = $this->getProductParamsFromRequest($params);
            $quoteItem = $this->cart->getQuote()->getItemById($this->getRequest()->getParam('updateCartItemId'));
            if (!$quoteItem) {
                throw new LocalizedException(
                    __("The quote item isn't found. Verify the item and try again.")
                );
            }
            
            $item = $this->cart->updateItem($quoteItem->getId(), new DataObject($productParams));
            if (is_string($item)) {
                throw new LocalizedException(__($item));
            }
            if ($item->getHasError()) {
                throw new LocalizedException(__($item->getMessage()));
            }

            $related = $this->getRequest()->getParam('related_product');
            if (!empty($related)) {
                $this->cart->addProductsByIds(explode(',', $related));
            }

            $this->cart->save();

            $this->_eventManager->dispatch(
                'checkout_cart_update_item_complete',
                [
                    'item' => $item, 
                    'request' => $this->getRequest(), 
                    'response' => $this->getResponse()
                ]
            );

            if (!$this->cart->getQuote()->getHasError()) {
                $message = __(
                    '%1 was updated in your shopping cart.',
                    $this->_objectManager->get(Escaper::class)
                        ->escapeHtml($item->getProduct()->getName())
                );
                $this->messageManager->addSuccessMessage($message);
            }
            return $this->_goBack($this->_url->getUrl('checkout/cart'));

        } catch (LocalizedException $e) {
            if ($this->_checkoutSession->getUseNotice(true)) {
                $this->messageManager->addNoticeMessage($e->getMessage());
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->messageManager->addErrorMessage($message);
                }
            }

            $url = $this->_checkoutSession->getRedirectUrl(true);
            if ($url) {
                return $this->resultRedirectFactory->create()->setUrl($url);
            } else {
                $cartUrl = $this->_objectManager->get(\Magento\Checkout\Helper\Cart::class)->getCartUrl();
                return $this->resultRedirectFactory->create()->setUrl($this->_redirect->getRedirectUrl($cartUrl));
            }
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('We can\'t update the item right now.'));
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            return $this->_goBack();
        }
        return $this->resultRedirectFactory->create()->setPath('*/*');
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
}

?>