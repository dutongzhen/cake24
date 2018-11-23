<?php
/**
 * Basic CakePHP functionality.
 *
 * Core functions for including other source files, loading models and so forth.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Basic defines for timing functions.
 */
	define('SECOND', 1);
	define('MINUTE', 60);
	define('HOUR', 3600);
	define('DAY', 86400);
	define('WEEK', 604800);
	define('MONTH', 2592000);
	define('YEAR', 31536000);
	
    $server_name = $_SERVER['SERVER_NAME'];
    
	/*****************用于定义主域名常量 start**************/
    $server_arr = explode('.', $server_name);
    if($server_arr[1] == '51youjuke'){

    	define('PRIMARY_DOMAIN', '51youjuke.com');
    }elseif($server_arr[1] == 'youjuke'){

    	define('PRIMARY_DOMAIN', 'youjuke.com');
    //添加新增360youjuke.com域名 dtz 20170719
    }elseif($server_arr[1] == '360youjuke'){
    	define('PRIMARY_DOMAIN', '360youjuke.com');
    }
    /*****************用于定义主域名常量 end**************/

    
    /*****************用于生成首页静态页 start**************/
    switch ($server_name){
        
        case "www.51youjuke.com":
        	// define('PRIMARY_DOMAIN', '51youjuke.com');
        	define('CREATE_STATIC_DOMAIN', 'http://www.51youjuke.com');
        	define('CREATE_STATIC_ADDRESS', '/home/dutongzhen');
        	break;
        case "test.51youjuke.com":
        	// define('PRIMARY_DOMAIN', '51youjuke.com');
        	define('CREATE_STATIC_DOMAIN', 'http://test.51youjuke.com');
        	define('CREATE_STATIC_ADDRESS', '/srv/php-data/youjk_test');
        	break;
        case "pre.51youjuke.com":
        	// define('PRIMARY_DOMAIN', '51youjuke.com');
        	define('CREATE_STATIC_DOMAIN', 'http://pre.51youjuke.com');
        	define('CREATE_STATIC_ADDRESS', '/srv/pre_youjk');
        	break;
        case "www.youjuke.com":
        	// define('PRIMARY_DOMAIN', 'youjuke.com');
        	define('CREATE_STATIC_DOMAIN', 'http://www.youjuke.com');
        	define('CREATE_STATIC_ADDRESS', '/srv/php-front');
        	break;
        default:
        	// define('PRIMARY_DOMAIN', 'youjuke.com');
        	define('CREATE_STATIC_DOMAIN', 'http://www.youjuke.com');
        	define('CREATE_STATIC_ADDRESS', '/srv/php-front');
        	break;
    }
    /*****************用于生成首页静态页 end**************/

/*
* 域名前缀
*/
if (!defined('PREFIX_DOMAIN')) {
 define('PREFIX_DOMAIN', ( strpos('http://'.$server_name, 'pre') ? 'pre' : '' ) );
}

/*
* 日记域名
*/
if (!defined('RJ_WEB_URL')) {
 define('RJ_WEB_URL', 'http://'.PREFIX_DOMAIN.'riji.'.PRIMARY_DOMAIN);
}
if (!defined('RJ_WEB_URL_HTTPS')) {
 define('RJ_WEB_URL_HTTPS', '');
}


/*
* 公司域名
*/
if (!defined('GS_WEB_URL')) {
 define('GS_WEB_URL', 'http://'.PREFIX_DOMAIN.'gongsi.'.PRIMARY_DOMAIN);
}
if (!defined('GS_WEB_URL_HTTPS')) {
 define('GS_WEB_URL_HTTPS', '');
}

/*
* 服务域名
*/
if (!defined('FW_WEB_URL')) {
 define('FW_WEB_URL', 'http://'.PREFIX_DOMAIN.'fuwu.'.PRIMARY_DOMAIN);
}
if (!defined('FW_WEB_URL_HTTPS')) {
 define('FW_WEB_URL_HTTPS', '');
}

/*
* 商城域名
*/
if (!defined('SP_WEB_URL')) {
 define('SP_WEB_URL', 'http://'.PREFIX_DOMAIN.'mall.'.PRIMARY_DOMAIN);
}

/*
* 资讯域名
*/
if (!defined('ZX_WEB_URL')) {
 define('ZX_WEB_URL', 'http://'.PREFIX_DOMAIN.'zixun.'.PRIMARY_DOMAIN);
}
if (!defined('ZX_WEB_URL_HTTPS')) {
 define('ZX_WEB_URL_HTTPS', '');
}

/*
* 首页域名
*/
if (!defined('INDEX_WEB_URL')) {
 define('INDEX_WEB_URL', 'http://'.(PREFIX_DOMAIN ? PREFIX_DOMAIN : 'www').'.'.PRIMARY_DOMAIN);
}
if (!defined('INDEX_WEB_URL_HTTPS')) {
 define('INDEX_WEB_URL_HTTPS', '');
}

/*
* 会员中心域名
*/
if (!defined('MEMBER_WEB_URL')) {
 define('MEMBER_WEB_URL', 'http://'.PREFIX_DOMAIN.'member.'.PRIMARY_DOMAIN);
}

/*
* 手机端域名
*/
if (!defined('MOBILE_WEB_URL')) {
 define('MOBILE_WEB_URL', 'http://'.PREFIX_DOMAIN.'m.'.PRIMARY_DOMAIN);
}


/*
* 手机端域名加HTTPS
*/
if (!defined('MOBILE_WEB_URL_HTTPS')) {	
	//$http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';	
	define('MOBILE_WEB_URL_HTTPS', '');
	//define('MOBILE_WEB_URL_HTTPS', $http_type.PREFIX_DOMAIN.'m.'.PRIMARY_DOMAIN);
	// if ($_SERVER['HTTPS'] !== "on") {
	// 	define('MOBILE_WEB_URL_HTTPS', 'http://'.PREFIX_DOMAIN.'m.'.PRIMARY_DOMAIN);
	// }else{
	// 	define('MOBILE_WEB_URL_HTTPS', 'https://'.PREFIX_DOMAIN.'m.'.PRIMARY_DOMAIN);
	// }
	// if ( ! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
 //    {
 //        define('MOBILE_WEB_URL_HTTPS', 'https://'.PREFIX_DOMAIN.'m.'.PRIMARY_DOMAIN);
 //    }
 //    elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
 //    {
 //        define('MOBILE_WEB_URL_HTTPS', 'https://'.PREFIX_DOMAIN.'m.'.PRIMARY_DOMAIN);
 //    }
 //    elseif ( ! empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off')
 //    {
 //        define('MOBILE_WEB_URL_HTTPS', 'https://'.PREFIX_DOMAIN.'m.'.PRIMARY_DOMAIN);
 //    }else{
 //    	define('MOBILE_WEB_URL_HTTPS', 'http://'.PREFIX_DOMAIN.'m.'.PRIMARY_DOMAIN);
 //    }

}

/*
* 图库域名
*/
if (!defined('TK_WEB_URL')) {
 define('TK_WEB_URL', 'http://'.PREFIX_DOMAIN.'tuku.'.PRIMARY_DOMAIN);
}
if (!defined('TK_WEB_URL_HTTPS')) {
 define('TK_WEB_URL_HTTPS', '');
}

/*
* 工地域名
*/
if (!defined('GD_WEB_URL')) {
 define('GD_WEB_URL', 'http://'.PREFIX_DOMAIN.'gongdi.'.PRIMARY_DOMAIN);
}
if (!defined('GD_WEB_URL_HTTPS')) {
 define('GD_WEB_URL_HTTPS', '');
}

/*
* 图片域名
*/
if (!defined('IMAGES_WEB_URL')) {
 define('IMAGES_WEB_URL', 'http://'.PREFIX_DOMAIN.'image.'.PRIMARY_DOMAIN);
}

/*
* 虚拟用户头像和评论的图片路径
*/
if (!defined('VIRTUAL_PIC_URL')) {
 define('VIRTUAL_PIC_URL', 'http://'.PREFIX_DOMAIN.'image.'.PRIMARY_DOMAIN.'/images/baoming/pic/marketing/invented_comment/');
}

/**
 *@用于报名表单重复报名后跳出的验证码
 *@author 120291704@qq.com
 *@date 2017-12-25
 */
if (!defined('VERIFY_CAPTCHA')) {
	define('VERIFY_CAPTCHA', true);
}


if (!function_exists('config')) {

/**
 * Loads configuration files. Receives a set of configuration files
 * to load.
 * Example:
 *
 * `config('config1', 'config2');`
 *
 * @return boolean Success
 * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#config
 */
	function config() {
		$args = func_get_args();
		$count = count($args);
		$included = 0;
		foreach ($args as $arg) {
			if (file_exists(APP . 'Config' . DS . $arg . '.php')) {
				include_once APP . 'Config' . DS . $arg . '.php';
				$included++;
			}
		}
		return $included === $count;
	}

}

if (!function_exists('debug')) {

/**
 * Prints out debug information about given variable.
 *
 * Only runs if debug level is greater than zero.
 *
 * @param boolean $var Variable to show debug information for.
 * @param boolean $showHtml If set to true, the method prints the debug data in a browser-friendly way.
 * @param boolean $showFrom If set to true, the method prints from where the function was called.
 * @return void
 * @link http://book.cakephp.org/2.0/en/development/debugging.html#basic-debugging
 * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#debug
 */
	function debug($var, $showHtml = null, $showFrom = true) {
		if (Configure::read('debug') > 0) {
			App::uses('Debugger', 'Utility');
			$file = '';
			$line = '';
			$lineInfo = '';
			if ($showFrom) {
				$trace = Debugger::trace(array('start' => 1, 'depth' => 2, 'format' => 'array'));
				$file = str_replace(array(CAKE_CORE_INCLUDE_PATH, ROOT), '', $trace[0]['file']);
				$line = $trace[0]['line'];
			}
			$html = <<<HTML
<div class="cake-debug-output">
%s
<pre class="cake-debug">
%s
</pre>
</div>
HTML;
			$text = <<<TEXT
%s
########## DEBUG ##########
%s
###########################

TEXT;
			$template = $html;
			if (php_sapi_name() === 'cli' || $showHtml === false) {
				$template = $text;
				if ($showFrom) {
					$lineInfo = sprintf('%s (line %s)', $file, $line);
				}
			}
			if ($showHtml === null && $template !== $text) {
				$showHtml = true;
			}
			$var = Debugger::exportVar($var, 25);
			if ($showHtml) {
				$template = $html;
				$var = h($var);
				if ($showFrom) {
					$lineInfo = sprintf('<span><strong>%s</strong> (line <strong>%s</strong>)</span>', $file, $line);
				}
			}
			printf($template, $lineInfo, $var);
		}
	}

}

if (!function_exists('sortByKey')) {

/**
 * Sorts given $array by key $sortBy.
 *
 * @param array $array Array to sort
 * @param string $sortBy Sort by this key
 * @param string $order Sort order asc/desc (ascending or descending).
 * @param integer $type Type of sorting to perform
 * @return mixed Sorted array
 * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#sortByKey
 */
	function sortByKey(&$array, $sortBy, $order = 'asc', $type = SORT_NUMERIC) {
		if (!is_array($array)) {
			return null;
		}

		foreach ($array as $key => $val) {
			$sa[$key] = $val[$sortBy];
		}

		if ($order === 'asc') {
			asort($sa, $type);
		} else {
			arsort($sa, $type);
		}

		foreach ($sa as $key => $val) {
			$out[] = $array[$key];
		}
		return $out;
	}

}

if (!function_exists('h')) {

/**
 * Convenience method for htmlspecialchars.
 *
 * @param string|array|object $text Text to wrap through htmlspecialchars. Also works with arrays, and objects.
 *    Arrays will be mapped and have all their elements escaped. Objects will be string cast if they
 *    implement a `__toString` method. Otherwise the class name will be used.
 * @param boolean $double Encode existing html entities
 * @param string $charset Character set to use when escaping. Defaults to config value in 'App.encoding' or 'UTF-8'
 * @return string Wrapped text
 * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#h
 */
	function h($text, $double = true, $charset = null) {
		if (is_array($text)) {
			$texts = array();
			foreach ($text as $k => $t) {
				$texts[$k] = h($t, $double, $charset);
			}
			return $texts;
		} elseif (is_object($text)) {
			if (method_exists($text, '__toString')) {
				$text = (string)$text;
			} else {
				$text = '(object)' . get_class($text);
			}
		} elseif (is_bool($text)) {
			return $text;
		}

		static $defaultCharset = false;
		if ($defaultCharset === false) {
			$defaultCharset = Configure::read('App.encoding');
			if ($defaultCharset === null) {
				$defaultCharset = 'UTF-8';
			}
		}
		if (is_string($double)) {
			$charset = $double;
		}
		return htmlspecialchars($text, ENT_QUOTES, ($charset) ? $charset : $defaultCharset, $double);
	}

}

if (!function_exists('pluginSplit')) {

/**
 * Splits a dot syntax plugin name into its plugin and class name.
 * If $name does not have a dot, then index 0 will be null.
 *
 * Commonly used like `list($plugin, $name) = pluginSplit($name);`
 *
 * @param string $name The name you want to plugin split.
 * @param boolean $dotAppend Set to true if you want the plugin to have a '.' appended to it.
 * @param string $plugin Optional default plugin to use if no plugin is found. Defaults to null.
 * @return array Array with 2 indexes. 0 => plugin name, 1 => class name
 * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#pluginSplit
 */
	function pluginSplit($name, $dotAppend = false, $plugin = null) {
		if (strpos($name, '.') !== false) {
			$parts = explode('.', $name, 2);
			if ($dotAppend) {
				$parts[0] .= '.';
			}
			return $parts;
		}
		return array($plugin, $name);
	}

}

if (!function_exists('pr')) {

/**
 * print_r() convenience function
 *
 * In terminals this will act the same as using print_r() directly, when not run on cli
 * print_r() will wrap <PRE> tags around the output of given array. Similar to debug().
 *
 * @see debug()
 * @param array $var Variable to print out
 * @return void
 * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#pr
 */
	function pr($var) {
		if (Configure::read('debug') > 0) {
			$template = php_sapi_name() !== 'cli' ? '<pre>%s</pre>' : "\n%s\n";
			printf($template, print_r($var, true));
		}
	}

}

if (!function_exists('am')) {

/**
 * Merge a group of arrays
 *
 * @param array First array
 * @param array Second array
 * @param array Third array
 * @param array Etc...
 * @return array All array parameters merged into one
 * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#am
 */
	function am() {
		$r = array();
		$args = func_get_args();
		foreach ($args as $a) {
			if (!is_array($a)) {
				$a = array($a);
			}
			$r = array_merge($r, $a);
		}
		return $r;
	}

}

if (!function_exists('env')) {

/**
 * Gets an environment variable from available sources, and provides emulation
 * for unsupported or inconsistent environment variables (i.e. DOCUMENT_ROOT on
 * IIS, or SCRIPT_NAME in CGI mode). Also exposes some additional custom
 * environment information.
 *
 * @param string $key Environment variable name.
 * @return string Environment variable setting.
 * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#env
 */
	function env($key) {
		if ($key === 'HTTPS') {
			if (isset($_SERVER['HTTPS'])) {
				return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
			}
			return (strpos(env('SCRIPT_URI'), 'https://') === 0);
		}

		if ($key === 'SCRIPT_NAME') {
			if (env('CGI_MODE') && isset($_ENV['SCRIPT_URL'])) {
				$key = 'SCRIPT_URL';
			}
		}

		$val = null;
		if (isset($_SERVER[$key])) {
			$val = $_SERVER[$key];
		} elseif (isset($_ENV[$key])) {
			$val = $_ENV[$key];
		} elseif (getenv($key) !== false) {
			$val = getenv($key);
		}

		if ($key === 'REMOTE_ADDR' && $val === env('SERVER_ADDR')) {
			$addr = env('HTTP_PC_REMOTE_ADDR');
			if ($addr !== null) {
				$val = $addr;
			}
		}

		if ($val !== null) {
			return $val;
		}

		switch ($key) {
			case 'DOCUMENT_ROOT':
				$name = env('SCRIPT_NAME');
				$filename = env('SCRIPT_FILENAME');
				$offset = 0;
				if (!strpos($name, '.php')) {
					$offset = 4;
				}
				return substr($filename, 0, -(strlen($name) + $offset));
			case 'PHP_SELF':
				return str_replace(env('DOCUMENT_ROOT'), '', env('SCRIPT_FILENAME'));
			case 'CGI_MODE':
				return (PHP_SAPI === 'cgi');
			case 'HTTP_BASE':
				$host = env('HTTP_HOST');
				$parts = explode('.', $host);
				$count = count($parts);

				if ($count === 1) {
					return '.' . $host;
				} elseif ($count === 2) {
					return '.' . $host;
				} elseif ($count === 3) {
					$gTLD = array(
						'aero',
						'asia',
						'biz',
						'cat',
						'com',
						'coop',
						'edu',
						'gov',
						'info',
						'int',
						'jobs',
						'mil',
						'mobi',
						'museum',
						'name',
						'net',
						'org',
						'pro',
						'tel',
						'travel',
						'xxx'
					);
					if (in_array($parts[1], $gTLD)) {
						return '.' . $host;
					}
				}
				array_shift($parts);
				return '.' . implode('.', $parts);
		}
		return null;
	}

}

if (!function_exists('cache')) {

/**
 * Reads/writes temporary data to cache files or session.
 *
 * @param string $path File path within /tmp to save the file.
 * @param mixed $data The data to save to the temporary file.
 * @param mixed $expires A valid strtotime string when the data expires.
 * @param string $target The target of the cached data; either 'cache' or 'public'.
 * @return mixed The contents of the temporary file.
 * @deprecated Will be removed in 3.0. Please use Cache::write() instead.
 */
	function cache($path, $data = null, $expires = '+1 day', $target = 'cache') {
		if (Configure::read('Cache.disable')) {
			return null;
		}
		$now = time();

		if (!is_numeric($expires)) {
			$expires = strtotime($expires, $now);
		}

		switch (strtolower($target)) {
			case 'cache':
				$filename = CACHE . $path;
				break;
			case 'public':
				$filename = WWW_ROOT . $path;
				break;
			case 'tmp':
				$filename = TMP . $path;
				break;
		}
		$timediff = $expires - $now;
		$filetime = false;

		if (file_exists($filename)) {
			//@codingStandardsIgnoreStart
			$filetime = @filemtime($filename);
			//@codingStandardsIgnoreEnd
		}

		if ($data === null) {
			if (file_exists($filename) && $filetime !== false) {
				if ($filetime + $timediff < $now) {
					//@codingStandardsIgnoreStart
					@unlink($filename);
					//@codingStandardsIgnoreEnd
				} else {
					//@codingStandardsIgnoreStart
					$data = @file_get_contents($filename);
					//@codingStandardsIgnoreEnd
				}
			}
		} elseif (is_writable(dirname($filename))) {
			//@codingStandardsIgnoreStart
			@file_put_contents($filename, $data, LOCK_EX);
			//@codingStandardsIgnoreEnd
		}
		return $data;
	}

}

if (!function_exists('clearCache')) {

/**
 * Used to delete files in the cache directories, or clear contents of cache directories
 *
 * @param string|array $params As String name to be searched for deletion, if name is a directory all files in
 *   directory will be deleted. If array, names to be searched for deletion. If clearCache() without params,
 *   all files in app/tmp/cache/views will be deleted
 * @param string $type Directory in tmp/cache defaults to view directory
 * @param string $ext The file extension you are deleting
 * @return true if files found and deleted false otherwise
 */
	function clearCache($params = null, $type = 'views', $ext = '.php') {
		if (is_string($params) || $params === null) {
			$params = preg_replace('/\/\//', '/', $params);
			$cache = CACHE . $type . DS . $params;

			if (is_file($cache . $ext)) {
				//@codingStandardsIgnoreStart
				@unlink($cache . $ext);
				//@codingStandardsIgnoreEnd
				return true;
			} elseif (is_dir($cache)) {
				$files = glob($cache . '*');

				if ($files === false) {
					return false;
				}

				foreach ($files as $file) {
					if (is_file($file) && strrpos($file, DS . 'empty') !== strlen($file) - 6) {
						//@codingStandardsIgnoreStart
						@unlink($file);
						//@codingStandardsIgnoreEnd
					}
				}
				return true;
			}
			$cache = array(
				CACHE . $type . DS . '*' . $params . $ext,
				CACHE . $type . DS . '*' . $params . '_*' . $ext
			);
			$files = array();
			while ($search = array_shift($cache)) {
				$results = glob($search);
				if ($results !== false) {
					$files = array_merge($files, $results);
				}
			}
			if (empty($files)) {
				return false;
			}
			foreach ($files as $file) {
				if (is_file($file) && strrpos($file, DS . 'empty') !== strlen($file) - 6) {
					//@codingStandardsIgnoreStart
					@unlink($file);
					//@codingStandardsIgnoreEnd
				}
			}
			return true;

		} elseif (is_array($params)) {
			foreach ($params as $file) {
				clearCache($file, $type, $ext);
			}
			return true;
		}
		return false;
	}

}

if (!function_exists('stripslashes_deep')) {

/**
 * Recursively strips slashes from all values in an array
 *
 * @param array $values Array of values to strip slashes
 * @return mixed What is returned from calling stripslashes
 * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#stripslashes_deep
 */
	function stripslashes_deep($values) {
		if (is_array($values)) {
			foreach ($values as $key => $value) {
				$values[$key] = stripslashes_deep($value);
			}
		} else {
			$values = stripslashes($values);
		}
		return $values;
	}

}

if (!function_exists('__')) {

/**
 * Returns a translated string if one is found; Otherwise, the submitted message.
 *
 * @param string $singular Text to translate
 * @param mixed $args Array with arguments or multiple arguments in function
 * @return mixed translated string
 * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#__
 */
	function __($singular, $args = null) {
		if (!$singular) {
			return;
		}

		App::uses('I18n', 'I18n');
		$translated = I18n::translate($singular);
		if ($args === null) {
			return $translated;
		} elseif (!is_array($args)) {
			$args = array_slice(func_get_args(), 1);
		}
		return vsprintf($translated, $args);
	}

}

if (!function_exists('__n')) {

/**
 * Returns correct plural form of message identified by $singular and $plural for count $count.
 * Some languages have more than one form for plural messages dependent on the count.
 *
 * @param string $singular Singular text to translate
 * @param string $plural Plural text
 * @param integer $count Count
 * @param mixed $args Array with arguments or multiple arguments in function
 * @return mixed plural form of translated string
 * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#__n
 */
	function __n($singular, $plural, $count, $args = null) {
		if (!$singular) {
			return;
		}

		App::uses('I18n', 'I18n');
		$translated = I18n::translate($singular, $plural, null, 6, $count);
		if ($args === null) {
			return $translated;
		} elseif (!is_array($args)) {
			$args = array_slice(func_get_args(), 3);
		}
		return vsprintf($translated, $args);
	}

}

if (!function_exists('__d')) {

/**
 * Allows you to override the current domain for a single message lookup.
 *
 * @param string $domain Domain
 * @param string $msg String to translate
 * @param mixed $args Array with arguments or multiple arguments in function
 * @return translated string
 * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#__d
 */
	function __d($domain, $msg, $args = null) {
		if (!$msg) {
			return;
		}
		App::uses('I18n', 'I18n');
		$translated = I18n::translate($msg, null, $domain);
		if ($args === null) {
			return $translated;
		} elseif (!is_array($args)) {
			$args = array_slice(func_get_args(), 2);
		}
		return vsprintf($translated, $args);
	}

}

if (!function_exists('__dn')) {

/**
 * Allows you to override the current domain for a single plural message lookup.
 * Returns correct plural form of message identified by $singular and $plural for count $count
 * from domain $domain.
 *
 * @param string $domain Domain
 * @param string $singular Singular string to translate
 * @param string $plural Plural
 * @param integer $count Count
 * @param mixed $args Array with arguments or multiple arguments in function
 * @return plural form of translated string
 * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#__dn
 */
	function __dn($domain, $singular, $plural, $count, $args = null) {
		if (!$singular) {
			return;
		}
		App::uses('I18n', 'I18n');
		$translated = I18n::translate($singular, $plural, $domain, 6, $count);
		if ($args === null) {
			return $translated;
		} elseif (!is_array($args)) {
			$args = array_slice(func_get_args(), 4);
		}
		return vsprintf($translated, $args);
	}

}

if (!function_exists('__dc')) {

/**
 * Allows you to override the current domain for a single message lookup.
 * It also allows you to specify a category.
 *
 * The category argument allows a specific category of the locale settings to be used for fetching a message.
 * Valid categories are: LC_CTYPE, LC_NUMERIC, LC_TIME, LC_COLLATE, LC_MONETARY, LC_MESSAGES and LC_ALL.
 *
 * Note that the category must be specified with a numeric value, instead of the constant name. The values are:
 *
 * - LC_ALL       0
 * - LC_COLLATE   1
 * - LC_CTYPE     2
 * - LC_MONETARY  3
 * - LC_NUMERIC   4
 * - LC_TIME      5
 * - LC_MESSAGES  6
 *
 * @param string $domain Domain
 * @param string $msg Message to translate
 * @param integer $category Category
 * @param mixed $args Array with arguments or multiple arguments in function
 * @return translated string
 * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#__dc
 */
	function __dc($domain, $msg, $category, $args = null) {
		if (!$msg) {
			return;
		}
		App::uses('I18n', 'I18n');
		$translated = I18n::translate($msg, null, $domain, $category);
		if ($args === null) {
			return $translated;
		} elseif (!is_array($args)) {
			$args = array_slice(func_get_args(), 3);
		}
		return vsprintf($translated, $args);
	}

}

if (!function_exists('__dcn')) {

/**
 * Allows you to override the current domain for a single plural message lookup.
 * It also allows you to specify a category.
 * Returns correct plural form of message identified by $singular and $plural for count $count
 * from domain $domain.
 *
 * The category argument allows a specific category of the locale settings to be used for fetching a message.
 * Valid categories are: LC_CTYPE, LC_NUMERIC, LC_TIME, LC_COLLATE, LC_MONETARY, LC_MESSAGES and LC_ALL.
 *
 * Note that the category must be specified with a numeric value, instead of the constant name. The values are:
 *
 * - LC_ALL       0
 * - LC_COLLATE   1
 * - LC_CTYPE     2
 * - LC_MONETARY  3
 * - LC_NUMERIC   4
 * - LC_TIME      5
 * - LC_MESSAGES  6
 *
 * @param string $domain Domain
 * @param string $singular Singular string to translate
 * @param string $plural Plural
 * @param integer $count Count
 * @param integer $category Category
 * @param mixed $args Array with arguments or multiple arguments in function
 * @return plural form of translated string
 * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#__dcn
 */
	function __dcn($domain, $singular, $plural, $count, $category, $args = null) {
		if (!$singular) {
			return;
		}
		App::uses('I18n', 'I18n');
		$translated = I18n::translate($singular, $plural, $domain, $category, $count);
		if ($args === null) {
			return $translated;
		} elseif (!is_array($args)) {
			$args = array_slice(func_get_args(), 5);
		}
		return vsprintf($translated, $args);
	}

}

if (!function_exists('__c')) {

/**
 * The category argument allows a specific category of the locale settings to be used for fetching a message.
 * Valid categories are: LC_CTYPE, LC_NUMERIC, LC_TIME, LC_COLLATE, LC_MONETARY, LC_MESSAGES and LC_ALL.
 *
 * Note that the category must be specified with a numeric value, instead of the constant name. The values are:
 *
 * - LC_ALL       0
 * - LC_COLLATE   1
 * - LC_CTYPE     2
 * - LC_MONETARY  3
 * - LC_NUMERIC   4
 * - LC_TIME      5
 * - LC_MESSAGES  6
 *
 * @param string $msg String to translate
 * @param integer $category Category
 * @param mixed $args Array with arguments or multiple arguments in function
 * @return translated string
 * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#__c
 */
	function __c($msg, $category, $args = null) {
		if (!$msg) {
			return;
		}
		App::uses('I18n', 'I18n');
		$translated = I18n::translate($msg, null, null, $category);
		if ($args === null) {
			return $translated;
		} elseif (!is_array($args)) {
			$args = array_slice(func_get_args(), 2);
		}
		return vsprintf($translated, $args);
	}

}

if (!function_exists('LogError')) {

/**
 * Shortcut to Log::write.
 *
 * @param string $message Message to write to log
 * @return void
 * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#LogError
 */
	function LogError($message) {
		App::uses('CakeLog', 'Log');
		$bad = array("\n", "\r", "\t");
		$good = ' ';
		CakeLog::write('error', str_replace($bad, $good, $message));
	}

}

if (!function_exists('fileExistsInPath')) {

/**
 * Searches include path for files.
 *
 * @param string $file File to look for
 * @return Full path to file if exists, otherwise false
 * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#fileExistsInPath
 */
	function fileExistsInPath($file) {
		$paths = explode(PATH_SEPARATOR, ini_get('include_path'));
		foreach ($paths as $path) {
			$fullPath = $path . DS . $file;

			if (file_exists($fullPath)) {
				return $fullPath;
			} elseif (file_exists($file)) {
				return $file;
			}
		}
		return false;
	}

}

if (!function_exists('convertSlash')) {

/**
 * Convert forward slashes to underscores and removes first and last underscores in a string
 *
 * @param string String to convert
 * @return string with underscore remove from start and end of string
 * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#convertSlash
 */
	function convertSlash($string) {
		$string = trim($string, '/');
		$string = preg_replace('/\/\//', '/', $string);
		$string = str_replace('/', '_', $string);
		return $string;
	}

}

function strWidth($str, $len, $enc = 'utf8') {
    $length = mb_strlen($str, $enc);

    $count  = 0;
    $string = '';
    for ($i = 0; $i < $length; $i++) {
        $char = mb_substr($str, $i, 1, $enc);
        if (strlen($char) > 1) {
            $count += 2;
        }
        else {
            $count += 1;
        }

        if ($count > $len) {
            break;
        }
        else {
            $string .= $char;
        }
    }
    return $string;
}
