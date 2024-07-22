<?php

namespace rrsoacis\manager;


use rrsoacis\system\Config;

class CoreManager
{
	public static function update()
	{
		exec("timeout 5 ping 8.8.8.8 -c 1", $exec_out, $internet);
		$internet = ($internet == 0);
		if ($internet)
		{
			exec("timeout 30 git fetch", $exec_out, $exec_ret);
			$exec_out = (count($exec_out) >= 1? $exec_out[0] : "");
			if ($exec_ret != 0
				&& (strpos($exec_out,'verification') !== false
					|| strpos($exec_out,'Permission') !== false))
			{
				exec("cd /home/oacis/rrs-oacis; git remote set-url origin https://github.com/rrs-oacis/rrs-oacis.git");
				exec("cd /home/oacis/rrs-oacis; git remote set-url --push origin git@github.com:rrs-oacis/rrs-oacis.git");
				exec("timeout 30 git fetch", $exec_out, $exec_ret);

				exec("cd /home/oacis/rrs-oacis/rrsenv; git remote set-url origin https://github.com/harrki/rrsenv.git");
				exec("cd /home/oacis/rrs-oacis/rrsenv; git remote set-url --push origin git@github.com:harrki/rrsenv.git");
			}

			if ($manifestJson = file_get_contents('https://raw.githubusercontent.com/rrs-oacis/rrs-oacis/master/manifest.json')) {
				$upgradableVersionArray = explode('.', json_decode($manifestJson[upgradable_version])["upgradable_version"]);
				$installedVersionArray = explode('.', Config::APP_VERSION);
				$upgradableVersion = $upgradableVersionArray[0]*1000000
					+ $upgradableVersionArray[1]*1000 + $upgradableVersionArray[2];
				$installedVersion = $installedVersionArray[0]*1000000
					+ $installedVersionArray[1]*1000 + $installedVersionArray[2];
				if ($exec_ret == 0) {
					if ($installedVersion >= $upgradableVersion) {
						exec("cd /home/oacis/rrs-oacis; git pull");
						exec("cd /home/oacis/rrs-oacis/rrsenv; git pull");
					}
				}
			}
		}
	}

	const GITLOG_NOINTERNET = -1;
	const GITLOG_LATEST = 0;
	const GITLOG_OLD = 1;

	public static function getGitLog()
	{
		exec("timeout 5 ping 8.8.8.8 -c 1", $exec_out, $internet);
		$internet = ($internet == 0);
		if ($internet)
		{
			exec("timeout 30 git fetch", $exec_out, $exec_ret);
			$exec_out = (count($exec_out) >= 1? $exec_out[0] : "");
			if ($exec_ret != 0
				&& (strpos($exec_out,'verification') !== false
					|| strpos($exec_out,'Permission') !== false))
			{
				exec("echo StrictHostKeyChecking no >> ~/.ssh/config");
				exec("git remote set-url origin https://github.com/rrs-oacis/rrs-oacis.git");
				exec("git remote set-url --push origin git@github.com:rrs-oacis/rrs-oacis.git");
				exec("timeout 30 git fetch", $exec_out, $exec_ret);
			}

			exec("test \"`git log -1 HEAD --oneline`\" != \"`git log -1 origin/master HEAD --oneline`\"", $exec_out, $gitcheck_ret);
			exec("git log -1 origin/master HEAD --decorate", $gitlog_remote, $exec_ret);
		}
		exec("git log -1 HEAD --decorate", $gitlog_local, $exec_ret);

		$results = [];
		$results[latest_log] = $gitlog_remote;
		$results[current_log] = $gitlog_local;

		$results[state] = self::GITLOG_LATEST;
		if (!$internet) {
			$results[state] = self::GITLOG_NOINTERNET;
			$results[latest_log] = "No internet connection\n\n\n\n";
		} elseif ($gitcheck_ret == 0) {
			$results[state] = self::GITLOG_OLD;
		}

		return $results;
	}
}
