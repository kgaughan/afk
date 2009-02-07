<ul class="pagination">
<?php for ($i = 1; $i <= $limit; $i++) { ?>
	<?php if ($i == $page) { ?>
		<li><strong><?php echo $i ?></strong></li>
	<?php } else { ?>
		<li><a href="?<?php ee($name) ?>=<?php ee($i, $params) ?>"><?php echo $i ?></a></li>
	<?php } ?>
<?php } ?>
</ul>
