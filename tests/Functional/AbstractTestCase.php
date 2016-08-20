<?php

namespace Tests\Functional;

use App\Executor;
use Interop\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;

abstract class AbstractTestCase extends \PHPUnit_Framework_TestCase
{

	final protected function runAppMocked($requestData, array $values, $command)
    {
		$request = $this->prepareRequest('alksjdljzcxl')
			->withParsedBody(json_decode($requestData, TRUE));

		return $this->assertCommandEnvironment($request, $values, $command);
	}

	final protected function runApp($requestData, array $settings = [])
	{
		$request = $this->prepareRequest('alksjdljzcxl')
			->withParsedBody(json_decode($requestData, TRUE));

		return $this->runRequest($request, $this->buildApp($settings));
	}


	final protected function runInvalid()
	{
		$request = $this->prepareRequest('alksjdljzcxl')
			->withParsedBody([]);

		return $this->runRequest($request, $this->buildApp());
	}


	final protected function runUnsecured()
	{
		$request = $this->prepareRequest()
			->withParsedBody([
				'object_kind' => 'push'
			]);

		return $this->runRequest($request, $this->buildApp());
	}


	final protected function runNotHandled()
	{
		$request = $this->prepareRequest('alksjdljzcxl')
			->withParsedBody([
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
	private function assertCommandEnvironment(Request $request, array $values, $command)
	{

		$app = $this->buildApp();

		if ($command !== NULL) {
			$app->getContainer()[Executor::class] = function () use ($values, $command) {
				$mock = $this->getMockBuilder(Executor::class)
					->setMethods(['executeCommand'])
					->getMock();

				$mock->expects($this->once())
					->method('executeCommand')
					->with($this->equalTo($command), $this->equalTo($values));

				return $mock;
			};
		}

		return $this->runRequest($request, $app);
	}


	/**
	 * @param array $settingsOverride
	 * @return App
	 */
	private function buildApp(array $settingsOverride = [])
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
	 * @param Request $request
	 * @param App $app
	 * @return Response
	 */
	private function runRequest(Request $request, App $app)
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
	 * @param string|NULL $secret
	 * @return Request
	 */
	private function prepareRequest($secret = NULL)
	{
		return Request::createFromEnvironment($this->prepareEnvironment($secret));
	}


	/**
	 * @param string|NULL $secret
	 * @return Environment
	 */
	private function prepareEnvironment($secret = NULL)
	{
		$data = [
			'REQUEST_METHOD' => 'POST',
			'REQUEST_URI' => '/',
			'HTTP_CONTENT_TYPE' => 'application/json'
		];

		if ($secret !== NULL) {
			$data['HTTP_X-Gitlab-Token'] = $secret;
		}

		return Environment::mock($data);
	}

}
