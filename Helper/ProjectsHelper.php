<?php
namespace Aurigma\CustomersCanvas\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;
use \Psr\Log\LoggerInterface;

use \GuzzleHttp\Client;
use \Aurigma\Storefront\HeaderSelector;
use \Aurigma\Storefront\Configuration;
use \Aurigma\Storefront\Api\ProjectsApi;
use \Aurigma\Storefront\Model\CreateProjectDto;
use \Aurigma\Storefront\Model\ProjectItemDto;

use Aurigma\CustomersCanvas\Api\PluginSettingsManager;
use Aurigma\CustomersCanvas\Helper\BackOfficeTokenHelper;

class ProjectsHelper extends AbstractHelper
{
    protected $settings;
    protected $tokenHelper;
    protected $_logger;

    public function __construct(
        Context $context, 
        PluginSettingsManager $settingsManager,
        BackOfficeTokenHelper $tokenHelper,
        LoggerInterface $logger)
    {
        $this->_logger = $logger;
        $this->tokenHelper = $tokenHelper;
        $this->settings = $settingsManager->getSettings();
        parent::__construct($context);
    }

    public function createProject($project, string $userId, string $userName, int $orderId, int $productId, string $productName, string $orderUrl)
    {
        $projectApi = $this->createApiClient($this->settings);

        $createProjectDto = $this->createProjectDtoObject($project, $userId, $userName, $orderId, $productId, $productName, $orderUrl);
        $response = $projectApi->projectsCreate($this->settings->getBackOfficeStorefrontId(), $this->settings->getBackOfficeTenantId(), null, $createProjectDto);
        $this->_logger->debug('Project was created in Back Office: ' . json_encode($response) , $this->getLogContext(__METHOD__));
        return $response;
    }

    public function changeProjectStatus(int $projectId, int $newStatusCode)
    {
        $projectApi = $this->createApiClient($this->settings);

        $response = $projectApi->projectsForceStatus($projectId, $newStatusCode, $this->settings->getBackOfficeTenantId(), null);
        $this->_logger->debug('Project ' . $projectId . ' status was changed in Back Office to code ' . $newStatusCode , $this->getLogContext(__METHOD__));
        return $response;
    }

    private function createProjectDtoObject(
        $project, 
        string $userId, 
        string $userName, 
        int $orderId, 
        int $productId, 
        string $productName, 
        string $orderUrl): CreateProjectDto
    {
        $data['product_reference'] = $productId ?? null;
        $data['order_id'] = strval($orderId) ?? null;
        $data['order_url'] = $orderUrl ?? null;
        $data['order_number'] = $orderId ?? null;
        $data['customer_id'] = $userId ?? null;
        $data['customer_name'] = $userName ?? null;
        $data['name'] = "order#".$orderId ?? null;
        $data['owner_id'] = $userId ?? null;
        $data['items'] = $this->getItemsFromBufferProject($project, $productName) ?? null;
        return new CreateProjectDto($data, $productName);
    }

    private function getItemsFromBufferProject($project, string $productName): array
    {
        $result = array();
        $properties = json_decode($project->getProperties());

        $stateIds = isset($properties->{'_stateId'}) ? $properties->{'_stateId'} : array();
        $hidden = isset($properties->{'_hidden'}) ? $properties->{'_hidden'} : null;

        $fields = isset($properties->{'_fields'}) ? (array) $properties->{'_fields'} : null;

        if (isset($fields['files']) && $fields['files'] != null)
        {
            $filesArray = $fields['files'];
            $index = 1;
            foreach ($filesArray as $key => $file) {
                $fields['file_' . $index] = $file->{'Link'};
                $index++;
            }
            unset($fields['files']);
        }

        $result[] = new ProjectItemDto(array(
            'name' => $productName,
            'fields' => $fields,
            'hidden' => $hidden,
            'design_ids' => $stateIds,
            'quantity' => $project->getQuantity(),
        ));

        return $result;
    }

    private function createApiClient($settings)
    {

        if (substr($settings->getBackOfficeUrl(), -1) === '/') {
            $apiUrl = substr($settings->getBackOfficeUrl(), 0, -1);
        } else {
            $apiUrl = $settings->getBackOfficeUrl();
        }
        
        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $apiUrl,
            // You can set any number of default request options.
            'timeout'  => 60.0,
        ]);
        $selector = new HeaderSelector();
        $config = new Configuration();

        $config->setAccessToken($this->tokenHelper->getAccessToken());
        $config->setHost($apiUrl);
        
        return new ProjectsApi($client, $config, $selector);
    }

    private function getLogContext(string $methodName) 
    {
        return array('class' => get_class($this), 'method' => $methodName);
    }
}

?>