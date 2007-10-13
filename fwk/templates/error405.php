<?php $this->with_envelope() ?>
<p>&lsquo;Bother,&rsquo; said Pooh, as he attempted to use an invalid HTTP
method&mdash;<?php echo strtoupper($ctx->method()) ?>, if you must
know&mdash;on a resource that doesn&rsquo;t support it.</p>
<?php if (!empty($message)) { ?>
	<hr/>
	<p>The error message was: <?php ee($message) ?></p>
<?php } ?>
