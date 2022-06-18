<?php
namespace Aurigma\CustomersCanvas\Controller\Adminhtml\Settings;

use \Magento\Backend\App\Action;
use \Magento\Backend\App\Action\Context;
use \Magento\Framework\App\Config\Storage\WriterInterface;
use \Magento\Framework\Controller\Result\JsonFactory;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Psr\Log\LoggerInterface;

use \Magento\Framework\App\Cache\TypeListInterface;
use \Magento\Framework\App\Cache\Frontend\Pool;
use \Magento\Framework\Message\ManagerInterface;

use Aurigma\CustomersCanvas\Api\PluginSettingsManager;

class Upload extends Action
{
    protected $_configWriter;
    protected $resultJsonFactory; 
    protected $_logger;

    protected $cacheTypeList;
    protected $cacheFrontendPool;

    protected $_messageManager;
    protected $settingManager;

    public function __construct(
        Context $context, 
        JsonFactory $resultJsonFactory, 
        LoggerInterface $logger, 
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        Pool $cacheFrontendPool,
        ManagerInterface $messageManager,
        PluginSettingsManager $settingManager
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_logger = $logger;
        $this->_configWriter = $configWriter;

        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;

        $this->_messageManager = $messageManager;

        $this->settingManager = $settingManager;
    } 

    public function execute()
    {
        try {
            $this->_logger->debug('Request for update customer\'s canvas settings from file was received', $this->getLogContext(__METHOD__));

            $filesData = $this->getRequest()->getFiles('ConfigFile');
            $configFileStr = file_get_contents($filesData['tmp_name']);
            $configJson = json_decode($configFileStr, true);

            if (!$this->_validateConfigJson($configJson)) {
                throw new \Exception('Config file has a wrong format.');
            }

            $postData = $this->getRequest()->getPostValue();
            $this->_logger->debug(json_encode($postData), $this->getLogContext(__METHOD__));
            $scopeId = array_key_exists('scopeId', $postData) ? $postData['scopeId'] : 0;
            $this->saveSettings($configJson, $postData['scopeType'], $scopeId);

            $this->cacheFunction();

            $responseMsg = __('Settings were updated from file successfully.');
            $this->_messageManager->addSuccess($responseMsg);
            
            $resultJson = $this->resultJsonFactory->create();
            $this->_logger->info('Settings were updated from file successfully.', $this->getLogContext(__METHOD__));
            return $resultJson->setData(array('status' => 'success', 'message' => $responseMsg));
        } catch (\Throwable $e) {
            $this->_logger->error(
                'Error when updating settings from file. '. PHP_EOL . $e->getMessage() . PHP_EOL . $e->getTraceAsString(), 
                $this->getLogContext(__METHOD__)
            );
            throw $e;
        }
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Aurigma_CustomersCanvas::settings');
    }

    /**
     * return boolean
     */
    protected function _validateConfigJson($json) 
    {
        return (
            isset($json['backOfficeUrl']) && !empty($json['backOfficeUrl']) &&
            isset($json['assetStorageUrl']) && !empty($json['assetStorageUrl']) &&
            isset($json['assetProcessorUrl']) && !empty($json['assetProcessorUrl']) &&
            isset($json['backOfficeTenantId']) && !empty($json['backOfficeTenantId']) &&
            isset($json['backOfficeStorefrontId']) && !empty($json['backOfficeStorefrontId']) &&
            isset($json['backOfficeClientId']) && !empty($json['backOfficeClientId']) &&
            isset($json['backOfficeClientSecret']) && !empty($json['backOfficeClientSecret']) &&
            isset($json['tenancyName']) && !empty($json['tenancyName'])
        );
    }

    protected function saveSettings($settings, $scopeType = 'default', $scopeId = 0) 
    {
        $newSettings = $this->settingManager->getSettings($scopeType);

        $newSettings->setBackOfficeUrl($settings['backOfficeUrl']);
        $newSettings->setAssetStorageUrl($settings['assetStorageUrl']);
        $newSettings->setAssetProcessorUrl($settings['assetProcessorUrl']);
        $newSettings->setTenancyName($settings['tenancyName']);
        $newSettings->setBackOfficeTenantId($settings['backOfficeTenantId']);
        $newSettings->setBackOfficeStorefrontId($settings['backOfficeStorefrontId']);
        $newSettings->setBackOfficeClientId($settings['backOfficeClientId']);
        $newSettings->setBackOfficeClientSecret($settings['backOfficeClientSecret']);

        $this->settingManager->save($newSettings, $scopeType, $scopeId);
    }

    private function getLogContext(string $methodName) 
    {
        return array('class' => get_class($this), 'method' => $methodName);
    }

    public function cacheFunction()
    {
        $types = array(
            'config',
            'layout',
            'block_html',
            'collections',
            'reflection',
            'db_ddl',
            'eav',
            'config_integration',
            'config_integration_api',
            'full_page',
            'translate',
            'config_webservice');

        foreach ($types as $type) {
            $this->cacheTypeList->cleanType($type);
        }
        foreach ($this->cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }
    }
}

?>