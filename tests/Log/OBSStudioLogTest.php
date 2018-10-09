<?php

namespace RytoEX\OBS\LogAnalyzer\Tests\Log;

final class OBSStudioLogTest extends \PHPUnit\Framework\TestCase
{
	protected $sampleLogFile;
	protected $obsLog;

	public function setUp()
	{
		$this->sampleLogFile = './tests/LogSamples/basic_windows_log_(2017-04-12 02-09-41).txt';
		$this->obsLog = new \RytoEX\OBS\LogAnalyzer\Log\OBSStudioLog($this->sampleLogFile, false);
		$this->obsLog->process_log();
	}

	public function testCanBeCreatedFromValidLogFile()
	{
		$this->assertInstanceOf(
			\RytoEX\OBS\LogAnalyzer\Log\OBSStudioLog::class,
			new \RytoEX\OBS\LogAnalyzer\Log\OBSStudioLog($this->sampleLogFile, false)
		);
	}

	/*
	 * @covers OBSStudioLog::find_cpu_info
	 * @covers OBSStudioLog::find_cpu_name
	 * @covers OBSStudioLog::find_cpu_speed
	 * @covers OBSStudioLog::find_cpu_cores
	 * @covers OBSStudioLog::get_cpu_name
	 * @covers OBSStudioLog::get_cpu_speed
	 * @covers OBSStudioLog::get_cpu_physical_cores
	 * @covers OBSStudioLog::get_cpu_logical_cores
	 */
	public function testGet_CPU_Info()
	{
		$this->assertEquals($this->obsLog->get_cpu_name(),
			"Intel(R) Core(TM) i5-3570K CPU @ 3.40GHz");
		$this->assertEquals($this->obsLog->get_cpu_speed(), "3468MHz");
		$this->assertEquals($this->obsLog->get_cpu_physical_cores(), "4");
		$this->assertEquals($this->obsLog->get_cpu_logical_cores(), "4");
	}

	/*
	 * @covers OBSStudioLog::find_memory_info
	 * @covers OBSStudioLog::find_memory_physical_total
	 * @covers OBSStudioLog::find_memory_physical_free
	 * @covers OBSStudioLog::get_memory_physical_total
	 * @covers OBSStudioLog::get_memory_physical_free
	 */
	public function testGet_Memory_Info()
	{
		$this->assertEquals($this->obsLog->get_memory_physical_total(), "16327MB");
		$this->assertEquals($this->obsLog->get_memory_physical_free(), "6225MB");
	}

	/*
	 * @covers OBSStudioLog::find_os_info
	 * @covers OBSStudioLog::find_os_name
	 * @covers OBSStudioLog::find_os_version
	 * @covers OBSStudioLog::get_os_name
	 * @covers OBSStudioLog::get_os_version
	 * @covers OBSStudioLog::get_os_build
	 * @covers OBSStudioLog::get_os_revision
	 * @covers OBSStudioLog::get_os_bitness
	 */
	public function testGet_OS_Version()
	{
		$this->assertEquals($this->obsLog->get_os_name(), "Windows");
		$this->assertEquals($this->obsLog->get_os_version(), "10.0");
		$this->assertEquals($this->obsLog->get_os_build(), "14393");
		$this->assertEquals($this->obsLog->get_os_revision(), "953");
		$this->assertEquals($this->obsLog->get_os_bitness(), "64");
	}

	/*
	 * @covers OBSStudioLog::find_run_as_admin
	 * @covers OBSStudioLog::get_run_as_admin
	 */
	public function testGet_Run_as_admin()
	{
		$this->assertEquals($this->obsLog->get_run_as_admin(), "false");
	}

	/*
	 * @covers OBSStudioLog::find_aero
	 * @covers OBSStudioLog::get_aero_status
	 */
	public function testGet_Aero_status()
	{
		$this->assertEquals($this->obsLog->get_aero_status(), "Enabled");
	}

	/*
	 * @covers OBSStudioLog::find_portable_mode
	 * @covers OBSStudioLog::get_portable_mode_status
	 */
	public function testGet_Portable_Mode_status()
	{
		$this->assertEquals($this->obsLog->get_portable_mode_status(), "false");
	}

	/*
	 * @covers OBSStudioLog::find_obs_version_info
	 * @covers OBSStudioLog::get_obs_version
	 * @covers OBSStudioLog::get_obs_version_bitness
	 */
	public function testGet_OBS_Version()
	{
		$this->assertEquals($this->obsLog->get_obs_version(), "17.0.2");
		$this->assertEquals($this->obsLog->get_obs_version_bitness(), "64");
	}

	/*
	 * @covers OBSStudioLog::find_audio_settings
	 * @covers OBSStudioLog::get_audio_samples_per_sec
	 * @covers OBSStudioLog::get_audio_speakers
	 */
	public function testGet_Audio_Settings()
	{
		$this->assertEquals($this->obsLog->get_audio_samples_per_sec(), "44100");
		$this->assertEquals($this->obsLog->get_audio_speakers(), "2");
	}

	/*
	 * @covers OBSStudioLog::find_renderer
	 * @covers OBSStudioLog::find_d3d11_adapter
	 * @covers OBSStudioLog::find_d3d11_feature_level
	 * @covers OBSStudioLog::get_renderer_name
	 * @covers OBSStudioLog::get_renderer_adapter
	 * @covers OBSStudioLog::get_renderer_feature_level
	 * @covers OBSStudioLog::is_renderer_d3d11
	 */
	public function testGet_Renderer_Info()
	{
		$this->assertEquals($this->obsLog->get_renderer_name(), "D3D11");
		$this->assertEquals($this->obsLog->get_renderer_adapter(),
			"NVIDIA GeForce GTX 760");
		$this->assertEquals($this->obsLog->get_renderer_feature_level(), "45056");
		$this->assertTrue($this->obsLog->is_renderer_d3d11());
	}

	/*
	 * @covers OBSStudioLog::find_renderer
	 * @covers OBSStudioLog::find_d3d11_adapter
	 * @covers OBSStudioLog::find_video_adapters
	 * @covers OBSStudioLog::get_renderer_name
	 * @covers OBSStudioLog::get_renderer_adapter
	 * @covers OBSStudioLog::is_renderer_d3d11
	 */
	public function testGet_Video_Adapters()
	{
		$adapter = $this->obsLog->get_video_adapter(1);
		$this->assertInstanceOf(\RytoEX\OBS\LogAnalyzer\Video\Adapter::class,
			$adapter);
		$this->assertEquals($this->obsLog->get_video_adapter_number(1), "1");
		$this->assertEquals($this->obsLog->get_video_adapter_name(1),
			"NVIDIA GeForce GTX 760");
		$this->assertEquals($this->obsLog->get_video_adapter_dedicated_vram(1),
			"2115698688");
		$this->assertEquals($this->obsLog->get_video_adapter_shared_vram(1),
			"4265453568");

		$output1 = $adapter->get_output(1);
		$this->assertInstanceOf(\RytoEX\OBS\LogAnalyzer\Video\Output::class,
			$output1);
		$this->assertEquals($output1->get_number(), "1");
		$this->assertEquals($output1->get_pos_x(), "0");
		$this->assertEquals($output1->get_pos_y(), "0");
		$this->assertEquals($output1->get_size_x(), "1920");
		$this->assertEquals($output1->get_size_y(), "1080");
		$this->assertEquals($output1->get_attached(), "true");

		$output2 = $adapter->get_output(2);
		$this->assertInstanceOf(\RytoEX\OBS\LogAnalyzer\Video\Output::class,
			$output2);
		$this->assertEquals($output2->get_number(), "2");
		$this->assertEquals($output2->get_pos_x(), "-1920");
		$this->assertEquals($output2->get_pos_y(), "0");
		$this->assertEquals($output2->get_size_x(), "1920");
		$this->assertEquals($output2->get_size_y(), "1080");
		$this->assertEquals($output2->get_attached(), "true");
	}

	/*
	 * @covers OBSStudioLog::find_video_settings
	 */
	public function testGet_Video_Settings()
	{
		$this->assertNotNull($this->obsLog->video_settings);
		$this->assertCount(1, $this->obsLog->video_settings);
		$this->assertInstanceOf(\RytoEX\OBS\LogAnalyzer\Video\Settings::class,
			$this->obsLog->video_settings[0]);
	}

	/*
	 * @covers OBSStudioLog::has_coreaudio
	 */
	public function testGet_CoreAudio_Status()
	{
		$this->assertTrue($this->obsLog->has_coreaudio());
	}

	/*
	 * @covers OBSStudioLog::has_amf_support
	 */
	public function testGet_AMF_Encoder_Status()
	{
		$this->assertFalse($this->obsLog->has_amf_support());
	}

	/*
	 * @covers OBSStudioLog::has_nvenc_support
	 */
	public function testGet_NVENC_Encoder_Status()
	{
		$this->assertTrue($this->obsLog->has_nvenc_support());
	}

	/*
	 * @covers OBSStudioLog::has_browser_source
	 */
	public function testGet_Browser_Source_Status()
	{
		$this->assertTrue($this->obsLog->has_browser_source());
		$this->assertSame($this->obsLog->browser_source_module->version, '1.28.0');
	}

	/*
	 * @covers OBSStudioLog::has_vlc
	 */
	public function testGet_VLC_Status()
	{
		$this->assertFalse($this->obsLog->has_vlc());
	}

	/*
	 * @covers OBSStudioLog::has_blackmagic_support
	 */
	public function testGet_Blackmagic_Status()
	{
		$this->assertFalse($this->obsLog->has_blackmagic_support());
	}

	/*
	 * @covers OBSStudioLog::find_loaded_modules_string
	 * @covers OBSStudioLog::find_loaded_modules
	 */
	public function testGet_Loaded_Modules()
	{
		/*$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);*/
		$this->assertSame($this->obsLog->loaded_modules,
			[
				'win-wasapi.dll',
				'win-mf.dll',
				'win-dshow.dll',
				'win-decklink.dll',
				'win-capture.dll',
				'vlc-video.dll',
				'text-freetype2.dll',
				'rtmp-services.dll',
				'obs-x264.dll',
				'obs-transitions.dll',
				'obs-text.dll',
				'obs-qsv11.dll',
				'obs-outputs.dll',
				'obs-filters.dll',
				'obs-ffmpeg.dll',
				'obs-browser.dll',
				'image-source.dll',
				'frontend-tools.dll',
				'enc-amf.dll',
				'coreaudio-encoder.dll'
			]
		);
	}
}
