<?php
function has_slot($slot) {
	return AFK_Registry::slots()->has($slot);
}

function get_slot($slot, $default='') {
	AFK_Registry::slots()->get($slot, $default);
}

function start_slot($slot) {
	AFK_Registry::slots()->start($slot);
}

function end_slot() {
	AFK_Registry::slots()->end();
}

function end_slot_append() {
	AFK_Registry::slots()->end_append();
}
