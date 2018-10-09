<?php

// Try to identify common issues found in an OBS Studio log file.

// run from within RytoEX\OBS\LogAnalyzer\Issue\Handler
// use $this->obs_log_object instead of $this

/*
 * $this must be a \RytoEX\OBS\LogAnalyzer\Issue\Handler object
 * $this->obs_log_object must be a \RytoEX\OBS\LogAnalyzer\Log\OBSStudioLog object
 */

// Clean/Complete log
if ($this->obs_log_object->is_complete() === false) {
	$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
	$issue->severity = 'Minor';
	$issue->short_name = 'Your log file is incomplete';
	$issue->long_text = 'Your log file is incomplete because it lacks important profiler data.';
	$issue->proposal = 'Please provide a log that contains profiler data to help ensure a proper diagnosis.';
	$this->add_issue($issue);
	unset($issue);
}
if ($this->obs_log_object->is_clean() === false) {
	$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
	$issue->severity = 'Minor';
	$issue->short_name = 'Your log file is not clean';
	$issue->long_text = 'Your log file is not considered "clean" because it has multiple recording/streaming sessions, it has incomplete recording/streaming sessions, or you changed the audio/video settings after initialization.';
	$issue->proposal = 'Please provide a clean log.  To make a clean log file, first restart OBS, then start your stream/recording for about 30 seconds and stop it again. Make sure you replicate any issues as best you can, which means having any games/apps open and captured, etc.';
	$this->add_issue($issue);
	unset($issue);
}

// OBS version issues
// @todo: Check OBS version against OS-specific current latest version
if (!$this->obs_log_object->is_obs_from_git() && !$this->obs_log_object->is_obs_ftl_enabled() && !$this->obs_log_object->is_obs_version_custom()
	&& version_compare(\RytoEX\OBS\LogAnalyzer\Log\OBSStudioLog::LATEST_OBS_VERSION, $this->obs_log_object->get_obs_version()) === 1) {
	$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
	$issue->severity = 'Warning';
	$issue->short_name = 'Using an older version of OBS';
	$issue->long_text = 'You are using an OBS version that is not the latest version.';
	$issue->proposal = 'Please update your OBS installation.';
	$this->add_issue($issue);
	unset($issue);
}
if ($this->obs_log_object->is_obs_from_git()) {
	$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
	$issue->severity = 'Info';
	$issue->short_name = 'Using OBS version built from Git';
	$issue->long_text = 'You are using an OBS version that is built from Git rather than an official, stable release.';
	$issue->proposal = 'If you were not told to use a custom version by a community helper, and you are not on Linux, you should strongly consider using one of the official releases.';
	$this->add_issue($issue);
	unset($issue);
}
if ($this->obs_log_object->is_obs_ftl_enabled()) {
	$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
	$issue->severity = 'Info';
	$issue->short_name = 'Using OBS-FTL variant';
	$issue->long_text = 'You are using an OBS version that is a third-party variant.';
	$issue->proposal = 'If you are having issues, try to replicate them using a regular version OBS Studio to ensure that this is an OBS problem and not a problem specific to the FTL variant.';
	$this->add_issue($issue);
	unset($issue);
}

// audio
if ($this->obs_log_object->has_max_audio_buffering()) {
	$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
	$issue->severity = 'Minor';
	$issue->short_name = 'Max audio buffering reached';
	$issue->long_text = 'You reached the maximum limit for audio buffering.  This indicates that your system is straining to keep up.';
	$issue->proposal = '';
	$this->add_issue($issue);
	unset($issue);
}
if ($this->obs_log_object->has_coreaudio() === false && $this->obs_log_object->is_os_windows()) {
	$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
	$issue->severity = 'Info';
	$issue->short_name = 'No CoreAudio';
	$issue->long_text = 'Your PC does not have CoreAudio support.';
	$issue->proposal = 'If you are having audio issues, consider installing CoreAudio.  You can install Apple\'s CoreAudio codecs for better audio quality without bothering with iTunes or QuickTime. Follow the instructions at <a href="http://obsproject.com/forum/resources/obs-studio-enable-coreaudio-aac-encoder-windows.220/">http://obsproject.com/forum/resources/obs-studio-enable-coreaudio-aac-encoder-windows.220/</a>';
	$this->add_issue($issue);
	unset($issue);
}

// recording/streaming sessions
if ($this->obs_log_object->has_incomplete_recording_session()) {
	$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
	$issue->severity = 'Warning';
	$issue->short_name = 'Has incomplete recording session';
	$issue->long_text = 'At least one of your recording sessions is incomplete, meaning it does not have both a start and stop marker.';
	$issue->proposal = 'Please make sure to start <i>and</i> stop a recording session before uploading a log file.';
	$this->add_issue($issue);
	unset($issue);
}
if ($this->obs_log_object->has_incomplete_streaming_session()) {
	$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
	$issue->severity = 'Warning';
	$issue->short_name = 'Has incomplete streaming session';
	$issue->long_text = 'At least one of your streaming sessions is incomplete, meaning it does not have both a start and stop marker.';
	$issue->proposal = 'Please make sure to start <i>and</i> stop a streaming session before uploading a log file.';
	$this->add_issue($issue);
	unset($issue);
}
if ((!isset($this->obs_log_object->recording_sessions) || $this->obs_log_object->recording_sessions === 0)
	&&
	(!isset($this->obs_log_object->streaming_sessions) || $this->obs_log_object->streaming_sessions === 0)) {
	$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
	$issue->severity = 'Info';
	$issue->short_name = 'No recording or streaming sessions';
	$issue->long_text = 'Your log contains no recording or streaming sessions.';
	$issue->proposal = 'Please make sure to start <i>and</i> stop a streaming session before uploading a log file.';
	$this->add_issue($issue);
	unset($issue);
}

// user video settings
if (isset($this->obs_log_object->video_settings) && count($this->obs_log_object->video_settings) > 0) {
	$upscaled_output = false;
	$default_format = false;
	$common_fps = true;
	$yuv_range_is_default = false;
	$yuv_space_is_default = false;
	foreach ($this->obs_log_object->video_settings as $vs) {
		if (is_bool($vs)) {
			// if video settings is a bool, just skip it
			// probably should flag somehow
			continue;
		}
		if ($vs->is_output_upscaled()) {
			$upscaled_output = true;
		}
		if ($vs->is_format_default()) {
			$default_format = true;
		}
		if (!$vs->is_fps_common()) {
			$common_fps = false;
		}
		if ($vs->is_yuv_color_range_default()) {
			$yuv_range_is_default = true;
		}
		if ($vs->is_yuv_color_space_default()) {
			$yuv_space_is_default = true;
		}
	}
	if ($upscaled_output) {
		$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
		$issue->severity = 'Warning';
		$issue->short_name = 'Video output is upscaled';
		$issue->long_text = 'Your video settings show that your output resolution is higher than your base resolution.  This can cause additional GPU load, leading to performance problems.';
		$issue->proposal = 'Set your output resolution to be equal to or lower than your base resolution.';
		$issue->tags = 'user;settings;video;scale';
		$this->add_issue($issue);
		unset($issue);
	}
	if (!$default_format) {
		$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
		$issue->severity = 'Warning';
		$issue->short_name = 'Video format is not default';
		$issue->long_text = 'Your video settings show you have set your video format to something other than NV12.';
		$issue->proposal = 'If you do not need this other format for a specific reason, it is best to set the video format to NV12.';
		$issue->tags = 'user;settings;video;format';
		$this->add_issue($issue);
		unset($issue);
	}
	if (!$common_fps) {
		$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
		$issue->severity = 'Warning';
		$issue->short_name = 'Video FPS is non-standard';
		$issue->long_text = 'Your video settings show you have set your video FPS to a value not in the common values.  This may cause issues as GPUs can more easily render at those common framerates.';
		$issue->proposal = 'If you do not need this FPS for a specific reason, it is best to set the video FPS to something in the "Common FPS values" dropdown.';
		$issue->tags = 'user;settings;video;fps';
		$this->add_issue($issue);
		unset($issue);
	}
	if (!$yuv_range_is_default) {
		$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
		$issue->severity = 'Warning';
		$issue->short_name = 'Video YUV Color Range is not default';
		$issue->long_text = 'Your video settings show you have set your YUV Color Range to a non-default value.  This may cause unexpected changes in the appearance of video output.';
		$issue->proposal = 'If you do not need this setting changed for a specific reason, it is best to use the default setting.';
		$issue->tags = 'user;settings;video;fps';
		$this->add_issue($issue);
		unset($issue);
	}
	if (!$yuv_space_is_default) {
		$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
		$issue->severity = 'Warning';
		$issue->short_name = 'Video YUV Color Space is not default';
		$issue->long_text = 'Your video settings show you have set your YUV Color Space to a non-default value.  This may cause unexpected changes in the appearance of video output.';
		$issue->proposal = 'If you do not need this setting changed for a specific reason, it is best to use the default setting.';
		$issue->tags = 'user;settings;video;fps';
		$this->add_issue($issue);
		unset($issue);
	}
}

// special Windows issues
if ($this->obs_log_object->is_os_windows_10() && $this->obs_log_object->os_info['version']['build'] === '14393'
	&& $this->obs_log_object->has_d3d11_error_887A0005()) {
	// Add checks for graphics driver versions if we can?
	// Fixed in AMD 16.10.2 and Nvidia 376.33
	$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
	$issue->severity = 'Major';
	$issue->short_name = 'Windows 10 Anniversary Update Issue';
	$issue->long_text = 'You may be suffering from the Windows 10 Anniversary Update issue.';
	$issue->proposal = 'Update your graphics card drivers.  See this thread for more information:  <a href="https://obsproject.com/forum/threads/psa-for-windows-10-anniversary-update-users.55256/">https://obsproject.com/forum/threads/psa-for-windows-10-anniversary-update-users.55256/</a>';
	$issue->tags = 'Windows;Windows 10';
	$this->add_issue($issue);
	unset($issue);
}
if ($this->obs_log_object->is_os_windows_10() && $this->obs_log_object->os_info['version']['build'] === '15063'
	&& $this->obs_log_object->has_game_capture_fall_back_to_memcap()) {
	$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
	$issue->severity = 'Major';
	$issue->short_name = 'Windows 10 Creators Update Issue';
	$issue->long_text = 'You may be suffering from the Windows 10 Creators Update issue.';
	$issue->proposal = 'See this thread for more information:  <a href="https://obsproject.com/forum/threads/windows-10-creator-update-psa.66825/">https://obsproject.com/forum/threads/windows-10-creator-update-psa.66825/</a>';
	$issue->tags = 'Windows;Windows 10';
	$this->add_issue($issue);
	unset($issue);
}
if ($this->obs_log_object->is_os_windows() && $this->obs_log_object->is_run_as_admin() === false) {
	$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
	$issue->severity = 'Info';
	$issue->short_name = 'OBS not run as administrator';
	$issue->long_text = 'You are not running OBS as an administrator.  This can cause issues with capturing some games.';
	$issue->proposal = 'If you are having issues capturing some games, try running OBS as an administrator.  Please note that you will be unable to drag & drop files onto OBS while it is being run in admin mode.';
	$issue->tags = 'Windows';
	$this->add_issue($issue);
	unset($issue);
}

// General OS update notifications
if ($this->obs_log_object->is_os_windows_10()) {
	$win10_has_available_update = false;

	$release_data = $this->obs_log_object->win10info->release_data;
	foreach ($release_data as $release) {
		if ($release['build'] === $this->obs_log_object->os_info['version']['build'] 
			&& $release['revision'] > $this->obs_log_object->os_info['version']['revision']) {
			$win10_has_available_update = true;
		}
	}
	if ($win10_has_available_update === true) {
		$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
		$issue->severity = 'Minor';
		$issue->short_name = 'Windows 10 is out of date';
		$issue->long_text = 'Your Windows 10 installation is out of date.';
		$issue->proposal = 'Check Windows Update to update your Windows installation.';
		$issue->tags = 'Windows;Windows 10';
		$this->add_issue($issue);
		unset($issue);
	}
} elseif ($this->obs_log_object->is_os_windows_7()) {
	$win7_has_available_update = false;

	if ($this->obs_log_object->os_info['version']['build'] === \RytoEX\OBS\LogAnalyzer\Log\OBSStudioLog::LATEST_WINDOWS_7_VERSION['build']
		&& \RytoEX\OBS\LogAnalyzer\Log\OBSStudioLog::LATEST_WINDOWS_7_VERSION['revision'] > $this->obs_log_object->os_info['version']['revision']) {
		$win7_has_available_update = true;
	}
	if ($win7_has_available_update === true) {
		$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
		$issue->severity = 'Minor';
		$issue->short_name = 'Windows 7 is out of date';
		$issue->long_text = 'Your Windows 7 installation is out of date.';
		$issue->proposal = 'Check Windows Update to update your Windows installation.';
		$issue->tags = 'Windows;Windows 7';
		$this->add_issue($issue);
		unset($issue);
	}
} elseif ($this->obs_log_object->is_os_windows_8()) {
	$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
	$issue->severity = 'Major';
	$issue->short_name = 'Windows 8 is not supported';
	$issue->long_text = 'Your Windows 8 installation is out of date.';
	$issue->proposal = 'You should check the Windows Store to update to Windows 8.1.  See this webpage for more information:  <a href="https://support.microsoft.com/en-us/help/15288/windows-8-update-to-windows-8-1">https://support.microsoft.com/en-us/help/15288/windows-8-update-to-windows-8-1</a>';
	$issue->tags = 'Windows;Windows 8';
	$this->add_issue($issue);
	unset($issue);
} elseif ($this->obs_log_object->is_os_windows_81()) {
	// @todo: implement Windows 8.1 version testing; need a Win8.1 log to test this
	/*
	$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
	$issue->severity = 'Minor';
	$issue->short_name = 'Windows 8 is out of date';
	$issue->long_text = 'Your Windows 8 installation is out of date.';
	$issue->proposal = 'You should check the Windows Store to update to Windows 8.1.  See this webpage for more information:  <a href="https://support.microsoft.com/en-us/help/15288/windows-8-update-to-windows-8-1">https://support.microsoft.com/en-us/help/15288/windows-8-update-to-windows-8-1</a>';
	$issue->tags = 'Windows;Windows 8.1';
	$this->add_issue($issue);
	unset($issue);
	*/
} elseif ($this->obs_log_object->is_os_mac()) {
	$macos_has_available_update = false;

	if ($this->obs_log_object->os_info['version']['version'] < $this->obs_log_object->macosinfo->get_latest_release()) {
		$macos_has_available_update = true;
	}
	if ($macos_has_available_update === true) {
		$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
		$issue->severity = 'Minor';
		$issue->short_name = 'macOS is out of date';
		$issue->long_text = 'Your macOS installation is out of date.';
		$issue->proposal = 'Consider updating your macOS installation.';
		$issue->tags = 'macOS';
		$this->add_issue($issue);
		unset($issue);
	}
}

// plugin issues
if ($this->obs_log_object->has_clr_host_plugin()) {
	$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
	$issue->severity = 'Minor';
	$issue->short_name = 'CLR Host Plugin is installed';
	$issue->long_text = 'You have the CLR Host Plugin installed.  CLR Host Plugin is a plugin for OBS Classic, not OBS Studio.';
	$issue->proposal = 'Remove all CLR Host Plugin files from your OBS Studio installation directory.  If you need a source to show websites or HTML files, use the Browser Source plugin for OBS Studio.';
	$issue->tags = 'obs-plugin';
	$this->add_issue($issue);
	unset($issue);
}
if (isset($this->obs_log_object->browser_source_module) && 
	$this->obs_log_object->browser_source_module->version < \RytoEX\OBS\LogAnalyzer\Log\OBSStudioLog::LATEST_BROWSER_SOURCE_VERSION) {
	$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
	$issue->severity = 'Minor';
	$issue->short_name = 'Browser Source plugin is outdated';
	$issue->long_text = 'You have an outdated version of the Browser Source plugin installed.';
	$issue->proposal = 'Consider updating your Browser Source plugin.  See this link for details:  <a href="https://obsproject.com/forum/resources/browser-plugin.115/">https://obsproject.com/forum/resources/browser-plugin.115/</a>';
	$issue->tags = 'obs-plugin;obs-browser';
	$this->add_issue($issue);
	unset($issue);
}
if (isset($this->obs_log_object->amf_encoder_module) && 
	$this->obs_log_object->amf_encoder_module->version < \RytoEX\OBS\LogAnalyzer\Log\OBSStudioLog::LATEST_AMF_ENCODER_VERSION) {
	$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
	$issue->severity = 'Minor';
	$issue->short_name = 'AMF Encoder Plugin is outdated';
	$issue->long_text = 'You have an outdated version of the AMF Encoder Plugin installed.';
	$issue->proposal = 'Consider updating your AMF Encoder Plugin.  See this link for details:  <a href="https://obsproject.com/forum/resources/amd-advanced-media-framework-encoder-plugin-for-obs-studio.427/">https://obsproject.com/forum/resources/amd-advanced-media-framework-encoder-plugin-for-obs-studio.427/</a>';
	$issue->tags = 'obs-plugin;enc-amf';
	$this->add_issue($issue);
	unset($issue);
}

// renderer
if ($this->obs_log_object->is_renderer_d3d11() && $this->obs_log_object->get_renderer_adapter() === "Microsoft Basic Render Driver") {
	$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
	$issue->severity = 'Major';
	$issue->short_name = 'Graphics drivers not detected';
	$issue->long_text = 'Either you do not have a graphics card installed or your graphics card drivers are not installed correctly.';
	$issue->proposal = 'Uninstall and reinstall your graphics card drivers.';
	$issue->tags = 'renderer';
	$this->add_issue($issue);
	unset($issue);
}
if ($this->obs_log_object->is_renderer_d3d11()) {
	if ($this->obs_log_object->get_renderer_feature_level() < \RytoEX\OBS\LogAnalyzer\Log\OBSStudioLog::D3D_FEATURE_LEVEL_10_1) {
		$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
		$issue->severity = 'Critical';
		$issue->short_name = 'DirectX 10.1 not supported';
		$issue->long_text = 'OBS Studio requires a DirectX 10.1 (Windows) or OpenGL 3.2 (Mac, Linux) compatible video card (GPU).  Your graphics card does not support DirectX 10.1, so your computer cannot run OBS.';
		$issue->proposal = 'Consider upgrading your computer hardware or getting a new computer.';
		$issue->tags = 'renderer;D3D;DirectX';
		$this->add_issue($issue);
	} elseif ($this->obs_log_object->get_renderer_feature_level() < \RytoEX\OBS\LogAnalyzer\Log\OBSStudioLog::D3D_FEATURE_LEVEL_11_0) {
		// @todo: investigate the actual effects and downsides of lacking full DX11 support
		$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
		$issue->severity = 'Minor';
		$issue->short_name = 'DirectX 11 not supported';
		$issue->long_text = 'Your graphics card is fairly old, and it may not be able to smoothly run OBS Studio.';
		$issue->proposal = 'Consider upgrading your computer hardware or getting a new computer.';
		$issue->tags = 'renderer;D3D;DirectX';
		$this->add_issue($issue);
	}
	unset($issue);
}
if ($this->obs_log_object->is_renderer_opengl() && $this->obs_log_object->is_os_windows()) {
	$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue('Major',
		'Using OpenGL renderer on Windows',
		'You are using the OpenGL renderer on Windows, which can decrease capture performance due to the lack of specific features used to maximize capture performance.',
		'Use the Direct3D 11 renderer instead.  You can change the renderer in OBS Studio Settings > Advanced > Video > Renderer.');
	$issue->tags = 'renderer';
	$this->add_issue($issue);
	unset($issue);
}
if ($this->obs_log_object->is_renderer_on_igpu_not_discrete()) {
	$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
	$issue->severity = 'Minor';
	$issue->short_name = 'Using iGPU instead of discrete GPU';
	$issue->long_text = "OBS has loaded the renderer on your system's integrated GPU (iGPU) instead of an available discrete graphics card.  If the iGPU is weaker than your discrete graphics card, this could cause performance issues.  It can also cause issues with game capture working correctly on laptops.";
	$issue->proposal = 'Consider forcing OBS to run on your discrete graphics card.  See this link for details:  <a href="https://github.com/obsproject/obs-studio/wiki/Laptop-Performance-Issues">https://github.com/obsproject/obs-studio/wiki/Laptop-Performance-Issues</a>';
	$issue->tags = 'renderer';
	$this->add_issue($issue);
	unset($issue);
}

// memory leaks are generally low priority
if (isset($this->obs_log_object->memory_leaks) && $this->obs_log_object->memory_leaks > 0) {
	if ($this->obs_log_object->memory_leaks >= 100) {
		$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
		$issue->severity = 'Critical';
		$issue->short_name = 'Has memory leaks';
		$issue->long_text = 'Your log shows a significant number of memory leaks.';
		$issue->proposal = 'Please ensure that you are running an official release of OBS.  If you are running an official release, please report this on the Support Forums.';
	} elseif ($this->obs_log_object->memory_leaks >= 10) {
		$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
		$issue->severity = 'Major';
		$issue->short_name = 'Has memory leaks';
		$issue->long_text = 'Your log shows some memory leaks.';
		$issue->proposal = 'Please ensure that you are running an official release of OBS.  If you are running an official release, please report this on the Support Forums.';
	} elseif ($this->obs_log_object->memory_leaks > 1) {
		$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
		$issue->severity = 'Minor';
		$issue->short_name = 'Has memory leaks';
		$issue->long_text = 'Your log shows some memory leaks.';
		$issue->proposal = 'Please ensure that you are running an official release of OBS.';
	} elseif (false) {
		// check some "paranoid" setting here to warn on all leaks
		$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
		$issue->severity = 'Minor';
		$issue->short_name = 'Has memory leaks';
		$issue->long_text = 'Your log shows some memory leaks.';
		$issue->proposal = 'Please ensure that you are running an official release of OBS.';
	} else {
		// no leaks or none worth flagging
		// do anything?
	}

	if (isset($issue)) {
		$this->add_issue($issue);
		unset($issue);
	}
}

/* @todo: Check VLC plugin status
 * minor by default, major if user is having issues with VLC source?
 */

/* @todo: Check MP4 recording */
