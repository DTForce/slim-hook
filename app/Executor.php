<?php

namespace App;

use App\Exception\ExecutionFailed;


class Executor
{

	/**
	 * @param string|array $scriptPath
	 * @param array $env
	 * @return string
	 * @throws ExecutionFailed
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
		$result = [];
		foreach ($commands as $command) {
			exec($command, $result, $return);
			if ($return !== 0) {
				throw new ExecutionFailed('Command ' . $command . 'resulted in error: ' . $return, $return);
			}
		}
		if ($oldCwd !== NULL) {
			chdir($oldCwd);
		}
		return implode("\n", $result);
	}

}
