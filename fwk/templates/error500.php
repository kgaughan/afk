<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<?php AFK::load_helper('exceptions') ?>
<html lang="en"><head>

	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta name="robots" content="NONE,NOARCHIVE">
	<title><?php ee(get_class($_exception)) ?> in <?php ee(truncate_filename($_exception->getFile())) ?></title>
	<style type="text/css">
	html * { padding:0; margin:0; }
	body * { padding:10px 20px; }
	body * * { padding:0; }
	body { font:small Calibri, Helvetica, Arial, sans-serif; line-height: 1.5 }
	body>div { border-bottom:1px solid #ddd; }
	h1 { font-weight:normal; line-height: 1.2 }
	h2 { margin-bottom:.4em; line-height: 1.2 }
	h2 span { font-size:80%; color:#666; font-weight:normal; }
	ul.traceback { list-style-type:none; }
	ul.traceback li.frame { margin-bottom:1em; }
	ul.traceback li.frame span.method { float: right; font-weight: bold }
	div.context { margin: 0 0 10px 0; background: white; border: 1px solid #ddd }
	div.context ol { padding-left:30px; margin:0 10px; list-style-position: inside; }
	div.context ol li { font-family:monospace; white-space:pre; color:#666; }
	div.context ol li.context-line { color:black; background-color:#eeb; }
	#summary { background: #ffc; }
	#summary h2 { font-weight: normal; color: #666; }
	#traceback { background:#eee; }
	#request-info { background:#f6f6f6; }
	</style>

</head><body>

<div id="summary">
<h1><?php ee(get_class($_exception)) ?> in <?php ee(truncate_filename($_exception->getFile())) ?></h1>
<h2><?php ee($_exception->getMessage()) ?></h2>
</div>

<div id="traceback">
<h2>Traceback <span>(innermost first)</span></h2>
<ul class="traceback">
	<li class="frame">
	<code><?php ee(truncate_filename($_exception->getFile())) ?></code>
	<div class="context">
	<?php list($start, $context) = get_context_lines($_exception->getFile(), $_exception->getLine()) ?>
		<ol start="<?php echo $start ?>">
		<?php foreach ($context as $line_no=>$line) { ?>
		<li<?php if ($line_no == $_exception->getLine()) echo ' class="context-line"' ?>><?php ee($line) ?></li>
		<?php } ?>
		</ol>
	</div>
	</li>
<?php foreach ($_exception->getTrace() as $i=>$frame) { ?>
	<li class="frame">
	<?php if (empty($frame['file'])) { ?>
	<?php AFK::dump($frame) ?>
	<?php } else { ?>
	<span class="method"><?php ee(frame_to_name($frame)) ?>()</span>
	<code><?php ee(truncate_filename($frame['file'])) ?></code>
	<div class="context">
	<?php list($start, $context) = get_context_lines($frame['file'], $frame['line']) ?>
		<ol start="<?php echo $start ?>">
		<?php foreach ($context as $line_no=>$line) { ?>
		<li<?php if ($line_no == $frame['line']) echo ' class="context-line"' ?>><?php ee($line) ?></li>
		<?php } ?>
		</ol>
	</div>
	<?php } ?>
	</li>
<?php } ?>
  </ul>
</div>

<div id="request-info">
<h2>Request Context</h2>
<?php AFK::dump($ctx) ?>
</div>

</body></html>
