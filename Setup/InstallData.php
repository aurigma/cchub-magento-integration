<?php
namespace Aurigma\CustomersCanvas\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Catalog\Model\Product;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use \Psr\Log\LoggerInterface;

class InstallData implements InstallDataInterface
{
	public const INTEGRATED_ATTRIBUTE = 'customers_canvas_integrated';

	private $eavSetupFactory;
    protected $_logger;

	public function __construct(EavSetupFactory $eavSetupFactory, LoggerInterface $logger)
	{
		$this->eavSetupFactory = $eavSetupFactory;
        $this->_logger = $logger;
	}

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
		try {
			$this->_logger->info('Customer\'s Canvas Integrated Attribute started adding to General set.', $this->getLogContext(__METHOD__));
			$eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
			$eavSetup->addAttribute(
				Product::ENTITY,
				InstallData::INTEGRATED_ATTRIBUTE,
				[
					'group' => 'General',
					'type' => 'int',
					'backend' => '',
					'frontend' => '',
					'label' => __('Integrated with Customer\'s Canvas'),
					'input' => 'select',
					'class' => '',
					'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
					'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
					'visible' => false,
					'required' => true,
					'user_defined' => false,
					'default' => '0',
					'searchable' => false,
					'filterable' => false,
					'comparable' => false,
					'visible_on_front' => false,
					'used_in_product_listing' => true,
					'unique' => false,
					'apply_to' => ''
				]
			);
			$this->_logger->info('Customer\'s Canvas Integrated Attribute was added to General set.', $this->getLogContext(__METHOD__));

		} catch (\Throwable $e) {
			$this->_logger->error(
                'Error when install Customer\'s Canvas Integrated Attribute. '. PHP_EOL . $e->getMessage() . PHP_EOL . $e->getTraceAsString(), 
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