<?php

use Symfony\Component\Yaml\Parser as YamlParser;
use Zend\Config\Factory as ConfigFactory;

// This first line is just for the shorter yml suffix
ConfigFactory::registerReader('yml', 'yaml');

// Adding the parser to the reader
$reader  = ConfigFactory::getReaderPluginManager()->get('yaml');
$reader->setYamlDecoder([new YamlParser(), 'parse']);
if ( ! defined('CONFIG_DIR')) {
	define('CONFIG_DIR', __DIR__ . '/../config');
}

$config = ConfigFactory::fromFiles([CONFIG_DIR . '/global.yaml', CONFIG_DIR . '/local.yaml']);
return $config;