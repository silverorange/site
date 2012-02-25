function SiteCommentDisplay(id, comment_id, comment_status, spam, edit_uri)
{
	this.id = id;
	this.comment_id = comment_id;
	this.comment_status = comment_status;
	this.comment_spam = spam;
	this.edit_uri = edit_uri;

	if (!SiteCommentDisplay.xml_rpc_client) {
		SiteCommentDisplay.xml_rpc_client = new XML_RPC_Client(
			SiteCommentDisplay.comment_component + '/AjaxServer');
	}

	this.container = document.getElementById(this.id);
	this.status_container = document.getElementById(this.id + '_status');
	this.comment = YAHOO.util.Dom.getElementsByClassName(
		'comment', 'div', this.container).shift();

	this.initControls();
	this.initConfirmation();
	this.hideControls();

	this.controls_animation = null;

	YAHOO.util.Event.on(this.comment, 'mouseover', function(e) {
		this.showControls();
	}, this, true);

	YAHOO.util.Event.on(this.controls, 'mouseover', function(e) {
		this.showControls();
	}, this, true);

	YAHOO.util.Event.on(this.container, 'mouseout', function(e) {
		var hide = true;
		var target = YAHOO.util.Event.getRelatedTarget(e);
		if (target == this.comment || target == this.controls) {
			hide = false;
		} else {
			while (target.parentNode) {
				target = target.parentNode;
				if (target == this.comment || target == this.controls) {
					hide = false;
					break;
				}
			}
		}
		if (hide) {
			this.hideControls();
		}
	}, this, true);
}

SiteCommentDisplay.edit_text      = 'Edit';
SiteCommentDisplay.approve_text   = 'Approve';
SiteCommentDisplay.deny_text      = 'Deny';
SiteCommentDisplay.publish_text   = 'Publish';
SiteCommentDisplay.unpublish_text = 'Unpublish';
SiteCommentDisplay.spam_text      = 'Spam';
SiteCommentDisplay.not_spam_text  = 'Not Spam';
SiteCommentDisplay.delete_text    = 'Delete';
SiteCommentDisplay.cancel_text    = 'Cancel';

SiteCommentDisplay.status_spam_text        = 'Spam';
SiteCommentDisplay.status_pending_text     = 'Pending';
SiteCommentDisplay.status_unpublished_text = 'Unpublished';

SiteCommentDisplay.delete_confirmation_text = 'Delete comment?';

SiteCommentDisplay.STATUS_PENDING     = 0;
SiteCommentDisplay.STATUS_PUBLISHED   = 1;
SiteCommentDisplay.STATUS_UNPUBLISHED = 2;

SiteCommentDisplay.comment_component = 'Comment';

// {{{ initControls()

SiteCommentDisplay.prototype.initControls = function()
{
	this.controls = document.getElementById(this.id + '_controls');

	this.edit_button = document.createElement('input');
	this.edit_button.type = 'button';
	this.edit_button.value = SiteCommentDisplay.edit_text;
	YAHOO.util.Event.on(this.edit_button, 'click',
		function (e) { window.location = this.edit_uri; }, this, true);

	this.approve_button = document.createElement('input');
	this.approve_button.type = 'button';
	this.approve_button.value = SiteCommentDisplay.approve_text;
	YAHOO.util.Event.on(this.approve_button, 'click',
		this.publish, this, true);

	this.deny_button = document.createElement('input');
	this.deny_button.type = 'button';
	this.deny_button.value = SiteCommentDisplay.deny_text;
	YAHOO.util.Event.on(this.deny_button, 'click',
		this.unpublish, this, true);

	this.publish_toggle_button = document.createElement('input');
	this.publish_toggle_button.type = 'button';
	this.publish_toggle_button.value = SiteCommentDisplay.publish_text;
	YAHOO.util.Event.on(this.publish_toggle_button, 'click',
		this.togglePublished, this, true);

	this.spam_toggle_button = document.createElement('input');
	this.spam_toggle_button.type = 'button';
	this.spam_toggle_button.value = SiteCommentDisplay.spam_text;
	YAHOO.util.Event.on(this.spam_toggle_button, 'click',
		this.toggleSpam, this, true);

	this.delete_button = document.createElement('input');
	this.delete_button.type = 'button';
	this.delete_button.value = SiteCommentDisplay.delete_text;
	YAHOO.util.Event.on(this.delete_button, 'click',
		this.confirmDelete, this, true);

	if (this.comment_status == SiteCommentDisplay.STATUS_PUBLISHED) {
		this.publish_toggle_button.value = SiteCommentDisplay.unpublish_text;
	}

	if (this.comment_spam) {
		this.spam_toggle_button.value = SiteCommentDisplay.not_spam_text;
		this.approve_button.style.display = 'none';
		this.deny_button.style.display = 'none';
		this.publish_toggle_button.style.display = 'none';
	} else {
		switch (this.comment_status) {
		case SiteCommentDisplay.STATUS_PENDING:
			this.publish_toggle_button.style.display = 'none';
			break;

		case SiteCommentDisplay.STATUS_PUBLISHED:
		case SiteCommentDisplay.STATUS_UNPUBLISHED:
			this.approve_button.style.display = 'none';
			this.deny_button.style.display = 'none';
			break;
		}
	}

	this.controls.appendChild(this.edit_button);
	this.controls.appendChild(document.createTextNode(' '));
	this.controls.appendChild(this.approve_button);
	this.controls.appendChild(document.createTextNode(' '));
	this.controls.appendChild(this.deny_button);
	this.controls.appendChild(document.createTextNode(' '));
	this.controls.appendChild(this.publish_toggle_button);
	this.controls.appendChild(document.createTextNode(' '));
	this.controls.appendChild(this.spam_toggle_button);
	this.controls.appendChild(document.createTextNode(' '));
	this.controls.appendChild(this.delete_button);
}

// }}}
// {{{ initConfirmation()

SiteCommentDisplay.prototype.initConfirmation = function()
{
	this.confirmation = document.createElement('div');
	this.confirmation.className = 'site-comment-display-confirmation';
	this.confirmation.style.display = 'none';

	var message_div = document.createElement('div');
	SiteCommentDisplay.setTextContent(message_div,
		SiteCommentDisplay.delete_confirmation_text);

	this.confirmation.appendChild(message_div);

	this.confirmation_cancel = document.createElement('input');
	this.confirmation_cancel.type ='button';
	this.confirmation_cancel.value = 'Cancel'; //TODO
	this.confirmation.appendChild(this.confirmation_cancel);
	YAHOO.util.Event.on(this.confirmation_cancel, 'click', this.cancelDelete,
		this, true);

	this.confirmation.appendChild(document.createTextNode(' '));

	this.confirmation_ok = document.createElement('input');
	this.confirmation_ok.type ='button';
	this.confirmation_ok.value = SiteCommentDisplay.delete_text;
	this.confirmation.appendChild(this.confirmation_ok);
	YAHOO.util.Event.on(this.confirmation_ok, 'click', this.deleteComment,
		this, true);

	this.delete_button.parentNode.appendChild(this.confirmation);
}

// }}}
// {{{ publish()

SiteCommentDisplay.prototype.publish = function()
{
	this.setSensitivity(false);

	var that = this;
	function callBack(response)
	{
		that.comment_status = SiteCommentDisplay.STATUS_PUBLISHED;

		that.approve_button.style.display = 'none';
		that.deny_button.style.display = 'none';
		that.publish_toggle_button.style.display = 'inline';
		that.publish_toggle_button.value = SiteCommentDisplay.unpublish_text;

		YAHOO.util.Dom.removeClass(that.container, 'site-comment-red');
		YAHOO.util.Dom.removeClass(that.container, 'site-comment-yellow');
		YAHOO.util.Dom.addClass(that.container, 'site-comment-green');

		that.updateStatus();
		that.setSensitivity(true);
	}

	SiteCommentDisplay.xml_rpc_client.callProcedure('publish', callBack,
		[this.comment_id], ['int']);
}

// }}}
// {{{ unpublish()

SiteCommentDisplay.prototype.unpublish = function()
{
	this.setSensitivity(false);

	var that = this;
	function callBack(response)
	{
		that.comment_status = SiteCommentDisplay.STATUS_UNPUBLISHED;

		that.approve_button.style.display = 'none';
		that.deny_button.style.display = 'none';
		that.publish_toggle_button.style.display = 'inline';
		that.publish_toggle_button.value = SiteCommentDisplay.publish_text;

		YAHOO.util.Dom.removeClass(that.container, 'site-comment-green');
		YAHOO.util.Dom.removeClass(that.container, 'site-comment-yellow');
		YAHOO.util.Dom.addClass(that.container, 'site-comment-red');

		that.updateStatus();
		that.setSensitivity(true);
	}

	SiteCommentDisplay.xml_rpc_client.callProcedure('unpublish', callBack,
		[this.comment_id], ['int']);
}

// }}}
// {{{ togglePublished()

SiteCommentDisplay.prototype.togglePublished = function()
{
	if (this.comment_status === SiteCommentDisplay.STATUS_PUBLISHED) {
		this.unpublish();
	} else {
		this.publish();
	}
}

// }}}
// {{{ spam()

SiteCommentDisplay.prototype.spam = function()
{
	this.setSensitivity(false);

	var that = this;
	function callBack(response)
	{
		that.comment_spam = true;

		that.approve_button.style.display = 'none';
		that.deny_button.style.display = 'none';
		that.publish_toggle_button.style.display = 'none';
		that.spam_toggle_button.value = SiteCommentDisplay.not_spam_text;

		YAHOO.util.Dom.removeClass(that.container, 'site-comment-green');
		YAHOO.util.Dom.removeClass(that.container, 'site-comment-yellow');
		YAHOO.util.Dom.addClass(that.container, 'site-comment-red');

		that.updateStatus();
		that.setSensitivity(true);
	}

	SiteCommentDisplay.xml_rpc_client.callProcedure('spam', callBack,
		[this.comment_id], ['int']);
}

// }}}
// {{{ notSpam()

SiteCommentDisplay.prototype.notSpam = function()
{
	this.setSensitivity(false);

	var that = this;
	function callBack(response)
	{
		that.comment_spam = false;

		that.spam_toggle_button.value = SiteCommentDisplay.spam_text;

		YAHOO.util.Dom.removeClass(that.container, 'site-comment-red');

		if (that.comment_status == SiteCommentDisplay.STATUS_PENDING) {
			YAHOO.util.Dom.removeClass(that.container, 'site-comment-green');
			YAHOO.util.Dom.addClass(that.container, 'site-comment-yellow');
			that.approve_button.style.display = 'inline';
			that.deny_button.style.display = 'inline';
		} else {
			that.publish_toggle_button.style.display = 'inline';
			YAHOO.util.Dom.removeClass(that.container, 'site-comment-yellow');
			YAHOO.util.Dom.addClass(that.container, 'site-comment-green');
		}

		that.updateStatus();
		that.setSensitivity(true);
	}

	SiteCommentDisplay.xml_rpc_client.callProcedure('notSpam', callBack,
		[this.comment_id], ['int']);
}

// }}}
// {{{ toggleSpam()

SiteCommentDisplay.prototype.toggleSpam = function()
{
	if (this.comment_spam) {
		this.notSpam();
	} else {
		this.spam();
	}
}

// }}}
// {{{ setSensitivity()

SiteCommentDisplay.prototype.setSensitivity = function(sensitive)
{
	this.approve_button.disabled        = !sensitive;
	this.deny_button.disabled           = !sensitive;
	this.publish_toggle_button.disabled = !sensitive;
	this.spam_toggle_button.disabled    = !sensitive;
	this.delete_button.disabled         = !sensitive;
}

// }}}
// {{{ updateStatus()

SiteCommentDisplay.prototype.updateStatus = function()
{
	if (this.comment_spam) {
		SiteCommentDisplay.setTextContent(this.status_container,
			' - ' + SiteCommentDisplay.status_spam_text);
	} else {
		switch (this.comment_status) {
		case SiteCommentDisplay.STATUS_UNPUBLISHED:
			SiteCommentDisplay.setTextContent(this.status_container,
				' - ' + SiteCommentDisplay.status_unpublished_text);

			break;

		case SiteCommentDisplay.STATUS_PENDING:
			SiteCommentDisplay.setTextContent(this.status_container,
				' - ' + SiteCommentDisplay.status_pending_text);

			break;

		default:
			SiteCommentDisplay.setTextContent(this.status_container, '');
			break;
		}
	}
}

// }}}
// {{{ deleteComment()

SiteCommentDisplay.prototype.deleteComment = function()
{
	this.confirmation.style.display = 'none';

	var that = this;
	function callBack(response)
	{
		var attributes = { opacity: { to: 0 } };
		var anim = new YAHOO.util.Anim(that.container, attributes, 0.25,
			YAHOO.util.Easing.easeOut);

		anim.onComplete.subscribe(that.shrink, that, true);
		anim.animate();
	}

	SiteCommentDisplay.xml_rpc_client.callProcedure('delete', callBack,
		[this.comment_id], ['int']);
}

// }}}
// {{{ shrink()

SiteCommentDisplay.prototype.shrink = function()
{
	var anim = new YAHOO.util.Anim(this.container, { height: { to: 0 } },
		0.3, YAHOO.util.Easing.easeInStrong);

	anim.onComplete.subscribe(this.removeContainer, this, true);
	anim.animate();
}

// }}}
// {{{ removeContainer()

SiteCommentDisplay.prototype.removeContainer = function()
{
	YAHOO.util.Event.purgeElement(this.container, true);
	this.container.parentNode.removeChild(this.container);
	delete this.container;
}

// }}}
// {{{ confirmDelete()

SiteCommentDisplay.prototype.confirmDelete = function()
{
	this.setSensitivity(false);

	var parent_region = YAHOO.util.Dom.getRegion(this.delete_button);

	this.confirmation.style.display = 'block';

	var region = YAHOO.util.Dom.getRegion(this.confirmation);
	YAHOO.util.Dom.setXY(this.confirmation,
		[parent_region.right - (region.right - region.left),
		parent_region.top]);

	this.confirmation_cancel.focus();
}

// }}}
// {{{ cancelDelete()

SiteCommentDisplay.prototype.cancelDelete = function()
{
	this.confirmation.style.display = 'none';
	this.setSensitivity(true);
}

// }}}

SiteCommentDisplay.prototype.hideControls = function()
{
	if (this.controls_animation && this.controls_animation.isAnimated()) {
		this.controls_animation.stop(false);
	}

	this.controls_animation = new YAHOO.util.Anim(this.controls, { opacity: { to: 0 } }, 0.5);
	this.controls_animation.animate();
};

SiteCommentDisplay.prototype.showControls = function()
{
	if (this.controls_animation && this.controls_animation.isAnimated()) {
		this.controls_animation.stop(false);
	}
	YAHOO.util.Dom.setStyle(this.controls, 'opacity', 1);
};

// {{{ static setTextContent()

SiteCommentDisplay.setTextContent = function(element, text)
{
	if (element.innerText) {
		element.innerText = text;
	} else {
		element.textContent = text;
	}
}

// }}}
