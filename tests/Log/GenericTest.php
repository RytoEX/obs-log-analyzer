<?php

namespace RytoEX\OBS\LogAnalyzer\Tests\Log;

final class GenericTest extends \PHPUnit\Framework\TestCase
{
	protected $sampleLogFile;
	protected $obsLog;

	public function setUp()
	{
		$this->sampleLogFile = './tests/LogSamples/basic_windows_log_(2017-04-12 02-09-41).txt';
		$this->obsLog = new \RytoEX\OBS\LogAnalyzer\Log\Generic($this->sampleLogFile);
	}

	public function testCanBeCreatedFromValidLogFile()
	{
		$this->assertInstanceOf(
			\RytoEX\OBS\LogAnalyzer\Log\Generic::class,
			$this->obsLog
		);
	}

	public function testDetect_OBS_Studio_Log()
	{
		$this->assertTrue($this->obsLog->process());
		$this->assertTrue($this->obsLog->is_log_from_studio());
	}

	public function testMake_OBS_Studio_Log_Object()
	{
		$this->assertTrue($this->obsLog->process());
		$this->assertInstanceOf(
			\RytoEX\OBS\LogAnalyzer\Log\OBSStudioLog::class,
			$this->obsLog->make_log_object()
		);
	}
}
