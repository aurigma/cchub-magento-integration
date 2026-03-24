<?php

namespace Aurigma\CustomersCanvas\Controller\Token;

use Magento\Customer\Model\Context as ContextAuth;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;

use Aurigma\CustomersCanvas\Api\StorefrontUsersService;
use Aurigma\CustomersCanvas\Plugin\Session\CustomerSessionContext;

class Get extends Action implements HttpGetActionInterface
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var HttpContext
     */
    private $httpContext;

    /**
     * @var StorefrontUsersService
     */
    private $storefrontUsersService;

    /**
     * @var LoggerInterface
     */
    private $_logger;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        HttpContext $httpContext,
        StorefrontUsersService $storefrontUsersService,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->httpContext = $httpContext;
        $this->storefrontUsersService = $storefrontUsersService;
        $this->_logger = $logger;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            $storefrontUserId = $this->httpContext->getValue(CustomerSessionContext::MODIFY_CUSTOMER_ID_KEY);
            if (empty($storefrontUserId)) {
                return $result->setData([
                    'success' => false,
                    'token' => '',
                    'message' => 'Storefront user id is empty.'
                ]);
            }

            $isLoggedIn = (bool)$this->httpContext->getValue(ContextAuth::CONTEXT_AUTH);
            $token = $this->storefrontUsersService->getCcHubTokenForStorefrontUser($storefrontUserId, !$isLoggedIn);

            return $result->setData([
                'success' => !empty($token),
                'token' => $token,
                'userId' => $storefrontUserId
            ]);
        } catch (\Throwable $e) {
            $this->_logger->error(
                'Error when getting token in token endpoint. '. PHP_EOL . $e->getMessage() . PHP_EOL . $e->getTraceAsString(),
                ['class' => get_class($this), 'method' => __METHOD__]
            );

            return $result->setData([
                'success' => false,
                'token' => '',
                'message' => 'Unexpected token endpoint error.'
            ]);
        }
    }
}
