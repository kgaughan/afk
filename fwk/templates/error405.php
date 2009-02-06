<?php $this->with_envelope() ?>
<h2>Your Kung Fu is not strong!</h2>
<p>&lsquo;Bother,&rsquo; said Pooh, as he attempted to use an invalid HTTP
method&mdash;<?php echo strtoupper($ctx->method()) ?>, if you must
know&mdash;on a resource that doesn&rsquo;t support it.</p>

<p>Ok, why don&rsquo;t <em>you</em> think of a witty way to put it. Hrumph.</p>

<?php if (!empty($message)) { ?>
	<hr/>
	<p>The error message was: <?php ee($message) ?></p>
<?php } ?>
