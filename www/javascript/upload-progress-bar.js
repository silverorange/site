/**
 * @copyright 2007 silverorange
 */

// {{{ SiteUploadProgressManager

SiteUploadProgressManager = {
};

SiteUploadProgressManager.status_client = null;
SiteUploadProgressManager.clients = [];
SiteUploadProgressManager.interval_period = 1500; // in milliseconds
SiteUploadProgressManager.interval = null;
SiteUploadProgressManager.sequence = 0;
SiteUploadProgressManager.received_sequence = 0;

SiteUploadProgressManager.setStatusClient = function(uri)
{
	SiteUploadProgressManager.status_client = new XML_RPC_Client(uri);
}

SiteUploadProgressManager.addClient = function(client)
{
	var first_client = (SiteUploadProgressManager.clients.length == 0);

	SiteUploadProgressManager.clients.push(client);

	if (first_client)
		SiteUploadProgressManager.setInterval();
}

SiteUploadProgressManager.removeClient = function(client)
{
	for (var i = 0; i < SiteUploadProgressManager.clients.length; i++) {
		if (SiteUploadProgressManager.clients[i] === client) {
			SiteUploadProgressManager.clients.splice(i, 1);
			break;
		}
	}

	if (SiteUploadProgressManager.clients.length == 0)
		SiteUploadProgressManager.clearInterval();
}

SiteUploadProgressManager.getClient = function(id)
{
	var client = null;
	for (var i = 0; i < SiteUploadProgressManager.clients.length; i++) {
		if (SiteUploadProgressManager.clients[i].id === id) {
			client = SiteUploadProgressManager.clients[i];
			break;
		}
	}
	return client;
}

SiteUploadProgressManager.setInterval = function()
{
	if (SiteUploadProgressManager.interval === null) {
		SiteUploadProgressManager.interval = window.setInterval(
			SiteUploadProgressManager.updateStatus,
			SiteUploadProgressManager.interval_period);
	}
}

SiteUploadProgressManager.clearInterval = function()
{
	window.clearInterval(SiteUploadProgressManager.interval);
	SiteUploadProgressManager.interval = null;
}

SiteUploadProgressManager.updateStatus = function()
{
	if (SiteUploadProgressManager.clients.length > 0) {

		var client_map = {};
		var client;
		for (var i = 0; i < SiteUploadProgressManager.clients.length; i++) {
			client = SiteUploadProgressManager.clients[i];
			client_map[client.id] = client.getUploadIdentifier();
		}

		SiteUploadProgressManager.sequence++;

		SiteUploadProgressManager.status_client.callProcedure('getStatus',
			SiteUploadProgressManager.statusCallback,
			[SiteUploadProgressManager.sequence, client_map],
			['int', 'struct']);
	}
}

SiteUploadProgressManager.statusCallback = function(response)
{
	if (response.sequence > SiteUploadProgressManager.received_sequence) {
		var client;
		for (client_id in response.statuses) {
			client = SiteUploadProgressManager.getClient(client_id);
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
		SiteUploadProgressManager.received_sequence = response.sequence;
	}
}

// }}}
// {{{ SiteUploadProgressClient

SiteUploadProgressClient = function(id, status_server, progress_bar)
{
	this.id = id;
	this.progress_bar = progress_bar;
	this.uploaded_files = [];
	this.status_enabled = false;

	SiteUploadProgressManager.setStatusClient(status_server);

	this.progress_bar.pulse_step = 0.10;

	this.form = document.getElementById(id);
	this.container = document.getElementById(this.id + '_container');

	this.createIFrame();

	YAHOO.util.Event.addListener(this.form, 'submit', this.upload,
		this, true);
}

SiteUploadProgressClient.progress_unknown_text = 'uploading ...';

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
	this.progress_bar.setValue(percent);

	var hours = Math.floor(time / 360);
	var minutes = Math.floor(time / 60) % 60;
	var seconds = time % 60;

	var text = '';
	text += (hours > 0) ? hours + ' hours ' : '';
	text += (minutes > 0) ? minutes + ' minutes ' : '';
	text += seconds + ' seconds left';

	this.progress_bar.setText(text);
}

SiteUploadProgressClient.prototype.upload = function(event)
{
	this.progress_bar.setValue(0);
	this.progress_bar.setText(SiteUploadProgressClient.progress_unknown_text);
	this.showProgressBar();
	SiteUploadProgressManager.addClient(this);
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

SiteUploadProgressClient.prototype.createIFrame = function()
{
	// TODO: better browser detection
	if (navigator.userAgent.indexOf('MSIE') > 0) {
		var div = document.createElement('div');
		div.style.display = 'inline';
		div.innerHTML = '<iframe name="' + this.id + '_iframe" ' +
			'id="' + this.id + '_iframe" ' +
			'src="about:blank" style="border: 0; width: 0; height: 0;">' +
			'</iframe>';

		this.container.insertBefore(div, this.container);
	} else {
		var iframe = document.createElement('iframe');
		iframe.name = this.id + '_iframe';
		iframe.id = this.id + '_iframe';
		iframe.style.border = '0';
		iframe.style.width = '0';
		iframe.style.height = '0';
		this.container.parentNode.insertBefore(iframe, this.container);
	}
}

SiteUploadProgressClient.prototype.getUploadIdentifier = function()
{
	return document.getElementById(this.id + '_identifier').value;
}
