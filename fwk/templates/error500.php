<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<html lang="en"><head>

	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta name="robots" content="NONE,NOARCHIVE">
	<title><?php ee($page_title) ?></title>
	<style type="text/css">
	/* <[CDATA[ */
	html * { padding:0; margin:0 }
	body * { padding:10px 20px }
	body * * { padding:0 }
	body { font:small Calibri, Helvetica, Arial, sans-serif; line-height:1.5 }
	body>div { border-bottom:1px solid #ddd }
	h1 { font-weight:normal; line-height:1.2 }
	h2 { margin-bottom:.4em; line-height:1.2 }
	h2 span { font-size:80%; color:#666; font-weight:normal }
	ul.traceback { list-style-type:none }
	ul.traceback li.frame { margin-bottom:1em }
	ul.traceback li.frame span.method { float:right; font-weight:bold }
	div.context { margin:0 0 10px 0; background:white; border:1px solid #ddd }
	div.context ol { padding-left:6em; margin:1px; list-style-position:outside }
	div.context ol li { font-family:monospace; white-space:pre; color:#666 }
	div.context ol li.ctx-line { color:black; background-color:#eeb }
	li.hidden ol li.otr-line { display:none }
	li.hidden div.context { opacity:0.33 }
	li.hidden:hover div.context { opacity:1 }
	li.internal:active div.context { border-color:#aa7 }
	ul.traceback li.external div.context { border-color:#aa7 }
	ul.traceback li.internal { cursor:pointer }
	#summary { background:#ffc }
	#summary h2 { font-weight:normal; color:#666 }
	#traceback { background:#eee }
	#request-info { background:#f6f6f6 }
	address { overflow:hidden }
	address img { float:right }
	/* ]]> */
	</style>
	<script type="text/javascript">
	// <![CDATA[
	function fetchInternalFrames() {
		var frames = [];
		var tb = document.getElementById('traceback');
		for (var i in tb.childNodes) {
			var ul = tb.childNodes[i];
			if (ul.nodeType == 1 && ul.className == 'traceback') {
				for (var j in ul.childNodes) {
					var li = ul.childNodes[j];
					if (li.nodeType == 1 && /\binternal\b/.test(li.className)) {
						frames[frames.length] = li;
					}
				}
			}
		}
		return frames;
	}
	function assignHandlers(elems, evt, handler) {
		for (var i in elems) {
			elems[i][evt] = handler;
		}
	}
	window.onload = function() {
		var frames = fetchInternalFrames();
		assignHandlers(frames, 'onclick', function(e) {
			var re = / hidden\b/;
			if (re.test(this.className)) {
				this.className = this.className.replace(re, '');
			} else {
				this.className += ' hidden';
			}
		});
		window.onload = null;
		window.onunload = function() {
			window.onunload = null;
			assignHandlers(frames, 'onclick', null);
		};
	};
	// ]]>
	</script>

</head><body>

<div id="summary">
	<h1><?php ee($page_title) ?></h1>
	<h2><?php ee($message) ?></h2>
</div>

<div id="traceback">
	<h2>Traceback <span>(<?php echo count($traceback) ?> significant frames, innermost first, click to expand AFK internals)</span></h2>
	<ul class="traceback"><?php $this->render_each('error500-frame', $traceback) ?></ul>
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
