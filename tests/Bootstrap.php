<?php
/**
 * Manual test
 */
require_once dirname(__DIR__) .'/vendor/autoload.php';

$application = new Hoogi91\ReleaseFlow\Application('release-flow TEST');
$application->run();