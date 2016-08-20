<?php

namespace App;

use Interop\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;


final class HookController
{

	const SECRET_HEADER = 'X-Gitlab-Token';

	/**
	 * @var string
	 */
	private $secret;

	/**
	 * @var callable[]
	 */
	private $router;


	public function __construct(ContainerInterface $ci, HookHandler $handler)
	{
		$this->secret = (string) $ci->get('settings')['secret'];

		$this->router['pipeline'] = function (array $event) use($handler) {
			foreach ($event['builds'] as $build) {
				if ($build['stage'] === 'deploy') {
					$handler->handleDeploy($event, $build);
				}
			}
		};

		$this->router['push'] = function (array $event) use($handler) {
			$handler->handlePush($event);
		};

		$this->router['tag_push'] = function (array $event) use($handler) {
			$handler->handleTag($event);
		};
	}


	public function __invoke(Request $request, Response $response, array $args)
	{
		if ( ! $this->isValid($request)) {
			return $response->withStatus(500);
		}

		if ( ! $this->isSecured($request)) {
			return $response->withStatus(403);
		}

		if ($this->isHandled($request)) {
			$this->handle($request);
			return $response->withStatus(200);
		}

		return $response->withStatus(404);
	}


	/**
	 * @param Request $request
	 * @return bool
	 */
	private function isValid(Request $request)
	{
		$body = $request->getParsedBody();
		if ( ! isset($body['object_kind'])) {
			return FALSE;
		}

		return TRUE;
	}


	/**
	 * @param Request $request
	 * @return bool
	 */
	private function isSecured(Request $request)
	{
		$secured = FALSE;
		foreach ($request->getHeader(self::SECRET_HEADER) as $secret) {
			if ($secret == $this->secret) { // allow cast
				$secured = TRUE;
			}
		}

		return $this->secret === NULL || $secured;
	}


	/**
	 * @param Request $request
	 * @return bool
	 */
	private function isHandled(Request $request)
	{
		$body = $request->getParsedBody();
		return isset($this->router[$body['object_kind']]);
	}


	/**
	 * @param Request $request
	 */
	private function handle(Request $request)
	{
		$body = $request->getParsedBody();
		$this->router[$body['object_kind']]($body);
	}

}
