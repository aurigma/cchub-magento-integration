<?php
namespace Aurigma\CustomersCanvas\Logger\Handler;

use Monolog\Logger;

class CustomersCanvasInfo extends CustomersCanvasBase
{
    protected $loggerType = Logger::INFO;
    protected $fileBaseName = 'info';
}

?>