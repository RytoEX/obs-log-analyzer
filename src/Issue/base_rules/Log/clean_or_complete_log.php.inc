// Clean/Complete log
if ($this->is_complete() === false) {
	$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
	$issue->severity = 'Minor';
	$issue->short_name = 'Your log file is incomplete';
	$issue->long_text = 'Your log file is incomplete because it lacks important profiler data.';
	$issue->proposal = 'Please provide a log that contains profiler data to help ensure a proper diagnosis.';
	$this->issues[] = $issue;
	unset($issue);
}
if ($this->is_clean() === false) {
	$issue = new \RytoEX\OBS\LogAnalyzer\Issue\BasicIssue();
	$issue->severity = 'Minor';
	$issue->short_name = 'Your log file is not clean';
	$issue->long_text = 'Your log file is not considered "clean" because it has multiple recording/streaming sessions, it has incomplete recording/streaming sessions, or you changed the audio/video settings after initialization.';
	$issue->proposal = 'Please provide a clean log.  To make a clean log file, first restart OBS, then start your stream/recording for about 30 seconds and stop it again. Make sure you replicate any issues as best you can, which means having any games/apps open and captured, etc.';
	$this->issues[] = $issue;
	unset($issue);
}
