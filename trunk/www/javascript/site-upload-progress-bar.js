/**
 * Upload progress bar
 *
 *  Contains code from http://github.com/drogus/jquery-upload-progress
 *  originally licensed under the MIT license.
 *
 * NOTE: For this to work in circa-2009 versions of WebKit browsers, the
 * callback code needs to run from an invisible iframe.
 *
 * @package   Site
 * @copyright 2007-2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */

// {{{ SiteUploadProgressManager

SiteUploadProgressManager = {
};

SiteUploadProgressManager.status_client = null;
SiteUploadProgressManager.clients = [];
SiteUploadProgressManager.period = 500; // in milliseconds
SiteUploadProgressManager.timeout = null;
SiteUploadProgressManager.sequence = 0;
SiteUploadProgressManager.frame_name = 'upload_progress';
SiteUploadProgressManager.is_webkit =
	(/AppleWebKit|Konqueror|KHTML/gi).test(navigator.userAgent);

/**
 * Workaround for Webkit browsers
 *
 * Contains code from http://github.com/drogus/jquery-upload-progress
 */
SiteUploadProgressManager.init = function()
{
	if (SiteUploadProgressManager.is_webkit &&
		window.name !== SiteUploadProgressManager.frame_name) {

		var iframe = document.createElement('iframe');
		iframe.name = SiteUploadProgressManager.frame_name;

		iframe.style.width    = '0';
		iframe.style.height   = '0';
		iframe.style.position = 'absolute';
		iframe.style.top      = '-3000px';

		var body = document.getElementsByTagName('body')[0];
		body.appendChild(iframe);

		var d = iframe.contentWindow.document;
		var b = d.body;

		var Loader = function(d, el, script)
		{
			this.d = d;
			this.el = el;
			this.script = script;
			this.chained_loader = null;
		};

		Loader.prototype.load = function(f)
		{
			if (this.script) {
				var s = this.d.createElement('script');
				s.src = this.script;

				if (this.chained_loader) {
					var that = this;
					s.onload = function() {
						that.chained_loader.load(f);
					}
				} else if (f) {
					s.onload = f;
				}

				this.el.appendChild(s);
			} else {
				if (this.chained_loader) {
					this.chained_loader.load(f);
				}
			}
		};

		Loader.prototype.chain = function(el, script)
		{
			this.chained_loader = new Loader(this.d, el, script);
			return this.chained_loader;
		}

		var loader = new Loader(d, null, null);
		loader.chain(b, 'packages/yui/yahoo-dom-event/yahoo-dom-event.js')
		      .chain(b, 'packages/yui/animation/animation-min.js')
		      .chain(b, 'packages/yui/connection/connection-min.js')
		      .chain(b, 'packages/xml-rpc-ajax/javascript/xml-rpc-ajax.js')
		      .chain(b, 'packages/swat/javascript/swat-progress-bar.js')
		      .chain(b, 'packages/site/javascript/site-upload-progress-bar.js');

		loader.load(function() {
			SiteUploadProgressManager.onLoad.fire();
		});
	} else {
		SiteUploadProgressManager.onLoad.fire();
	}
}

SiteUploadProgressManager.onLoad = new YAHOO.util.CustomEvent('onLoad');

YAHOO.util.Event.onDOMReady(SiteUploadProgressManager.init);

SiteUploadProgressManager.getManager = function()
{
	var progress_manager;

	if (SiteUploadProgressManager.is_webkit) {
		if (window.name == SiteUploadProgressManager.frame_name) {
			progress_manager = SiteUploadProgressManager;
		} else {
			var frame = window.frames[SiteUploadProgressManager.frame_name];
			progress_manager = frame.SiteUploadProgressManager;
		}
	} else {
		progress_manager = SiteUploadProgressManager;
	}

	return progress_manager;
}

SiteUploadProgressManager.setStatusClient = function(uri)
{
	var manager = SiteUploadProgressManager.getManager();
	manager.status_client = new XML_RPC_Client(uri);
}

SiteUploadProgressManager.addClient = function(client)
{
	var manager = SiteUploadProgressManager.getManager();

	var first_client = (manager.clients.length == 0);

	manager.clients.push(client);

	if (first_client) {
		manager.setTimeout();
	}
}

SiteUploadProgressManager.removeClient = function(client)
{
	var manager = SiteUploadProgressManager.getManager();

	for (var i = 0; i < manager.clients.length; i++) {
		if (manager.clients[i] === client) {
			manager.clients.splice(i, 1);
			break;
		}
	}
}

SiteUploadProgressManager.getClient = function(id)
{
	var manager = SiteUploadProgressManager.getManager();

	var client = null;
	for (var i = 0; i < manager.clients.length; i++) {
		if (manager.clients[i].id === id) {
			client = manager.clients[i];
			break;
		}
	}
	return client;
}

SiteUploadProgressManager.setTimeout = function()
{
	var manager = SiteUploadProgressManager.getManager();

	if (manager.timeout !== null) {
		clearTimeout(manager.timeout);
	}

	manager.timeout = setTimeout(
		manager.updateStatus,
		manager.period);
}

SiteUploadProgressManager.updateStatus = function()
{
	var manager = SiteUploadProgressManager.getManager();

	if (manager.clients.length > 0) {

		var client_map = {};
		var client;
		for (var i = 0; i < manager.clients.length; i++) {
			client = manager.clients[i];
			client_map[client.id] = client.getUploadIdentifier();
		}

		manager.sequence++;

		manager.status_client.callProcedure('getStatus',
			manager.statusCallback,
			[manager.sequence, client_map],
			['int', 'struct']);
	}
}

SiteUploadProgressManager.statusCallback = function(response)
{
	var manager = SiteUploadProgressManager.getManager();

	var client;

	for (client_id in response.statuses) {
		client = manager.getClient(client_id);
		if (client) {
			if (response.statuses[client_id] === 'none') {
				client.progress();
			} else {
				var percent = response.statuses[client_id].bytes_uploaded /
					response.statuses[client_id].bytes_total;

				var time = response.statuses[client_id].est_sec;

				client.setStatus(percent, time);
			}
		}
	}

	manager.setTimeout();
}

// }}}
// {{{ SiteUploadProgressClient

SiteUploadProgressClient = function(id, status_server, progress_bar)
{
	this.id = id;
	this.progress_bar = progress_bar;
	this.uploaded_files = [];
	this.status_enabled = false;

	SiteUploadProgressManager.onLoad.subscribe(
		function() {
			var manager = SiteUploadProgressManager.getManager();
			manager.setStatusClient(status_server);
		}, this, true);

	this.progress_bar.pulse_step = 0.10;

	this.form = document.getElementById(id);
	this.container = document.getElementById(this.id + '_container');

	YAHOO.util.Event.addListener(this.form, 'submit', this.upload,
		this, true);
}

SiteUploadProgressClient.progress_unknown_text = 'uploading ...';
SiteUploadProgressClient.hours_text = 'hours';
SiteUploadProgressClient.minutes_text = 'minutes';
SiteUploadProgressClient.seconds_left_text = 'seconds left';

SiteUploadProgressClient.prototype.progress = function()
{
	if (this.status_enabled) {
		this.setStatus(1, 0);
	} else {
		this.progress_bar.pulse();
		this.progress_bar.setText(
			SiteUploadProgressClient.progress_unknown_text);
	}
}

SiteUploadProgressClient.prototype.setStatus = function(percent, time)
{
	this.status_enabled = true;
	this.progress_bar.setValueWithAnimation(percent);

	var hours = Math.floor(time / 360);
	var minutes = Math.floor(time / 60) % 60;
	var seconds = time % 60;

	var hours_text = SiteUploadProgressClient.hours_text;
	var minutes_text = SiteUploadProgressClient.minutes_text;
	var seconds_left_text = SiteUploadProgressClient.seconds_left_text;

	var text = '';
	text += (hours > 0) ? hours + ' ' + hours_text + ' ' : '';
	text += (minutes > 0) ? minutes + ' ' + minutes_text + ' ' : '';
	text += seconds + ' ' + seconds_left_text;

	this.progress_bar.setText(text);
}

SiteUploadProgressClient.prototype.upload = function(event)
{
	this.progress_bar.setValue(0);
	this.progress_bar.setText(SiteUploadProgressClient.progress_unknown_text);
	this.showProgressBar();
	SiteUploadProgressManager.getManager().addClient(this);
}

/**
 * Shows the progress bar for this uploader using a smooth animation
 */
SiteUploadProgressClient.prototype.showProgressBar = function()
{
	var animate_div = this.progress_bar.container;
	animate_div.parentNode.style.display = 'block';
	animate_div.parentNode.style.opacity = '0';
	animate_div.parentNode.style.overflow = 'hidden';
	animate_div.parentNode.style.height = '0';
	animate_div.style.visibility = 'hidden';
	animate_div.style.overflow = 'hidden';
	animate_div.style.display = 'block';
	animate_div.style.height = '';
	var height = animate_div.offsetHeight;
	animate_div.style.height = '0';
	animate_div.style.visibility = 'visible';
	animate_div.parentNode.style.height = '';
	animate_div.parentNode.style.overflow = 'visible';

	var slide_animation = new YAHOO.util.Anim(animate_div,
		{ height: { from: 0, to: height } }, 0.5, YAHOO.util.Easing.easeOut);

	var fade_animation = new YAHOO.util.Anim(animate_div.parentNode,
		{ opacity: { from: 0, to: 1 } }, 0.5);

	slide_animation.onComplete.subscribe(fade_animation.animate,
		fade_animation, true);

	slide_animation.animate();
}

SiteUploadProgressClient.prototype.getUploadIdentifier = function()
{
	return document.getElementById(this.id + '_identifier').value;
}
