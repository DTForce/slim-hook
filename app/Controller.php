<?php

namespace App;

use Interop\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;


final class Controller
{

	/**
	 * @var ContainerInterface
	 */
	private $ci;

	/**
	 * @var string
	 */
	private $secret;

	/**
	 * @var bool
	 */
	private $secured = FALSE;

	/**
	 * @var callable[]
	 */
	private $router;

	public function __construct(ContainerInterface $ci)
	{
		$this->ci = $ci;
		$this->secret = $ci->get('settings')['secret'];
		$this->scripts = $ci->get('scripts');

		$this->router['pipeline'] = function (array $event) {
			foreach ($event['builds'] as $build) {
				if ($build['stage'] === 'deploy') {
					$this->handleDeploy($event, $build);
				}
			}
		};

		$this->router['push'] = function (array $event) {
			$this->handlePush($event);
		};
	}


	public function __invoke(Request $request, Response $response, $args)
	{
		$body = $request->getParsedBody();
		if ( ! isset($body['object_kind'])) {
			return $response->withStatus(500);
		}

		foreach ($request->getHeader('X-Gitlab-Token') as $secret) {
			if ($secret === $this->secret) {
				$this->secured = TRUE;
			}
		}

		if (isset($this->router[$body['object_kind']])) {
			$this->router[$body['object_kind']]($body);
		}


		return $response->withStatus(200);
	}


	private function handleDeploy(array $event, array $build)
	{
		if ($build['status'] === 'created') {
			$projectName = $event['project']['path_with_namespace'];
			$buildId = $build['id'];
			$commitId = $event['commit']['id'];
			$name = $build['name'];
			if (isset($this->scripts[$projectName]['deploy'][$name])) {
				putenv('HOOK_PROJECT_PATH=' . $projectName);
				putenv('HOOK_BUILD_ID=' . $buildId);
				putenv('HOOK_BUILD_REF=' . $commitId);
				$this->executeCommand($this->scripts[$projectName]['deploy'][$name]);
			}
		}
	}


	private function handlePush(array $event)
	{
		$projectName = $event['project']['path_with_namespace'];
		$ref = $event['ref'];
		if (isset($this->scripts[$projectName]['push'][$ref])) {
			putenv('HOOK_PROJECT_PATH=' . $projectName);
			putenv('HOOK_REF=' . $ref);
			putenv('HOOK_BRANCH=' . $this->extractBranchName($ref));
			putenv('HOOK_BUILD_REF=' . $event['after']);
			$this->executeCommand($this->scripts[$projectName]['push'][$ref]);
		}
	}


	private function extractBranchName($ref)
	{
		return substr($ref, strlen('refs/heads/'));
	}


	/**
	 * @param string|array $scriptPath
	 */
	private function executeCommand($scriptPath)
	{
		$oldCwd = NULL;
		if (is_array($scriptPath)) {
			$cwd = $scriptPath['cwd'];
			$scriptPath = $scriptPath['command'];
			$oldCwd = getcwd();
			chdir($cwd);
		}
		shell_exec($scriptPath);
		if ($oldCwd !== NULL) {
			chdir($oldCwd);
		}
	}

}
