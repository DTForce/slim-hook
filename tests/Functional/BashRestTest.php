<?php

namespace Tests\Functional;

use InvalidArgumentException;
use Slim\Http\RequestBody;


class BashRestTest extends AbstractTestCase
{

	public static function setUpBeforeClass()
	{
		self::setSecurityHeader('X-Secret');
	}


	public function testAction1()
	{
		self::setPath('/bash-rest/test-app/action1');
		$this->simpleTest('', ['HOOK_PROJECT_PATH' => 'bash-rest/test-app', 'HOOK_ACTION' => 'action1'] , 'test');
	}


	public function testAction2()
	{
		self::setPath('/bash-rest/test-app/action2');
		$this->simpleTest('[]', ['HOOK_PROJECT_PATH' => 'bash-rest/test-app', 'HOOK_ACTION' => 'action2'] , [
			'cwd' => 'dir',
			'test1',
			'test2'
		]);
	}


	public function testAction2Env()
	{
		self::setPath('/bash-rest/test-app/action2');
		$input = [
			'test' => 'a',
			'down' => [
				'test' => 'b',
				'more' => [
					'test' => 3
				]
			],
			'array' => ['a', 'b', 'c'],
			'last' => 'zxc'
		];

		$this->simpleTest(json_encode($input), [
			'HOOK_PROJECT_PATH' => 'bash-rest/test-app',
			'HOOK_ACTION' => 'action2',
			'HOOK_test' => 'a',
			'HOOK_down_test' => 'b',
			'HOOK_down_more_test' => 3,
			'HOOK_array_0' => 'a',
			'HOOK_array_1' => 'b',
			'HOOK_array_2' => 'c',
			'HOOK_last' => 'zxc'
		] , [
			'cwd' => 'dir',
			'test1',
			'test2'
		]);
	}


	public function testUnsecured()
	{
		self::setPath('/bash-rest/test-app/noAction');
		$response = $this->runUnsecured();

		$this->assertEquals(403, $response->getStatusCode());
	}


	public function testNotHandled()
	{
		self::setPath('/bash-rest/test-app/noAction');
		$response = $this->runAppMocked('[]', [], NULL);

		$this->assertEquals(404, $response->getStatusCode());
	}


	public function testParsedBodyObject()
	{
		$this->setExpectedExceptionRegExp(InvalidArgumentException::class, '#Unexpected parser result.#');

		$body = new RequestBody();
		$body->write(json_encode(['data' => 'abc']));

		self::setPath('/bash-rest/test-app/action1');
		$request = $this->prepareRequest(self::SECRET)
			->withBody($body);

		$request->registerMediaTypeParser('application/json', function ($input) {
				return json_decode($input);
			});

		$response  = $this->runRequest($request, $this->buildApp());
		$this->assertEquals(200, $response->getStatusCode());
	}


	public function testExecutorPushTag()
	{
		self::setPath('/groupName/projectName/deploy');
		$result = shell_exec("bash -c \"echo abc\"");
		if ($result === "abc\n") {
			$response = $this->runApp('{"ENV":"production"}');

			$this->assertEquals(200, $response->getStatusCode());
			$this->assertEquals("Application groupName/projectName action deploy called with ENV set to production", (string)$response->getBody());
		}
	}


	/**
	 * @param string $data
	 * @param array $env
	 * @param string $command
	 */
	protected function simpleTest($data, array $env, $command)
	{
		$response = $this->runAppMocked($data, $env, $command);
		$this->assertEquals(200, $response->getStatusCode());
		$responseBody = (string)$response->getBody();
		$this->assertEquals($env, json_decode($responseBody, TRUE));
	}

}
