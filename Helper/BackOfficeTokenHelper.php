<?php

namespace Aurigma\CustomersCanvas\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;
use \Psr\Log\LoggerInterface;

use Aurigma\CustomersCanvas\Api\PluginSettingsManager;

use \Jumbojett\OpenIDConnectClient;

class BackOfficeTokenHelper extends AbstractHelper
{
    protected $settings;
    protected $_logger;

    protected $oidc;

    public function __construct(Context $context, PluginSettingsManager $settingsManager, LoggerInterface $logger)
    {
        $this->_logger = $logger;
        $this->settings = $settingsManager->getSettings();

        $this->oidc = new OpenIDConnectClient(
            $this->settings->getBackOfficeUrl(), 
            $this->settings->getBackOfficeClientId(), 
            $this->settings->getBackOfficeClientSecret());
            
        $this->oidc->providerConfigParam(array('token_endpoint'=> $this->settings->getBackOfficeUrl().'connect/token'));

        parent::__construct($context);
    }

    public function getAccessToken(): string
    {
        try {
            return $this->oidc->requestClientCredentialsToken()->access_token;
        } catch (\Throwable $e) {
            $this->_logger->error(
                'Back office token not gotten. '. PHP_EOL . $e->getMessage() . PHP_EOL . $e->getTraceAsString(), 
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