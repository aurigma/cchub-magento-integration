<?php

namespace Aurigma\CustomersCanvas\Api\Data;

class PluginSettings {

    const BASE_PATH = 'customers_canvas_settings/';
    const CONNECT_PATH = 'connect/';
    const EDITOR_PATH = 'editor/';

    const BACK_OFFICE_URL = 'backoffice_url';
    const ASSETSTORAGE_URL = 'assetstorage_url';
    const ASSETPROCESSOR_URL = 'assetprocessor_url';
    const TENANCY_NAME = 'tenancyname';
    const TENANT_ID = 'tenantid';
    const STOREFRONT_ID = 'storefrontid';
    const CLIENT_ID = 'clientid';
    const CLIENT_SECRET = 'client_secret';

    const EDITOR_MODE = 'editor_mode';

    private $backOfficeUrl;
    private $assetStorageUrl;
    private $assetProcessorUrl;
    private $tenancyName;
    private $backOfficeTenantId;
    private $backOfficeStorefrontId;
    private $backOfficeClientId;
    private $backOfficeClientSecret;
    private $editorMode;

    public function setBackOfficeUrl($value) 
    {
        $this->backOfficeUrl = $value;
    }
    public function getBackOfficeUrl()
    {
        return $this->backOfficeUrl;
    }

    public function setAssetStorageUrl($value)
    {
        $this->assetStorageUrl = $value;
    }
    public function getAssetStorageUrl()
    {
        return $this->assetStorageUrl;
    }

    public function setAssetProcessorUrl($value)
    {
        $this->assetProcessorUrl = $value;
    }
    public function getAssetProcessorUrl()
    {
        return $this->assetProcessorUrl;
    }

    public function setTenancyName($value)
    {
        $this->tenancyName = $value;
    }
    public function getTenancyName()
    {
        return $this->tenancyName;
    }

    public function setBackOfficeTenantId($value)
    {
        $this->backOfficeTenantId = $value;
    }
    public function getBackOfficeTenantId()
    {
        return $this->backOfficeTenantId;
    }

    public function setBackOfficeStorefrontId($value)
    {
        $this->backOfficeStorefrontId = $value;
    }
    public function getBackOfficeStorefrontId()
    {
        return $this->backOfficeStorefrontId;
    }

    public function setBackOfficeClientId($value)
    {
        $this->backOfficeClientId = $value;
    }
    public function getBackOfficeClientId()
    {
        return $this->backOfficeClientId;
    }

    public function setBackOfficeClientSecret($value)
    {
        $this->backOfficeClientSecret = $value;
    }
    public function getBackOfficeClientSecret()
    {
        return $this->backOfficeClientSecret;
    }

    public function setEditorMode($value)
    {
        $this->editorMode = $value;
    }
    public function getEditorMode()
    {
        return $this->editorMode;
    }

}

?>