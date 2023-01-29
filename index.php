<?php

use App\Inject;
use App\Kernel;

require __DIR__ . '/vendor/autoload.php';

Inject::clearLogDir();
$logger = Inject::getLogger();

$app = new Kernel($logger);
$app->run();