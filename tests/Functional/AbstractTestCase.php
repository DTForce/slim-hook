<?php

namespace Tests\Functional;

use App\Executor;
use App\Handler;
use Interop\Container\ContainerInterface;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;

abstract class AbstractTestCase extends \PHPUnit_Framework_TestCase
{

    public function runApp($requestData = null, array $values, $command)
    {
        // Create a mock environment for testing with
        $environment = Environment::mock(
            [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/',
				'HTTP_CONTENT_TYPE' => 'application/json',
				'HTTP_X-Gitlab-Token' => 'alksjdljzcxl'
            ]
        );

        // Set up a request object based on the environment
        $request = Request::createFromEnvironment($environment);

        // Add request data, if it exists
        if (isset($requestData)) {
            $request = $request->withParsedBody(json_decode($requestData, TRUE));
        }

        // Set up a response object
        $response = new Response();

        // Use the application settings
		if ( ! defined('CONFIG_DIR')) {
			define('CONFIG_DIR', __DIR__ . '/config');
		}
        $settings = require __DIR__ . '/../../src/settings.php';

        // Instantiate the application
        $app = new App($settings);

		// Set up dependencies
		require __DIR__ . '/../../src/dependencies.php';

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

        // Register routes
        require __DIR__ . '/../../src/routes.php';

        // Process the application
        $response = $app->process($request, $response);

        // Return the response
        return $response;
    }
}
