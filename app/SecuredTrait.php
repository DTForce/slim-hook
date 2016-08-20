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

use Slim\Http\Request;


trait SecuredTrait
{

	/**
	 * @var string
	 */
	private $secret;


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

}
