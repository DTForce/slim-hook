<?php

namespace App;

use Interop\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;


final class Controller
{

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

	public function __construct(ContainerInterface $ci, Handler $handler)
	{
		$this->secret = $ci->get('settings')['secret'];

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


	public function __invoke(Request $request, Response $response, $args)
	{
		$body = $request->getParsedBody();
		if ( ! isset($body['object_kind'])) {
			return $response->withStatus(500);
		}

		foreach ($request->getHeader('X-Gitlab-Token') as $secret) {
			if ($secret == $this->secret) { // allow cast
				$this->secured = TRUE;
			}
		}

		if ($this->secret !== NULL && ! $this->secured) {
			return $response->withStatus(403);
		}

		if (isset($this->router[$body['object_kind']])) {
			$this->router[$body['object_kind']]($body);
			return $response->withStatus(200);
		}


		return $response->withStatus(404);
	}

}
