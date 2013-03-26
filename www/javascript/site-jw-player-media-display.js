/**
 * Class for managing media players
 *
 * @copyright 2011-2013 silverorange
 */
function SiteJwPlayerMediaDisplay(media_id)
{
	this.media_id = media_id;

	this.sources = [];
	this.images  = [];
	this.valid_mime_types = [];

	this.skin = null;
	this.image = null;
	this.duration = null;
	this.aspect_ratio = null;
	this.start_position = 0;
	this.record_end_point = false;

	this.upgrade_message = null;
	this.on_complete_message = null;
	this.resume_message =
		'<p>You’ve previously watched part of this video.</p>' +
		'<p>Would you like to:</p>';

	// whether or not to show the on-complete-message when the video loads.
	// this is useful if you want to remind the user they've seen the video
	// before
	this.display_on_complete_message_on_load = false;

	this.on_ready_event = new YAHOO.util.CustomEvent('on_ready', this, true);
	this.end_point_recorded_event =
		new YAHOO.util.CustomEvent('end_point_recorded', this, true);

	this.place_holder = document.createElement('div');

	SiteJwPlayerMediaDisplay.players.push(this);

	YAHOO.util.Event.onDOMReady(this.init, this, true);
}

SiteJwPlayerMediaDisplay.current_player_id = null;
SiteJwPlayerMediaDisplay.record_interval = 30; // in seconds
SiteJwPlayerMediaDisplay.players = [];

// {{{ SiteJwPlayerMediaDisplay.getPlayers()

// static method to get current players
SiteJwPlayerMediaDisplay.getPlayers = function()
{
	return SiteJwPlayerMediaDisplay.players;
}

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.init = function()

SiteJwPlayerMediaDisplay.prototype.init = function()
{
	this.container = document.getElementById('media_display_' + this.media_id);

	if (this.isVideoSupported()) {
		this.embedPlayer();
		this.drawDialogs();
	} else {
		var upgrade = document.createElement('div');
		upgrade.className = 'video-player-upgrade';
		upgrade.innerHTML = this.upgrade_message;
		document.getElementById(this.container).appendChild(upgrade);
	}
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.embedPlayer = function()

SiteJwPlayerMediaDisplay.prototype.embedPlayer = function()
{
	this.player = jwplayer(this.container.childNodes[0]).setup( {
		playlist: [{
			image: this.getImage(),
			sources: this.getSources() 
		}],
		skin:    this.skin,
		primary: 'flash', // to allow for RTMP streaming
		width:   '100%',
		height:  this.getPlayerHeight(),
		ga:      {} // this can be blank. JW Player will use the _gaq var.
	});

	//this.debug();

	var that = this;
	this.player.onReady(function() {
		that.on_ready_event.fire(this);
	});

	this.player.onFullscreen(function (e) {
		if (e.fullscreen) {
			that.handleFullscreen();
		}
	});

	YAHOO.util.Event.on(window, 'resize', function() {
		this.setPlayerDimensions();
	}, this, true);

	if (this.record_end_point == true) {
		this.recordEndPoint();
	}
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.isVideoSupported = function()

SiteJwPlayerMediaDisplay.prototype.isVideoSupported = function()
{
	// check to see if HTML5 video tag is supported
	var video_tag = document.createElement('video');

	var html5_video = false;
	if (video_tag.canPlayType) {
		for (var i = 0; i < this.valid_mime_types.length; i++) {
			var mime_type = this.valid_mime_types[i];
			if (video_tag.canPlayType(mime_type).replace(/no/, '')) {
				html5_video = true;
				break;
			}
		}
	}

	var flash9 = typeof(YAHOO.util.SWFDetect) === 'undefined'
		|| YAHOO.util.SWFDetect.isFlashVersionAtLeast(9);

	return (flash9 || html5_video);
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.addSource = function()

SiteJwPlayerMediaDisplay.prototype.addSource = function(
	source_uri, width, label)
{
	this.sources.push({file: source_uri, label: label, width: width});
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.addImage = function()

SiteJwPlayerMediaDisplay.prototype.addImage = function(
	image_uri, width)
{
	this.images.push({uri: image_uri, width: width});
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.getSources = function()

SiteJwPlayerMediaDisplay.prototype.getSources = function()
{
	var region = YAHOO.util.Dom.getRegion(this.container);
	var player_width = region.width;

	var default_source = null;
	var min_diff = null;
	for (var i = 0; i < this.sources.length; i++) {
		if (!this.sources[i].width) {
			continue;
		}

		var diff = Math.abs(this.sources[i].width - player_width);
		if (min_diff === null || diff < min_diff) {
			min_diff = diff;
			default_source = i;
		}
	}

	if (default_source !== null) {
		this.sources[default_source].default = true;
	}

	return this.sources;
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.getImage = function()

SiteJwPlayerMediaDisplay.prototype.getImage = function()
{
	var region = YAHOO.util.Dom.getRegion(this.container);
	var player_width = region.width;

	var default_image = null;
	var min_diff = null;
	for (var i = 0; i < this.images.length; i++) {
		var diff = Math.abs(this.images[i].width - player_width);
		if (min_diff === null || diff < min_diff) {
			min_diff = diff;
			default_image = i;
		}
	}

	return (default_image === null) ? null : this.images[default_image].uri;
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.addValidMimeType = function()

SiteJwPlayerMediaDisplay.prototype.addValidMimeType = function(mime_type)
{
	this.valid_mime_types.push(mime_type);
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.recordEndPoint = function()

SiteJwPlayerMediaDisplay.prototype.recordEndPoint = function()
{
	var that = this;

	var interval = SiteJwPlayerMediaDisplay.record_interval;
	var current_position = 0;
	var old_position = 0;

	function recordEndPoint() {
		function callback(response) {
			that.end_point_recorded_event.fire(response);
		}

		var client = new XML_RPC_Client('xml-rpc/media-player');
		client.callProcedure('recordEndPoint', callback,
			[that.media_id, current_position], ['int', 'double']);

		old_position = current_position;
	}

	function autoRecordEndPoint(ev) {
		current_position = ev.position;

		if (current_position > old_position + interval) {
			recordEndPoint();
		}
	}

	this.player.onTime(autoRecordEndPoint);
	this.player.onPause(recordEndPoint);
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.play = function()

SiteJwPlayerMediaDisplay.prototype.play = function()
{
	// pause other videos on the page
	this.pauseAll();

	// player not available yet or overlay shown
	if (this.overlay.style.display == 'block') {
		return;
	}

	var that = this;
	SiteJwPlayerMediaDisplay.current_player_id = this.player_id;

	// there's a strange jwplayer side-effect that can cause a buffering
	// video to not pause, thus switching videos can make two players
	// play at the same time.
	function checkIfCurrent() {
		if (that.player_id != SiteJwPlayerMediaDisplay.current_player_id) {
			this.player.stop();
		}
	}

	this.player.onPlay(checkIfCurrent);
	this.player.play(true);
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.pause = function()

SiteJwPlayerMediaDisplay.prototype.pause = function()
{
	// Both play() and pause() are toggles for jwplayer API unless state is
	// passed. pause(true) doesn't work correctly and is still a toggle, so
	// use play(false) instead.
	this.player.play(false);
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.pauseAll = function()

SiteJwPlayerMediaDisplay.prototype.pauseAll = function()
{
	var i = 0;
	while (typeof jwplayer(i) !== 'undefined' &&
		typeof jwplayer(i).play !== 'undefined') {

		jwplayer(i).play(false);
		i++;
	}
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.onPlay = function()

SiteJwPlayerMediaDisplay.prototype.onPlay = function(callback)
{
	this.player.onPlay(callback);
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.getPlayerHeight = function()

SiteJwPlayerMediaDisplay.prototype.getPlayerHeight = function()
{
	var region = YAHOO.util.Dom.getRegion(this.container);
	return parseInt(region.width / this.aspect_ratio);
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.setPlayerDimensions = function()

SiteJwPlayerMediaDisplay.prototype.setPlayerDimensions = function()
{
	this.player.resize('100%', this.getPlayerHeight());
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.handleFullscreen = function()

SiteJwPlayerMediaDisplay.prototype.handleFullscreen = function()
{
	var quality = this.player.getCurrentQuality();
	var width = Math.max(screen.width, screen.height);
	var levels = this.player.getQualityLevels();

	for (var i = levels.length - 1; i >= 0; i--) {
		if (levels[i].width < width && quality != i) {
			this.player.setCurrentQuality(i);
			break;
		}
	}
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.debug = function()

SiteJwPlayerMediaDisplay.prototype.debug = function()
{
	var debug_container = document.createElement('div');
	debug_container.style.padding = '4px';
	debug_container.style.position = 'absolute';
	debug_container.style.top = 0;
	debug_container.style.left = 0;
	debug_container.style.backgroundColor = 'rgba(255, 255, 255, 0.7)';
	this.container.appendChild(debug_container);

	var that = this;

	this.player.onMeta(function (v) {
		var meta = v.metadata;
		if (!meta.hasOwnProperty('bufferfill') ||
			!meta.hasOwnProperty('bandwidth')) {

			return;
		}

		var quality_levels = that.player.getQualityLevels();
		var current_level = quality_levels[meta.qualitylevel];
		debug_container.innerHTML = 'player-width: ' + meta.screenwidth + 'px' +
			'<br />transitioning: ' + ((meta.transitioning) ? 'yes' : 'no') +
			'<br />buffer-fill: ' + meta.bufferfill + 's' +
			'<br />quality-level: <strong>' + current_level.label +
				'</strong> (' + meta.qualitylevel + ')' +
			'<br />bandwidth: ' + Math.round(meta.bandwidth / 1024, 2) +
				' Mb/s (' + meta.bandwidth + ')';
	});
};

// }}}

// dialogs
// {{{ SiteJwPlayerMediaDisplay.prototype.drawDialogs = function()

SiteJwPlayerMediaDisplay.prototype.drawDialogs = function()
{
	this.overlay = document.createElement('div');
	this.overlay.style.display = 'none';
	this.overlay.className = 'overlay';
	this.overlay.appendChild(document.createTextNode(''));
	this.container.appendChild(this.overlay);

	// if the video has been watched before, and we're more than 60s from the
	// end or start, show a message allowing the viewed to resume or start
	// from the beginning
	if (this.start_position > 0) {
		this.appendResumeMessage();

		var that = this;
		this.player.onReady(function () {
			if (that.start_position > 60 &&
				that.start_position < that.duration - 60) {

				that.displayResumeMessage();
			}
		});
	}

	// when the video is complete, show a message to resume or go elsewhere
	if (this.on_complete_message !== null) {
		this.appendCompleteMessage();

		if (this.display_on_complete_message_on_load &&
			this.start_position > this.duration - 60) {
			this.displayCompleteMessage();
		}

		var that = this;
		this.player.onComplete(function () {
			that.displayCompleteMessage();
		});
	}
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.appendCompleteMessage = function()

SiteJwPlayerMediaDisplay.prototype.appendCompleteMessage = function()
{
	this.complete_overlay = document.createElement('div');
	this.complete_overlay.style.display = 'none';
	this.complete_overlay.className = 'overlay-content';
	this.complete_overlay.innerHTML = this.on_complete_message;

	var restart_link = document.createElement('a');
	restart_link.href = '#';
	restart_link.className = 'restart-video';
	restart_link.appendChild(document.createTextNode('Watch Again'));

	var that = this;
	YAHOO.util.Event.on(restart_link, 'click', function (e) {
		YAHOO.util.Event.preventDefault(e);

		if (that.player.getRenderingMode() == 'html5') {
			that.showVideo();
		}

		that.overlay.style.display = 'none';
		that.complete_overlay.style.display = 'none';
		that.player.seek(0);
	});

	this.complete_overlay.appendChild(restart_link);

	this.overlay.parentNode.appendChild(this.complete_overlay);
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.appendResumeMessage = function()

SiteJwPlayerMediaDisplay.prototype.appendResumeMessage = function()
{
	this.resume_overlay = document.createElement('div');
	this.resume_overlay.style.display = 'none';
	this.resume_overlay.className = 'overlay-content';
	this.resume_overlay.innerHTML = this.resume_message;

	var minutes = Math.floor(this.start_position / 60);
	var seconds = this.start_position % 60;
	seconds = (seconds < 10 ? '0' : '') + seconds;

	var resume_link = document.createElement('a');
	resume_link.href = '#';
	resume_link.className = 'resume-video';
	resume_link.appendChild(document.createTextNode(
		'Resume Where You Left Off (' + minutes + ':' + seconds + ')'));

	var that = this;
	YAHOO.util.Event.on(resume_link, 'click', function (e) {
		YAHOO.util.Event.preventDefault(e);
		that.overlay.style.display = 'none';
		that.resume_overlay.style.display = 'none';
		that.player.seek(that.start_position);

		if (that.player.getRenderingMode() == 'html5') {
			that.showVideo();
		}
	});

	this.resume_overlay.appendChild(resume_link);

	var restart_link = document.createElement('a');
	restart_link.href = '#';
	restart_link.className = 'restart-video';
	restart_link.appendChild(document.createTextNode(
		'Start From the Beginning'));

	YAHOO.util.Event.on(restart_link, 'click', function (e) {
		YAHOO.util.Event.preventDefault(e);
		that.overlay.style.display = 'none';
		that.resume_overlay.style.display = 'none';
		that.player.seek(0);

		if (that.player.getRenderingMode() == 'html5') {
			that.showVideo();
		}
	});

	this.resume_overlay.appendChild(restart_link);

	this.overlay.parentNode.appendChild(this.resume_overlay);
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.displayCompleteMessage = function()

SiteJwPlayerMediaDisplay.prototype.displayCompleteMessage = function()
{
	this.overlay.style.display = 'block';
	this.complete_overlay.style.display = 'block';

	if (this.player.getRenderingMode() == 'html5') {
		this.hideVideo();
	}
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.displayResumeMessage = function()

SiteJwPlayerMediaDisplay.prototype.displayResumeMessage = function()
{
	this.overlay.style.display = 'block';
	this.resume_overlay.style.display = 'block';

	if (this.player.getRenderingMode() == 'html5') {
		this.hideVideo();
	}
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.hideVideo = function()

SiteJwPlayerMediaDisplay.prototype.hideVideo = function()
{
	var player = this.container.childNodes[0];
	var region = YAHOO.util.Dom.getRegion(player);

	this.place_holder.style.width = region.width + 'px';
	this.place_holder.style.height = region.height + 'px';

	this.container.appendChild(this.place_holder);
	player.style.display = 'none';
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.showVideo = function()

SiteJwPlayerMediaDisplay.prototype.showVideo = function()
{
	var player = this.container.childNodes[0];
	player.style.display = 'block';
	this.container.removeChild(this.place_holder);
};

// }}}
