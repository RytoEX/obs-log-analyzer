<?php

// Try to identify common issues found in an OBS Studio log file.

// run from within RytoEX\OBS\LogAnalyzer\Issue\Handler
// use $this->obs_log_object instead of $this

/*
 * $this must be a \RytoEX\OBS\LogAnalyzer\Issue\Handler object
 * $this->obs_log_object must be a \RytoEX\OBS\LogAnalyzer\Log\OBSStudioLog object
 */

// AMF Intel OpenCL conflict
if ($this->obs_log_object->has_amf_intel_opencl_crash()) {
	$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
	$issue->severity = 'Critical';
	$issue->short_name = 'AMD AMF OpenCL conflict with Intel OpenCL';
	$issue->long_text = 'The AMD AMF Encoder Plugin has crashed.  For more information, please see <a href="https://github.com/Xaymar/obs-studio_amf-encoder-plugin/issues/317">this GitHub Issue</a>.';
	$issue->proposal = 'You can avoid this crash by using x264 instead of the AMD AMF encoder. If you still want to use the AMD AMF encoder, try the following steps:';
	$issue->steps = array('Update your AMD drivers to 18.3.4 or newer.',
		'If #1 doesn\'t fix the crash, try <a href="https://github.com/Xaymar/obs-studio_amf-encoder-plugin/wiki/Frequently-Asked-Questions#q-ffmpeg-shows-an-error-that-avcodec-avutil-and-avformat-are-missing-after-enabling-opencl-features">this</a>.',
		'If #2 doesn\'t fix it, then you\'ll have to avoid using the AMD AMF encoder until the crash condition is fixed by AMD or the encoder plugin.  Use x264 or QSV as your encoder.');
	$this->add_issue($issue);
	unset($issue);
}

// amf plugin generic
if ($this->obs_log_object->has_amf_plugin_startup_crash()) {
	$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
	$issue->severity = 'Critical';
	$issue->short_name = 'AMF plugin startup crash';
	$issue->long_text = 'The AMF Encoder Plugin has caused OBS to crash during startup.';
	$issue->proposal = 'If you do not use this plugin, try updating it or removing it.';
	$issue->steps = null;
	$this->add_issue($issue);
	unset($issue);
} elseif ($this->obs_log_object->has_amf_generic_crash()) {
	$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
	$issue->severity = 'Critical';
	$issue->short_name = 'AMF plugin crash';
	$issue->long_text = 'The AMF Encoder Plugin has caused OBS to crash.';
	$issue->proposal = 'Please consult the AMF Encoder plugin troubleshooting guide.';
	$issue->steps = array('Check <a href="https://support.amd.com/en-us/download">the AMD website</a> for new drivers.');
	$this->add_issue($issue);
	unset($issue);
}

// generic AMD driver crash
if ($this->obs_log_object->has_amd_driver_generic_crash()) {
	$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
	$issue->severity = 'Critical';
	$issue->short_name = 'AMD driver crash';
	$issue->long_text = 'The AMD driver has caused OBS to crash.';
	$issue->proposal = 'Try updating your AMD drivers.';
	$issue->steps = array('Check <a href="https://support.amd.com/en-us/download">the AMD website</a> for new drivers.');
	$this->add_issue($issue);
	unset($issue);
}

// generic VST crash
// @todo: identify crashing VST plugin
if ($this->obs_log_object->has_vst_crash()) {
	$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
	$issue->severity = 'Critical';
	$issue->short_name = 'VST plugin crash';
	$issue->long_text = 'A VST plugin has caused OBS to crash.';
	$issue->proposal = 'Try disabling the VST plugin.';
	$issue->steps = null;
	$this->add_issue($issue);
	unset($issue);
}


// external app interference
// Dell Backup and Recovery
if ($this->obs_log_object->has_dell_backup_and_recovery_crash()) {
	$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
	$issue->severity = 'Critical';
	$issue->short_name = 'Dell Backup and Recovery';
	$issue->long_text = 'Dell Backup and Recovery has caused OBS to crash.';
	$issue->proposal = 'If you need Dell Backup and Recovery, try <a href="http://www.dell.com/support/article/us/en/04/SLN298526">updating it</a>.  If you do not need it, try uninstalling it.';
	$issue->steps = null;
	$this->add_issue($issue);
	unset($issue);
}


if ($this->obs_log_object->has_personify_crash()) {
	$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
	$issue->severity = 'Critical';
	$issue->short_name = 'Personify';
	$issue->long_text = 'Personify has caused OBS to crash.';
	$issue->proposal = 'If you use Personify, you can try to update it.  If you do not use it, you can uninstall it.';
	$issue->steps = null;
	$this->add_issue($issue);
	unset($issue);
}
