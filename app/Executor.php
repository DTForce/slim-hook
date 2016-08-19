<?php

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
