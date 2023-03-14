<?php
namespace Aurigma\CustomersCanvas\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Catalog\Model\Product;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use \Psr\Log\LoggerInterface;
use Aurigma\CustomersCanvas\Setup\InstallData;

class UpgradeData implements UpgradeDataInterface
{
	private $eavSetupFactory;
    protected $_logger;
	private $eavSetup;

	public function __construct(EavSetupFactory $eavSetupFactory, LoggerInterface $logger)
	{
		$this->eavSetupFactory = $eavSetupFactory;
        $this->_logger = $logger;
	}

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
		$this->eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

		$this->_logger->info('Upgrading process started', $this->getLogContext(__METHOD__));

		if($this->IsEditorFamilyAttributeExists()) {
			return;
		}

		try {
			$this->_logger->info('Customer\'s Canvas Editor Family Attribute started adding to General set.', $this->getLogContext(__METHOD__));
			
			$this->eavSetup->addAttribute(
				Product::ENTITY,
				InstallData::EDITOR_FAMILY_ATTRIBUTE,
				[
					'group' => 'General',
					'type' => 'int',
					'backend' => '',
					'frontend' => '',
					'label' => __('Customer\'s Canvas Editor Family'),
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
			$this->_logger->info('Customer\'s Editor Family Attribute was added to General set.', $this->getLogContext(__METHOD__));

		} catch (\Throwable $e) {
			$this->_logger->error(
				'Error when install Customer\'s Canvas Editor Family Attribute. '. PHP_EOL . $e->getMessage() . PHP_EOL . $e->getTraceAsString(), 
				$this->getLogContext(__METHOD__)
			);
			throw $e;
		}
    }

	private function getLogContext(string $methodName) 
	{
        return array('class' => get_class($this), 'method' => $methodName);
    }

	private function IsEditorFamilyAttributeExists() 
	{
		$this->_logger->info('Making attempt to get Editor Family Attribute', $this->getLogContext(__METHOD__));

		try{
			$attributeId = $this->eavSetup->getAttributeId(Product::ENTITY, InstallData::EDITOR_FAMILY_ATTRIBUTE);
			if(empty($attributeId) || $attributeId === null){
				$this->_logger->info('Editor Family Attribute does not exists', $this->getLogContext(__METHOD__));
				return false;
			}
			else{
				$this->_logger->info('Editor Family Attribute exists', $this->getLogContext(__METHOD__));
				return true;
			}
		}
		catch(\Throwable $e) {
			$this->_logger->error(
                'Error when getting Editor Family Attribute'. PHP_EOL . $e->getMessage() . PHP_EOL . $e->getTraceAsString(), 
                $this->getLogContext(__METHOD__)
            );
            throw $e;
		}
    }
}
?>