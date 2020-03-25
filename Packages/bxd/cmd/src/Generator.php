<?php
namespace BxdFramework\Cmd;
class Generator
{
	/**
	 * 生成App命令
	 */
	static public function makeApp ($cmd, $params)
	{
		$conf = require 'app.conf.php';
		list($host, $action) = explode('.', $cmd);
		if (!isset($conf[$host][$action])) return '';
		$cmd = sprintf($conf[$host][$action], urlencode(json_encode($params)));
		return $cmd;
	}

	/**
	 * 生成App命令
	 */
	static public function makeAppOld ($cmd, $params)
	{
		$conf = require 'app.conf.php';
        list($host, $action) = explode('.', $cmd);
		if (!isset($conf[$host][$action])) return '';
        $cmd = sprintf($conf[$host][$action], urlencode(json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)));
		return $cmd;
	}

	/**
	 * 生成Web命令
	 */
	static public function makeWeb ($cmd, $params)
	{
		$conf = require 'web.conf.php';
		list($host, $action) = explode('.', $cmd);
		if (!isset($conf[$host][$action])) return '';
		$cmd = vsprintf($conf[$host][$action], $params);
		return $cmd;
	}
}