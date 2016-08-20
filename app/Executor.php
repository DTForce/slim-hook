<?php

namespace App;

class Executor
{

	/**
	 * @param string|array $scriptPath
	 * @param array $env
	 */
	public function executeCommand($scriptPath, array $env = [])
	{
		$oldCwd = NULL;
		if (is_array($scriptPath)) {
			if (isset($scriptPath['cwd'])) {
				$cwd = $scriptPath['cwd'];
				unset($scriptPath['cwd']);
				$oldCwd = getcwd();
				chdir($cwd);
			}
			$commands = $scriptPath;
		} else {
			$commands = [$scriptPath];
		}
		foreach ($env as $key => $value) {
			putenv($key . '=' . $value);
		}
		$result = '';
		foreach ($commands as $command) {
			$result .= shell_exec($command);
		}
		if ($oldCwd !== NULL) {
			chdir($oldCwd);
		}
		return $result;
	}

}
