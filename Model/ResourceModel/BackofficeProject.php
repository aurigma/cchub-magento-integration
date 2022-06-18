<?php

namespace Aurigma\CustomersCanvas\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use \Magento\Framework\Model\ResourceModel\Db\Context;


class BackofficeProject extends AbstractDb
{
	const TABLE_NAME = 'cc_backoffice_projects';

	public function __construct(Context $context)
	{
		parent::__construct($context);
	}
	
	protected function _construct()
	{
		$this->_init(BackofficeProject::TABLE_NAME, 'project_id');
	}
	
}

?>