<?php

namespace Aurigma\CustomersCanvas\Observer\Auth;

use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer;
use \Magento\Customer\Model\Session;
use \Magento\Checkout\Model\Session as CheckoutSession;
use \Psr\Log\LoggerInterface;

use Aurigma\CustomersCanvas\Model\BackofficeProjectFactory;
use Aurigma\CustomersCanvas\Api\StorefrontUsersService;
use Aurigma\CustomersCanvas\Helper\CustomerIdConverter;

class CustomerLoginMerge implements ObserverInterface
{
    /**
 	* @var \Magento\Customer\Model\Session
 	*/
    protected $customerSession;

    /**
     * @var \Magento\Checkout\Model\Session
    */
    protected $checkoutSession;

    /**
     * @var Aurigma\CustomersCanvas\Model\BackofficeProjectFactory
     */
    protected $backOfficeProjectFactory;

    /**
     * @var Aurigma\CustomersCanvas\Api\StorefrontUsersService
     */
    protected $storefrontUserService;

    /**
     * @var Aurigma\CustomersCanvas\Helper\CustomerIdConverter
     */
    protected $idConverter;

    protected $_logger;

    public function __construct(
        StorefrontUsersService $storefrontUserService, 
        Session $customerSession, 
        CheckoutSession $checkoutSession,
        BackofficeProjectFactory $backOfficeProjectFactory,
        CustomerIdConverter $idConverter, 
        LoggerInterface $logger)
	{
        $this->storefrontUserService = $storefrontUserService;
        $this->customerSession = $customerSession;
    	$this->checkoutSession = $checkoutSession;
    	$this->backOfficeProjectFactory = $backOfficeProjectFactory;
    	$this->idConverter = $idConverter;
        $this->_logger = $logger;
	}

    public function execute(Observer $observer)
    {
        try {
            $customer = $observer->getEvent()->getCustomer();
            $customerId = $customer->getId();

            $this->_logger->info("Customer with id $customerId and email " . $customer->getEmail() . " logged in system.", $this->getLogContext(__METHOD__));

            $tempCustomerId = $this->customerSession->getAurigmaTempCustomerId() ?? $this->checkoutSession->getAurigmaTempCustomerId();

            if ($tempCustomerId) {
                $this->_logger->debug('Temp Customer exists', $this->getLogContext(__METHOD__));
                $this->_logger->debug($tempCustomerId);
            } else {
                $this->_logger->debug('Temp Customer doesn\'t exist', $this->getLogContext(__METHOD__));
            }

            $customerId = $this->idConverter->convertToBackOfficeId($customerId);

            if (!$this->checkStorefrontUser($customerId)) {
                $this->createRegularStorefrontUser($customerId);
            }
            
            $backOfficeTempCustomerId = $this->idConverter->convertToBackOfficeId($tempCustomerId);
            
            if ($customerId && $this->checkStorefrontUser($backOfficeTempCustomerId)) {

                $this->storefrontUserService->mergeAnonymous($backOfficeTempCustomerId, $customerId);
                $this->mergeProjectsInBd($backOfficeTempCustomerId, $customerId);
                
            } else {
                $this->_logger->debug('Temp Customer doesn\'t exist in BackOffice side.', $this->getLogContext(__METHOD__));
            }

        } catch (\Throwable $e) {
			$this->_logger->error(
                'Error when merge customers during login. '. PHP_EOL . $e->getMessage() . PHP_EOL . $e->getTraceAsString(), 
                $this->getLogContext(__METHOD__)
            );
            throw $e;
		}
    }

    private function mergeProjectsInBd($tempCustomerId, $customerId)
    {
        $modelContext = $this->backOfficeProjectFactory->create();
        $projects = $modelContext->getCollection()->addFieldToFilter('properties', ['like' => "%$tempCustomerId%"]);

        if ($projects->getSize()) {
            foreach ($projects as $project) {
                $this->swapUserInProperties($project, $customerId, $tempCustomerId);
            }
        } else {
            $this->_logger->debug('There are not backoffice projects for changing user', $this->getLogContext(__METHOD__));
        }
    }

    private function swapUserInProperties($project, $customerId, $tempCustomerId) 
    {
        $oldPropertiesStr = $project->getProperties();
        $this->_logger->debug('Old project properties: ' . $oldPropertiesStr, $this->getLogContext(__METHOD__));
        $newPropertiesStr = str_replace($tempCustomerId, $customerId, $oldPropertiesStr);
        $this->_logger->debug('New project properties: ' . $newPropertiesStr, $this->getLogContext(__METHOD__));

        // Why is there json_encode?? I don't now, but it dies without it
        $transferObj = json_decode($newPropertiesStr);
        $project->setProperties(json_encode($transferObj));
        $project->save();
    }

    private function checkStorefrontUser($currentUserId)
    {
        $storefrontUser = $this->storefrontUserService->getStorefrontUser($currentUserId);
        if ($storefrontUser) {
            return true;
        } else {
            return false;
        }
    }

    private function createRegularStorefrontUser($currentUserId)
    {
        $this->storefrontUserService->createStorefrontUser($currentUserId);
    }

    private function getLogContext(string $methodName) 
    {
        return array('class' => get_class($this), 'method' => $methodName);
    }
}

?>