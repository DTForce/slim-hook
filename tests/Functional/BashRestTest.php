<?php

namespace Tests\Functional;

class BashRestTest extends AbstractTestCase
{

	public static function setUpBeforeClass()
	{
		self::setSecurityHeader('X-Secret');
	}


	public function testAction1()
	{
		self::setPath('/bash-rest/test-app/action1');
		$this->simpleTest('[]', [] , 'test');
	}


	public function testAction2()
	{
		self::setPath('/bash-rest/test-app/action2');
		$this->simpleTest('[]', [] , [
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
			'last' => 'zxc'
		];

		$this->simpleTest(json_encode($input), [
			'HOOK_test' => 'a',
			'HOOK_down_test' => 'b',
			'HOOK_down_more_test' => 3,
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


	protected function simpleTest($data, array $env, $command)
	{
		$response = $this->runAppMocked($data, $env, $command);
		$this->assertEquals(200, $response->getStatusCode());
	}

}
