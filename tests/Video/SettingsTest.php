<?php

namespace RytoEX\OBS\LogAnalyzer\Tests\Video;

final class SettingsTest extends \PHPUnit\Framework\TestCase
{
	protected $sampleString_01 = "
02:52:18.520: 	base resolution:   1920x1080
02:52:18.520: 	output resolution: 1280x720
02:52:18.520: 	downscale filter:  Lanczos
02:52:18.520: 	fps:               60/1
02:52:18.520: 	format:            NV12
02:52:18.521";
	protected $sampleString_02 = "
22:21:05.958: 	base resolution:   1920x1080
22:21:05.958: 	output resolution: 1920x1080
22:21:05.958: 	downscale filter:  Bicubic
22:21:05.958: 	fps:               60/1
22:21:05.958: 	format:            NV12
22:21:05.958: 	YUV mode:          601/Partial
22:21:05.963";

	protected $video_settings;
	protected $video_settings_empty;
	protected $video_settings_01;
	protected $video_settings_02;


	public function setUp()
	{
		
		$this->video_settings_01 = new \RytoEX\OBS\LogAnalyzer\Video\Settings($this->sampleString_01);
		$this->video_settings_02 = new \RytoEX\OBS\LogAnalyzer\Video\Settings($this->sampleString_02);

		// special settings objects
		$this->video_settings = clone $this->video_settings_02;
		
		$this->video_settings_empty = new \RytoEX\OBS\LogAnalyzer\Video\Settings;
		
	}

	public function testCanCreateFromString()
	{
		$this->assertInstanceOf(
			\RytoEX\OBS\LogAnalyzer\Video\Settings::class,
			$this->video_settings
		);
	}

	public function testCanProcess()
	{
		$this->assertTrue($this->video_settings->process());
	}


	public function baseResolutionProvider()
	{
		return [
			['video_settings_01', '1920x1080'],
			['video_settings_02', '1920x1080']
		];
	}

	/**
	 * @dataProvider baseResolutionProvider
	 * @covers VideoSettings::find_base_resolution
	 */
	public function testFindBaseResolution($data, $expectedValue)
	{
		$this->assertSame($this->$data->find_base_resolution(),
			$expectedValue);
	}

	/**
	 * @depends testFindBaseResolution
	 * @dataProvider baseResolutionProvider
	 * @covers VideoSettings::set_base_resolution
	 * @covers VideoSettings::get_base_resolution
	 */
	public function testGetBaseResolution($data, $expectedValue)
	{
		$this->$data->set_base_resolution($expectedValue);
		$this->assertSame($this->$data->get_base_resolution(),
			$expectedValue);
	}

	public function baseResolutionWidthProvider()
	{
		return [
			['video_settings_01', '1920x1080', '1920'],
			['video_settings_02', '1920x1080', '1920']
		];
	}

	/**
	 * @depends testGetBaseResolution
	 * @dataProvider baseResolutionWidthProvider
	 * @covers VideoSettings::set_base_resolution
	 * @covers VideoSettings::get_base_resolution_width
	 */
	public function testGetBaseResolutionWidth($data, $baseRes, $expectedValue)
	{
		$this->$data->set_base_resolution($baseRes);
		$this->assertSame($this->$data->get_base_resolution_width(),
			$expectedValue);
	}

	public function baseResolutionHeightProvider()
	{
		return [
			['video_settings_01', '1920x1080', '1080'],
			['video_settings_02', '1920x1080', '1080']
		];
	}

	/**
	 * @depends testGetBaseResolution
	 * @dataProvider baseResolutionHeightProvider
	 * @covers VideoSettings::set_base_resolution
	 * @covers VideoSettings::get_base_resolution_height
	 */
	public function testGetBaseResolutionHeight($data, $baseRes, $expectedValue)
	{
		$this->$data->set_base_resolution($baseRes);
		$this->assertSame($this->$data->get_base_resolution_height(),
			$expectedValue);
	}

	public function outputResolutionProvider()
	{
		return [
			['video_settings_01', '1280x720'],
			['video_settings_02', '1920x1080']
		];
	}

	/**
	 * @dataProvider outputResolutionProvider
	 * @covers VideoSettings::find_base_resolution
	 */
	public function testFindOutputResolution($data, $expectedValue)
	{
		$this->assertSame($this->$data->find_output_resolution(),
			$expectedValue);
	}

	/**
	 * @depends testFindOutputResolution
	 * @dataProvider outputResolutionProvider
	 * @covers VideoSettings::set_output_resolution
	 * @covers VideoSettings::get_output_resolution
	 */
	public function testGetOutputResolution($data, $expectedValue)
	{
		$this->$data->set_output_resolution($expectedValue);
		$this->assertSame($this->$data->get_output_resolution(),
			$expectedValue);
	}

	public function outputResolutionWidthProvider()
	{
		return [
			['video_settings_01', '1280x720', '1280'],
			['video_settings_02', '1920x1080', '1920']
		];
	}

	/**
	 * @depends testGetOutputResolution
	 * @dataProvider outputResolutionWidthProvider
	 * @covers VideoSettings::set_output_resolution
	 * @covers VideoSettings::get_output_resolution_width
	 */
	public function testGetOutputResolutionWidth($data, $outputRes, $expectedValue)
	{
		$this->$data->set_output_resolution($outputRes);
		$this->assertSame($this->$data->get_output_resolution_width(),
			$expectedValue);
	}

	public function outputResolutionHeightProvider()
	{
		return [
			['video_settings_01', '1280x720', '720'],
			['video_settings_02', '1920x1080', '1080']
		];
	}

	/**
	 * @depends testGetOutputResolution
	 * @dataProvider outputResolutionHeightProvider
	 * @covers VideoSettings::set_output_resolution
	 * @covers VideoSettings::get_output_resolution_height
	 */
	public function testGetOutputResolutionHeight($data, $outputRes, $expectedValue)
	{
		$this->$data->set_output_resolution($outputRes);
		$this->assertSame($this->$data->get_output_resolution_height(),
			$expectedValue);
	}

	public function downscaleFilterProvider()
	{
		return [
			['video_settings_01', 'Lanczos'],
			['video_settings_02', 'Bicubic']
		];
	}

	/**
	 * @dataProvider downscaleFilterProvider
	 * @covers VideoSettings::find_downscale_filter
	 */
	public function testFindDownscaleFilter($data, $expectedValue)
	{
		$this->assertSame($this->$data->find_downscale_filter(),
			$expectedValue);
	}

	public function fpsProvider()
	{
		return [
			['video_settings_01', '60/1'],
			['video_settings_02', '60/1']
		];
	}

	/**
	 * @dataProvider fpsProvider
	 * @covers VideoSettings::find_fps
	 */
	public function testFind_FPS($data, $expectedValue)
	{
		$this->assertSame($this->$data->find_fps(),
			$expectedValue);
	}

	public function fpsNumProvider()
	{
		return [
			['video_settings_01', '60/1', 60],
			['video_settings_02', '60/1', 60]
		];
	}

	/**
	 * @depends testFind_FPS
	 * @dataProvider fpsNumProvider
	 * @covers VideoSettings::find_fps
	 * @covers VideoSettings::calc_fps_num
	 */
	public function testFind_FPS_num($data, $fps_string, $expectedValue)
	{
		$this->$data->fps_string = $this->$data->find_fps();
		$this->assertSame($this->$data->calc_fps_num(),
			$expectedValue);
	}

	public function formatProvider()
	{
		return [
			['video_settings_01', 'NV12'],
			['video_settings_02', 'NV12']
		];
	}

	/**
	 * @dataProvider formatProvider
	 * @covers VideoSettings::find_format
	 */
	public function testFindFormat($data, $expectedValue)
	{
		$this->assertSame($this->$data->find_format(),
			$expectedValue);
	}

	public function YUVModeProvider()
	{
		return [
			['video_settings_01', false],
			['video_settings_02', '601/Partial']
		];
	}

	/**
	 * @dataProvider YUVModeProvider
	 * @covers VideoSettings::find_yuv_mode
	 */
	public function testFind_YUV_Mode($data, $expectedValue)
	{
		$this->assertSame($this->$data->find_yuv_mode(),
			$expectedValue);
	}

	public function YUVColorSpaceProvider()
	{
		return [
			['video_settings_01', false, false],
			['video_settings_02', '601/Partial', '601']
		];
	}

	/**
	 * @dataProvider YUVColorSpaceProvider
	 * @covers VideoSettings::find_yuv_color_space
	 */
	public function testFind_YUV_Color_Space($data, $yuv_mode, $expectedValue)
	{
		$this->assertSame($this->$data->find_yuv_color_space($yuv_mode),
			$expectedValue);
	}

	public function YUVColorRangeProvider()
	{
		return [
			['video_settings_01', false, false],
			['video_settings_02', '601/Partial', 'Partial']
		];
	}

	/**
	 * @dataProvider YUVColorRangeProvider
	 * @covers VideoSettings::find_yuv_color_range
	 */
	public function testFind_YUV_Color_Range($data, $yuv_mode, $expectedValue)
	{
		$this->assertSame($this->$data->find_yuv_color_range($yuv_mode),
			$expectedValue);
	}
}
