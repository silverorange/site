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
	this.stretching = null;
	this.image = null;
	this.duration = null;
	this.aspect_ratio = [];
	this.start_position = 0;
	this.record_end_point = false;
	this.space_to_pause = false;
	this.swf_uri = null;
	this.vtt_uri = null;

	this.menu_title = null;
	this.menu_link = null;

	this.location_identifier = null;

	this.upgrade_message = null;
	this.on_complete_message = null;
	this.resume_message =
		'<p>You’ve previously watched part of this video.</p>' +
		'<p>Would you like to:</p>';

	this.rtmp_error_message =
		'<h3>We can’t stream video to you</h3>' +
		'<p>Unfortunately, your firewall seems to be blocking us. To work ' +
		'around this, try switching to a browser that supports HTML5 ' +
		'video, like the latest version of Internet Explorer, Chrome, or ' +
		'Safari.</p>';

	this.android_rtmp_error_message =
		'<h3>We can’t stream video to you</h3>' +
		'<p>Unfortunately, your firewall seems to be blocking us.</p>';

	// whether or not to show the on-complete-message when the video loads.
	// this is useful if you want to remind the user they've seen the video
	// before
	this.display_on_complete_message_on_load = false;

	this.on_ready_event = new YAHOO.util.CustomEvent('on_ready', this, true);
	this.end_point_recorded_event =
		new YAHOO.util.CustomEvent('end_point_recorded', this, true);

	this.place_holder = document.createElement('div');
	this.seek_done = false;

	SiteJwPlayerMediaDisplay.players.push(this);

	YAHOO.util.Event.onDOMReady(this.init, this, true);
}

SiteJwPlayerMediaDisplay.primaryPlayerType = 'flash'; // to allow for RTMP streaming
SiteJwPlayerMediaDisplay.current_player_id = null;
SiteJwPlayerMediaDisplay.record_interval = 30; // in seconds
SiteJwPlayerMediaDisplay.players = [];

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
		this.container.appendChild(upgrade);

		var that = this;
		function resizeUpgradeContainer() {
			var container_height = that.getPlayerHeight();
			that.container.style.position = 'relative';
			that.container.style.height = container_height + 'px';

			var upgrade_height = YAHOO.util.Dom.getRegion(upgrade).height;
			upgrade.style.position = 'absolute';
			YAHOO.util.Dom.setStyle(upgrade, 'top',
				((container_height - upgrade_height) / 2) + 'px');
		}

		YAHOO.util.Event.on(window, 'resize', resizeUpgradeContainer);
		resizeUpgradeContainer();
	}
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.embedPlayer = function()

SiteJwPlayerMediaDisplay.prototype.embedPlayer = function()
{
	this.player_id = this.container.firstChild.id;

	if (YAHOO.env.ua.ie === 0 || YAHOO.env.ua.ie > 7) {
		var aspect_ratio = this.aspect_ratio[0] + ':' + this.aspect_ratio[1];
	} else {
		// aspect ratio is broken in IE7 and shows up far too tall for the width
		var aspect_ratio = null;
	}

	var playlist = this.getPlaylist();

	var options = {
		playlist:    playlist,
		skin:        this.getSkin(),
		stretching:  this.stretching,
		primary:     this.getPrimaryPlayerType(),
		width:       '100%',
		aspectratio: aspect_ratio,
		flashplayer: this.swf_uri,
		abouttext:   this.menu_title,
		aboutlink:   this.menu_link,
		analytics:   {
			enabled: false // turn off JW Player's built-in analytics
		},
		ga:          {} // this can be blank. JW Player will use the _gaq var.
	};

	this.player = jwplayer(this.player_id).setup(options);

	// this.debug();

	var that = this;
	this.player.onError(function (error) {
		that.handleError(error);
	});

	this.player.onReady(function() {
		that.on_ready_event.fire(that);
	});

	this.player.onFullscreen(function (e) {
		that.handleFullscreen(e.fullscreen);
	});

	if (this.record_end_point === true) {
		this.recordEndPoint();
	}

	if (this.space_to_pause) {
		this.handleSpaceBar();
	}

	this.player.onBeforePlay(function() {
		SiteJwPlayerMediaDisplay.current_player_id = that.player_id;
	});

	// there's a strange jwplayer side-effect that can cause a buffering
	// video to not pause, thus switching videos can make two players
	// play at the same time.
	function checkIfCurrent() {
		var all_players = SiteJwPlayerMediaDisplay.players;
		for (var i = 0; i < all_players.length; i++) {
			if (all_players[i].player_id !=
				SiteJwPlayerMediaDisplay.current_player_id) {

				all_players[i].pause();
			}
		}
	}

	this.player.onPlay(checkIfCurrent);
	this.player.onPlay(function() {
		if (that.overlay !== null) {
			that.overlay.style.display = 'none';
		}
	});

	// if a video inits in a hidden state, there will be no image set
	// when the video becomes visible, set the image
	this.player.onResize(function() {
		if (!playlist[0].image) {
			playlist[0].image = that.getImage();
			that.player.load(playlist);
		}
	});
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.isVideoSupported = function()

SiteJwPlayerMediaDisplay.prototype.isVideoSupported = function()
{
	var html5_video = this.isHTML5VideoSupported();
	var flash10 = typeof(YAHOO.util.SWFDetect) === 'undefined'
		|| YAHOO.util.SWFDetect.isFlashVersionAtLeast(10);

	return (flash10 || html5_video);
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.isHTML5VideoSupported = function()

SiteJwPlayerMediaDisplay.prototype.isHTML5VideoSupported = function()
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

	return html5_video;
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.getPrimaryPlayerType = function()

SiteJwPlayerMediaDisplay.prototype.getPrimaryPlayerType = function()
{
	var player_type = SiteJwPlayerMediaDisplay.primaryPlayerType;

	if (this.location_identifier !== null &&
		YAHOO.util.Cookie.get(this.location_identifier + '_type') == 'html5') {
		player_type = 'html5';
	}

	return player_type;
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.addSource = function()

SiteJwPlayerMediaDisplay.prototype.addSource = function(
	source_uri, width, label)
{
	// TODO: add new "type" property for 'rtmp' or 'mp4'
	var source = {
		file: source_uri,
		label: label,
		width: width
	};

	this.sources.push(source);
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
	var default_source = this.getBestQualitySource(region.width, region.height);
	if (default_source !== null) {
		default_source['default'] = true;
	}

	var rtmp_blocked = (YAHOO.util.Cookie.get(
		this.location_identifier + '_rtmp_status') == 'blocked');

	// clone sources so that jwplayer doesn't wipe out the width property
	var sources = [];
	for (var i = 0; i < this.sources.length; i++) {
		if (rtmp_blocked && this.sources[i].file.slice(-5) == '.smil') {
			continue;
		}

		var s = {};
		s.prototype = this.sources[i].prototype;
		for (var k in this.sources[i]) {
			s[k] = this.sources[i][k];
		}

		sources.push(s);
	}

	return sources;
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.getPlaylist = function()

SiteJwPlayerMediaDisplay.prototype.getPlaylist = function()
{
	return [{
		image: this.getImage(),
		sources: this.getSources(),
		tracks: this.getTracks()
	}];
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.getTracks = function()

SiteJwPlayerMediaDisplay.prototype.getTracks = function()
{
	var tracks = [];
	if (this.vtt_uri !== null) {
		var vtt_track = {};
		vtt_track.file = this.vtt_uri;
		vtt_track.kind = 'thumbnails';
		tracks.push(vtt_track);
	}

	return tracks;
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.getImage = function()

SiteJwPlayerMediaDisplay.prototype.getImage = function()
{
	var region = YAHOO.util.Dom.getRegion(this.container);
	var player_width = region.width;

	if (player_width === 0 || this.images.length === 0) {
		return null;
	}

	var default_image = 0;
	var min_diff = null;
	for (var i = 0; i < this.images.length; i++) {
		var diff = this.images[i].width - player_width;
		if (diff >= 0 && (min_diff === null || diff < min_diff)) {
			min_diff = diff;
			default_image = i;
		}
	}

	return (default_image === null) ? null : this.images[default_image].uri;
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.getSkin = function()

SiteJwPlayerMediaDisplay.prototype.getSkin = function()
{
	var base_tag = document.getElementsByTagName('base');
	var base_href = (base_tag.length > 0) ? base_tag[0].href : '';
	return base_href + this.skin;
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.getBestQualitySource = function()

SiteJwPlayerMediaDisplay.prototype.getBestQualitySource = function(
	player_width, player_height)
{
	var default_source = null;
	var min_diff = null;
	var count = 0;

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

	return (default_source === null) ? null : this.sources[default_source];
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
	this.player.onSeek(recordEndPoint);
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

	return parseInt(region.width *
		this.aspect_ratio[1] / this.aspect_ratio[0], 10);
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.handleFullscreen = function()

SiteJwPlayerMediaDisplay.prototype.handleFullscreen = function(fullscreen)
{
	// only automatically change the quality for HTML5
	if (this.player.getRenderingMode() == 'flash') {
		return;
	}

	if (fullscreen) {
		var default_source = this.getBestQualitySource(
			YAHOO.util.Dom.getViewportWidth(),
			YAHOO.util.Dom.getViewportHeight());
	} else {
		// Disable this for now. JwPlayer has a bug when paused videos return
		// from fullscreen they start playing again. On desktop browsers/tablets
		// this is annoying. On phones that require fullscreen playback it leads
		// to not being able to close the video.
		//var region = YAHOO.util.Dom.getRegion(this.container);
		//var default_source = this.getBestQualitySource(
		//	region.width, region.height);
		var default_source = null;
	}

	if (default_source !== null) {
		// look up the level from the source
		var levels = this.player.getQualityLevels();

		for (var i = 0; i < levels.length; i++) {
			if (levels[i].label == default_source.label) {
				this.player.setCurrentQuality(i);
				break;
			}
		}
	}
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.handleSpaceBar = function()

SiteJwPlayerMediaDisplay.prototype.handleSpaceBar = function()
{
	YAHOO.util.Event.on(document, 'keydown', function (e) {
		var target = YAHOO.util.Event.getTarget(e);

		// don't capture keyboard events for inputs
		var tag = target.tagName.toLowerCase();
		if (tag === 'textarea' || tag === 'input' ||
			this.player_id != SiteJwPlayerMediaDisplay.current_player_id) {
			return;
		}

		if (YAHOO.util.Event.getCharCode(e) == 32) {
			// toggle between play/pause
			this.player.play();
			YAHOO.util.Event.preventDefault(e);
		}
	}, this, true);
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.handleError = function()

SiteJwPlayerMediaDisplay.prototype.handleError = function(error)
{
	this.appendErrorMessage();

	switch (error.message) {
	case 'Error loading stream: Could not connect to server' :
		// set a cookie to remove the RTMP source and reload the playlist
		if (this.player.getRenderingMode() == 'flash') {
			var rtmp_blocked = (YAHOO.util.Cookie.get(
				this.location_identifier + '_rtmp_status') == 'blocked');

			if (!rtmp_blocked) {
				YAHOO.util.Cookie.set(this.location_identifier + '_rtmp_status',
					'blocked');

				this.player.load(this.getPlaylist());
				this.player.play();
			} else {
				if (YAHOO.env.ua.android) {
					this.displayErrorMessage(this.android_rtmp_error_message);
				} else {
					this.displayErrorMessage(this.rtmp_error_message);
				}
			}
		}

		break;
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

		var quality_levels = that.player.getQualityLevels();
		var current_level = quality_levels[that.player.getCurrentQuality()];

		if (meta && meta.hasOwnProperty('screenwidth')) {
			var content = 'player-width: ' + meta.screenwidth + 'px';
		} else {
			var region = YAHOO.util.Dom.getRegion(that.container);
			var content = 'player-width: ' + region.width + 'px';
		}

		if (meta && meta.hasOwnProperty('transitioning')) {
			content += '<br />transitioning: ' +
				((meta.transitioning) ? 'yes' : 'no');
		}

		if (meta && meta.hasOwnProperty('bufferfill')) {
			content += '<br />buffer-fill: ' + meta.bufferfill + 's';
		}

		content += '<br />quality-level: <strong>' + current_level.label +
			'</strong> (' + that.player.getCurrentQuality() + ')';

		if (meta && meta.hasOwnProperty('bandwidth')) {
			content += '<br />bandwidth: ' +
				Math.round(meta.bandwidth / 1024, 2) + ' Mb/s ' +
				'(' + meta.bandwidth + ')';
		}

		debug_container.innerHTML = content;
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

	var div = document.createElement('div');
	div.innerHTML = this.on_complete_message;

	var restart_link = document.createElement('a');
	restart_link.href = '#';
	restart_link.className = 'restart-video';
	restart_link.appendChild(document.createTextNode('Watch Again'));

	var that = this;
	YAHOO.util.Event.on(restart_link, 'click', function (e) {
		YAHOO.util.Event.preventDefault(e);
		that.overlay.style.display = 'none';
		that.complete_overlay.style.display = 'none';
		that.play();
	});

	div.appendChild(restart_link);
	this.complete_overlay.appendChild(div);

	this.overlay.parentNode.appendChild(this.complete_overlay);

	YAHOO.util.Event.on(window, 'resize', function () {
		this.positionOverlay(this.complete_overlay);
	}, this, true);
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.appendResumeMessage = function()

SiteJwPlayerMediaDisplay.prototype.appendResumeMessage = function()
{
	this.resume_overlay = document.createElement('div');
	this.resume_overlay.style.display = 'none';
	this.resume_overlay.className = 'overlay-content';

	var div = document.createElement('div');
	div.innerHTML = this.resume_message;

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
		that.seek(that.start_position);
	});

	div.appendChild(resume_link);

	var restart_link = document.createElement('a');
	restart_link.href = '#';
	restart_link.className = 'restart-video';
	restart_link.appendChild(document.createTextNode(
		'Start From the Beginning'));

	YAHOO.util.Event.on(restart_link, 'click', function (e) {
		YAHOO.util.Event.preventDefault(e);
		that.overlay.style.display = 'none';
		that.resume_overlay.style.display = 'none';
		that.play();
	});

	div.appendChild(restart_link);
	this.resume_overlay.appendChild(div);

	this.overlay.parentNode.appendChild(this.resume_overlay);

	YAHOO.util.Event.on(window, 'resize', function () {
		this.positionOverlay(this.resume_overlay);
	}, this, true);
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.appendErrorMessage = function()

SiteJwPlayerMediaDisplay.prototype.appendErrorMessage = function()
{
	this.error_overlay = document.createElement('div');
	this.error_overlay.style.display = 'none';
	this.error_overlay.className = 'overlay-content error-overlay';

	var div = document.createElement('div');
	this.error_overlay.appendChild(div);
	this.overlay.parentNode.appendChild(this.error_overlay);

	YAHOO.util.Event.on(window, 'resize', function () {
		this.positionOverlay(this.error_overlay);
	}, this, true);
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.displayCompleteMessage = function()

SiteJwPlayerMediaDisplay.prototype.displayCompleteMessage = function()
{
	this.overlay.style.display = 'block';
	this.complete_overlay.style.display = 'block';
	this.positionOverlay(this.complete_overlay);
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.displayResumeMessage = function()

SiteJwPlayerMediaDisplay.prototype.displayResumeMessage = function()
{
	this.overlay.style.display = 'block';
	this.resume_overlay.style.display = 'block';
	this.positionOverlay(this.resume_overlay);
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.displayErrorMessage = function()

SiteJwPlayerMediaDisplay.prototype.displayErrorMessage = function(message)
{
	this.error_overlay.firstChild.innerHTML = message;

	this.overlay.style.display = 'block';
	this.error_overlay.style.display = 'block';
	this.positionOverlay(this.error_overlay);
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.positionOverlay = function()

SiteJwPlayerMediaDisplay.prototype.positionOverlay = function(overlay)
{
	var overlay_region = YAHOO.util.Dom.getRegion(overlay);
	var content_region = YAHOO.util.Dom.getRegion(overlay.firstChild);

	var margin = Math.floor(Math.max(0,
		(overlay_region.height - content_region.height) / 2));

	YAHOO.util.Dom.setStyle(overlay.firstChild, 'margin-top', margin + 'px');
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.seek = function()

SiteJwPlayerMediaDisplay.prototype.seek = function(position)
{
	var that = this;

	if (YAHOO.env.ua.ios || YAHOO.env.ua.android) {
		this.player.onTime(function(e) {
			if (!that.seek_done && e.position > 1) {
				that.player.seek(position);
				that.seek_done = true;
			}
		});

		this.play();
	} else {
		this.player.seek(position);
	}
};

// }}}
