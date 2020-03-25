<?php

namespace BxdFramework\ImageStyle;

class ImageStyle
{
	// ua是否接受webp格式
	private $webp = false;

	// 样式列表
	private $conf;

	// 普通类型
	const TYPE_COMM = 0;
	// webp类型
	const TYPE_WEBP = 1;

	/**
	 * 制作样式
	 */
	static public function make ($img, $position)
	{
		$instance = self::_getInstance();
		$style = $instance->getStyle($position);
		return $instance->mergeImage($img, $style);
	}

	/**
	 * 实例化
	 */
	static private function _getInstance ()
	{
		static $instance;
		if ($instance) return $instance;
		$instance = new self();
		return $instance;
	}

	/**
	 * 构造
	 */
	public function __construct ()
	{
		$this->_init();
	}

	/**
	 * 合成
	 */
	public function mergeImage ($img, $style)
	{
		// 无图片
		if (empty($img)) return $img;
		// 无样式
		if (empty($style)) return $img;
		// gif
		if (strpos(strtolower($img) , '.gif') !== false) return $img;
		// 已有样式
		if (strpos($img, '@') !== false) return $img;
		// 已有样式
		if (strpos($img, 'x-oss-process=image') !== false) return $img;
		// 非大V店域名
		if (strpos($img, 'dvmama') === false && strpos($img, 'bingxindan') === false && strpos($img, 'vyohui') === false && strpos($img, 'bravetime') === false) return $img;
		// 上样式
		$newImg = sprintf("%s?x-oss-process=image%s", $img, $style);
		return $newImg;
	}

	/**
	 * 获取样式
	 */
	public function getStyle ($position)
	{		
		$uaType = $this->webp ? self::TYPE_WEBP : self::TYPE_COMM;
		$style = isset($this->conf[$position][$uaType]) ? $this->conf[$position][$uaType] : '';
		return $style;
	}

	/**
	 * 初始化
	 */
	private function _init ()
	{
		// 判断ua是否支持webp
		$this->_initWebp();
		// 加载样式列表
		$this->_loadConf();
	}

	/**
	 * 判断ua是否支持webp
	 */
	private function _initWebp ()
	{
		if (isset($_COOKIE['webp'])) {
			$this->webp = $_COOKIE['webp'] ? true : false;
		} else {
			$this->webp = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false;
		}
	}

	/**
	 * 加载样式列表
	 */
	private function _loadConf ()
	{
		$this->conf = require 'style.conf.php';
	}
}
