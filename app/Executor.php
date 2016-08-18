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

class Executor
{

	/**
	 * @param string|array $scriptPath
	 */
	public function executeCommand($scriptPath, array $env)
	{
		$oldCwd = NULL;
		if (is_array($scriptPath)) {
			$cwd = $scriptPath['cwd'];
			$scriptPath = $scriptPath['command'];
			$oldCwd = getcwd();
			chdir($cwd);
		}
		foreach ($env as $key => $value) {
			putenv($key . '=' . $value);
		}
		shell_exec($scriptPath);
		if ($oldCwd !== NULL) {
			chdir($oldCwd);
		}
	}

}
