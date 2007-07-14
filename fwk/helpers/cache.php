<?php
function cache($id, $max_age=300) {
	return AFK_OutputCache::start($id, $max_age);
}

function cache_end() {
	AFK_OutputCache::end();
}
?>
