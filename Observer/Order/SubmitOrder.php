<?php
namespace Aurigma\CustomersCanvas\Observer\Order;

use \Magento\Framework\Event\Observer as EventObserver;
use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\App\Http\Context as HttpContext;
use \Magento\Backend\Model\UrlInterface;

use \Magento\Customer\Model\Session;
use \Magento\Checkout\Model\Session as CheckoutSession;
use \Magento\Framework\Session\SessionManager;
use \Aurigma\CustomersCanvas\Helper\CustomerIdConverter;

use \Psr\Log\LoggerInterface;

use Aurigma\CustomersCanvas\Model\BackofficeProjectFactory;
use Aurigma\CustomersCanvas\Observer\Cart\CheckoutCartAdd;
use Aurigma\CustomersCanvas\Plugin\Session\CustomerSessionContext;
use Aurigma\CustomersCanvas\Helper\ProjectsHelper;

class SubmitOrder implements ObserverInterface
{
    const BACKOFFICE_OPTION_NAME = 'customers_canvas_bo_project_key';

    private $projectHelper;
    private $backOfficeProjectFactory;
    protected $httpContext;
    protected $backendUrl;

    protected $customerSession;
    protected $checkoutSession;
    protected $sessionManager;
    protected $idConverter;

    protected $_logger;
 
    public function __construct(
        ProjectsHelper $projectHelper,
        BackofficeProjectFactory $backOfficeProjectFactory,
        HttpContext $httpContext,
        UrlInterface $backendUrl,
        Session $customerSession, 
		CheckoutSession $checkoutSession,
        SessionManager $sessionManager,
        CustomerIdConverter $idConverter, 
        LoggerInterface $logger
    ) {
        $this->projectHelper = $projectHelper;
        $this->backOfficeProjectFactory = $backOfficeProjectFactory;
        $this->httpContext = $httpContext;
        $this->backendUrl = $backendUrl;

        $this->customerSession = $customerSession;
    	$this->checkoutSession = $checkoutSession;
        $this->sessionManager = $sessionManager;
        $this->idConverter = $idConverter;

        $this->_logger = $logger;
    }
 
    public function execute(EventObserver $observer)
    {
        try {
            $quote = $observer->getData('quote');
            $order = $observer->getData('order');

            if ($quote) {
                $itemsCollection = $quote->getAllVisibleItems();
                foreach ($itemsCollection as $quoteItem) {
                    $this->processItemIfNeed($quoteItem, $order);
                }
            }
        } catch (\Throwable $e) {
			$this->_logger->error(
                'Error when processing quote order item. '. PHP_EOL . $e->getMessage() . PHP_EOL . $e->getTraceAsString(), 
                $this->getLogContext(__METHOD__)
            );
            throw $e;
		}
    }

    private function processItemIfNeed($quoteItem, $order)
    {
        if ($this->isItemPersonalized($quoteItem)) {
            $backOfficeProject = $this->getBackOfficeProjectForItem($quoteItem);
            $projectKey = $backOfficeProject->getProjectKey();

            $orderItem = $this->getOrderItemForQuoteId($quoteItem->getId(), $order);

            $savedUserId = $this->getUserIdFromProject($backOfficeProject->getProperties());
            $userId = $this->getCurrentCustomerId($savedUserId);
            $customerNiceName = trim($this->customerSession->getCustomer()->getName());
            if ($savedUserId != $userId) {
                $backOfficeProject->setProperties($this->setRegularUser($backOfficeProject->getProperties(), $userId));
            }

            if (!$customerNiceName) {
                $customerNiceName = $order->getCustomerName();
            }

            $submitProductId = $this->getProductIdForbackOffice($quoteItem);
            $createdProject = $this->projectHelper->createProject(
                $backOfficeProject, 
                $userId, 
                $customerNiceName ?? $userId, 
                $order->getId(), 
                $submitProductId, 
                $quoteItem->getName(),
                $this->backendUrl->getUrl('sales/order/view/order_id/' . $order->getId(), [])
            );
            
            $this->_logger->info(json_encode($createdProject));

            $this->deleteFromDb($projectKey);
            // TODO: remove option by code CheckoutCartAdd::BACKOFFICE_OPTION_NAME
            $this->_logger->error('Submit ' . get_class($quoteItem));

            $quoteItem->addOption(array(
                'code' => SubmitOrder::BACKOFFICE_OPTION_NAME,
                'value' => $createdProject['id']
            ));

            $existentOptions = $orderItem->getProductOptions();
            $existentOptions[SubmitOrder::BACKOFFICE_OPTION_NAME] = $createdProject['id'];
            $orderItem->setProductOptions($existentOptions)->save();

            $this->projectHelper->changeProjectStatus($createdProject['id'], 3); // 3 - active
        }
    }

    private function getProductIdForbackOffice($quoteItem)
    {
        $productId = $quoteItem->getProductId();

        $itemOption = $quoteItem->getOptionByCode(CheckoutCartAdd::ORIGINAL_PRODUCT_NAME);
        if (!$itemOption) {
            return $productId;
        }
        $originalProductId = $itemOption->getValue();

        if (!$originalProductId || $originalProductId === '') {
            return $productId;
        } else {
            $productId = (int) $originalProductId;
        }

        return $productId;
    }

    private function getOrderItemForQuoteId($quoteItemId, $order)
    {
        $orderItems = $order->getAllItems();
        foreach ($orderItems as $orderItem) {
            $orderItemData = $orderItem->getData();
            if ($orderItemData['quote_item_id'] === $quoteItemId) {
                return $orderItem;
            }
        }
        return null;
    }

    private function getCurrentCustomerId($savedId) 
	{
		$customerId = $this->customerSession->getCustomerId();
		if ($customerId) {
            return $this->idConverter->convertToBackOfficeId($customerId);
		}
		return $savedId;
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

    protected function deleteFromDb($projectKey)
    {
        $modelContext = $this->backOfficeProjectFactory->create();
        $projects = $modelContext->getCollection()->addFieldToFilter('project_key', ['eq' => $projectKey]);

        if ($projects->getSize()) {
            $project = $projects->getFirstItem();
            $project->delete();
            $this->_logger->info('Project with key: ' . $projectKey . ' was deleted from db.' , $this->getLogContext(__METHOD__));
        } else {
            $this->_logger->info('Project with key: ' . $projectKey . ' was not found in db.' , $this->getLogContext(__METHOD__));
        }
    }

    private function getUserIdFromProject(string $propertiesString)
    {
        $resultId = null;
        $properties = json_decode($propertiesString);
        $userId = $properties->{'_userId'};
        if ($userId) {
            $resultId = $userId;
        }
        return $resultId;
    }

    private function setRegularUser($propertiesString, $userId) {
        $properties = json_decode($propertiesString);
        $properties->{'_userId'} = $userId;

        return json_encode($properties);
    }

    private function isItemPersonalized($quoteItem)
    {
        $personalizedOption = $quoteItem->getOptionByCode(CheckoutCartAdd::BACKOFFICE_OPTION_NAME);
        return $personalizedOption ? true : false;
    }

    private function getLogContext(string $methodName) 
    {
        return array('class' => get_class($this), 'method' => $methodName);
    }
}

?>