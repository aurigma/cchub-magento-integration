<?php
namespace Aurigma\CustomersCanvas\Observer\Order;

use \Magento\Framework\Event\Observer as EventObserver;
use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Model\AbstractModel;

use \Psr\Log\LoggerInterface;

use Aurigma\CustomersCanvas\Observer\Order\SubmitOrder;
use Aurigma\CustomersCanvas\Helper\ProjectsHelper;

class SaveOrder implements ObserverInterface
{

    private $projectHelper;
    protected $_logger;
 
    public function __construct(
        ProjectsHelper $projectHelper,
        LoggerInterface $logger
    ) {
        $this->projectHelper = $projectHelper;
        $this->_logger = $logger;
    }


    public function execute(EventObserver $observer)
    {
        try {
            $order = $observer->getEvent()->getOrder();

            if ($order instanceof AbstractModel) {
                $statusTransitionTo = $order->getState();

                $this->_logger->debug($statusTransitionTo);

                $isOrderIntegrated = false;

                $items = $order->getAllItems();

                foreach ($items as $orderItem) {
                    if ($this->isIntegratedOrderItem($orderItem)) {
                        $isOrderIntegrated = true;

                        $this->changeProjectStatus($orderItem, $statusTransitionTo);
                    }
                }

                if ($isOrderIntegrated) {
                    $this->_logger->debug('The status of order with id ' . json_encode($order->getId()) . ' was changed to ' 
                        . json_encode($statusTransitionTo), $this->getLogContext(__METHOD__));
                }


            }
            return $this;
        } catch (\Throwable $e) {
			$this->_logger->error(
                'Error when changing project status. '. PHP_EOL . $e->getMessage() . PHP_EOL . $e->getTraceAsString(), 
                $this->getLogContext(__METHOD__)
            );
            throw $e;
		}
        
    }

    private function isIntegratedOrderItem($item): bool
    {
        $itemOption = $item->getProductOptionByCode(SubmitOrder::BACKOFFICE_OPTION_NAME);
        return $itemOption ? true : false;
    }

    private function changeProjectStatus($orderItem, $statusTransitionTo)
    {
        $projectId = $this->getProjectIdFromItem($orderItem);
        $newCode = $this->getStatusCodeForBackOffice($statusTransitionTo);
        $this->projectHelper->changeProjectStatus(intval($projectId), $newCode);
    }

    private function getProjectIdFromItem($item)
    {
        $existentOptions = $item->getProductOptions();
        $projectId = $existentOptions[SubmitOrder::BACKOFFICE_OPTION_NAME];
        $this->_logger->debug('ProjectId: ' . json_encode($projectId), $this->getLogContext(__METHOD__));
        return $projectId;
    }

    private function getStatusCodeForBackOffice($statusTransitionTo): int
    {
        switch ($statusTransitionTo) {
            case 'new':
                return 1;
            case 'pending_payment':
                return 1;
            case 'payment_review':
                return 1;
            case 'holded':
                return 2;
            case 'processing':
                return 3;
            case 'complete':
                return 4;
            case 'canceled':
                return 5;
            case 'closed':
                return 5;
            default:
                return 1;
        }
    }

    private function getLogContext(string $methodName) 
    {
        return array('class' => get_class($this), 'method' => $methodName);
    }
}

?>