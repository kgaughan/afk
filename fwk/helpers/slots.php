<?php
function has_slot($slot) {
	return AFK_Registry::_('slots')->has($slot);
}

function get_slot($slot, $default='') {
	return AFK_Registry::_('slots')->get($slot, $default);
}

function start_slot($slot) {
	AFK_Registry::_('slots')->start($slot);
}

function end_slot() {
	AFK_Registry::_('slots')->end();
}

function end_slot_append() {
	AFK_Registry::_('slots')->end_append();
}
