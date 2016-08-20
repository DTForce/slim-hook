<?php

use App\BashRestController;
use App\HookController;


$app->post('/', HookController::class);
$app->post('/{group}/{project}/{action}', BashRestController::class);
