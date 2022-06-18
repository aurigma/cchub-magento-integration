<?php 

namespace Aurigma\CustomersCanvas\Observer\Cart;
 
use \Magento\Framework\Event\Observer as EventObserver;
use \Magento\Framework\Event\ObserverInterface;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Framework\View\LayoutInterface;
use \Magento\Framework\App\RequestInterface;
use \Magento\Framework\Serialize\SerializerInterface;
use \Psr\Log\LoggerInterface;

use Aurigma\CustomersCanvas\Helper\Guid;
use Aurigma\CustomersCanvas\Model\BackofficeProjectFactory;
use Aurigma\CustomersCanvas\Observer\Cart\CheckoutCartAdd;
 
class CheckoutCartUpdate implements ObserverInterface
{

    protected $layout;
    protected $storeManager;
    protected $request;
    private $serializer;
    private $guidHelper;
    private $backOfficeProjectFactory;
    protected $_logger;
 
    public function __construct(
        StoreManagerInterface $storeManager,
        LayoutInterface $layout,
        RequestInterface $request,
        SerializerInterface $serializer,
        Guid $guidHelper,
        BackofficeProjectFactory $backOfficeProjectFactory,
        LoggerInterface $logger
    ) {
        $this->layout = $layout;
        $this->storeManager = $storeManager;
        $this->request = $request;
        $this->serializer = $serializer;
        $this->guidHelper = $guidHelper;
        $this->backOfficeProjectFactory = $backOfficeProjectFactory;
        $this->_logger = $logger;
    }
 
    public function execute(EventObserver $observer)
    {
        try {

            $post = $this->request->getPost();

            if (!isset($post->projectJson)) {
                return;
            }
            $item = $observer->getQuoteItem();

            $oldProjectOption = $item->getOptionByCode(CheckoutCartAdd::BACKOFFICE_OPTION_NAME);
            if ($oldProjectOption) {
                $this->deleteFromDb($oldProjectOption);
            }

            $newGuid = $this->guidHelper->create();
            $newBackOfficeProject = $this->backOfficeProjectFactory->create();

            $newBackOfficeProject->setData([
                'project_key' => $newGuid,
                'quantity' => $post->quantity,
                'properties' => $post->projectJson,
                'created_at' => (new \DateTime())->format('Y-m-d H:i:s')
            ])->save();

            $item->addOption(array(
                'product_id' => $item->getProductId(),
                'code' => CheckoutCartAdd::BACKOFFICE_OPTION_NAME,
                'value' => $newGuid
            ));

        } catch (\Throwable $e) {
			$this->_logger->error(
                'Error when adding project key option to cart item. '. PHP_EOL . $e->getMessage() . PHP_EOL . $e->getTraceAsString(), 
                $this->getLogContext(__METHOD__)
            );
            throw $e;
		}
    }

    private function deleteFromDb($projectKeyOption)
    {
        $modelContext = $this->backOfficeProjectFactory->create();
        $projects = $modelContext->getCollection()->addFieldToFilter('project_key', ['eq' => $projectKeyOption->getValue()]);

        if ($projects->getSize()) {
            $project = $projects->getFirstItem();
            $project->delete();
            $this->_logger->info('Project with key: ' . $projectKeyOption->getValue() . ' was deleted from db.' , $this->getLogContext(__METHOD__));
        } else {
            $this->_logger->info('Project with key: ' . $projectKeyOption->getValue() . ' was not found in db.' , $this->getLogContext(__METHOD__));
        }
    }

    private function getLogContext(string $methodName) 
    {
        return array('class' => get_class($this), 'method' => $methodName);
    }
}

?>