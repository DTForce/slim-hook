<?php

namespace Tests\Functional;

class BasicTest extends AbstractTestCase
{

    public function testDeploy()
    {
		$this->simpleTest(__DIR__ . '/data/pipeline.json',
		[
			'HOOK_PROJECT_PATH' => 'gitlab-org/gitlab-test',
			'HOOK_BUILD_ID' => 379,
			'HOOK_BUILD_REF' => 'bcbb5ec396a2c0f828686f14fac9b80b780504f2',
			'HOOK_ENV_NAME' => 'staging'
		], [
			'bash /testing-dir/script.bash deploy',
			'bash do-something-else'
		]);
    }


    public function testPush()
    {
		$this->simpleTest(__DIR__ . '/data/push.json', [
			'HOOK_PROJECT_PATH' => 'gitlab-org/gitlab-test',
			'HOOK_REF' => 'refs/heads/master',
			'HOOK_BRANCH' => 'master',
			'HOOK_BUILD_REF' => 'da1560886d4f094c3e6c9ef40349f7d38b5d27d7'
		], [
			'cwd' => '/testing-dir',
			'command' => 'bash /testing-dir/script.bash push'
		]);
    }


	public function testPushTag()
	{
		$this->simpleTest(__DIR__ . '/data/tag.json',
			[
				'HOOK_PROJECT_PATH' => 'jsmith/example',
				'HOOK_REF' => 'refs/tags/v1.0.0',
				'HOOK_TAG' => 'v1.0.0',
				'HOOK_BUILD_REF' => '82b3d5ae55f7080f1e6022629cdb57bfae7cccc7'
			],
			'bash test.bash xcasdzcxzsdda'
		);
	}


	public function testExecutorPushTag()
	{
		$result = shell_exec("bash -c \"echo abc\"");
		if ($result === "abc\n") {
			if (file_exists(__DIR__ . '/log')) {
				unlink(__DIR__ . '/log');
			}

			$response = $this->runApp(file_get_contents(__DIR__ . '/data/tag.json') , [
				'scripts' => [
					'jsmith/example' => [
						'tag' => [
							'cwd' => __DIR__,
							'bash test.bash ABC',
							'bash test.bash CDE'
						]
					]
				]
			]);
			$logFile = file_get_contents(__DIR__ . '/log');
			$this->assertEquals(
				"jsmith/example refs/tags/v1.0.0 v1.0.0 82b3d5ae55f7080f1e6022629cdb57bfae7cccc7 ABC\n" .
				"jsmith/example refs/tags/v1.0.0 v1.0.0 82b3d5ae55f7080f1e6022629cdb57bfae7cccc7 CDE\n",
				$logFile
			);

			$this->assertEquals(200, $response->getStatusCode());

			unlink(__DIR__ . '/log');
		}
	}


	public function testExecutorSinglePushTag()
	{
		$result = shell_exec("bash -c \"echo abc\"");
		if ($result === "abc\n") {
			if (file_exists(__DIR__ . '/log')) {
				unlink(__DIR__ . '/log');
			}

			$oldDir = getcwd();
			chdir(__DIR__);

			$response = $this->runApp(file_get_contents(__DIR__ . '/data/tag.json') , [
				'scripts' => [
					'jsmith/example' => [
						'tag' => 'bash test.bash DEF'
					]
				]
			]);
			$logFile = file_get_contents(__DIR__ . '/log');
			$this->assertEquals(
				"jsmith/example refs/tags/v1.0.0 v1.0.0 82b3d5ae55f7080f1e6022629cdb57bfae7cccc7 DEF\n",
				$logFile
			);

			$this->assertEquals(200, $response->getStatusCode());

			chdir($oldDir);
			unlink(__DIR__ . '/log');
		}
	}


	public function testNo()
	{
		$response = $this->runInvalid();

		$this->assertEquals(500, $response->getStatusCode());
	}


	public function testUnsecured()
	{
		$response = $this->runUnsecured();

		$this->assertEquals(403, $response->getStatusCode());
	}


	public function testNotHandled()
	{
		$response = $this->runNotHandled();

		$this->assertEquals(404, $response->getStatusCode());
	}


	protected function simpleTest($file, array $env, $command)
	{
		$response = $this->runAppMocked(file_get_contents($file), $env, $command);
		$this->assertEquals(200, $response->getStatusCode());
	}

}
