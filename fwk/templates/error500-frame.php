<li class="frame <?php echo substr($file, 0, 4) == 'AFK:' ? 'internal hidden' : 'external' ?>">
<div>
<span class="method"><?php
if (empty($method)) {
	echo '&mdash;';
} else {
	ee($method);
}
?></span>
<code><?php ee($file) ?></code>
</div>
<div class="context">
	<ol>
	<?php foreach ($context as $i => $text) { ?>
	<li value="<?php echo $i ?>" class="<?php echo $i == $line ? 'ctx' : 'otr' ?>-line"><?php ee($text) ?></li>
	<?php } ?>
	</ol>
</div>
</li>
