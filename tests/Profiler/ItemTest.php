<?php

namespace RytoEX\OBS\LogAnalyzer\Tests\Profiler;

final class ItemTest extends \PHPUnit\Framework\TestCase
{
	protected $testLine01;
	protected $testLine02;
	protected $testLine03;
	protected $testLine04;
	protected $testLine05;
	protected $testLine06;
	protected $testLine07;
	protected $testLine08;
	protected $testLine09;
	protected $testLine10;
	protected $testLine11;
	protected $testLine12;

	public function setUp()
	{
		$this->testLine01 = '02:10:11.623: run_program_init: 722.319 ms';
		$this->testLine02 = '02:10:11.623:  ┣OBSApp::AppInit: 3.141 ms';
		$this->testLine03 = '02:10:11.623:  ┃ ┗OBSApp::InitLocale: 0.806 ms';
		$this->testLine04 = '02:10:11.623:  ┗OBSApp::OBSInit: 681.013 ms';
		$this->testLine05 = '02:10:11.623:    ┣obs_startup: 1.247 ms';
		$this->testLine06 = '02:10:11.623:      ┣OBSBasic::InitBasicConfig: 0.332 ms';
		$this->testLine07 = '02:10:11.623:      ┃ ┣obs_init_module(obs-qsv11.dll): 44.031 ms';
		$this->testLine08 = '02:10:11.624: obs_video_thread(33.3333 ms): min=0.196 ms, median=0.796 ms, max=764.609 ms, 99th percentile=2.496 ms, 99.8837% below 33.333 ms';
		$this->testLine09 = '02:10:11.624:  ┗output_frame: min=0.192 ms, median=0.485 ms, max=1.511 ms, 99th percentile=0.681 ms';
		$this->testLine10 = '02:10:11.624:    ┃ ┃ ┗stage_output_texture: min=0 ms, median=0.002 ms, max=0.164 ms, 99th percentile=0.023 ms';
		$this->testLine11 = '02:10:11.624:    ┃ ┣download_frame: min=0 ms, median=0.001 ms, max=0.358 ms, 99th percentile=0.023 ms';
		$this->testLine12 = '02:10:11.624: obs_video_thread(33.3333 ms): min=2.049 ms, median=33.333 ms, max=764.616 ms, 97.9045% within ±2% of 33.333 ms (1.04773% lower, 1.04773% higher)';
	}

	/**
	 * @dataProvider timestampProvider
	 * @covers OBSStudioLogLine::find_timestamp
	 */
	public function testFindTimestamp($logVarName, $expectedValue)
	{
		$tmpProfilerItem = new \RytoEX\OBS\LogAnalyzer\Profiler\Item($this->$logVarName);
		$this->assertSame($tmpProfilerItem->timestamp,
			$expectedValue);
	}

	public function timestampProvider()
	{
		return [
			['testLine01', '02:10:11.623'],
			['testLine02', '02:10:11.623'],
			['testLine03', '02:10:11.623'],
			['testLine04', '02:10:11.623'],
			['testLine05', '02:10:11.623'],
			['testLine06', '02:10:11.623'],
			['testLine07', '02:10:11.623'],
			['testLine08', '02:10:11.624'],
			['testLine09', '02:10:11.624'],
			['testLine10', '02:10:11.624'],
			['testLine11', '02:10:11.624'],
			['testLine12', '02:10:11.624']
		];
	}

	/**
	 * @dataProvider itemStringProvider
	 * @covers OBSStudioLogLine::find_item_string
	 */
	public function testFindItemString($logVarName, $expectedValue)
	{
		$tmpProfilerItem = new \RytoEX\OBS\LogAnalyzer\Profiler\Item($this->$logVarName);
		$this->assertSame($tmpProfilerItem->item_string,
			$expectedValue);
	}

	public function itemStringProvider()
	{
		return [
			['testLine01', 'run_program_init: 722.319 ms'],
			['testLine02', ' ┣OBSApp::AppInit: 3.141 ms'],
			['testLine03', ' ┃ ┗OBSApp::InitLocale: 0.806 ms'],
			['testLine04', ' ┗OBSApp::OBSInit: 681.013 ms'],
			['testLine05', '   ┣obs_startup: 1.247 ms'],
			['testLine06', '     ┣OBSBasic::InitBasicConfig: 0.332 ms'],
			['testLine07', '     ┃ ┣obs_init_module(obs-qsv11.dll): 44.031 ms'],
			['testLine08', 'obs_video_thread(33.3333 ms): min=0.196 ms, median=0.796 ms, max=764.609 ms, 99th percentile=2.496 ms, 99.8837% below 33.333 ms'],
			['testLine09', ' ┗output_frame: min=0.192 ms, median=0.485 ms, max=1.511 ms, 99th percentile=0.681 ms'],
			['testLine10', '   ┃ ┃ ┗stage_output_texture: min=0 ms, median=0.002 ms, max=0.164 ms, 99th percentile=0.023 ms'],
			['testLine11', '   ┃ ┣download_frame: min=0 ms, median=0.001 ms, max=0.358 ms, 99th percentile=0.023 ms'],
			['testLine12', 'obs_video_thread(33.3333 ms): min=2.049 ms, median=33.333 ms, max=764.616 ms, 97.9045% within ±2% of 33.333 ms (1.04773% lower, 1.04773% higher)']
		];
	}

	/**
	 * @dataProvider itemTypeProvider
	 * @covers OBSStudioProfilerItem::find_type
	 */
	public function testFindItemType($logVarName, $expectedValue)
	{
		$tmpProfilerItem = new \RytoEX\OBS\LogAnalyzer\Profiler\Item($this->$logVarName);
		$this->assertSame($tmpProfilerItem->item_type,
			$expectedValue);
	}

	public function itemTypeProvider()
	{
		return [
			['testLine01', 'init'],
			['testLine02', 'init'],
			['testLine03', 'init'],
			['testLine04', 'init'],
			['testLine05', 'init'],
			['testLine06', 'init'],
			['testLine07', 'init'],
			['testLine08', 'thread'],
			['testLine09', 'thread'],
			['testLine10', 'thread'],
			['testLine11', 'thread'],
			['testLine12', 'time_between_calls']
		];
	}

	/**
	 * @dataProvider nameProvider
	 * @covers OBSStudioProfilerItem::find_name
	 */
	public function testFindName($logVarName, $expectedValue)
	{
		$tmpProfilerItem = new \RytoEX\OBS\LogAnalyzer\Profiler\Item($this->$logVarName);
		$this->assertSame($tmpProfilerItem->name,
			$expectedValue);
	}

	public function nameProvider()
	{
		return [
			['testLine01', 'run_program_init'],
			['testLine02', 'OBSApp::AppInit'],
			['testLine03', 'OBSApp::InitLocale'],
			['testLine04', 'OBSApp::OBSInit'],
			['testLine05', 'obs_startup'],
			['testLine06', 'OBSBasic::InitBasicConfig'],
			['testLine07', 'obs_init_module'],
			['testLine08', 'obs_video_thread'],
			['testLine09', 'output_frame'],
			['testLine10', 'stage_output_texture'],
			['testLine11', 'download_frame'],
			['testLine12', 'obs_video_thread']
		];
	}

	/**
	 * @dataProvider itemInfoProvider
	 * @covers OBSStudioProfilerItem::find_item_info
	 */
	public function testFindItemInfo($logVarName, $expectedValue)
	{
		$tmpProfilerItem = new \RytoEX\OBS\LogAnalyzer\Profiler\Item($this->$logVarName);
		$this->assertSame($tmpProfilerItem->item_info,
			$expectedValue);
	}

	public function itemInfoProvider()
	{
		return [
			['testLine01', false],
			['testLine02', false],
			['testLine03', false],
			['testLine04', false],
			['testLine05', false],
			['testLine06', false],
			['testLine07', 'obs-qsv11.dll'],
			['testLine08', '33.3333 ms'],
			['testLine09', false],
			['testLine10', false],
			['testLine11', false],
			['testLine12', '33.3333 ms']
		];
	}

	/**
	 * @dataProvider indentLevelProvider
	 * @covers OBSStudioProfilerItem::find_indent_level
	 */
	public function testFindIndentLevel($logVarName, $expectedValue)
	{
		$tmpProfilerItem = new \RytoEX\OBS\LogAnalyzer\Profiler\Item($this->$logVarName);
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
			['testLine05', 2],
			['testLine06', 3],
			['testLine07', 4],
			['testLine08', 0],
			['testLine09', 1],
			['testLine10', 4],
			['testLine11', 3],
			['testLine12', 0]
		];
	}

	/**
	 * @dataProvider runTimeProvider
	 * @covers OBSStudioProfilerItem::find_run_time
	 */
	public function testFindRunTime($logVarName, $expectedValue)
	{
		$tmpProfilerItem = new \RytoEX\OBS\LogAnalyzer\Profiler\Item($this->$logVarName);
		$this->assertSame($tmpProfilerItem->run_time,
			$expectedValue);
	}

	public function runTimeProvider()
	{
		return [
			['testLine01', '722.319'],
			['testLine02', '3.141'],
			['testLine03', '0.806'],
			['testLine04', '681.013'],
			['testLine05', '1.247'],
			['testLine06', '0.332'],
			['testLine07', '44.031'],
			['testLine08', null],
			['testLine09', null],
			['testLine10', null],
			['testLine11', null],
			['testLine12', null]
		];
	}

	/**
	 * @dataProvider minProvider
	 * @covers OBSStudioProfilerItem::find_stat_value
	 */
	public function testFindMin($logVarName, $expectedValue)
	{
		$tmpProfilerItem = new \RytoEX\OBS\LogAnalyzer\Profiler\Item($this->$logVarName);
		$this->assertSame($tmpProfilerItem->min,
			$expectedValue);
	}

	public function minProvider()
	{
		return [
			['testLine01', null],
			['testLine02', null],
			['testLine03', null],
			['testLine04', null],
			['testLine05', null],
			['testLine06', null],
			['testLine07', null],
			['testLine08', '0.196'],
			['testLine09', '0.192'],
			['testLine10', '0'],
			['testLine11', '0'],
			['testLine12', '2.049']
		];
	}

	/**
	 * @dataProvider medianProvider
	 * @covers OBSStudioProfilerItem::find_stat_value
	 */
	public function testFindMedian($logVarName, $expectedValue)
	{
		$tmpProfilerItem = new \RytoEX\OBS\LogAnalyzer\Profiler\Item($this->$logVarName);
		$this->assertSame($tmpProfilerItem->median,
			$expectedValue);
	}

	public function medianProvider()
	{
		return [
			['testLine01', null],
			['testLine02', null],
			['testLine03', null],
			['testLine04', null],
			['testLine05', null],
			['testLine06', null],
			['testLine07', null],
			['testLine08', '0.796'],
			['testLine09', '0.485'],
			['testLine10', '0.002'],
			['testLine11', '0.001'],
			['testLine12', '33.333']
		];
	}

	/**
	 * @dataProvider maxProvider
	 * @covers OBSStudioProfilerItem::find_stat_value
	 */
	public function testFindMax($logVarName, $expectedValue)
	{
		$tmpProfilerItem = new \RytoEX\OBS\LogAnalyzer\Profiler\Item($this->$logVarName);
		$this->assertSame($tmpProfilerItem->max,
			$expectedValue);
	}

	public function maxProvider()
	{
		return [
			['testLine01', null],
			['testLine02', null],
			['testLine03', null],
			['testLine04', null],
			['testLine05', null],
			['testLine06', null],
			['testLine07', null],
			['testLine08', '764.609'],
			['testLine09', '1.511'],
			['testLine10', '0.164'],
			['testLine11', '0.358'],
			['testLine12', '764.616']
		];
	}

	/**
	 * @dataProvider percentile99Provider
	 * @covers OBSStudioProfilerItem::find_stat_value
	 */
	public function testFindPercentile99($logVarName, $expectedValue)
	{
		$tmpProfilerItem = new \RytoEX\OBS\LogAnalyzer\Profiler\Item($this->$logVarName);
		$this->assertSame($tmpProfilerItem->percentile99,
			$expectedValue);
	}

	public function percentile99Provider()
	{
		return [
			['testLine01', null],
			['testLine02', null],
			['testLine03', null],
			['testLine04', null],
			['testLine05', null],
			['testLine06', null],
			['testLine07', null],
			['testLine08', '2.496'],
			['testLine09', '0.681'],
			['testLine10', '0.023'],
			['testLine11', '0.023'],
			['testLine12', null]
		];
	}

	/**
	 * @dataProvider callsPerParentCallProvider
	 * @covers OBSStudioProfilerItem::find_parent_calls
	 */
	public function testFindCallsPerParentCall($logVarName, $expectedValue)
	{
		$tmpProfilerItem = new \RytoEX\OBS\LogAnalyzer\Profiler\Item($this->$logVarName);
		$this->assertSame($tmpProfilerItem->calls_per_parent_call,
			$expectedValue);
	}

	public function callsPerParentCallProvider()
	{
		return [
			['testLine01', null],
			['testLine02', null],
			['testLine03', null],
			['testLine04', null],
			['testLine05', null],
			['testLine06', null],
			['testLine07', null],
			['testLine08', false],
			['testLine09', false],
			['testLine10', false],
			['testLine11', false],
			['testLine12', null]
		];
	}

	/**
	 * @dataProvider percentBelowThresholdProvider
	 * @covers OBSStudioProfilerItem::find_percent_below_threshold
	 */
	public function testFindPercentBelowThreshold($logVarName, $expectedValue)
	{
		$tmpProfilerItem = new \RytoEX\OBS\LogAnalyzer\Profiler\Item($this->$logVarName);
		$this->assertSame($tmpProfilerItem->percent_below_threshold,
			$expectedValue);
	}

	public function percentBelowThresholdProvider()
	{
		return [
			['testLine01', null],
			['testLine02', null],
			['testLine03', null],
			['testLine04', null],
			['testLine05', null],
			['testLine06', null],
			['testLine07', null],
			['testLine08', '99.8837%'],
			['testLine09', false],
			['testLine10', false],
			['testLine11', false],
			['testLine12', null]
		];
	}
}
