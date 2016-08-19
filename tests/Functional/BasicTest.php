<?php

namespace Tests\Functional;

class BasicTest extends AbstractTestCase
{

    public function testDeploy()
    {
		$response = $this->runApp(
			file_get_contents(__DIR__ . '/data/pipeline.json'),
			[
				'HOOK_PROJECT_PATH' => 'gitlab-org/gitlab-test',
				'HOOK_BUILD_ID' => 379,
				'HOOK_BUILD_REF' => 'bcbb5ec396a2c0f828686f14fac9b80b780504f2',
				'HOOK_ENV_NAME' => 'staging'
			],
			'bash /testing-dir/script.bash deploy'
		);

        $this->assertEquals(200, $response->getStatusCode());
    }


    public function testPush()
    {
		$response = $this->runApp(
			file_get_contents(__DIR__ . '/data/push.json'),
			[
				'HOOK_PROJECT_PATH' => 'gitlab-org/gitlab-test',
				'HOOK_REF' => 'refs/heads/master',
				'HOOK_BRANCH' => 'master',
				'HOOK_BUILD_REF' => 'da1560886d4f094c3e6c9ef40349f7d38b5d27d7'
			],
			[
				'cwd' => '/testing-dir',
				'command' => 'bash /testing-dir/script.bash push'
			]
		);

		$this->assertEquals(200, $response->getStatusCode());
    }


	public function testPushTag()
	{
		$response = $this->runApp(
			file_get_contents(__DIR__ . '/data/tag.json'),
			[
				'HOOK_PROJECT_PATH' => 'jsmith/example',
				'HOOK_REF' => 'refs/tags/v1.0.0',
				'HOOK_TAG' => 'v1.0.0',
				'HOOK_BUILD_REF' => '82b3d5ae55f7080f1e6022629cdb57bfae7cccc7'
			],
			'bash /testing-dir/script.bash tag'
		);

		$this->assertEquals(200, $response->getStatusCode());
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

}
