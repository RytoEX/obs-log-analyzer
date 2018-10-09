# obs-log-analyzer
A log analyzer backend for OBS Studio logs


## What is this?

This is a log analyzer backend for [OBS Studio](https://obsproject.com/) 
logs. This backend is written in PHP, with a focus on object-oriented 
and class-based code. Since this is a backend, it must be called or 
invoked by some other code to get any results from it.


## Why PHP?

I chose PHP because it's the language I am most familiar with. I also 
wanted an excuse to practice namespaces and PSR-4 autoloading.


## Is this officially related to OBS Project?

No. This is a personal project that I wrote because I wanted to see if I 
could.


## How do I use it?

The simplest way to invoke the analyzer is this:

```
// Create a new OBSLog object.
$tmp_obs_log = new \RytoEX\OBS\LogAnalyzer\Log\Generic($obs_log_text);

// Do some simple processing.
// Check if this is a valid regular or crash log for OBS Classic or OBS Studio.
$res = $tmp_obs_log->process();

// Get a class object for the appropriate OBS log type.
$obs_log = $tmp_obs_log->make_log_object();

// Really process the log.
$res = $obs_log->process_log();
```

At this point, you can manually process the data from the log file, or 
you can output a list of detected issues with suggested resolutions and 
additional information. To retrieve issue data as JSON, use this:

```
$json_output = $obs_log->build_json_result();
```
