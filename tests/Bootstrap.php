<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';

// build our virtual working directory with composer.json
$GLOBALS['WORKING_DIRECTORY'] = \org\bovigo\vfs\vfsStream::setup('working-directory', null, [
    '.git'          => [],
    'fixtures'      => [
        'Configuration' => [
            'composer.json' => file_get_contents(__DIR__ . '/Fixtures/read-composer-configuration.json'),
        ],
        'Provider'      => [
            'composer-input.json'  => '', // empty input that will be filled from test to output file
            'composer-output.json' => file_get_contents(__DIR__ . '/Fixtures/provider-composer-output.json'),
        ],
    ],
    'composer.json' => file_get_contents(dirname(__DIR__) . '/composer.json'),
]);

if (!defined('PHP_WORKDIR')) {
    define('PHP_WORKDIR', $GLOBALS['WORKING_DIRECTORY']->url());
}