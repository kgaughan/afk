<?php $ctx->header('Content-Type: text/html; charset=utf-8') ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<html lang="en"><head>

	<title><?php ee($page_title) ?></title>
	<meta name="robots" content="NONE,NOARCHIVE">
	<?php stylesheets(array('all' => 'afk/traceback')) ?>
	<?php javascript(array('afk/js/traceback')) ?>

</head><body>

<div id="summary">
	<h1><?php ee($page_title) ?></h1>
	<h2><?php ee($message) ?></h2>
</div>

<div id="traceback">
	<h2>Traceback <span>(<?php echo count($traceback) ?> frames, innermost first, click to expand AFK internals)</span></h2>
	<ul class="traceback"><?php $this->render_each('error500-frame', $traceback) ?></ul>
</div>

<div id="request-info">
	<h2>Request Context</h2>
	<?php
	$ctx->__unset('traceback');
	$ctx->__unset('page_title');
	AFK::dump($ctx);
	?>
</div>

<address>
<img src="<?php ee($ctx->application_root()) ?>assets/afk/images/logo.png"
	width="205" height="53" alt="">
This error page is based on the 500 page from
<a href="http://djangoproject.com/">Django</a>,
designed by <a href="http://wilsonminer.com/">Wilson Miner</a>.
<br>
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
