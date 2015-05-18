<?php

$env = getenv('APPLICATION_ENV') ?: 'production';

$modules = array(
    'DoctrineModule',
    'DoctrineORMModule',
    'Foundation',
    'Application',
    'Order',
);

if($env === 'development') {
    $modules[]= 'ZendDeveloperTools';
}

return array(
    'modules' => $modules,
    'module_listener_options' => array(
        'module_paths' => array(
            './module',
            './vendor',
        ),
        'config_glob_paths' => array(
            'config/autoload/{,*.}{global,local}.php',
        ),
    ),
);
