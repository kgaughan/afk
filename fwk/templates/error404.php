<?php $this->with_envelope() ?>
<?php AFK::load_helper('core', 'events') ?>

<h2>Lost?</h2>

<p>&lsquo;Bother,&rsquo; said Pooh, as he discovered he&rsquo;d wandered into
the bad part of town...</p>

<?php if (empty($ctx->HTTP_REFERER)) { ?>
	<p>It appears you&rsquo;ve mistyped the address of the page you&rsquo;re
	looking for. Double-check it, and correct any mistakes.</p>
	<p>If you&rsquo;ve tried to open this page from a bookmark, it might be
	incorrect or obsolete.</p>
<?php } elseif ($ctx->is_referrer_this_host()) { ?>
	<p>Oops! It looks like we&rsquo;ve messed up. You selected a broken link
	on this site.</p>
	<?php
	list($unhandled) = trigger_event(
		'afk:404',
		$ctx->as_array('HTTP_REFERER', 'REQUEST_URI')
	);
	?>
	<?php if (!$unhandled) { ?>
		<p>We&rsquo;ve taken note of this, and will investigate the
		problem.</p>
	<?php } ?>
<?php } else { ?>
	<p>The site you came from seems to have a bad link to this site. You might
	want to contact them to have them check this out.</p>
<?php } ?>

<?php if (!empty($message)) { ?>
	<hr/>
	<p>The error message was: <?php ee($message) ?></p>
<?php } ?>
