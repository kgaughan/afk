<?php $this->with_envelope() ?>
<?php if (!empty($message)) { ?>
	<p><?php ee($message) ?></p>
<?php } else { ?>
	<p>You appear to have stumbled across a page that doesn't exist. Joy!</p>
<?php } ?>
