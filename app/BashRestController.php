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
use Slim\Http\Request;
use Slim\Http\Response;


final class BashRestController
{

	const SECRET_HEADER = 'X-Secret';

	/**
	 * @var Executor
	 */
	private $executor;

	/**
	 * @var string
	 */
	private $secret;

	/**
	 * @var array
	 */
	private $scripts;

	public function __construct(ContainerInterface $ci, Executor $executor)
	{
		$this->executor = $executor;
		$this->secret = $ci->get('settings')['secret'];
		$this->scripts = $ci->get('bashREST');
	}


	public function __invoke(Request $request, Response $response, array $args)
	{
		$secured = FALSE;
		foreach ($request->getHeader(self::SECRET_HEADER) as $secret) {
			if ($secret == $this->secret) { // allow cast
				$secured = TRUE;
			}
		}

		if ($this->secret !== NULL && ! $secured) {
			return $response->withStatus(403);
		}

		$projectName = $args['group'] . '/' . $args['project'];
		$action = $args['action'];

		if ( ! isset($this->scripts[$projectName][$action])) {
			return $response->withStatus(404);
		}

		return $response->withStatus(200)
			->withJson([
				'result' => $this->executor->executeCommand(
					$this->scripts[$projectName][$action],
					$this->flatten($request->getParsedBody())
				)
			]);
	}


	private function flatten(array $data)
	{
		$toProcess = [[
			'data' => $data,
			'prefix' => 'HOOK'
		]];

		$flattened = [];

		while ( ! empty($toProcess)) {
			$actual = array_pop($toProcess);
			foreach ($actual['data'] as $key => $value) {
				if (is_scalar($value)) {
					$flattened[$actual['prefix'] . '_' . $key] = $value;
				} else if (is_array($value)) {
					array_push($toProcess, [
						'data' => $value,
						'prefix' => $actual['prefix'] . '_' . $key
					]);
				}
			}
		}

		return $flattened;
	}

}
