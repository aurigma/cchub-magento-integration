<?php

namespace Aurigma\CustomersCanvas\Plugin\Csp;

use \Magento\Csp\Model\Collector\CspWhitelistXmlCollector;
use \Magento\Csp\Model\Policy\FetchPolicy;
use \Magento\Store\Model\ScopeInterface;
use \Psr\Log\LoggerInterface;

use Aurigma\CustomersCanvas\Api\PluginSettingsManager;

class Csp
{

    /**
     * @var Aurigma\CustomersCanvas\Api\Data\PluginSettings
     */
    protected $settings;

    /**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $_logger;

    /**
     * @param \Aurigma\CustomersCanvas\Api\PluginSettingsManager $settingManager
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(PluginSettingsManager $settingManager, LoggerInterface $logger) 
    {
        $this->settings = $settingManager->getSettings(ScopeInterface::SCOPE_STORE);
        $this->_logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function afterCollect(CspWhitelistXmlCollector $cspWhitelistXmlCollector, $defaultPolicies = []): array
    {
        $policyIds = [
            'connect-src',
            'script-src',
            'img-src',
            'form-action',
            'frame-src',
            'style-src',
            'font-src',
            'default-src',
            'object-src',
            'media-src',
            'frame-ancestors'
        ];

        $url = $this->settings->getBackOfficeUrl();
        $parsed = parse_url($url);
        if (isset($parsed) && isset($parsed['host'])) {
            $defaultPolicies = array_merge($defaultPolicies, $this->addUrlToWhiteList($parsed['host'], $policyIds));
        }
        

        $url = $this->settings->getAssetStorageUrl();
        $parsed = parse_url($url);
        if (isset($parsed) && isset($parsed['host'])) {
            $defaultPolicies = array_merge($defaultPolicies, $this->addUrlToWhiteList($parsed['host'], $policyIds));
        }

        $url = $this->settings->getAssetProcessorUrl();
        $parsed = parse_url($url);
        if (isset($parsed) && isset($parsed['host'])) {
            $defaultPolicies = array_merge($defaultPolicies, $this->addUrlToWhiteList($parsed['host'], $policyIds));
        }

        // TO DO: Get this programmatically
        $defaultPolicies = array_merge($defaultPolicies, $this->addUrlToWhiteList('staticjs-aurigma.azureedge.net', $policyIds));
        $defaultPolicies = array_merge($defaultPolicies, $this->addUrlToWhiteList('cc-farm-dev.eastus.cloudapp.azure.com', $policyIds));
        $defaultPolicies = array_merge($defaultPolicies, $this->addUrlToWhiteList('cc-farm.aurigma.net', $policyIds));
        $defaultPolicies = array_merge($defaultPolicies, $this->addUrlToWhiteList('cc-apps.aurigma.net', $policyIds));

        return $defaultPolicies;
    }

    private function addUrlToWhiteList($host, $policyIds): array 
    {
        $result = [];

        $hosts = $host == 'localhost' ? [ "$host:*", $host ] : [ $host ];

        foreach ($policyIds as $policyId) {
            $result[] = new FetchPolicy(
                $policyId,
                false,
                $hosts,
                [],
                false,
                false,
                false,
                [],
                [],
                false,
                false
            );
        }

        $this->_logger->debug("Policy CSP for host($host) was edded.");

        return $result;
    }
}

?>