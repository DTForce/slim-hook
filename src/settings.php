<?php
//return [
//    'settings' => [
//        'displayErrorDetails' => true, // set to false in production
//        'addContentLengthHeader' => false, // Allow the web server to send the content-length header
//
//        // Renderer settings
//        'renderer' => [
//            'template_path' => __DIR__ . '/../templates/',
//        ],
//
//        // Monolog settings
//        'logger' => [
//            'name' => 'slim-hook',
//            'path' => __DIR__ . '/../logs/app.log',
//            'level' => \Monolog\Logger::DEBUG,
//        ],
//		'secret' => '3219874514564'
//    ],
//];
use Symfony\Component\Yaml\Parser as YamlParser;
use Zend\Config\Factory as ConfigFactory;

// This first line is just for the shorter yml suffix
ConfigFactory::registerReader('yml', 'yaml');

// Adding the parser to the reader
$reader  = ConfigFactory::getReaderPluginManager()->get('yaml');
$reader->setYamlDecoder([new YamlParser(), 'parse']);
define('CONFIG_DIR',  __DIR__ . '/../config');

$config = ConfigFactory::fromFiles([CONFIG_DIR . '/global.yaml', CONFIG_DIR . '/local.yaml']);
return $config;