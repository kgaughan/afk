<?php // Adapted from Django <djangoproject.com> via web.py ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<html lang="en"><head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta name="robots" content="NONE,NOARCHIVE">
	<title><?php ee(get_class($_exception)) ?> in <?php ee($_exception->getFile()) ?></title>
  <style type="text/css">
    html * { padding:0; margin:0; }
    body * { padding:10px 20px; }
    body * * { padding:0; }
    body { font:small sans-serif; }
    body>div { border-bottom:1px solid #ddd; }
    h1 { font-weight:normal; }
    h2 { margin-bottom:.8em; }
    h2 span { font-size:80%; color:#666; font-weight:normal; }
    h3 { margin:1em 0 .5em 0; }
    h4 { margin:0 0 .5em 0; font-weight: normal; }
    table { 
        border:1px solid #ccc; border-collapse: collapse; background:white; }
    tbody td, tbody th { vertical-align:top; padding:2px 3px; }
    thead th { 
        padding:1px 6px 1px 3px; background:#fefefe; text-align:left; 
        font-weight:normal; font-size:11px; border:1px solid #ddd; }
    tbody th { text-align:right; color:#666; padding-right:.5em; }
    table.vars { margin:5px 0 2px 40px; }
    table.vars td, table.req td { font-family:monospace; }
    table td.code { width:100%;}
    table td.code div { overflow:hidden; }
    table.source th { color:#666; }
    table.source td { 
        font-family:monospace; white-space:pre; border-bottom:1px solid #eee; }
    ul.traceback { list-style-type:none; }
    ul.traceback li.frame { margin-bottom:1em; }
    div.context { margin: 10px 0; }
    div.context ol { 
        padding-left:30px; margin:0 10px; list-style-position: inside; }
    div.context ol li { 
        font-family:monospace; white-space:pre; color:#666; }
    div.context ol li.context-line { color:black; background-color:#ccc; }
    div.commands { margin-left: 40px; }
    div.commands a { color:black; text-decoration:none; }
    #summary { background: #ffc; }
    #summary h2 { font-weight: normal; color: #666; }
    #explanation { background:#eee; }
    #template, #template-not-exist { background:#f6f6f6; }
    #template-not-exist ul { margin: 0 0 0 20px; }
    #traceback { background:#eee; }
    #requestinfo { background:#f6f6f6; padding-left:20px; }
    #summary table { border:none; background:transparent; }
    #requestinfo h2, #requestinfo h3 { position:relative; margin-left:-0px; }
    #requestinfo h3 { margin-bottom:-1em; }
    .error { background: #ffc; }
    .specific { color:#cc3300; font-weight:bold; }
  </style>
</head>
<body>

<div id="summary">
	<h1><?php ee(get_class($_exception)) ?> in <?php ee($_exception->getFile()) ?></h1>
	<h2><?php ee($_exception->getMessage()) ?></h2>
</div>

<div id="traceback">
<h2>Traceback <span>(innermost first)</span></h2>
<ul class="traceback">
	<li class="frame">
	<code><?php ee($_exception->getFile()) ?></code>
	<div class="context" id="c0">
	<?php list($start, $context) = AFK::get_context_lines($_exception->getFile(), $_exception->getLine()) ?>
		<ol start="<?php echo $start ?>">
		<?php foreach ($context as $line_no=>$line) { ?>
		<li<?php if ($line_no == $_exception->getLine()) echo ' class="context-line"' ?>><?php ee($line) ?></li>
		<?php } ?>
		</ol>
	</div>
	</li>
<?php foreach ($_exception->getTrace() as $i=>$frame) { ?>
	<li class="frame">
	<code><?php ee($frame['file']) ?></code><br>
	<code><?php ee(AFK::frame_to_name($frame)) ?></code>
	<div class="context" id="c<?php echo $i ?>">
	<?php list($start, $context) = AFK::get_context_lines($frame['file'], $frame['line']) ?>
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

<div id="requestinfo">
<h2>Request Context</h2>
<?php AFK::dump($ctx) ?>
</div>

</body>
</html>
