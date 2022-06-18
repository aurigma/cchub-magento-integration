<?php

namespace Aurigma\CustomersCanvas\Plugin\CheckoutCart;

use \Psr\Log\LoggerInterface;

use Aurigma\CustomersCanvas\Model\BackofficeProjectFactory;
use Aurigma\CustomersCanvas\Helper\BackOfficeProjectHelper;
use Aurigma\CustomersCanvas\Observer\Cart\CheckoutCartAdd;
 
class Image 
{
    protected $backOfficeProjectFactory;
    protected $projectHelper;

    protected $_logger;

    public function __construct(
        BackofficeProjectFactory $backOfficeProjectFactory,
        BackOfficeProjectHelper $projectHelper,
        LoggerInterface $logger
    ) {
        $this->backOfficeProjectFactory = $backOfficeProjectFactory;
        $this->projectHelper = $projectHelper;
        $this->_logger = $logger;
    }

    public function afterGetImage($subject, $result)
    {
        $item = $subject->getItem();
        $itemOption = $item->getOptionByCode(CheckoutCartAdd::BACKOFFICE_OPTION_NAME);

        if (!empty($itemOption)) {
            $projectKey = $itemOption->getValue();
        } else {
            return $result;
        }

        $backOfficeProjects = $this->backOfficeProjectFactory->create();
        $projectCollection = $backOfficeProjects->getCollection()->addFieldToFilter('project_key', ['eq' => $projectKey]);

        if($projectCollection->getSize()) {
            $project = $projectCollection->getFirstItem();
            $projectImageLink = $this->projectHelper->getPreviewImageUrl($project);

            if ($projectImageLink) {
                $this->_logger->debug('Image for cart item (' . $item->getId() . ') was changed.', $this->getLogContext(__METHOD__));
                $result->setImageUrl($projectImageLink);
            }
        } else {
            $this->_logger->debug("BackOffice project with key $projectKey was not found in db.", $this->getLogContext(__METHOD__));
        }

        return $result;
    }

    private function getLogContext(string $methodName) 
    {
        return array('class' => get_class($this), 'method' => $methodName);
    }
}

?>