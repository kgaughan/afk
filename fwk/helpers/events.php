<?php
function trigger_event($event, $value, $with_universal_observers=false) {
	return AFK_Registry::broker()->trigger($event, $value, $with_universal_observers);
}

function announce_event($event, $value) {
	AFK_Registry::broker()->announce($event, $value);
}

function register_listener($event, $callback, $singleton=false) {
	AFK_Registry::broker()->register($event, $callback, $singleton);
}
?>
