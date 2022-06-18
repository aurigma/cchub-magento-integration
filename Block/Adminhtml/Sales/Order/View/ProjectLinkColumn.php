<?php

namespace Aurigma\CustomersCanvas\Block\Adminhtml\Sales\Order\View;

use \Magento\Backend\Block\Template\Context;
use \Magento\CatalogInventory\Api\StockRegistryInterface;
use \Magento\CatalogInventory\Api\StockConfigurationInterface;
use \Magento\Framework\Registry;
use \Magento\Catalog\Model\Product\OptionFactory;
use \Magento\Sales\Block\Adminhtml\Items\Column\DefaultColumn;
use \Psr\Log\LoggerInterface;

use Aurigma\CustomersCanvas\Observer\Order\SubmitOrder;
use Aurigma\CustomersCanvas\Api\PluginSettingsManager;

/**
 * Adds colum for LineItems in Order with link to BackOffice project
 */
class ProjectLinkColumn extends DefaultColumn
{
    protected $settings;
    protected $_logger;

    public function __construct(
        Context $context,
        StockRegistryInterface $stockRegistry,
        StockConfigurationInterface $stockConfiguration,
        Registry $registry,
        OptionFactory $optionFactory,
        PluginSettingsManager $settingsManager, 
        LoggerInterface $logger,
        array $data = [])
    {
        $this->settings = $settingsManager->getSettings();
        $this->_logger = $logger;

        parent::__construct($context, $stockRegistry, $stockConfiguration, $registry, $optionFactory, $data);
    }

    public function getProjectLink() 
    {
        $options = $this->getItem()->getProductOptions();

        if ($options && isset($options[SubmitOrder::BACKOFFICE_OPTION_NAME])) {
            $backOfficeUrl = $this->settings->getBackOfficeUrl();
            $value = json_encode($options[SubmitOrder::BACKOFFICE_OPTION_NAME]);
            return '<a target="_blank" href="' . $backOfficeUrl . 'app/projects/' . $value . '" >' . $value . '</a>';
        } else {
            return '';
        }
    }
}

?>