<?php

namespace RytoEX\OBS\LogAnalyzer\Encoder\Video;

/*
18:30:06.541: [x264 encoder: 'recording_h264'] preset: veryfast
18:30:06.541: [x264 encoder: 'recording_h264'] profile: high
18:30:06.542: [x264 encoder: 'recording_h264'] settings:
18:30:06.542: 	rate_control: CRF
18:30:06.542: 	bitrate: 0
18:30:06.542: 	buffer size: 0
18:30:06.542: 	crf: 20
18:30:06.542: 	fps_num: 60
18:30:06.542: 	fps_den: 1
18:30:06.542: 	width: 1920
18:30:06.542: 	height: 1080
18:30:06.542: 	keyint: 120
18:30:06.542: 	vfr: off
18:30:06.542:
18:30:06.552: [Media Foundation AAC: 'Stream Audio']: encoder created
18:30:06.552: 	bitrate: 160
18:30:06.552: 	channels: 2
18:30:06.552: 	sample rate: 44100
18:30:06.552: 	bits-per-sample: 16
18:30:06.552:
*/
class VideoEncoder
{
	public $name;
	public $rate_control;
	public $bitrate;
	public $cqp;
	public $crf;
	public $keyint;
	public $preset;
	public $profile;
	public $level;
	public $width;
	public $height;
	// amd: ???
	// nvenc: 2pass, bframes, gpu
	// x264: buffer size, fps_num, fps_den, tune, vfr, x264 opts
}
