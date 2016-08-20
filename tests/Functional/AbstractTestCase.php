<?php

namespace Tests\Functional;

use App\Executor;
use PHPUnit_Framework_MockObject_Stub_ReturnCallback;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\RequestBody;
use Slim\Http\Response;
use Slim\Http\Environment;

abstract class AbstractTestCase extends \PHPUnit_Framework_TestCase
{
	private static $path;
	private static $securityHeader;

	const SECRET = 'alksjdljzcxl';


	/**
	 * @param string $path
	 */
	public static function setPath($path)
	{
		self::$path = $path;
	}


	/**
	 * @param string $securityHeader
	 */
	public static function setSecurityHeader($securityHeader)
	{
		self::$securityHeader = $securityHeader;
	}


	/**
	 * @return Response
	 */
	final protected function runAppMocked($requestData, array $values, $command)
    {
		$request = $this->prepareRequest(self::SECRET, $requestData);
		return $this->assertCommandEnvironment($request, $values, $command);
	}


	/**
	 * @return Response
	 */
	final protected function runApp($requestData, array $settings = [])
	{
		$request = $this->prepareRequest(self::SECRET, $requestData);
		return $this->runRequest($request, $this->buildApp($settings));
	}


	/**
	 * @return Response
	 */
	final protected function runInvalid()
	{
		$request = $this->prepareRequest(self::SECRET, []);
		return $this->runRequest($request, $this->buildApp());
	}


	/**
	 * @return Response
	 */
	final protected function runUnsecured()
	{
		$request = $this->prepareRequest(NULL, [
			'object_kind' => 'push'
		]);
		return $this->runRequest($request, $this->buildApp());
	}


	/**
	 * @return Response
	 */
	final protected function runNotHandled()
	{
		$request = $this->prepareRequest(self::SECRET, [
			'object_kind' => 'test'
		]);
		return $this->runRequest($request, $this->buildApp());
	}


	/**
	 * @param Request $request
	 * @param array $values
	 * @param string $command
	 * @return ResponseInterface|Response
	 */
	final protected function assertCommandEnvironment(Request $request, array $values, $command)
	{
		$app = $this->buildApp();

		$app->getContainer()[Executor::class] = function () use ($values, $command) {
			$mock = $this->getMockBuilder(Executor::class)
				->setMethods(['executeCommand'])
				->getMock();

			if ($command !== NULL) {
				$mock->expects($this->once())
					->method('executeCommand')
					->with($this->equalTo($command), $this->equalTo($values))
					->will(new PHPUnit_Framework_MockObject_Stub_ReturnCallback(function ($command, $values) {
						asort($values);
						return json_encode($values);
					}));
			} else {
				$mock->expects($this->never())
					->method('executeCommand');
			}

			return $mock;
		};

		return $this->runRequest($request, $app);
	}


	/**
	 * @param Request $request
	 * @param App $app
	 * @return Response
	 */
	final protected function runRequest(Request $request, App $app)
	{
		// Register routes
		require __DIR__ . '/../../src/routes.php';

		// Set up a response object
		$response = new Response();

		// Process the application
		$response = $app->process($request, $response);

		// Return the response
		return $response;
	}


	/**
	 * @param array $settingsOverride
	 * @return App
	 */
	final protected function buildApp(array $settingsOverride = [])
	{
		// Use the application settings
		if ( ! defined('CONFIG_DIR')) {
			define('CONFIG_DIR', __DIR__ . '/config');
		}
		$settings = require __DIR__ . '/../../src/settings.php';
		$settings = array_replace_recursive($settings, $settingsOverride);

		// Instantiate the application
		$app = new App($settings);
		unset($app->getContainer()['errorHandler']);

		// Set up dependencies
		require __DIR__ . '/../../src/dependencies.php';

		return $app;
	}


	/**
	 * @param string|NULL $secret
	 * @param array|string $data
	 * @return Request
	 */
	protected function prepareRequest($secret = NULL, $data = '')
	{
		$body = new RequestBody();
		$body->write(is_array($data) ? json_encode($data) : $data);

		return Request::createFromEnvironment($this->prepareEnvironment($secret))
			->withBody($body);
	}


	/**
	 * @param string|NULL $secret
	 * @return Environment
	 */
	private function prepareEnvironment($secret = NULL)
	{
		$data = [
			'REQUEST_METHOD' => 'POST',
			'REQUEST_URI' => self::$path,
			'HTTP_CONTENT_TYPE' => 'application/json'
		];

		if ($secret !== NULL) {
			$data['HTTP_' . self::$securityHeader] = $secret;
		}

		return Environment::mock($data);
	}

}
