<?php
/**
 * @author    Gonçalo Esteves
 * @version   1.0
 * @copyright Copyright (c) 2017, Gonçalo Esteves
 */
 
include_once('http://raw.githubusercontent.com/sunra/php-simple-html-dom-parser/1.5.0/Src/Sunra/PhpSimple/simplehtmldom_1_5/simple_html_dom.php');

//======================================================================
// Changelog
//======================================================================
//	1.0 / 23/08/2017
//	- creation of script


//======================================================================
// Configs
//======================================================================

// minhateca username & password
$username = '';
$password = '';
$cookie = 'ChomikSession=; __RequestVerificationToken_Lw__=;';

// use cookie or credencials
$use_cookie = false;

$cookie = $use_cookie == true ? $cookie : get_cookie_access($username, $password);

// minhateca url folder
$folder_url = "";


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
	global $folder_url, $cookie;
	
	$dom = new simple_html_dom(null);
	$contents = file_get_contents_curl($url);
	$dom->load($contents, true);
	
	preg_match('/<input name=\"__RequestVerificationToken\" type=\"hidden\" value=\"(.*)\" \/>/U', $dom, $match);
	$token = $match[1];
	
	$list = $dom->getElementById('listView');
	$list_page = $list->find('div[class=listView_paginator]',0);
	$folders = $dom->getElementById('foldersList');	

	if ($list != null) {
		if ($list_page != null) {
			foreach($list_page->find('li') as $list_page_li) {
				if ($list_page_li->getAttribute('class')=='current') {
					foreach($list->find('h3') as $list_h3) {
						foreach($list_h3->find('a') as $list_a) {  
							// show link to the file
							echo "http://minhateca.com.br" . $list_a->getAttribute('href') . "<br>";
							// show direct link for download
							//echo get_direct_download_link("http://minhateca.com.br" . $list_a->getAttribute('href'), $token) . "<br>";
						}
					}
					if ($list_page_li->innertext < count($list_page->find('li'))) {
						get_links_from_folder($folder_url . "," . ($list_page_li->innertext+1));	
					}
				}
			}
		}
		else {
			foreach($list->find('h3') as $list_h3) {
				foreach($list_h3->find('a') as $list_a) {  
					// show link to the file
					echo "http://minhateca.com.br" . $list_a->getAttribute('href') . "<br>";
					// show direct link for download
					//echo get_direct_download_link("http://minhateca.com.br" . $list_a->getAttribute('href'), $token) . "<br>";
				}
			}
		}
	}
	if ($folders != null ) {
		foreach($folders->find('a') as $folders_a) { 
			get_links_from_folder("http://minhateca.com.br" . $folders_a->getAttribute('href'),"http://minhateca.com.br" . $folders_a->getAttribute('href'));
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
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36');
	curl_setopt($ch, CURLOPT_POST, true);
	$data = array('Login' => $username, 'Password' => $password);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	$result = curl_exec($ch);
	
	preg_match_all('/^Set-Cookie:\s*([^\r\n]*)/mi', $result, $ms);
	$cookies = array();
	foreach ($ms[1] as $m) {
		list($name, $value) = explode('=', $m, 2);
		$cookies[$name] = $value;
	}
	
	print_r($cookies);
}

/**
*	function to get url content
*/
function file_get_contents_curl($url) {
	global $cookie;
	
	$ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_COOKIE, $cookie);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36');
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

/**
*	function to get direct link download from url
*/
function get_direct_download_link($url, $token) {
	global $cookie;
	
	if (strpos($atoken, '__RequestVerificationToken_Lw__') !== false) {
		preg_match('/__RequestVerificationToken_Lw__=(.*);/U', $cookie, $matches);
		$token = $matches[1];
	}

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
	$info = curl_getinfo($ch);
    curl_close($ch);
	
	$obj = json_decode($page);
	
	try {
		return $obj->{'redirectUrl'};
	} catch (Exception $e) {
		print $obj->{'Content'};
	}
}

?>