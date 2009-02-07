<?php
function paginate($name, $limit, $extra=array()) {
	$ctx = AFK_Registry::context();
	$params = $ctx->to_query($extra, '&');
	$page = coalesce($ctx->__get($name), 1);

	$t = new AFK_TemplateEngine();
	$t->render('pagination', compact('page', 'limit', 'name', 'params'));
}
