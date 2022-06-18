<?php

namespace Aurigma\CustomersCanvas\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;

class BackOfficeProjectHelper extends AbstractHelper
{
    public function getPreviewImageUrl($project)
    {
        $result = null;
        $projectPropertiesStr = $project->getProperties();
        if ($projectPropertiesStr) {
            $result = $this->getBackOfficeImageLink($projectPropertiesStr);
        }

        return $result;
    }

    private function getBackOfficeImageLink(string $propertiesString)
    {
        $resultLink = null;
        $properties = json_decode($propertiesString);
        $imageLinks = $properties->_hidden->images;
        if ($imageLinks) {
            $resultLink = $imageLinks[0];
        }
        return $resultLink;
    }

    public function getSnapshotFromProject($project)
    {
        $result = null;
        $projectPropertiesStr = $project->getProperties();
        if ($projectPropertiesStr) {
            $result = $this->getSnapshotFromProperties($projectPropertiesStr);
        }

        return $result;
    }

    private function getSnapshotFromProperties(string $propertiesString)
    {
        $snapshot = '';
        $properties = json_decode($propertiesString);
        if ($properties && isset($properties->_hidden)) {
            $snapshot = $properties->_hidden->snapshot;
        }
        return $snapshot;
    }

    public function getStateIdFromProject($project)
    {
        $result = null;
        $projectPropertiesStr = $project->getProperties();
        if ($projectPropertiesStr) {
            $result = $this->getStateIdFromProperties($projectPropertiesStr);
        }

        return $result;
    }

    private function getStateIdFromProperties(string $propertiesString)
    {
        $stateId = '';
        $properties = json_decode($propertiesString);
        if ($properties && isset($properties->_stateId)) {
            $stateId = $properties->_stateId[0];
        }  
        return $stateId;
    }
}

?>