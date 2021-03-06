<?php
function paginate($name, $limit, array $extra=array())
{
	$ctx = AFK_Registry::context();
	$params = $ctx->to_query($extra, '&');
	$page = AFK::coalesce($ctx->__get($name), 1);

	$t = AFK_Registry::_('template_engine');
	$t->render('pagination', compact('page', 'limit', 'name', 'params'));
}
