<?php

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
	// assumes Composer package
	$autoloader = require __DIR__ . '/../vendor/autoload.php';
	$autoloader->add('RytoEX\\OBS\\LogAnalyzer\\Tests\\', __DIR__);
} elseif (file_exists(__DIR__ . '/../src/autoload.php')) {
	require __DIR__ . '/../src/autoload.php';
} elseif (file_exists(__DIR__ . '/../autoload.php')) {
	require __DIR__ . '/../autoload.php';
} elseif (file_exists(__DIR__ . '/../../../../autoload.php')) {
	require __DIR__ . '/../../../../autoload.php';
}

define("TESTDIR", __DIR__);
define("SRCDIR", __DIR__ . '/../src');
