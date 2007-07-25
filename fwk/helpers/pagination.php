<?php
function paginate($name, $limit, $extra=array()) {
	$ctx = AFK_Registry::context();
	$params = $ctx->to_query($extra);
	if ($params != '') {
		$params = '&amp;' . $params;
	}
	$page = coalesce($ctx->__get($name), 1);

	echo '<ul class="pagination">';
	for ($i = 1; $i <= $limit; $i++) {
		echo '<li>';
		if ($i == $page) {
			echo "<strong>$i</strong>";
		} else {
			echo '<a href="?', e($name), '=', $i, $params, '">', $i, '</a>';
		}
		echo '</li>';
	}
	echo '</ul>';
}
?>
