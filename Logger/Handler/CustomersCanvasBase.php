<?php
namespace Aurigma\CustomersCanvas\Logger\Handler;

use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class CustomersCanvasBase extends Base
{
    protected $fileName = '';

    protected $loggerType = Logger::DEBUG;

    protected $fileBasePath = 'var/log/cc/';
    protected $fileBaseName = 'logs';
    protected $fileBaseNameExt = '.log';
    

    public function __construct(DriverInterface $filesystem)
    {
        parent::__construct($filesystem, null, $this->getCurrentName());
    }

    protected function getCurrentName(): string
    {
        $dateStr = $this->getCurrentDataString();
        return $this->fileBasePath . $dateStr . $this->fileBaseName . $this->fileBaseNameExt;
    }

    private function getCurrentDataString(): string
    {
        $date = new \DateTime('now');
        return $date->format('(Y.m.d)-');
    }
}

?>