<?php
function cache($id, $max_age=300) {
	AFK_Cache::start($id, $max_age);
}

function cache_end() {
	AFK_Cache::end();
}
?>
