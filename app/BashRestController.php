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
use InvalidArgumentException;
use Slim\Http\Request;
use Slim\Http\Response;


final class BashRestController
{

	use SecuredTrait;

	const SECRET_HEADER = 'X-Secret';

	/**
	 * @var Executor
	 */
	private $executor;

	/**
	 * @var array
	 */
	private $scripts;


	public function __construct(ContainerInterface $ci, Executor $executor)
	{
		$this->executor = $executor;
		$this->secret = (string) $ci->get('settings')['secret'];
		$this->scripts = (array) $ci->get('bashREST');
	}


	/**
	 * @param Request $request
	 * @param Response $response
	 * @param array $args
	 * @return Response
	 */
	public function __invoke(Request $request, Response $response, array $args)
	{
		if ( ! $this->isSecured($request)) {
			return $response->withStatus(403);
		}

		$projectName = $args['group'] . '/' . $args['project'];
		$action = $args['action'];

		if ( ! $this->isHandled($projectName, $action)) {
			return $response->withStatus(404);
		}

		$response = $this->handle($request, $response, $projectName, $action);
		return $response->withStatus(200);
	}


	/**
	 * @param array|NULL $data
	 * @return array
	 */
	private function flatten($data)
	{
		if ($data === NULL) {
			return [];
		}

		if (is_object($data)) {
			throw new InvalidArgumentException('Unexpected parser result.');
		}

		$toProcess = [[
			'data' => $data,
			'prefix' => 'HOOK'
		]];

		$flattened = [];

		while ( ! empty($toProcess)) {
			$actual = array_pop($toProcess);
			$this->flattenProcessArray($actual, $flattened, $toProcess);
		}

		return $flattened;
	}


	/**
	 * @param string $projectName
	 * @param string $action
	 * @return bool
	 */
	private function isHandled($projectName, $action)
	{
		return isset($this->scripts[$projectName][$action]);
	}


	/**
	 * @param array $actual
	 * @param array $flattened
	 * @param array $toProcess
	 * @return array
	 */
	private function flattenProcessArray(array $actual, array &$flattened, array &$toProcess)
	{
		foreach ($actual['data'] as $key => $value) {
			if (is_scalar($value)) {
				$flattened[$actual['prefix'] . '_' . $key] = $value;
			} else {
				if (is_array($value)) {
					array_push(
						$toProcess,
						[
							'data' => $value,
							'prefix' => $actual['prefix'] . '_' . $key
						]
					);
				}
			}
		}
	}


	/**
	 * @param Request $request
	 * @param string $projectName
	 * @param string $action
	 * @return Response
	 */
	private function handle(Request $request, Response $response, $projectName, $action)
	{
		$defaultEnv =  ['HOOK_PROJECT_PATH' => $projectName, 'HOOK_ACTION' => $action];
		$result = $this->executor->executeCommand(
			$this->scripts[$projectName][$action],
			$this->flatten($request->getParsedBody()) + $defaultEnv
		);

		$body = $response->getBody();
		$body->rewind();
		$body->write($result);

		return $response->withHeader('Content-Type', 'application/text;charset=utf-8');
	}

}
