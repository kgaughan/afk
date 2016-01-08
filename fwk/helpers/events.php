<?php
function trigger_event($event, $value)
{
	return AFK_Registry::_('broker')->trigger($event, $value);
}

function register_listener($event, $callback, $singleton=false)
{
	AFK_Registry::_('broker')->register($event, $callback, $singleton);
}
