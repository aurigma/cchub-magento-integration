<?php
namespace Aurigma\CustomersCanvas\Logger\Handler;

use Monolog\Logger;

class CustomersCanvasError extends CustomersCanvasBase
{
    protected $loggerType = Logger::ERROR;
    protected $fileBaseName = 'error';
}

?>