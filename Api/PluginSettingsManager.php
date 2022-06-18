<?php

namespace Aurigma\CustomersCanvas\Api;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\App\Config\Storage\WriterInterface;
use \Magento\Store\Model\ScopeInterface;
use \Psr\Log\LoggerInterface;

use Aurigma\CustomersCanvas\Api\Data\PluginSettings;

class PluginSettingsManager {

    protected $scopeConfig;
    protected $configWriter;

    protected $_logger;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->_logger = $logger;
    }

    public function getSettings($scopeType = ScopeInterface::SCOPE_STORE): PluginSettings
    {
        try {

            $settings = new PluginSettings();

            $connectPath = PluginSettings::BASE_PATH . PluginSettings::CONNECT_PATH;
            $editorPath = PluginSettings::BASE_PATH . PluginSettings::EDITOR_PATH;

            $settings->setBackOfficeUrl($this->scopeConfig->getValue($connectPath . PluginSettings::BACK_OFFICE_URL , $scopeType));
            $settings->setAssetStorageUrl($this->scopeConfig->getValue($connectPath . PluginSettings::ASSETSTORAGE_URL , $scopeType));
            $settings->setAssetProcessorUrl($this->scopeConfig->getValue($connectPath . PluginSettings::ASSETPROCESSOR_URL , $scopeType));
            $settings->setTenancyName($this->scopeConfig->getValue($connectPath . PluginSettings::TENANCY_NAME , $scopeType));
            $settings->setBackOfficeTenantId($this->scopeConfig->getValue($connectPath . PluginSettings::TENANT_ID , $scopeType));
            $settings->setBackOfficeStorefrontId($this->scopeConfig->getValue($connectPath . PluginSettings::STOREFRONT_ID , $scopeType));
            $settings->setBackOfficeClientId($this->scopeConfig->getValue($connectPath . PluginSettings::CLIENT_ID , $scopeType));
            $settings->setBackOfficeClientSecret($this->scopeConfig->getValue($connectPath . PluginSettings::CLIENT_SECRET , $scopeType));

            $settings->setPopupMode($this->scopeConfig->getValue($editorPath . PluginSettings::POPUP_MODE , $scopeType));

            return $settings;

        } catch (\Throwable $e) {
			$this->_logger->error(
                'Error when getting plugin settings. '. PHP_EOL . $e->getMessage() . PHP_EOL . $e->getTraceAsString(), 
                $this->getLogContext(__METHOD__)
            );
            throw $e;
		}
        
    }

    public function save(PluginSettings $settings, $scopeType = 'default', $scopeId = 0)
    {
        try {

            $connectPath = PluginSettings::BASE_PATH . PluginSettings::CONNECT_PATH;
            $editorPath = PluginSettings::BASE_PATH . PluginSettings::EDITOR_PATH;

            $this->configWriter->save($connectPath . PluginSettings::BACK_OFFICE_URL, $settings->getBackOfficeUrl(), $scopeType, $scopeId);
            $this->configWriter->save($connectPath . PluginSettings::ASSETSTORAGE_URL, $settings->getAssetStorageUrl(), $scopeType, $scopeId);
            $this->configWriter->save($connectPath . PluginSettings::ASSETPROCESSOR_URL, $settings->getAssetProcessorUrl(), $scopeType, $scopeId);
            $this->configWriter->save($connectPath . PluginSettings::TENANCY_NAME, $settings->getTenancyName(), $scopeType, $scopeId);
            $this->configWriter->save($connectPath . PluginSettings::TENANT_ID, $settings->getBackOfficeTenantId(), $scopeType, $scopeId);
            $this->configWriter->save($connectPath . PluginSettings::STOREFRONT_ID, $settings->getBackOfficeStorefrontId(), $scopeType, $scopeId);
            $this->configWriter->save($connectPath . PluginSettings::CLIENT_ID, $settings->getBackOfficeClientId(), $scopeType, $scopeId);
            $this->configWriter->save($connectPath . PluginSettings::CLIENT_SECRET, $settings->getBackOfficeClientSecret(), $scopeType, $scopeId);

            $this->configWriter->save($editorPath . PluginSettings::POPUP_MODE, $settings->getPopupMode(), $scopeType, $scopeId);

        } catch (\Throwable $e) {
			$this->_logger->error(
                'Error when saving plugin settings. '. PHP_EOL . $e->getMessage() . PHP_EOL . $e->getTraceAsString(), 
                $this->getLogContext(__METHOD__)
            );
            throw $e;
		}  
    }

    private function getLogContext(string $methodName) 
    {
        return array('class' => get_class($this), 'method' => $methodName);
    }
}

?>