function fetchInternalFrames() {
	var frames = [];
	var tb = document.getElementById('traceback');
	for (var i in tb.childNodes) {
		var ul = tb.childNodes[i];
		if (ul.nodeType == 1 && ul.className == 'traceback') {
			for (var j in ul.childNodes) {
				var li = ul.childNodes[j];
				if (li.nodeType == 1 && /\binternal\b/.test(li.className)) {
					frames[frames.length] = li;
				}
			}
		}
	}
	return frames;
}

function assignHandlers(elems, evt, handler) {
	for (var i in elems) {
		elems[i][evt] = handler;
	}
}

window.onload = function() {
	var frames = fetchInternalFrames();
	assignHandlers(frames, 'onclick', function(e) {
		var re = / hidden\b/;
		if (re.test(this.className)) {
			this.className = this.className.replace(re, '');
		} else {
			this.className += ' hidden';
		}
	});
	window.onload = null;
	window.onunload = function() {
		window.onunload = null;
		assignHandlers(frames, 'onclick', null);
	};
};
