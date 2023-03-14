<?php
/** 
 * Description: Gets back office tokens
*/

namespace Aurigma\CustomersCanvas\Api;

use \Psr\Log\LoggerInterface;

use Aurigma\CustomersCanvas\Helper\BackOfficeTokenHelper;
use Aurigma\CustomersCanvas\Api\PluginSettingsManager;

use \GuzzleHttp\Client;
use \Aurigma\Storefront\HeaderSelector;
use \Aurigma\Storefront\Configuration;
use \Aurigma\Storefront\Api\StorefrontUsersApi;
use \Aurigma\Storefront\Model\MergeAnonymousUserDataInput;
use \Aurigma\Storefront\Model\CreateStorefrontUserDto;

class StorefrontUsersService
{
    protected $settings;
    protected $tokenHelper;
    protected $_logger;

    public function __construct(PluginSettingsManager $settingsManager, BackOfficeTokenHelper $tokenHelper, LoggerInterface $logger)
    {
        $this->settings = $settingsManager->getSettings();
        $this->tokenHelper = $tokenHelper;
        $this->_logger = $logger;
    }

    public function mergeAnonymous($anonymousStorefrontUserId, $regularStorefrontUserId)
    {
        try {
            $storefrontUsersApi = $this->getStorefrontUsersApi();

            $mergeInfo = new MergeAnonymousUserDataInput();
            $mergeInfo->setAnonymousStorefrontUserId($anonymousStorefrontUserId);
            $mergeInfo->setRegularStorefrontUserId($regularStorefrontUserId);

            $response = $storefrontUsersApi->storefrontUsersMergeAnonymous(
                $this->settings->getBackOfficeStorefrontId(), 
                $this->settings->getBackOfficeTenantId(),
                $mergeInfo);

            $this->_logger->info("Anonymous user($anonymousStorefrontUserId) was merged with regular user($regularStorefrontUserId): "
                . json_encode($response) . PHP_EOL, $this->getLogContext(__METHOD__));
            return $response;
        } catch (\Throwable $e) {
            $this->_logger->error(
                'Error when merge anonymous and regular users. '. PHP_EOL . $e->getMessage() . PHP_EOL . $e->getTraceAsString(), 
                $this->getLogContext(__METHOD__)
            );
            throw $e;
        }
    }

    public function getStorefrontUser($regularStorefrontUserId)
    {
        try {
            $storefrontUsersApi = $this->getStorefrontUsersApi();

            $response = $storefrontUsersApi->storefrontUsersGet(
                $regularStorefrontUserId, 
                $this->settings->getBackOfficeStorefrontId(), 
                $this->settings->getBackOfficeTenantId());

            $this->_logger->debug("Storefront user($regularStorefrontUserId) was received". PHP_EOL, $this->getLogContext(__METHOD__));
            return $response;
        } catch (\Throwable $e) {
            $this->_logger->error(
                "Error when getting storefront user with id $regularStorefrontUserId. ". PHP_EOL . $e->getMessage() . PHP_EOL . $e->getTraceAsString(), 
                $this->getLogContext(__METHOD__)
            );
            return null;
        }
    }

    public function createStorefrontUser($regularStorefrontUserId)
    {
        try {
            $storefrontUsersApi = $this->getStorefrontUsersApi();

            $createInfo = new CreateStorefrontUserDto();
            $createInfo->setIsAnonymous(false);
            $createInfo->setStorefrontUserId($regularStorefrontUserId);

            $response = $storefrontUsersApi->storefrontUsersCreate(
                $this->settings->getBackOfficeStorefrontId(), 
                $this->settings->getBackOfficeTenantId(),
                $createInfo);

            $this->_logger->info("Regular storefront user($regularStorefrontUserId) was created.", $this->getLogContext(__METHOD__));
            return $response;
        } catch (\Throwable $e) {
            $this->_logger->error(
                'Error when creating regular storefront users. '. PHP_EOL . $e->getMessage() . PHP_EOL . $e->getTraceAsString(), 
                $this->getLogContext(__METHOD__)
            );
            throw $e;
        }
    }

    private function getStorefrontUsersApi()
    {
        $this->tenantId = $this->settings->getBackOfficeTenantId();

        if (substr($this->settings->getBackOfficeUrl(), -1) === '/') {
            $apiUrl = substr($this->settings->getBackOfficeUrl(), 0, -1);
        } else {
            $apiUrl = $this->settings->getBackOfficeUrl();
        }

        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $apiUrl,
            // You can set any number of default request options.
            'timeout'  => 60.0,
        ]);
        $selector = new HeaderSelector();
        $config = new Configuration();
        $config->setAccessToken($this->tokenHelper->getAccessToken());
        $config->setHost($apiUrl);
        
        return new StorefrontUsersApi($client, $config, $selector);
    }

    private function getLogContext(string $methodName) 
    {
        return array('class' => get_class($this), 'method' => $methodName);
    }
}

?>