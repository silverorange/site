// {{{ function SiteCommentDisplay()

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
	this.hideActions();

	this.controls_animation = null;
	this.actions_animation = null;
	this.actions_shown = false;
}

// }}}

SiteCommentDisplay.edit_text      = 'Edit';
SiteCommentDisplay.approve_text   = 'Approve';
SiteCommentDisplay.deny_text      = 'Deny';
SiteCommentDisplay.publish_text   = 'Publish';
SiteCommentDisplay.unpublish_text = 'Unpublish';
SiteCommentDisplay.spam_text      = 'Spam';
SiteCommentDisplay.not_spam_text  = 'Not Spam';
SiteCommentDisplay.delete_text    = 'Delete';
SiteCommentDisplay.cancel_text    = 'Cancel';
SiteCommentDisplay.actions_text   = 'Actions';

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

	this.actions_container = document.createElement('div');
	this.actions_container.className = 'site-comment-display-actions';

	this.actions_content = document.createElement('div');
	this.actions_content.className = 'site-comment-display-actions-content';

	this.actions_arrow = document.createElement('div');
	this.actions_arrow.className = 'site-comment-display-actions-arrow';

	this.actions_button = document.createElement('input');
	this.actions_button.type = 'button';
	this.actions_button.value = SiteCommentDisplay.actions_text;
	this.actions_button.className = 'site-comment-display-actions-button';
	YAHOO.util.Event.on(this.actions_button, 'click',
		this.toggleActions, this, true);

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

	this.actions_content.appendChild(this.delete_button);
	this.actions_content.appendChild(document.createTextNode(' '));
	this.actions_content.appendChild(this.spam_toggle_button);
	this.actions_content.appendChild(document.createTextNode(' '));
	this.actions_content.appendChild(this.deny_button);
	this.actions_content.appendChild(document.createTextNode(' '));
	this.actions_content.appendChild(this.approve_button);
	this.actions_content.appendChild(document.createTextNode(' '));
	this.actions_content.appendChild(this.publish_toggle_button);
	this.actions_content.appendChild(document.createTextNode(' '));
	this.actions_content.appendChild(this.edit_button);

	this.actions_container.appendChild(this.actions_content);
	this.actions_container.appendChild(this.actions_arrow);

	this.controls.appendChild(this.actions_button);
	this.controls.appendChild(this.actions_container);
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
	this.confirmation_cancel.value = SiteCommentDisplay.cancel_text;
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
		[parent_region.left,
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
// {{{ toggleActions()

SiteCommentDisplay.prototype.toggleActions = function()
{
	if (this.actions_shown) {
		this.hideActions();
	} else {
		this.showActions();
	}
};

// }}}
// {{{ hideActions()

SiteCommentDisplay.prototype.hideActions = function()
{
	if (this.actions_animation && this.actions_animation.isAnimated()) {
		this.actions_animation.stop(false);
	}

	this.actions_animation = new YAHOO.util.Anim(
		this.actions_container, { opacity: { to: 0 } }, 0.4,
		YAHOO.util.Easing.easeOut);

	this.actions_animation.onComplete.subscribe(function() {
		this.actions_container.style.display = 'none';
	}, this, true);

	this.actions_animation.animate();
	this.actions_shown = false;

	YAHOO.util.Event.removeListener(
		document, 'click', this.handleDocumentClick);
};

// }}}
// {{{ showActions()

SiteCommentDisplay.prototype.showActions = function()
{
	if (this.actions_animation && this.actions_animation.isAnimated()) {
		this.actions_animation.stop(false);
	}
	YAHOO.util.Dom.setStyle(this.actions_container, 'opacity', 1);
	this.actions_container.style.display = 'block';
	this.actions_shown = true;
	YAHOO.util.Event.addListener(
		document, 'click', this.handleDocumentClick, this, true);
};

// }}}
// {{{ handleDocumentClick()

SiteCommentDisplay.prototype.handleDocumentClick = function(e)
{
	var in_menu = false;
	var target = YAHOO.util.Event.getTarget(e);

	while (target.parentNode) {
		if (target == this.actions_container || target == this.actions_button) {
			in_menu = true;
			break;
		}
		target = target.parentNode;
	}

	if (!in_menu) {
		this.hideActions();
	}
};

// }}}
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
