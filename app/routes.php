<?php

use App\Controllers\IndexController;
use App\Controllers\RequestController;

return [
    '/' => [IndexController::class, 'index'],
     '/request' => [RequestController::class, 'index'],
     '/clear' => function() {
        unset($_SESSION['chat']);
        redirect('/');
     }
];