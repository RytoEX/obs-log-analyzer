<?php

namespace RytoEX\OBS\LogAnalyzer\Tests\Utils;

class UrlHandlerTest extends \PHPUnit\Framework\TestCase
{
	/*
	 * @covers UrlHandler::__construct
	 * @covers UrlHandler::validate
	 * @covers UrlHandler::sanitize
	 * @covers UrlHandler::process
	 * @covers UrlHandler::build_html_link
	 * @covers UrlHandler::build_query_string
	 */
	public function testOBSProject_Forum_URL()
	{
		// setup
		$url = 'https://obsproject.com/forum/attachments/2018-02-07-15-38-20-txt.34065/';
		$raw_url = 'https://obsproject.com/forum/attachments/2018-02-07-15-38-20-txt.34065/';
		$url_link = "<a href=\"$raw_url\">$raw_url</a>";
		$query_string = '?url=' . rawurlencode($raw_url);

		// get handler
		$urlHandler = new \RytoEX\OBS\LogAnalyzer\Utils\UrlHandler($url);

		// tests
		$this->assertSame($url, $urlHandler->original_url);
		$this->assertSame($url, $urlHandler->sanitized_url);
		$this->assertTrue($urlHandler->validate($urlHandler->original_url));
		$this->assertTrue($urlHandler->validate($urlHandler->sanitized_url));
		$this->assertSame($raw_url, $urlHandler->process($url));
		$this->assertSame($url_link, $urlHandler->build_html_link($raw_url));
		$this->assertSame($query_string, $urlHandler->build_query_string($raw_url));
	}

	/*
	 * @covers UrlHandler::__construct
	 * @covers UrlHandler::validate
	 * @covers UrlHandler::sanitize
	 * @covers UrlHandler::process
	 * @covers UrlHandler::build_html_link
	 * @covers UrlHandler::build_query_string
	 */
	public function testGitHub_URL()
	{
		// setup
		$url = 'https://gist.github.com/anonymous/2bb887248b1b0b1a83698a0d9376f183';
		$raw_url = 'https://gist.githubusercontent.com/anonymous/2bb887248b1b0b1a83698a0d9376f183/raw/e0fad775b4c413cca6941612170a520733eab536/2018-03-12%2013-04-05.txt';
		$url_link = "<a href=\"$raw_url\">$raw_url</a>";
		$query_string = '?url=' . rawurlencode($raw_url);

		// get handler
		$urlHandler = new \RytoEX\OBS\LogAnalyzer\Utils\UrlHandler($url);

		// tests
		$this->assertSame($url, $urlHandler->original_url);
		$this->assertSame($url, $urlHandler->sanitized_url);
		$this->assertTrue($urlHandler->validate($urlHandler->original_url));
		$this->assertTrue($urlHandler->validate($urlHandler->sanitized_url));
		$this->assertSame($url_link, $urlHandler->build_html_link($raw_url));
		$this->assertSame($query_string, $urlHandler->build_query_string($raw_url));
	}

	/*
	 * @covers UrlHandler::__construct
	 * @covers UrlHandler::validate
	 * @covers UrlHandler::sanitize
	 * @covers UrlHandler::process
	 * @covers UrlHandler::build_html_link
	 * @covers UrlHandler::build_query_string
	 */
	public function testPastebin_URL()
	{
		// setup
		$url = 'https://pastebin.com/7jwP1Nim';
		$raw_url = 'https://pastebin.com/raw/7jwP1Nim';
		$url_link = "<a href=\"$raw_url\">$raw_url</a>";
		$query_string = '?url=' . rawurlencode($raw_url);

		// get handler
		$urlHandler = new \RytoEX\OBS\LogAnalyzer\Utils\UrlHandler($url);

		// tests
		$this->assertSame($url, $urlHandler->original_url);
		$this->assertSame($url, $urlHandler->sanitized_url);
		$this->assertTrue($urlHandler->validate($urlHandler->original_url));
		$this->assertTrue($urlHandler->validate($urlHandler->sanitized_url));
		$this->assertSame($url_link, $urlHandler->build_html_link($raw_url));
		$this->assertSame($query_string, $urlHandler->build_query_string($raw_url));
	}

	/*
	 * @covers UrlHandler::__construct
	 * @covers UrlHandler::validate
	 * @covers UrlHandler::sanitize
	 * @covers UrlHandler::process
	 * @covers UrlHandler::build_html_link
	 * @covers UrlHandler::build_query_string
	 */
	public function testHastebin_URL()
	{
		// setup
		$url = 'https://hastebin.com/uqusetihem';
		$raw_url = 'https://hastebin.com/raw/uqusetihem';
		$url_link = "<a href=\"$raw_url\">$raw_url</a>";
		$query_string = '?url=' . rawurlencode($raw_url);

		// get handler
		$urlHandler = new \RytoEX\OBS\LogAnalyzer\Utils\UrlHandler($url);

		// tests
		$this->assertSame($url, $urlHandler->original_url);
		$this->assertSame($url, $urlHandler->sanitized_url);
		$this->assertTrue($urlHandler->validate($urlHandler->original_url));
		$this->assertTrue($urlHandler->validate($urlHandler->sanitized_url));
		$this->assertTrue($urlHandler->is_host_hastebin());
		$this->assertSame($url_link, $urlHandler->build_html_link($raw_url));
		$this->assertSame($query_string, $urlHandler->build_query_string($raw_url));
	}

	/*
	 * @covers UrlHandler::__construct
	 * @covers UrlHandler::validate
	 * @covers UrlHandler::sanitize
	 * @covers UrlHandler::process
	 * @covers UrlHandler::build_html_link
	 * @covers UrlHandler::build_query_string
	 */
	public function testDropbox_URL()
	{
		// setup
		$url = 'https://www.dropbox.com/sh/tfb48t6wqx76xjb/AADWK47LDHthCGYhhlFbxBwWa/2017-08-27%2018-13-22.txt?dl=0';
		$raw_url = 'https://dl.dropbox.com/sh/tfb48t6wqx76xjb/AADWK47LDHthCGYhhlFbxBwWa/2017-08-27%2018-13-22.txt';
		$url_link = "<a href=\"$raw_url\">$raw_url</a>";
		$query_string = '?url=' . rawurlencode($raw_url);

		// get handler
		$urlHandler = new \RytoEX\OBS\LogAnalyzer\Utils\UrlHandler($url);

		// tests
		$this->assertSame($url, $urlHandler->original_url);
		$this->assertSame($url, $urlHandler->sanitized_url);
		$this->assertTrue($urlHandler->validate($urlHandler->original_url));
		$this->assertTrue($urlHandler->validate($urlHandler->sanitized_url));
		$this->assertSame($url_link, $urlHandler->build_html_link($raw_url));
		$this->assertSame($query_string, $urlHandler->build_query_string($raw_url));
	}

	/*
	 * @covers UrlHandler::__construct
	 * @covers UrlHandler::validate
	 */
	public function testBad_URLs()
	{
		// setup
		$url = 'not a url';

		// get handler
		$urlHandler = new \RytoEX\OBS\LogAnalyzer\Utils\UrlHandler($url);

		// tests
		$this->assertSame($url, $urlHandler->original_url);
		$this->assertFalse($urlHandler->is_valid);
		$this->assertFalse($urlHandler->is_sanitized);
	}
}
