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
	address {
		overflow: hidden;
	}
	address img {
		float: right;
	}
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
	<?php if (empty($frame['file'])) continue ?>
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
	</li>
<?php } ?>
  </ul>
</div>

<div id="request-info">
<h2>Request Context</h2>
<?php AFK::dump($ctx) ?>
</div>

<address>
<img src="<?php ee($ctx->application_root()) ?>assets-fwk/images/logo.png"
	width="205" height="53" alt="">
Powered by
AFK/<?php echo AFK_VERSION ?>:
<?php
$quips = array(
	"Kickin' the rest over fences!",
	"I can't believe it's not a framework!",
	'Fighting for your right to rock out!',
	'A Quinn/Martin production.',
	'Fighting for bovine freedom!',
	"Launch all 'Zigs'!",
	'For great freedom!',
	'Poking badgers with spoons for fun and profit!',
	'PROFIT!!!',
	"The bicycle to PHP's training wheels.",
	'Making PHP suck less.',
	"We don' need no steeking frameworks!",
	'Hmmm... shiny!',
	'Making your day suck that little bit less.',
	'More than just CRUD.',
	'Does exactly what it says in the README.',
	'Damn hippies!',
	'Ham sandwich!',
	'Oh! And there will be snacks, there will!',
	"Nine out of ten cats don't have a clue what it's for.",
	'Badger! Badger! Badger! Badger! Badger! Badger! Badger! Badger! Badger! Mushroom! Mushroom!!',
	'A product of Irish design since 1979.', // With apologies to DHH.
	'Yet another framework framework.');
ee($quips[mt_rand(0, count($quips) - 1)]);
?>
<br>
Copyright &copy; Keith Gaughan, <?php echo date('Y') ?>.
</address>

</body></html>
