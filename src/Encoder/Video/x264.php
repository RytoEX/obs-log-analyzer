<?php

namespace RytoEX\OBS\LogAnalyzer\Encoder\Video;

class x264 extends \RytoEX\OBS\LogAnalyzer\Encoder\Video\VideoEncoder
{
	public $buffer_size;
	public $fps_num;
	public $fps_den;
	public $tune;
	public $vfr;
	public $x264_opts;
}
