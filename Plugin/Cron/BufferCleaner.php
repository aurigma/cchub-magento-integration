<?php 

namespace Aurigma\CustomersCanvas\Plugin\Cron;

use \Magento\Quote\Model\QuoteRepository;
use \Magento\Quote\Model\ResourceModel\Quote\Collection as QuoteCollection;
use \Magento\Sales\Model\ResourceModel\Collection\ExpiredQuotesCollection;
use \Magento\Store\Model\StoreManagerInterface;
use \Psr\Log\LoggerInterface;

use Aurigma\CustomersCanvas\Observer\Cart\CheckoutCartAdd;
use Aurigma\CustomersCanvas\Model\BackofficeProjectFactory;
use Aurigma\CustomersCanvas\Helper\PrivateDesignsHelper;

class BufferCleaner
{
    /**
     * @var ExpiredQuotesCollection
     */
    private $expiredQuotesCollection;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var BackofficeProjectFactory
     */
    protected $backOfficeProjectFactory;

    /**
     * @var PrivateDesignsHelper
     */
    protected $privateDesignHelper;

    /**
     * @var LoggerInterface
     */
    private $_logger;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ExpiredQuotesCollection $expiredQuotesCollection
     * @param QuoteRepository $quoteRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ExpiredQuotesCollection $expiredQuotesCollection,
        QuoteRepository $quoteRepository,
        BackofficeProjectFactory $backOfficeProjectFactory,
        PrivateDesignsHelper $privateDesignHelper,
        LoggerInterface $logger
    ) {
        $this->storeManager = $storeManager;
        $this->expiredQuotesCollection = $expiredQuotesCollection;
        $this->quoteRepository = $quoteRepository;
        $this->backOfficeProjectFactory = $backOfficeProjectFactory;
        $this->privateDesignHelper = $privateDesignHelper;
        $this->_logger = $logger;
    }

    public function beforeExecute() 
    {
        $this->_logger->info('Clean buffer task started like before method.', $this->getLogContext(__METHOD__));

        $stores = $this->storeManager->getStores(true);
        foreach ($stores as $store) {
            /** @var $quoteCollection QuoteCollection */
            $quoteCollection = $this->expiredQuotesCollection->getExpiredQuotes($store);
            $quoteCollection->setPageSize(50);

            // Last page returns 1 even when we don't have any results
            $lastPage = $quoteCollection->getSize() ? $quoteCollection->getLastPageNumber() : 0;

            if ($lastPage == 0) {
                $this->_logger->debug('Nothing for clean.', $this->getLogContext(__METHOD__));
            }

            for ($currentPage = $lastPage; $currentPage >= 1; $currentPage--) {
                $quoteCollection->setCurPage($currentPage);

                $this->cleanProjectsForQuotes($quoteCollection);
            }
        }
    }

    /**
     * Deletes all backoffice projects for for quote items
     *
     * @param QuoteCollection $quoteCollection
     */
    private function cleanProjectsForQuotes(QuoteCollection $quoteCollection): void
    {
        foreach ($quoteCollection as $quote) {
            try {
                $itemsCollection = $quote->getAllVisibleItems();
                $this->cleanProjectsForItems($itemsCollection);
            } catch (Exception $e) {
                $message = sprintf(
                    'Error when deleting expired quote backoffice projects (ID: %s): %s',
                    $quote->getId(),
                    (string)$e
                );
                $this->logger->error($message);
            }
        }

        $quoteCollection->clear();
    }

    private function cleanProjectsForItems($cartItems) 
    {
        foreach ($cartItems as $cartItem) {
            $this->cleanProjectsForItem($cartItem);
        }
    }

    protected function cleanProjectsForItem($cartItem)
    {
        $projectKeyOption = $cartItem->getOptionByCode(CheckoutCartAdd::BACKOFFICE_OPTION_NAME);

        if ($projectKeyOption) {
            $this->deleteRemoteStorage($projectKeyOption);
            $this->deleteFromDb($projectKeyOption);
        }
    }

    protected function deleteRemoteStorage($projectOption)
    {
        $modelContext = $this->backOfficeProjectFactory->create();
        $projects = $modelContext->getCollection()->addFieldToFilter('project_key', ['eq' => $projectKeyOption->getValue()]);

        if ($projects->getSize()) {
            $project = $projects->getFirstItem();
            $this->privateDesignHelper->remove($project);
            $this->_logger->info('Project with key: ' . $projectKeyOption->getValue() . ' was deleted from remote storage.' , $this->getLogContext(__METHOD__));
        }
    }

    protected function deleteFromDb($projectKeyOption)
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