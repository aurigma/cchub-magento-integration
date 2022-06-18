<?php

namespace Aurigma\CustomersCanvas\Helper;


use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;
use \Psr\Log\LoggerInterface;

use \GuzzleHttp\Client;
use \Aurigma\AssetStorage\HeaderSelector;
use \Aurigma\AssetStorage\Configuration;
use \Aurigma\AssetStorage\Api\PrivateDesignsApi;

use Aurigma\CustomersCanvas\Api\PluginSettingsManager;
use Aurigma\CustomersCanvas\Helper\BackOfficeTokenHelper;


class PrivateDesignsHelper extends AbstractHelper
{

    protected $settings;
    protected $tokenHelper;
    protected $_logger;

    public function __construct(
        Context $context, 
        PluginSettingsManager $settingsManager,
        BackOfficeTokenHelper $tokenHelper,
        LoggerInterface $logger)
    {
        $this->_logger = $logger;
        $this->tokenHelper = $tokenHelper;
        $this->settings = $settingsManager->getSettings();
        parent::__construct($context);
    }

    public function remove($project)
    {
        $tenantId = $settings->getBackOfficeTenantId();
        $privateDesignsApi = $this->createApiClient($settings);

        try {
            $this->removeDesignsFromAssetStorage($project, $privateDesignsApi, $tenantId);
            $_logger->debug('Project with key (' . $project->getProjectKey() . ') was deleted from asset storage.', $this->getLogContext(__METHOD__));
        } catch (\Throwable $e) {
            $this->_logger->error(
                'Error when removing private design from assets storage. '. PHP_EOL . $e->getMessage() . PHP_EOL . $e->getTraceAsString(), 
                $this->getLogContext(__METHOD__)
            );
        }
    }

    private function createApiClient($settings)
    {

        if (substr($settings->getAssetStorageUrl(), -1) === '/') {
            $apiUrl = substr($settings->getAssetStorageUrl(), 0, -1);
        } else {
            $apiUrl = $settings->getAssetStorageUrl();
        }
        
        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $apiUrl,
            // You can set any number of default request options.
            'timeout'  => 60.0,
        ]);
        $selector = new HeaderSelector();
        $config = new Configuration();

        $config->setAccessToken($tokenHelper->getAccessToken());
        $config->setHost($apiUrl);
        
        return new PrivateDesignsApi($client, $config, $selector);
    }

    private function removeDesignsFromAssetStorage($project, $privateDesignsApi, $tenantId)
    {
        $properties = $project->getProperties();
        $designIds = $this->getDesignIds($properties);
        $userId = self::getUserId($properties);

        foreach ($designIds as $designId) {
            $privateDesignsApi->privateDesignsDelete($designId, $tenantId, $userId);
        }
    }

    private function getDesignIds(string $propertiesString): array
    {
        $stateIds = array();

        $properties = json_decode($propertiesString);
        if ($properties && isset($properties->{'_stateId'})) {
            $stateIds = $properties->{'_stateId'};
        }
    
        return $stateIds;
    }

    private function getUserId(string $propertiesString): string
    {
        $userId = '';

        $properties = json_decode($propertiesString);
        if ($properties && isset($properties->{'_userId'})) {
            $userId = $properties->{'_userId'};
        }
    
        return $userId;
    }

    private function getLogContext(string $methodName) 
    {
        return array('class' => get_class($this), 'method' => $methodName);
    }
}

?>