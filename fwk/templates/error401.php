<?php $this->with_envelope() ?>
<h2>Oi! You&rsquo;re not meant to be here!</h2>
<p>&lsquo;Bother&rsquo;, said Pooh as the nightclub bouncers dragged him down
an alley...</p>
<?php if (!empty($message)) { ?>
	<hr/>
	<p>The error message was: <?php ee($message) ?></p>
<?php } ?>
