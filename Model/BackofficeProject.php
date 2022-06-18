<?php

namespace Aurigma\CustomersCanvas\Model;

use \Magento\Framework\Model\AbstractModel;
use \Magento\Framework\DataObject\IdentityInterface;

class BackofficeProject extends AbstractModel implements IdentityInterface
{
	const CACHE_TAG = 'cc_backoffice_projects';

	protected $_cacheTag = 'cc_backoffice_projects';

	protected $_eventPrefix = 'cc_backoffice_projects';

	protected function _construct()
	{
		$this->_init('Aurigma\CustomersCanvas\Model\ResourceModel\BackofficeProject');
	}

	public function getIdentities()
	{
		return [self::CACHE_TAG . '_' . $this->getId()];
	}

	public function getDefaultValues()
	{
		$values = [];

		return $values;
	}
}

?>