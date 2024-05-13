<?php

spl_autoload_register(
    fn ($class_name) => require_once str_replace('App\\', '', $class_name) . '.php'
);

use App\Classes\Request;

(new Request)->handleRequest();
