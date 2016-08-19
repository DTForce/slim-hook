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

	final protected function runApp($requestData = null, array $values, $command)
    {
        $environment = Environment::mock(
            [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/',
				'HTTP_CONTENT_TYPE' => 'application/json',
				'HTTP_X-Gitlab-Token' => 'alksjdljzcxl'
            ]
        );

		$request = Request::createFromEnvironment($environment);

		if (isset($requestData)) {
			$request = $request->withParsedBody(json_decode($requestData, TRUE));
		}

		return $this->assertComandEnvironment($request, $values, $command);

	}


	final protected function runInvalid()
	{
		$environment = Environment::mock(
			[
				'REQUEST_METHOD' => 'POST',
				'REQUEST_URI' => '/',
				'HTTP_CONTENT_TYPE' => 'application/json',
				'HTTP_X-Gitlab-Token' => 'alksjdljzcxl'
			]
		);

		$request = Request::createFromEnvironment($environment);

		if (isset($requestData)) {
			$request = $request->withParsedBody([]);
		}

		return $this->runRequest($request, $this->buildApp());
	}


	final protected function runUnsecured()
	{
		$environment = Environment::mock(
			[
				'REQUEST_METHOD' => 'POST',
				'REQUEST_URI' => '/',
				'HTTP_CONTENT_TYPE' => 'application/json',
			]
		);

		$request = Request::createFromEnvironment($environment);

		$request = $request->withParsedBody([
			'object_kind' => 'push'
		]);

		return $this->runRequest($request, $this->buildApp());
	}


	final protected function runNotHandled()
	{
		$environment = Environment::mock(
			[
				'REQUEST_METHOD' => 'POST',
				'REQUEST_URI' => '/',
				'HTTP_CONTENT_TYPE' => 'application/json',
				'HTTP_X-Gitlab-Token' => 'alksjdljzcxl'
			]
		);

		$request = Request::createFromEnvironment($environment);

		$request = $request->withParsedBody([
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
	private function assertComandEnvironment(Request $request, array $values, $command)
	{

		$app = $this->buildApp();

		if ($command !== NULL) {
			$app->getContainer()[Executor::class] = function (ContainerInterface $c) use ($values, $command) {
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
	 * @return App
	 */
	private function buildApp()
	{
		// Use the application settings
		if ( ! defined('CONFIG_DIR')) {
			define('CONFIG_DIR', __DIR__ . '/config');
		}
		$settings = require __DIR__ . '/../../src/settings.php';

		// Instantiate the application
		$app = new App($settings);

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
}
