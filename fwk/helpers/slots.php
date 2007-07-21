<?php
function has_slot($slot) {
	return AFK_Slots::has($slot);
}

function get_slot($slot, $default='') {
	AFK_Slots::get($slot, $default);
}

function start_slot($slot) {
	AFK_Slots::start($slot);
}

function end_slot() {
	AFK_Slots::end();
}

function end_slot_append() {
	AFK_Slots::end_slot_append();
}
?>
