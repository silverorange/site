/**
 * Class for managing media players
 *
 * @copyright 2011-2016 silverorange
 */
function SiteJwPlayerMediaDisplay(media_id, container_id)
{
	this.media_id = media_id;
	this.container_id = container_id;

	this.sources = [];
	this.images  = [];
	this.valid_mime_types = [];

	this.skin = 'seven';
	this.stretching = null;
	this.image = null;
	this.duration = null;
	this.aspect_ratio = [];
	this.start_position = 0;
	this.record_end_point = false;
	this.swf_uri = null;
	this.vtt_uri = null;
	this.playback_rate_controls = true;
	this.mute = false;
	this.auto_start = false;
	this.controls = true;
	this.repeat = false;

	this.menu_title = null;
	this.menu_link = null;

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
	this.seek_done = false;

	SiteJwPlayerMediaDisplay.players.push(this);

	YAHOO.util.Event.onDOMReady(this.init, this, true);
}

SiteJwPlayerMediaDisplay.current_player_id = null;
SiteJwPlayerMediaDisplay.record_interval = 30; // in seconds
SiteJwPlayerMediaDisplay.players = [];

// {{{ SiteJwPlayerMediaDisplay.prototype.init = function()

SiteJwPlayerMediaDisplay.prototype.init = function()
{
	this.container = document.getElementById(this.container_id);

	this.embedPlayer();
	this.drawDialogs();
	this.setupAnalytics();

	var that = this;
	this.player.on('setupError', function() {
		var upgrade = document.createElement('div');
		upgrade.className = 'video-player-upgrade';
		upgrade.innerHTML = that.upgrade_message;
		that.container.appendChild(upgrade);

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
	});
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
		playbackRateControls: this.playback_rate_controls,
		mute:        this.mute,
		autostart:   this.auto_start,
		controls:    this.controls,
		repeat:      this.repeat,
		analytics:   {
			enabled: false // turn off JW Player's built-in analytics
		}
	};

	this.player = jwplayer(this.player_id).setup(options);

	// this.debug();

	var that = this;

	this.player.onReady(function() {
		that.on_ready_event.fire(that);
	});

	if (this.record_end_point) {
		this.recordEndPoint();
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
// {{{ SiteJwPlayerMediaDisplay.prototype.getPrimaryPlayerType = function()

SiteJwPlayerMediaDisplay.prototype.getPrimaryPlayerType = function()
{
	var player_type = SiteJwPlayerMediaDisplay.primaryPlayerType;

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

	// clone sources so that jwplayer doesn't wipe out the width property
	var sources = [];
	for (var i = 0; i < this.sources.length; i++) {
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
	return {
		name: this.skin
	};
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
// {{{ SiteJwPlayerMediaDisplay.prototype.setupAnalytics = function()
SiteJwPlayerMediaDisplay.prototype.setupAnalytics = function()
{
	var player = this.player;
	var currentItem = player.getPlaylistItem(player.getPlaylistIndex());
	var title = currentItem.file;
	var fireEvent = function(action, value) {
		if (_gaq) {
			_gaq.push(['_trackEvent', 'JW Player Video', action, title, value]);
		}
	}
	var getPos = function getPos() {
		return Math.floor(player.getPosition() * 1000);
	}

	player.on('play', function() {
		fireEvent('Play', getPos());
	});

	player.on('pause', function() {
		fireEvent('Pause', getPos());
	});

	player.on('seek', function(event) {
		fireEvent('SeekStart', Math.floor(event.position));
		fireEvent('SeekEnd', Math.floor(event.offset));
	});

	player.on('complete', function() {
		fireEvent('Complete', getPos());
	});

	player.on('playbackRateChanged', function(event) {
		fireEvent('PlaybackRate', event.playbackRate);
	});

}

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
			this.start_position > 0 &&
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
// {{{ SiteJwPlayerMediaDisplay.prototype.positionOverlay = function()

SiteJwPlayerMediaDisplay.prototype.positionOverlay = function(overlay)
{
	var overlay_region = YAHOO.util.Dom.getRegion(overlay);

	// If the overlay is not currently part of the DOM it won't have a region,
	// so don't try to reposition it.
	if (overlay_region) {
		var content_region = YAHOO.util.Dom.getRegion(overlay.firstChild);

		var margin = Math.floor(
			Math.max(
				0,
				(overlay_region.height - content_region.height) / 2
			)
		);

		YAHOO.util.Dom.setStyle(
			overlay.firstChild,
			'margin-top',
			margin + 'px'
		);
	}
};

// }}}
// {{{ SiteJwPlayerMediaDisplay.prototype.seek = function()

SiteJwPlayerMediaDisplay.prototype.seek = function(position)
{
	var that = this;

	// Old versions of Android don't play nicely with firstFrame when
	// seeking. It causes the video to look all messed up like it's
	// waiting for a keyframe to come along.
	if (YAHOO.env.ua.android) {
		this.player.onTime(function(e) {
			if (!that.seek_done && e.position > 1) {
				that.seek_done = true;
				that.player.seek(position);
			}
		});
	} else {
		this.player.on('firstFrame', function() {
			if (!that.seek_done) {
				that.player.seek(position);
				that.seek_done = true;
			}
		});
	}

	this.play();
};

// }}}
