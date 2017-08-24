<?php
/**
 * @author    Gonçalo Esteves
 * @version   1.1
 * @copyright Copyright (c) 2017, Gonçalo Esteves
 */
 
include_once('http://raw.githubusercontent.com/sunra/php-simple-html-dom-parser/1.5.0/Src/Sunra/PhpSimple/simplehtmldom_1_5/simple_html_dom.php');

//======================================================================
// Changelog
//======================================================================
//	1.0 / 23-08-2017
//	- creation of script
//
//  1.1 / 24-08-2017
//	- added folder password
//	- added isnullorempty function
//	- optimized code

//======================================================================
// Configs
//======================================================================

// minhateca username & password
$username = '';
$password = '';

// cookie format: ChomikSession=; __RequestVerificationToken_Lw__=;
$cookie = '';

// use cookie or credencials
$use_cookie = false;
$cookie = $use_cookie == true ? $cookie : get_cookie_access($username, $password);

// minhateca url folder
$folder_url = '';
$folder_password = '';

//======================================================================
// 
//======================================================================

get_links_from_folder($folder_url);
	
//======================================================================
// Functions
//======================================================================

/**
*	function to get links from url folder
*/
function get_links_from_folder($url) {
	global $folder_url, $folder_password, $cookie;
	$result_cookie = null;
	
	$dom = new simple_html_dom(null);
	$contents = file_get_contents_curl($url, null);
	$dom->load($contents, true);

	preg_match('/<input name=\"__RequestVerificationToken\" type=\"hidden\" value=\"(.*)\" \/>/U', $dom, $match);
	$token = count($match) > 1 ? $match[1] : '';
	
	if ($dom->getElementById('LoginToFolder') != null) {
		$mChomikName = $dom->getElementById('ChomikName')->getAttribute('value');
		$mFolderId = $dom->getElementById('FolderId')->getAttribute('value');

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://minhateca.com.br/action/Files/LoginToFolder');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'ChomikName=' . $mChomikName . '&FolderId=' . $mFolderId . '&Password=' . $folder_password . '&Remember=true');
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36');
		$result = curl_exec($ch);
		list($header, $body) = explode("\r\n\r\n", $result, 2);
		curl_close($ch);
		
		// get cookies from saved password
		preg_match_all('/\nSet-Cookie: (.*)(;|\r\n)/Ui', $header, $ms);
		$result_cookie = implode('; ', $ms[1]);
		
		$dom = new simple_html_dom(null);
		$dom->load(json_decode($body)->{'Data'}, true);
	}
	
	$list = $dom->getElementById('listView');
		
	if ($list != null) {
		$list_page = $list->find('div[class=listView_paginator]',0);
		if ($list_page != null && count($list_page) > 0) {
			$list_page_count = $list_page->find('ul',0) != null ? count($list_page->find('ul',0)->find('li')) : 1;
			for ($i = 1; $i <= $list_page_count; $i++) {
				if ($i==1) {
					foreach($list->find('h3') as $list_h3) {
						// show link to the file
						echo "http://minhateca.com.br" . $list_h3->find('a',0)->getAttribute('href') . "<br>";
						// show direct link for download (need to have an account)
						//echo get_direct_download_link("http://minhateca.com.br" . $list_h3->find('a',0)->getAttribute('href'), $token) . "<br>";
					}	
				}
				else {
					$dom = new simple_html_dom(null);
					$contents = file_get_contents_curl($url.',' .$i, $result_cookie);
					$dom->load($contents, true);
					
					$list = $dom->getElementById('listView');
					foreach($list->find('h3') as $list_h3) {
						// show link to the file
						echo "http://minhateca.com.br" . $list_h3->find('a',0)->getAttribute('href') . "<br>";
						// show direct link for download (need to have an account)
						//echo get_direct_download_link("http://minhateca.com.br" . $list_h3->find('a',0)->getAttribute('href'), $token) . "<br>";
					}
				}
			}
		}
		else {
			foreach($list->find('h3') as $list_h3) {
				// show link to the file
				echo "http://minhateca.com.br" . $list_h3->find('a',0)->getAttribute('href') . "<br>";
				// show direct link for download (need to have an account)
				//echo get_direct_download_link("http://minhateca.com.br" . $list_h3->find('a',0)->getAttribute('href'), $token) . "<br>";				
			}
		}
	}
}

/**
*	function to get cookies from login (username & password)
*/
function get_cookie_access($username, $password) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'http://minhateca.com.br/action/login/login');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36');
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
	curl_setopt($ch, CURLOPT_POSTFIELDS, 'Login='.urlencode($username).'&Password='.urlencode($password).'');
	$result = curl_exec($ch);
	curl_close($ch);

	preg_match_all('/\nSet-Cookie: (.*)(;|\r\n)/Ui', $result, $ms);
	$result_cookie = implode('; ', $ms[1]);
		
	return $result_cookie;
}

/**
*	function to get url content
*/
function file_get_contents_curl($url, $cookie) {
	$ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	if (!IsNullOrEmptyString($cookie))
		curl_setopt($ch, CURLOPT_COOKIE, $cookie);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36');
    $result = curl_exec($ch);
    curl_close($ch);
	
    return $result;
}

/**
*	function to get direct link download from url
*/
function get_direct_download_link($url) {
	global $cookie;

	preg_match('/__RequestVerificationToken_Lw__=(.*);/U', $cookie, $matches);
	$token = $matches[1];
	
	preg_match("/^(.*),(\d+)(.*)?$/i",$url,$tmp);

	$ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://minhateca.com.br/action/License/Download');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
	curl_setopt($ch, CURLOPT_COOKIE, $cookie);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, 'fileId=' . $tmp[2] . '&__RequestVerificationToken=' . urlencode($token));
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36');
    $page = curl_exec($ch);
    curl_close($ch);
	
	$obj = json_decode($page);

	try {
		return $obj->{'redirectUrl'};
	} catch (Exception $e) {
		return $obj->{'Content'};
	}
}

/**
*	IsNullOrEmptyString function like C#
*/
function IsNullOrEmptyString($var) {
  return (!isset($var) || trim($var)==='');
}

?>