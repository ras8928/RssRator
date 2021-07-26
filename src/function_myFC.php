<?php

ini_set('max_execution_time', 300);
if (!isset($GLOBALS['dontRSSheader'])) {
	header('Content-Type: application/rss+xml; charset=utf-8');
}
header('Connection: Keep-Alive');
header('Keep-Alive: timeout=300');
header('Cache-Control: no-cache, must-revalidate'); //HTTP 1.1
header('Pragma: no-cache'); //HTTP 1.0
if (isLocalhost()) {
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
}

if (!mt_rand(0, 50)) {
	rotateLog('error_log', 50000);
	rotateLog('RunHeadersLog.txt');
	rotateLog('GetsLog.txt');
	clearCache();
}

if (!function_exists('str_contains')) {
	/**
	 * @param array|string $needle
	 *
	 * @return bool
	 */
	function str_contains($needle, string $haystack)
	{
		if (!$haystack) {
			return false;
		}

		if (is_array($needle)) {
			foreach ($needle as $str) {
				if (false !== strpos($haystack, $str)) {
					return true;
				}
			}
		} else {
			return false !== strpos($haystack, $needle);
		}
	}
}

function relativeToAbsoluteURL($href, $url)
{
	extract(parse_url($url), EXTR_PREFIX_ALL, 'url');

	if (empty($url_scheme) || empty($url_host)) {
		return 'invalid baseURL (' . $url . '), cannot relativeToAbsoluteURL()';
	}

	extract(parse_url($href), EXTR_PREFIX_ALL, 'href');

	if (!isset($href_host)) {
		if ('//' === substr($href, 0, 2)) {
			$href = $url_scheme . ':' . $href;
		}
		if ('/' == $href[0]) {
			$href = $url_scheme . '://' . $url_host . $href;
		} else {
			$href = $url_scheme . '://' . $url_host . '/' . $href;
		}
	}

	if (filter_var($href, FILTER_VALIDATE_URL)) {
		return $href;
	}
}

function cleanSpecial($txt, $trim = true)
{
	$txt = str_replace('&#39;', "'", $txt);
	if ($trim) {
		$txt = trim($txt);
	}
	$txt = htmlspecialchars_decode(urldecode(stripcslashes(html_entity_decode($txt, ENT_COMPAT, 'UTF-8'))));
	if ($trim) {
		$txt = trim(preg_replace('/\s+/', ' ', trim($txt)));
	}

	return $txt;
}

function utf8_urldecode($str)
{
	$str = preg_replace('/%u([0-9a-f]{3,4})/i', '&#x\\1;', urldecode($str));

	return html_entity_decode($str, ENT_COMPAT, 'UTF-8');
}

function isLocalhost($whitelist = ['127.0.0.1', '::1'])
{
	return in_array($_SERVER['REMOTE_ADDR'] ?? '', $whitelist);
}

function getRealUserIp()
{
	switch (true) {
		case !empty($_SERVER['HTTP_X_REAL_IP']):
			return $_SERVER['HTTP_X_REAL_IP'];

		case !empty($_SERVER['HTTP_CLIENT_IP']):
			return $_SERVER['HTTP_CLIENT_IP'];

		case !empty($_SERVER['HTTP_X_FORWARDED_FOR']):
			return $_SERVER['HTTP_X_FORWARDED_FOR'];

		default:
			return $_SERVER['REMOTE_ADDR'];
	}
}

function clearCache($days = 60, $dir = 'cache', $pattern = '/*')
{
	$days = 60 * 60 * 24 * $days;

	$total = 0;
	$deleted = 0;
	foreach (glob("{$dir}{$pattern}") as $f) {
		++$total;
		if (is_file($f) && (time() - filemtime($f) > $days)) {
			$deleted += unlink($f);

			// echo  "Deleted: " . basename($f) . "\n";
		}
	}

	return ['deleted' => $deleted, 'total' => $total];
}

function myFC_GlobalParams($html)
{
	global $url;
	global $param;
	global $feedTitle;

	if (empty($html) || !$html || null === $html || 0 == strlen($html)) {
		exit;
	}

	if (empty($GLOBALS['feedTitle']) && empty($param['feedTitle']) && empty($feedTitle)) {
		if ($html->find('title', 0)) {
			$param['feedTitle'] = cleanSpecial($html->find('title', 0)->innertext);
		}
	}

	$param['favico'] = fetchPageFavicons($html);
	$param['datemodified'] = fetchPageModified($html);

	if (empty($GLOBALS['feedDescription']) && empty($param['feedDescription']) && empty($feedDescription)) {
		// $param['feedDescription'] = fetchPageDescription($html);
	}
}

function fetchPageModified()
{
	global $url;

	if (empty($GLOBALS['datemodified'])) {
		$GLOBALS['datemodified'] = myGetTime($url);
	} else {
		$GLOBALS['datemodified'] = strtotime($GLOBALS['datemodified']) > strtotime(myGetTime($url)) ? $GLOBALS['datemodified'] : myGetTime($url);
	}

	return $GLOBALS['datemodified'];
}

/**
 * @param $html_or_url
 * @param bool $fetch_from_url
 *
 * @return array|bool
 */
function fetchPageDescription($html_or_url, $fetch_from_url = false)
{
	if ($fetch_from_url) {
		$contents = myGetOnlyHead($html_or_url);

		if (!empty($contents['contents']) && strlen($contents['contents']) > 100) {
			$html = str_get_html($contents['contents']);
		} else {
			return false;
		}
	} else {
		$html = $html_or_url;
	}

	$selectors = [
		'//meta[name*="twitter:description"]',
		'//meta[property="og:description"]',
		'//meta[itemprop*="description"]',
		'//meta[name*="description"]',
	];
	$description = getAtrributeFromTag($html, $selectors, 'content');

	if ($html->find('//meta[property="og:image"]', 0)) {
		$image = cleanSpecial($html->find('//meta[property*="og:image"]', 0)->getAttribute('content'));
	} elseif ($html->find('//meta[name*="twitter:image"]', 0)) {
		$image = cleanSpecial($html->find('//meta[name*="twitter:image"]', 0)->getAttribute('content'));
	} elseif ($html->find('//meta[itemprop*="image"]', 0)) {
		$image = cleanSpecial($html->find('//meta[itemprop*="image"]', 0)->getAttribute('content'));
	} elseif ($html->find('//meta[name*="image"]', 0)) {
		$image = cleanSpecial($html->find('//meta[name*="image"]', 0)->getAttribute('content'));
	} else {
		$image = null;
	}

	if ($html->find('//meta[name*="twitter:title"]', 0)) {
		$title = cleanSpecial($html->find('//meta[name*="twitter:title"]', 0)->getAttribute('content'));
	} elseif ($html->find('//meta[property="og:title"]', 0)) {
		$title = cleanSpecial($html->find('//meta[property*="og:title"]', 0)->getAttribute('content'));
	} elseif ($html->find('//meta[name*="twitter:text:title"]', 0)) {
		$title = cleanSpecial($html->find('//meta[name*="twitter:text:title"]', 0)->getAttribute('content'));
	} elseif ($html->find('//meta[itemprop*="title"]', 0)) {
		$title = cleanSpecial($html->find('//meta[itemprop*="title"]', 0)->getAttribute('content'));
	} elseif ($html->find('//meta[name*="title"]', 0)) {
		$title = cleanSpecial($html->find('//meta[name*="title"]', 0)->getAttribute('content'));
	} else {
		$title = null;
	}

	if (empty($description)) {
		return false;
	}

	return [
		'title' => $title,
		'description' => $description,
		'image' => $image,
	];
}

function getAtrributeFromTag(simple_html_dom $html, array $selectors, string $attribute_name)
{
	foreach ($selectors as $item) {
		if ($html->find('' . $item . '')) {
			return cleanSpecial($html->find($item, 0)->getAttribute($attribute_name));
		}
	}
}

function isHTTP200($url, $returnMovedURL = false)
{
	$hdr = @get_headers($url, true);
	$hdr = @array_change_key_case($hdr, 1);

	if (!$hdr) {
		return false;
	}

	$http_code = strtolower(reset($hdr));
	//	 var_dump($hdr);

	if (!empty($hdr['LOCATION'])) {
		if (str_contains('moved', $http_code)) {
			$url = $hdr['LOCATION'];

			if (isHTTP200($url)) {
				if ($returnMovedURL) {
					return $url;
				}

				return true;
			}
		}
	}

	if (str_contains('200', $http_code)) {
		if ($returnMovedURL) {
			return $url;
		}

		return true;
	}

	return false;
}

function fetchPageOGImage($html, $fetchFromUrl = false)
{
	if ($fetchFromUrl) {
		$contents = myGetOnlyHead($html);

		if (!empty($contents['contents']) && strlen($contents['contents']) > 100) {
			$url = $html;
			$html = str_get_html($contents['contents']);
		} else {
			return;
		}
	} else {
		global $url;
	}

	if (empty($ogimg)) {
		$ogimg = fetchFavicon($html, 'meta', 'property', 'twitter:image', 'content');
	}
	if (empty($ogimg)) {
		$ogimg = fetchFavicon($html, 'meta', 'property', 'twitter:image:alt', 'content');
	}
	if (empty($ogimg)) {
		$ogimg = fetchFavicon($html, 'meta', 'property', 'twitter:image:src', 'content');
	}
	if (empty($ogimg)) {
		$ogimg = fetchFavicon($html, 'meta', 'property', 'sailthru.image.full', 'content');
	}
	if (empty($ogimg)) {
		$ogimg = fetchFavicon($html, 'meta', 'property', 'og:image', 'content');
	}
	if (empty($ogimg)) {
		$ogimg = fetchFavicon($html, 'meta', 'property', 'og:image:alt', 'content');
	}
	if (empty($ogimg)) {
		$ogimg = fetchFavicon($html, 'meta', 'property', 'image', 'content');
	}
	if (empty($ogimg)) {
		$ogimg = fetchFavicon($html, 'meta', 'property', 'thumbnail', 'content');
	}
	if (empty($ogimg)) {
		$ogimg = fetchFavicon($html, 'meta', 'property', 'pinterest:media', 'content');
	}
	if (empty($ogimg)) {
		$ogimg = fetchFavicon($html, 'meta', 'itemprop', 'image', 'value');
	}

	if (empty($ogimg)) {
		return;
	}

	return applyImageCache($ogimg);
}

function fetchPageFavicons($html)
{
	global $url;

	// PRIORITY SEQUENCE
	// logo 							- because its 			//svg???
	// mask-icon 						- because its 			//svg???
	// mac fluid-icon					- it is hig res
	// win8 msapplication-TileImage		- 144px					//png
	// apple-touch-icon					- 144ox					//png
	// apple-touch-icon-precomposed		- 114ox					//png
	// apple-touch-icon-precomposed		- 57ox	(also)			//png
	// Mpclarkson\IconScraper			- plz note it fetches homepage header ie. extra HTTP request

	if (empty(parse_url($url)['host'])) {
		return;
	}

	$localpath = 'cache/' . preg_replace('/\W+/', '', parse_url($url)['host']) . '.svg';
	if (file_exists($localpath)) {
		$favico = 'http://' . $_SERVER['HTTP_HOST'] . str_replace(basename($_SERVER['PHP_SELF']), '', $_SERVER['PHP_SELF']) . $localpath;
	}

	if (empty($favico)) {
		$favico = fetchFavicon($html, 'link', 'rel', 'logo', 'href');
	}
	if (empty($favico)) {
		$favico = fetchFavicon($html, 'link', 'rel', 'mask-icon', 'href');
	}
	if (empty($favico)) {
		$favico = fetchFavicon($html, 'link', 'rel', 'fluid-icon', 'href');
	}
	if (empty($favico)) {
		$favico = fetchFavicon($html, 'link', 'rel', 'msapplication-TileImage', 'content');
	}
	if (empty($favico)) {
		$favico = fetchFavicon($html, 'meta', 'name', 'msapplication-TileImage', 'content');
	}
	if (empty($favico)) {
		$favico = fetchFavicon($html, 'link', 'rel', 'apple-touch', 'href');
	}

	// if (empty($favico)) {
	// 	require_once('vendor/autoload.php');
	// 	$scraper = new \Mpclarkson\IconScraper\Scraper();
	// 	$icons = $scraper->getIcons($url);
	// 	if (!empty($icons))
	// 		if (empty($icons['error'])) {
	// 			{$favico=array_values($icons)[0]->href;}
	// 			$GLOBALS['favico_type']='iconscrapper';
	// 			$favico = relativeToAbsoluteURL($favico,$url);
	// 			$favico = isHTTP200($favico,true);
	// 			if (!$favico) { unset($favico);unset($GLOBALS['favico_type']); }
	// 		}
	// }

	if (empty($favico)) {
		$favico = fetchFavicon($html, 'link', 'rel', 'image', 'href');
	}
	if (empty($favico)) {
		$favico = fetchFavicon($html, 'link', 'rel', 'icon', 'href');
	}
	if (empty($favico)) {
		$favico = fetchFavicon($html, 'link', 'href', 'favico', 'href');
	}

	if (empty($favico) && !empty($url)) {
		$tmp_favico = parse_url($url)['scheme'] . '://' . parse_url($url)['host'] . '/favicon.ico';
		$tmp_favico = relativeToAbsoluteURL($tmp_favico, $url);
		$tmp_favico = isHTTP200($favico, true);

		if ($tmp_favico) {
			$favico = $tmp_favico;
			$GLOBALS['favico_type'] = 'favicon.ico';
		}
	}

	// if (empty($favico)) {
	// 	{$favico='https://www.google.com/s2/favicons?domain=' . parse_url($url)['host'];}
	// }

	if (empty($favico)) {
		return;
	}

	return $favico;
}

function fetchFavicon($html, $tag_name, $attribute_name, $attribute_content, $link_content)
{
	global $url;

	$qrystring = ("{$tag_name}[{$attribute_name}*={$attribute_content}]");

	if ($html->find($qrystring)) {
		$icons = [];

		foreach ($html->find($qrystring) as $link) {
			if (!empty($link->hasAttribute($attribute_name)) && !empty($link->getAttribute($link_content))) {
				$attribute = $link->getAttribute($attribute_name);
				$href = $link->getAttribute($link_content);

				// var_dump($link->outertext);

				$size = $link->hasAttribute('sizes') ? $link->getAttribute('sizes') : '10x10';
				$size = is_string($size) ? str_replace('×', 'x', $size) : $size;
				// $size = is_string($size) ? str_replace('x', '×', $size) : $size;
				$size = !is_array($size) ? explode('x', $size) : $size;

				// var_dump($size);

				$icons[] = [
					'href' => $href,
					'size' => $size,
				];
			}
		}
		usort($icons, 'usort_helper');

		// var_dump($icons);

		$favico = $icons[0]['href'];
		$GLOBALS['favico_type'] = "{$tag_name}{$attribute_name}_{$attribute_content}";
		$favico = relativeToAbsoluteURL($favico, $url);
		$favico = isHTTP200($favico, true);
		if (!$favico) {
			return;
		}

		// if (!str_contains('.svg',$favico) && !str_contains('.ico',$favico) ) {
		// 	$localpath = 'cache/' . preg_replace('/\W+/', '', parse_url($url)['host']) . '.svg';
		// 	if (!file_exists($localpath)) {
		// 		$rest7 = 'http://api.rest7.com/v1/raster_to_vector.php?url=' . $favico . '&format=svg';
		// 		$rest7svg = myGet($rest7,99*24);
		// 		// var_dump($rest7svg);die;
		// 		$rest7svg = json_decode($rest7svg);
		// 		if (@$rest7svg->success == 1)
		// 		{
		// 			// var_dump($rest7svg);
		// 			$rest7svg = $rest7svg->file;
		// 			$localrest = file_put_contents($localpath, file_get_contents($rest7svg));
		// 		}
		// 		else{
		// 			$fail=true;
		// 		}
		// 	}
		// 	if (empty($fail)) {
		// 		$favico ='http://'.$_SERVER['HTTP_HOST'] . str_replace(basename($_SERVER['PHP_SELF']), '', $_SERVER['PHP_SELF']) . $localpath;
		// 		// var_dump($localpath . "\n\n");
		// 	}
		// }
		return $favico;
	}
}

function usort_helper($a, $b)
{
	// if ($a == $b) {
	//     return 0;
	// }
	// return ($a < $b) ? -1 : 1;

	return $b['size'][0] - $a['size'][0];
}

/**
 * dump and die.
 *
 * @param mixed $var
 */
function dd($var, string $print_or_dump = 'print')
{
	'dump' == $print_or_dump
		? var_dump($var)
		: print_r($var);

	exit;
}

/**
 * @param $date
 *
 * @return null|string
 */
function getRSSFormatDate($date)
{
	try {
		return (new DateTime($date))->format(DATE_RSS);
	} catch (Exception $e) {
		return;
	}
}

function googleFirstImage($searchString)
{
	return;
	$searchString = urlencode(trim($searchString));
	$searchURL = 'http://www.bing.com/images/search?q=' . $searchString; //."&qft=+filterui:age-lt10080&FORM=IRFLTR";
	$newhtml = str_get_html(myGet($searchURL, 72));
	$img = $newhtml->find('img', 2)->src;

	return preg_replace('/&amp;w=.+/', '', $img);
}

function myGet($url, $cacheTimeOutHrs = 'N/A', $output = 'content')
{
	global $forceTrim;
	global $removeScript;

	if (is_array($cacheTimeOutHrs)) {
		$param = $cacheTimeOutHrs;

		$cacheTimeOutHrs = $param['cacheTimeOutHrs'] ?? 'N/A';
		$output = $param['output'] ?? 'content';
		$forceTrim = $param['forceTrim'] ?? 'false';
		$removeScript = $param['removeScript'] ?? 'false';
	} else {
		$forceTrim = false;
		$removeScript = false;
	}

	// if no cache timeout given give standard timeout
	if ('N/A' == $cacheTimeOutHrs) {
		// delay greater than 1 and less than 4
		$cacheTimeOutHrs = 1 + rand(0, 100) / 100 * 3;
	}

	$cache_filepath = myGetFilePath($url);

	if (isset($_GET['overrideCache'])) {
		$CacheContents = actualGet($url, $cache_filepath, 'update');
	}

	if (file_exists($cache_filepath)) {
		if (0 != $cacheTimeOutHrs && time() - filemtime($cache_filepath) > $cacheTimeOutHrs * 3600) {
			// too old , re-fetch
			$CacheContents = actualGet($url, $cache_filepath, 'update');
			// echo 'updated';
		} else {
			// cache is still fresh
			$CacheContents = file_get_contents($cache_filepath);
			$sts = 'fresh';
			$_SESSION['myGet'] = $sts;
			// echo 'present';
		}
	} else {
		// no cache, create one
		$CacheContents = actualGet($url, $cache_filepath, 'create');
		// echo 'created';
	}
	if ('filepath' == $output) {
		return $cache_filepath;
	}

	if (empty($CacheContents) || 0 == strlen(trim($CacheContents))) {
		throw new \Exception('Failure in myGet');
	}

	return $CacheContents;
}

function actualGet($url, $cache_filepath, $sts)
{
	global $forceTrim;
	global $removeScript;

	$referer = 'https://www.google.com/search?q=';
	$referer .= str_replace(['_', '-'], '+', basename($url));

	$opts = [
		'http' => [
			'header' => [
				"Referer: {$referer}",
				'Accept-language: en',
			],
		],
	];

	$context = stream_context_create($opts);

	$CacheContents = file_get_contents($url, false, $context);
	if ($forceTrim) {
		if ($removeScript) {
			$CacheContents = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $CacheContents);
			$CacheContents = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $CacheContents);
			$CacheContents = preg_replace('/<script.+/is', '', $CacheContents);
			$CacheContents = preg_replace('/<style.+/is', '', $CacheContents);
		}
		$CacheContents = preg_replace('/[\s\r]+/', ' ', $CacheContents);
		$CacheContents = tidy_repair_string($CacheContents);
	}
	$CacheContents = trim($CacheContents);

	file_put_contents($cache_filepath, $CacheContents);

	logGets($url, $sts);
	$_SESSION['myGet'] = $sts;

	return $CacheContents;
}

function myGetFilePath($url)
{
	$dirName = 'cache';

	if (!$url || !createDirIfNotExists($dirName)) {
		return false;
	}

	$cacheName = preg_replace('/\W+/', '', $url);

	return "{$dirName}/{$cacheName}";
}

function myGetOnlyHead($url)
{
	$file_path = myGetFilePath($url);
	if (file_exists($file_path)) {
		return [
			'contents' => file_get_contents($file_path),
		];
	}

	$url = isHTTP200($url, true);
	@$fp = fopen($url, 'r');
	if (!$fp) {
		return [
			'error' => 'ERROR: invalid URL ' . $url,
		];
	}
	$contents = '';
	$i = 0;

	while (false == strpos($contents, '<body')) {
		$buffer = trim(fgets($fp, 512));
		$contents .= $buffer;

		if ($i > 0 && empty($contents)) {
			return [
				'error' => 'ERROR: falied to myGetOnlyHead() ' . $url,
			];
		}

		if ($i > 400) {
			break;

			return [
				'error' => 'ERROR: couldnt download full head ' . $url,
			];
		}

		++$i;
	}

	$contents = preg_replace('/.+<head>/is', '<head>', $contents);
	$contents = preg_replace('/.+<head /is', '<head ', $contents);
	$contents = preg_replace('/\s*\/>/', '>', $contents);
	$contents = preg_replace('/\s+/s', ' ', $contents);
	$contents = preg_replace('/\s+/s', ' ', trim($contents));
	$contents = preg_replace('#<!--(.*?)(.*?)-->#is', '', $contents);
	$contents = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $contents);
	$contents = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $contents);
	$contents = preg_replace('/<script.+/is', '', $contents);
	$contents = preg_replace('/<style.+/is', '', $contents);
	$contents = str_replace('>', ">\n", $contents);

	file_put_contents($file_path, $contents);

	return [
		'contents' => $contents,
	];
}

function myGetTime($url)
{
	if (!$url) {
		return false;
	}

	$cache_filepath = myGetFilePath($url);

	return gmdate(DATE_RSS, filemtime($cache_filepath));
}

function createDirIfNotExists($dirName)
{
	// if directory named $dirName does not exsist, create it.
	if (!is_string($dirName)) {
		return false;
	}

	$mode = 0777;
	is_dir($dirName) || mkdir($dirName, $mode, true) || exit;

	return true;
}

function logGets($url, $getType)
{
	// createDirIfNotExists('logs');
	$logPath = 'GetsLog.txt';
	$mode = (!file_exists($logPath)) ? 'w' : 'a';
	$logfile = fopen($logPath, $mode);

	$data1 = (new DateTime())->format(DATE_RSS);
	$data20 = basename($_SERVER['SCRIPT_FILENAME']);
	$data25 = $_SERVER['QUERY_STRING'] ?? '';
	$data3 = $getType;
	$data4 = $url;
	$data5 = getRealUserIp();

	$mask = '%25.25s | %20.20s | %-40.40s | %-6.6s | %-100.100s | %-15.15s';
	$data = sprintf($mask, $data1, $data20, $data25, $data3, $data4, $data5);

	fwrite($logfile, "\n" . $data);
	fclose($logfile);
}

function logHeaders()
{
	// createDirIfNotExists('logs');
	$logPath = 'RunHeadersLog.txt';
	$mode = (!file_exists($logPath)) ? 'w' : 'a';
	$logfile = fopen($logPath, $mode);

	$data1 = (new DateTime())->format(DATE_RSS);
	$data2 = basename($_SERVER['SCRIPT_FILENAME']);
	$data3 = getRealUserIp();
	$data4 = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$data5 = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;

	$mask = '%25.25s | %-20.20s | %-15.15s | %-100.100s | %-50.50s';
	$data = sprintf($mask, $data1, $data2, $data3, $data4, $data5);

	// $data = trim($data);
	// $data = str_replace("|", "\t\t", $data);

	fwrite($logfile, "\n" . $data);
	fclose($logfile);
}

function rotateLog(
	string $filename,
	int $rotate_size = 500000,
	int $files_to_keep = 10
) {
	if (file_exists($filename)) {
		if (filesize($filename) > $rotate_size) {
			if (file_exists($filename . '.' . $files_to_keep)) {
				@unlink($filename . '.' . $files_to_keep);
			}
			for ($i = $files_to_keep; $i > 0; --$i) {
				if (file_exists($filename . '.' . $i)) {
					$next = $i + 1;
					@rename($filename . '.' . $i, $filename . '.' . $next);
				}
			}
			rename($filename, $filename . '.1');
		}
	}
}

function throwErr(string $message, int $code = 500): void
{
	header('Content-Type: text; charset=utf-8');

	switch ($code) {
		case '400':
			header('HTTP/1.1 400 Bad Request');

			break;

		default:
			$code = 500;
			header('HTTP/1.1 500 Internal Server Error');

			break;
	}

	throw new \Exception($message);
}

function getImageFromDataSrcSet($dataSrcSet)
{
	$dataSrcSet = explode(',', $dataSrcSet);
	$dataSrcSet = trim($dataSrcSet[count($dataSrcSet) - 1]);

	return preg_replace('/ .+/', '', $dataSrcSet);
}

/**
 * @param string src
 * @param mixed $src
 */
function applyImageCache($src): string
{
	return !$src
		? ''
		: (str_contains('?', $src)
			? localImageCache($src)
			: 'https://agvhvzedvo.cloudimg.io/v7/' . $src . '?width=800&org_if_sml=1&force_format=webp');
}

function localImageCache($src): string
{
	@$img = myGet($src, 'N/A', 'filepath') ?: null;

	if ($img) {
		$img = 'http://rivervalleyhay.com/assets/fc/' . $img;
		$img = applyImageCache($img);
	}

	return $img;
}

function hashify($excerpt)
{
	$excerpt = str_replace(' KSE-100 ', ' @KSE_100 ', $excerpt);

	$excerpt = str_replace(' Pakistan Muslim League – Nawaz (PML-N) ', ' #PML-N ', $excerpt);
	$excerpt = str_replace(' Pakistan Muslim League – Nawaz ', ' #PML-N ', $excerpt);
	$excerpt = str_replace(' Pakistan Muslim League-Nawaz (PML-N) ', ' #PML-N ', $excerpt);
	$excerpt = str_replace(' Pakistan Muslim League-Nawaz ', ' #PML-N ', $excerpt);
	$excerpt = str_replace(' (PML-N) ', ' #PML-N ', $excerpt);
	$excerpt = str_replace(' PML-N ', ' #PML-N ', $excerpt);

	$excerpt = str_replace(' PTI ', ' #PTI ', $excerpt);

	$excerpt = str_replace(' Jamaat-e-Islami (JI) ', ' #JI ', $excerpt);
	$excerpt = str_replace(' Jamaat-e-Islami ', ' #JI ', $excerpt);
	$excerpt = str_replace(' JI ', ' #JI ', $excerpt);

	$excerpt = str_replace(' (PSX) ', ' #$1 ', $excerpt);

	return str_replace(' (PSL) ', ' #$1 ', $excerpt);
}

function generateMusicDescription($meta)
{
	$description = '<pre>';

	if (!empty($meta['genre'])) {
		$description .= "\n<b>{$meta['genre']}</b>";
	}
	if (!empty($meta['date'])) {
		$description .= "\n<b>{$meta['date']}</b>";
	}
	$description .= "\n";
	$description .= generateMusicExternalLinks("{$meta['song']} - {$meta['artist']}");

	$description .= "\n\n";
	$description .= generateMusicExternalLinks($meta['song']);

	$description .= "\n by ";
	$description .= generateMusicExternalLinks($meta['artist']);

	if (!empty($meta['album'])) {
		$description .= "\n from ";
		$description .= generateMusicExternalLinks($meta['album']);
	}

	if (!empty($meta['preview'])) {
		$description .= "\n" . '<audio controls><source src="{%preview}" type="audio/ogg"></audio>';
		$description = str_replace('{%preview}', $meta['preview'], $description);
	}

	$description .= "\n" . '<hr></pre>';

	$iframe = '<iframe id="ytplayer" type="text/html" src="https://www.youtube.com/embed?listType=search&list={%song-e}+by+{%artist-e}&llist={%song-e}+by+{%artist-e}"></iframe>';
	$iframe = str_replace('{%song-e}', urlencode($meta['song']), $iframe);
	$iframe = str_replace('{%artist-e}', urlencode($meta['artist']), $iframe);

	$description .= $iframe;

	return $description;
}

function generateMusicExternalLinks($item)
{
	$itemEncoded = urlencode($item);

	$content = '<b>{%item}</b>: ';

	$content .= '<a href="http://www.google.com/search?tbm=vid&q={%itemEncoded}">G</a>';
	$content .= ' - ';
	$content .= '<a href="https://torrentz2.eu/search?f={%itemEncoded}">tz.eu</a>';

	$content = str_replace('{%item}', $item, $content);

	return str_replace('{%itemEncoded}', $itemEncoded, $content);
}

function parseOutput($feedItems, $param)
{
	if (!is_array($param)) {
		$param['feedTitle'] = $param;
	}
	$feedTitle = $param['feedTitle'];

	if (empty($param['feedDescription'])) {
		if (empty($param['description'])) {
			$param['feedDescription'] = $feedTitle;
		} else {
			$param['feedDescription'] = $param['description'];
		}
	}

	$out = null;
	$out .= '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	$out .= '<rss xmlns:media="http://search.yahoo.com/mrss/" xmlns:webfeeds="http://webfeeds.org/rss/1.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" version="2.0">' . "\n";
	$out .= '<channel>' . "\n";
	$out .= '<title>' . $feedTitle . '</title>' . "\n";
	$out .= '<description><![CDATA[' . $param['feedDescription'] . ']]></description>' . "\n";

	if (!empty($param['datemodified'])) {
		header('Last-Modified: ' . $param['datemodified']);
		$out .= "<lastBuildDate>{$param['datemodified']}</lastBuildDate>" . "\n";
		$out .= '<pubDate>' . gmdate(DATE_RSS) . '</pubDate>' . "\n";
	}

	if (!empty($param['favico'])) {
		if ($_SERVER['HTTP_HOST'] != parse_url($param['favico'])['host']) {
			$param['favico'] = applyImageCache($param['favico']);
		}

		$out .= "<image><url>{$param['favico']}</url></image>" . "\n";
		$out .= "<webfeeds:icon>{$param['favico']}</webfeeds:icon>" . "\n";
		// $out .= "<logo>http://googleweblight.com/?lite_image_url={$param['favico']}</logo>" . "\n";
		// $out .= "<icon>http://googleweblight.com/?lite_image_url={$param['favico']}</icon>" . "\n";
	}

	if (!empty($param['ttl'])) {
		$out .= "<ttl>{$param['ttl']}</ttl>";
	} elseif (!empty($param['datemodified']) && empty($param['ttl'])) {
		$out .= '<ttl>60</ttl>';
	}

	$out .= "\n";

	foreach ($feedItems as $item) {
		$out .= "\n";
		$out .= '<item>';
		$out .= "\n";
		$out .= '<title><![CDATA[' . $item['title'] . ']]></title>';

		if (array_key_exists('url', $item)) {
			$out .= "\n";
			$out .= '<link>' . $item['url'] . '</link>';
		}

		if (array_key_exists('guid', $item)) {
			$out .= "\n";
			$out .= '<guid>' . $item['guid'] . '</guid>';
		} elseif (array_key_exists('url', $item)) {
			$out .= "\n";
			$out .= '<guid isPermaLink="true">' . $item['url'] . '</guid>';
		}

		// determine img/thumb
		$img = null;
		$thumb = null;
		if (array_key_exists('thumb', $item)) {
			$thumb = $item['thumb'];
		}
		if (array_key_exists('img', $item)) {
			$img = $item['img'];
			$thumb = ($thumb ?: $img);
		} else {
			$img = ($thumb ?: false);
		}

		if ($img) {
			if (false !== strpos($thumb, 'http://googleweblight.com/?lite_image_url=')) {
				$thumb = str_replace('http://googleweblight.com/?lite_image_url=', '', $thumb);
				$thumb = urldecode($thumb);
			}
		}

		if (array_key_exists('description', $item)) {
			$out .= "\n";
			$out .= '<description><![CDATA[';
			if (!isset($item['DisableThumbsInDesc']) && strlen($img) > 0) {
				$imgcaption = empty($item['imgcaption']) ? null : $item['imgcaption'];
				$out .= "<p><img src=\"{$img}\" title=\"{$imgcaption}\"/></p>";
				// $out .= "<p><img class=\"webfeedsFeaturedVisual\" src=\"{$img}\" /></p>";
			}

			$out .= empty($item['imgcaption']) ? null : "<small><em>{$item['imgcaption']}</em></small><br><br>";
			$out .= $item['description'];
			$out .= ']]></description>';
		}

		if ($thumb) {
			$out .= "\n";
			// $out .= "<webfeeds:cover image=\"{$thumb}\" />";
			// $out .= "<image><url>$thumb</url></image>";
			// $out .= '<media:content xmlns:media="http://search.yahoo.com/mrss/" url="' . $thumb . '" media="image" isDefault="true" />' . "\n";
			// $out .= "<og:image>$thumb</og:image>\n";
			$out .= "<enclosure url=\"{$thumb}\" length=\"1\" type=\"image/jpeg\"/>";
		}

		if (array_key_exists('date', $item)) {
			$out .= "\n";
			$out .= '<pubDate><![CDATA[' . $item['date'] . ']]></pubDate>';
		}

		if (array_key_exists('author', $item)) {
			$out .= "\n";
			$out .= '<author><![CDATA[' . $item['author'] . ']]></author>';
		}

		if (array_key_exists('source', $item)) {
			$out .= "\n";
			$out .= '<source url="';
			$out .= false == strpos($item['source'], 'http') ? 'http://' . $item['source'] : $item['source'];
			$out .= '"><![CDATA[' . $item['source'] . ']]></source>';
		}

		$out .= "\n";
		$out .= '</item>';
		$out .= "\n";
	}

	$out .= "\n";
	$out .= '</channel></rss>';

	file_put_contents(rssCache_Name(), $out);

	echo $out;
}

function rssCache_Name()
{
	$cachename = basename($_SERVER['SCRIPT_FILENAME']) . ($_SERVER['QUERY_STRING'] ?? '');
	$cachename = myGetFilePath($cachename) . '.xml';

	return $cachename;
}

function rssCache_Serve()
{
	if (
		'fresh' == @$_SESSION['myGet']
		&& empty($_SESSION['overrideRSScache'])
		&& !isset($_GET['overrideCache'])
		&& !isset($_GET['overrideRSSCache'])
	) {
		if (file_exists(rssCache_Name())) {
			echo file_get_contents(rssCache_Name());
			$_SESSION['rssCache'] = true;

			exit;
		}
	}
}
