<?php
function cache($id, $max_age=300) {
	return AFK_Registry::_('output_cache')->start($id, $max_age);
}

function cache_end() {
	AFK_Registry::_('output_cache')->end();
}

function cache_remove($id) {
	return AFK_Registry::_('output_cache')->remove($id);
}

function cache_install(AFK_Cache $cache) {
	AFK_Registry::_('output_cache')->set_backend($cache);
}
