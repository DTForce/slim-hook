<?php

namespace Tests\Functional;

use App\Executor;
use App\Handler;
use Interop\Container\ContainerInterface;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;

/**
 * This is an example class that shows how you could set up a method that
 * runs the application. Note that it doesn't cover all use-cases and is
 * tuned to the specifics of this skeleton app, so if your needs are
 * different, you'll need to change it.
 */
abstract class AbstractTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Use middleware when running application?
     *
     * @var bool
     */
    protected $withMiddleware = true;

    /**
     * Process the application given a request method and URI
     *
     * @param string $requestMethod the request method (e.g. GET, POST, etc.)
     * @param string $requestUri the request URI
     * @param array|object|null $requestData the request data
     * @return \Slim\Http\Response
     */
    public function runApp($requestData = null, array $values, $command)
    {
        // Create a mock environment for testing with
        $environment = Environment::mock(
            [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/',
				'HTTP_CONTENT_TYPE' => 'application/json'
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

        // Register middleware
        if ($this->withMiddleware) {
            require __DIR__ . '/../../src/middleware.php';
        }

        // Register routes
        require __DIR__ . '/../../src/routes.php';

        // Process the application
        $response = $app->process($request, $response);

        // Return the response
        return $response;
    }
}
