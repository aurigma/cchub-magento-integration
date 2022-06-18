<?php

namespace Aurigma\CustomersCanvas\Plugin\Session;

use \Aurigma\CustomersCanvas\Helper\Guid;
use \Aurigma\CustomersCanvas\Helper\CustomerIdConverter;

use \Magento\Customer\Model\Session;
use \Magento\Checkout\Model\Session as CheckoutSession;
use \Magento\Framework\App\Http\Context;
use \Magento\Framework\App\ActionInterface;
use \Magento\Framework\App\RequestInterface;
use \Magento\Framework\Session\SessionManager;
use \Magento\Framework\Session\SessionManagerInterface;

use \Psr\Log\LoggerInterface;
use \Closure;

class CustomerSessionContext
{
	public const CUSTOMER_ID_KEY = 'customers_canvas_customer_id';
	public const MODIFY_CUSTOMER_ID_KEY = 'customers_canvas_modify_customer_id';
	public const CUSTOMER_NAME_KEY = 'customers_canvas_customer_name';
	public const CUSTOMER_EMAIL_KEY = 'customers_canvas_customer_email';

	private const USER_COOKIE_PREFIX = 'magento_';

	/**
 	* @var \Magento\Framework\Session\SessionManager
 	*/
	 protected $sessionManager;

    /**
 	* @var \Magento\Customer\Model\Session
 	*/
    protected $customerSession;

	/**
 	* @var \Magento\Checkout\Model\Session
 	*/
	 protected $checkoutSession;

    /**
     * @var \Magento\Framework\App\Http\Context
    */
    protected $httpContext;

	protected $session;

	/**
	 * @var \Aurigma\CustomersCanvas\Helper\Guid
	 */
	protected $guidHelper;

	/**
	 * @var \Aurigma\CustomersCanvas\Helper\CustomerIdConverter
	 */
	protected $idConverter;

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $_logger;
	 
    /**
     * @param \Magento\Framework\Session\SessionManager $sessionManager
     * @param \Magento\Customer\Model\Session $customerSession
	 * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\App\Http\Context $httpContext
	 * @param \Aurigma\CustomersCanvas\Helper\Guid $guidHelper
	 * @param \Aurigma\CustomersCanvas\Helper\CustomerIdConverter $idConverter
	 * @param \Psr\Log\LoggerInterface $logger
    */
    public function __construct(
		SessionManager $sessionManager,
		Session $customerSession, 
		CheckoutSession $checkoutSession, 
		Context $httpContext, 
		Guid $guidHelper, 
		CustomerIdConverter $idConverter,
		SessionManagerInterface $session,
		LoggerInterface $logger) 
    {
    	$this->sessionManager = $sessionManager;
    	$this->customerSession = $customerSession;
    	$this->checkoutSession = $checkoutSession;
    	$this->httpContext = $httpContext;
    	$this->guidHelper = $guidHelper;
    	$this->idConverter = $idConverter;

		$this->session = $session;

		$this->_logger = $logger;
    }

    /**
 	 * @param \Magento\Framework\App\ActionInterface $subject
 	 * @param callable $proceed
 	 * @param \Magento\Framework\App\RequestInterface $request
 	 * @return mixed
 	*/
    public function aroundDispatch(ActionInterface $subject, Closure $proceed, RequestInterface $request) 
    {
		try {

			$customerId = $this->getCurrentCustomerId();
			$this->httpContext->setValue(
				CustomerSessionContext::CUSTOMER_ID_KEY,
				$customerId,
				false
			);

			$this->httpContext->setValue(
				CustomerSessionContext::MODIFY_CUSTOMER_ID_KEY,
				$this->idConverter->convertToBackOfficeId($customerId),
				false
			);

			$this->httpContext->setValue(
				CustomerSessionContext::CUSTOMER_NAME_KEY,
				$this->customerSession->getCustomer()->getName(),
				false
			);

			$this->httpContext->setValue(
				CustomerSessionContext::CUSTOMER_EMAIL_KEY,
				$this->customerSession->getCustomer()->getEmail(),
				false
			);

			return $proceed($request);

		} catch (\Throwable $e) {
			$this->_logger->error(
                'Error when setting customer session data. '. PHP_EOL . $e->getMessage() . PHP_EOL . $e->getTraceAsString(), 
                $this->getLogContext(__METHOD__)
            );
            throw $e;
		}
    }

	private function getCurrentCustomerId() 
	{
		$customerId = $this->customerSession->getCustomerId();
		if (!$customerId) {

			$customerId = $this->sessionManager->getSessionId();

			$savedCustomerId = 
				$this->customerSession->getAurigmaTempCustomerId() ?? 
				$this->checkoutSession->getAurigmaTempCustomerId();

			if ($customerId) {
				$this->customerSession->setAurigmaTempCustomerId($customerId);
				$this->checkoutSession->setAurigmaTempCustomerId($customerId);

				$this->_logger->debug("Use session id $customerId like temp customer id.", $this->getLogContext(__METHOD__));
			}

			$customerId = 
				$this->customerSession->getAurigmaTempCustomerId() ?? 
				$this->checkoutSession->getAurigmaTempCustomerId();

			if (!$customerId) {
				$customerId = $this->guidHelper->create();
				$this->customerSession->setAurigmaTempCustomerId($customerId);
				$this->checkoutSession->setAurigmaTempCustomerId($customerId);

				$this->_logger->info("Temp customer with id $customerId was created.", $this->getLogContext(__METHOD__));
			}
		}
		return $customerId;
	}

	private function getLogContext(string $methodName) 
	{
        return array('class' => get_class($this), 'method' => $methodName);
    }
}


?>