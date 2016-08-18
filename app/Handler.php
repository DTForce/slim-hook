<?php

/**
 * This file is part of Lekarna.cz (http://www.lekarna.cz/)
 *
 * Copyright (c) 2014 Pears Health Cyber, s.r.o. (http://pearshealthcyber.cz)
 *
 * For the full copyright and license information, please view
 * the file LICENSE that was distributed with this source code.
 */

namespace App;

use Interop\Container\ContainerInterface;


class Handler
{

	/**
	 * @var array
	 */
	private $scripts;

	/**
	 * @var Executor
	 */
	private $executor;


	public function __construct(ContainerInterface $ci, Executor $executor)
	{
		$this->executor = $executor;
		$this->scripts = $ci->get('scripts');
	}


	public function handleDeploy(array $event, array $build)
	{
		if ($build['status'] === 'created') {
			$projectName = $event['project']['path_with_namespace'];
			$buildId = $build['id'];
			$commitId = $event['commit']['id'];
			$name = $build['name'];
			if (isset($this->scripts[$projectName]['deploy'][$name])) {
				$this->executeCommand($this->scripts[$projectName]['deploy'][$name], [
					'HOOK_PROJECT_PATH' => $projectName,
					'HOOK_ENV_NAME' => $name,
					'HOOK_BUILD_ID' => $buildId,
					'HOOK_BUILD_REF' => $commitId
				]);
			}
		}
	}


	public function handlePush(array $event)
	{
		$projectName = $event['project']['path_with_namespace'];
		$ref = $event['ref'];
		if (isset($this->scripts[$projectName]['push'][$ref])) {
			$this->executeCommand($this->scripts[$projectName]['push'][$ref], [
				'HOOK_PROJECT_PATH' => $projectName,
				'HOOK_REF' => $ref,
				'HOOK_BRANCH'=> $this->extractBranchName($ref),
				'HOOK_BUILD_REF' => $event['after']
			]);
		}
	}


	public function handleTag(array $event)
	{
		$projectName = $event['project']['path_with_namespace'];
		$ref = $event['ref'];
		if (isset($this->scripts[$projectName]['tag'])) {
			$this->executeCommand($this->scripts[$projectName]['tag'], [
				'HOOK_PROJECT_PATH' => $projectName,
				'HOOK_REF' => $ref,
				'HOOK_TAG'=> $this->extractTagName($ref),
				'HOOK_BUILD_REF' => $event['after']
			]);
		}
	}


	private function extractBranchName($ref)
	{
		return substr($ref, strlen('refs/heads/'));
	}


	private function extractTagName($ref)
	{
		return substr($ref, strlen('refs/tags/'));
	}


	/**
	 * @param string|array $scriptPath
	 * @param array $env
	 */
	private function executeCommand($scriptPath, array $env)
	{
		$this->executor->executeCommand($scriptPath, $env);
	}

}
