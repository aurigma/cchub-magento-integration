<?php

namespace Aurigma\CustomersCanvas\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;

use Aurigma\CustomersCanvas\Api\PluginSettingsManager;

class CustomerIdConverter extends AbstractHelper
{
    public const USER_COOKIE_PREFIX = 'magento';
    public const SEPARATOR = '_';

    protected $settings;

    public function __construct(Context $context, PluginSettingsManager $settingsManager)
    {
        $this->settings = $settingsManager->getSettings();
        parent::__construct($context);
    }

    public function convertToBackOfficeId($customerId): string
    {
        return self::USER_COOKIE_PREFIX . self::SEPARATOR . $this->settings->getBackOfficeStorefrontId() . self::SEPARATOR . $customerId;
    }

    public function getCustomerIdFromBackOfficeId($backOfficeId) 
    {
        return (explode(self::SEPARATOR, $backOfficeId))[2];
    }
}

?>