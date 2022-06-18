<?php
 
namespace Aurigma\CustomersCanvas\Setup;

use \Magento\Framework\DB\Adapter\AdapterInterface;
use \Magento\Framework\DB\Ddl\Table;
use \Magento\Framework\Setup\InstallSchemaInterface;
use \Magento\Framework\Setup\SchemaSetupInterface;
use \Magento\Framework\Setup\ModuleContextInterface;
use \Psr\Log\LoggerInterface;
 
class InstallSchema implements InstallSchemaInterface
{

    public const BO_BUFFER_TABLE_NAME = 'cc_backoffice_projects';

    protected $_logger;

    public function __construct(LoggerInterface $logger)
	{
        $this->_logger = $logger;
	}

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        try {

            $this->_logger->info('Customer\'s Canvas project buffer table started adding.', $this->getLogContext(__METHOD__));
            $installer = $setup;
            $installer->startSetup();
            $tableName = $installer->getTable( InstallSchema::BO_BUFFER_TABLE_NAME );
            //Check for the existence of the table
            if ($installer->getConnection()->isTableExists($tableName) != true) {
                $table = $installer->getConnection()
                    ->newTable($tableName)
                    ->addColumn(
                        'project_id',
                        \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                        null,
                        [
                                'identity' => true,
                                'nullable' => false,
                                'primary'  => true,
                                'unsigned' => true,
                        ],
                        'Project ID'
                    )
                    ->addColumn(
                        'project_key',
                        Table::TYPE_TEXT,
                        127,
                        [
                            'nullable' => false
                        ],
                        'BackOffice project key'
                    )
                    ->addColumn(
                        'quantity',
                        Table::TYPE_INTEGER,
                        null,
                        ['nullable' => false, 'default' => 1],
                        'Quantity of item'
                    )
                    ->addColumn(
                        'properties',
                        Table::TYPE_TEXT,
                        null,
                        ['nullable' => false],
                        'Json properties of project design'
                    )
                    ->addColumn(
                        'created_at',
                        Table::TYPE_DATETIME,
                        null,
                        ['nullable' => false],
                        'Created At'
                    )
                    ->addIndex(
                        $installer->getIdxName(
                            self::BO_BUFFER_TABLE_NAME,
                            ['project_key'],
                            AdapterInterface::INDEX_TYPE_UNIQUE
                        ),
                        'project_key',
                        ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
                    )
                    ->setComment('Backoffice project buffer')
                    ->setOption('charset', 'utf8');
                $installer->getConnection()->createTable($table);
            }
            $installer->endSetup();
            $this->_logger->info('Customer\'s Canvas project buffer table was added.', $this->getLogContext(__METHOD__));

        } catch (\Throwable $e) {
            $this->_logger->error(
                'Error when install Customer\'s Canvas project buffer table. '. PHP_EOL . $e->getMessage() . PHP_EOL . $e->getTraceAsString(), 
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