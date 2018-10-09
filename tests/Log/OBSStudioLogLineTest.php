<?php

namespace RytoEX\OBS\LogAnalyzer\Tests\Log;

final class OBSStudioLogLineTest extends \PHPUnit\Framework\TestCase
{
	protected $testLine01;
	protected $testLine02;
	protected $testLine03;
	protected $testLine04;
	protected $testLine05;

	public function setUp()
	{
		$this->testLine01 = '22:09:33.320: Available Video Adapters: ';
		$this->testLine02 = '22:09:33.321: 	Adapter 1: NVIDIA GeForce GTX 760';
		$this->testLine03 = '22:09:33.321: 	  Dedicated VRAM: 2115698688';
		$this->testLine04 = '22:09:34.013:   Loaded Modules:';
		$this->testLine05 = '22:09:34.013:     win-wasapi.dll';
	}

	/**
	 * @dataProvider timestampProvider
	 * @covers OBSStudioLogLine::find_timestamp
	 */
	public function testFindTimestamp($logVarName, $expectedValue)
	{
		$tmpLogLine = new \RytoEX\OBS\LogAnalyzer\Log\OBSStudioLogLine($this->$logVarName);
		$this->assertSame($tmpLogLine->timestamp,
			$expectedValue);
	}

	public function timestampProvider()
	{
		return [
			['testLine01', '22:09:33.320'],
			['testLine02', '22:09:33.321'],
			['testLine03', '22:09:33.321'],
			['testLine04', '22:09:34.013'],
			['testLine05', '22:09:34.013']
		];
	}

	/**
	 * @dataProvider itemStringProvider
	 * @covers OBSStudioLogLine::find_item_string
	 */
	public function testFindItemString($logVarName, $expectedValue)
	{
		$tmpLogLine = new \RytoEX\OBS\LogAnalyzer\Log\OBSStudioLogLine($this->$logVarName);
		$this->assertSame($tmpLogLine->item_string,
			$expectedValue);
	}

	public function itemStringProvider()
	{
		return [
			['testLine01', 'Available Video Adapters:'],
			['testLine02', '	Adapter 1: NVIDIA GeForce GTX 760'],
			['testLine03', '	  Dedicated VRAM: 2115698688'],
			['testLine04', '  Loaded Modules:'],
			['testLine05', '    win-wasapi.dll']
		];
	}

	/**
	 * @dataProvider indentLevelProvider
	 * @covers OBSStudioLogLine::find_indent_level
	 */
	public function testFindIndentLevel($logVarName, $expectedValue)
	{
		$tmpProfilerItem = new \RytoEX\OBS\LogAnalyzer\Log\OBSStudioLogLine($this->$logVarName);
		$this->assertSame($tmpProfilerItem->indent_level,
			$expectedValue);
	}

	public function indentLevelProvider()
	{
		return [
			['testLine01', 0],
			['testLine02', 1],
			['testLine03', 2],
			['testLine04', 1],
			['testLine05', 2]
		];
	}
}
