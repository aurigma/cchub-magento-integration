<?php

namespace Aurigma\CustomersCanvas\Setup;

use \Magento\Framework\Setup\UninstallInterface;
use \Magento\Framework\Setup\SchemaSetupInterface;
use \Magento\Framework\Setup\ModuleContextInterface;
use \Psr\Log\LoggerInterface;

use \Aurigma\CustomersCanvas\Setup\InstallSchema;

class Uninstall implements UninstallInterface
{
    protected $_logger;

    public function __construct(LoggerInterface $logger)
	{
        $this->_logger = $logger;
	}

	public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
	{
        try {
            $this->_logger->info('Customer\'s Canvas project buffer table started deleting.', $this->getLogContext(__METHOD__));

            $installer = $setup;
            $installer->startSetup();
    
            $installer->getConnection()->dropTable($installer->getTable( InstallSchema::BO_BUFFER_TABLE_NAME ));
    
            $installer->endSetup();

            $this->_logger->info('Customer\'s Canvas project buffer table was deleted.', $this->getLogContext(__METHOD__));

        }  catch (\Throwable $e) {
            $this->_logger->error(
                'Error when uninstall Customer\'s Canvas project buffer table. '. PHP_EOL . $e->getMessage() . PHP_EOL . $e->getTraceAsString(), 
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