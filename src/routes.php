<?php

use App\HookController;


$app->post('/', HookController::class);
$app->post('/{group}/{project}/{action}', HookController::class);
