<?php

namespace Aurigma\CustomersCanvas\Model\ResourceModel\BackofficeProject;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
	protected $_idFieldName = 'project_id';
	protected $_eventPrefix = 'cc_backoffice_projects_collection';
	protected $_eventObject = 'backoffice_projects_collection';

	/**
	 * Define resource model
	 *
	 * @return void
	 */
	protected function _construct()
	{
		$this->_init('Aurigma\CustomersCanvas\Model\BackofficeProject', 'Aurigma\CustomersCanvas\Model\ResourceModel\BackofficeProject');
	}

}

?>