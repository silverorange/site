var SiteDialog = function(el, user_config)
{
	SiteDialog.addSentinel();

	this.initConfig(user_config);
	this.initElements(el);

	SiteDialog.dialogs.push(this);
};

SiteDialog.STATE_OPENED = 1;
SiteDialog.STATE_CLOSED = 2;
SiteDialog.dialogs = [];
SiteDialog.has_push_state = (window.pushState);
SiteDialog.opened_dialog_stack = [];
SiteDialog.desktop_sentinel = null;
SiteDialog.is_desktop = false;
SiteDialog.scroll_top = 0;
SiteDialog.resize_debounce_delay = 30;

SiteDialog.hideBodyChildren = function()
{
	if (SiteDialog.opened_dialog_stack.length === 0) {
		for (i = 0; i < document.body.childNodes.length; i++) {
			var node = document.body.childNodes[i];
			YAHOO.util.Dom.removeClass(node, 'site-dialog-hidden');
		}
		window.scrollTo(0, SiteDialog.scroll_top);
	} else {
		// save scroll position
		SiteDialog.scroll_top = YAHOO.util.Dom.getDocumentScrollTop();

		window.scrollTo(0, 0);

		var top_index = SiteDialog.opened_dialog_stack.length - 1;
		var top_dialog = SiteDialog.opened_dialog_stack[top_index];
		for (i = 0; i < document.body.childNodes.length; i++) {
			var node = document.body.childNodes[i];

			// don't hide the top-level opened dialog
			if (node === top_dialog.dialog) {
				YAHOO.util.Dom.removeClass(node, 'site-dialog-hidden');

			// don't hide sentinel
			} else if (node !== SiteDialog.desktop_sentinel) {
				YAHOO.util.Dom.addClass(node, 'site-dialog-hidden');
			}
		}
	}
};

SiteDialog.showBodyChildren = function()
{
	for (i = 0; i < document.body.childNodes.length; i++) {
		var node = document.body.childNodes[i];
		YAHOO.util.Dom.removeClass(node, 'site-dialog-hidden');
	}
};

SiteDialog.raiseDialog = function(dialog)
{
	var index = null;
	for (var i = 0; i < SiteDialog.opened_dialog_stack.length; i++) {
		if (SiteDialog.opened_dialog_stack[i] === dialog) {
			index = i;
			break;
		}
	}

	if (index !== null) {
		SiteDialog.opened_dialog_stack.splice(index, 1);
	}

	SiteDialog.opened_dialog_stack.push(dialog);
	SiteDialog.hideBodyChildren();
};

SiteDialog.lowerDialog = function(dialog)
{
	var index = null;
	for (var i = 0; i < SiteDialog.opened_dialog_stack.length; i++) {
		if (SiteDialog.opened_dialog_stack[i] === dialog) {
			index = i;
			break;
		}
	}

	if (index !== null) {
		SiteDialog.opened_dialog_stack.splice(index, 1);
	}

	SiteDialog.hideBodyChildren();
};

SiteDialog.addSentinel = function()
{
	if (SiteDialog.desktop_sentinel === null) {
		SiteDialog.desktop_sentinel = document.createElement('div');
		SiteDialog.desktop_sentinel.className = 'site-dialog-sentinel';
		document.body.appendChild(SiteDialog.desktop_sentinel);

		var timeout = null;

		var checkSentinel = function()
		{
			var display = YAHOO.util.Dom.getComputedStyle(
				SiteDialog.desktop_sentinel,
				'display'
			);

			if (display === 'none' && !SiteDialog.is_desktop) {
				// changing from mobile to desktop
				SiteDialog.is_desktop = true;
				SiteDialog.handleLayoutChange();
			} else if (display === 'block' && SiteDialog.is_desktop) {
				// changing from desktop to mobile
				SiteDialog.is_desktop = false;
				SiteDialog.handleLayoutChange();
			}
		};

		var handleResize = function()
		{
			for (var i = 0; i < SiteDialog.dialogs.length; i++) {
				SiteDialog.dialogs[i].handleResize();
			}
		};

		// Initialize layout state
		if (YAHOO.util.Dom.hasClass(document.documentElement, 'ie8')) {
			// Give IE8 time to load responsive styles before initializing
			// mode. It needs to re-download and parse all the CSS. Respond.js
			// does not provide an event for this. This is a big fat hack.
			setTimeout(checkSentinel, 1000);
		} else {
			checkSentinel();
		}

		YAHOO.util.Event.on(
			window,
			'resize',
			function (e) {
				// Debounce resize updates so they only fire every at most
				// every 10 ms.
				if (timeout) {
					clearTimeout(timeout);
				}
				timeout = setTimeout(function() {
					checkSentinel();
					handleResize();
					timeout = null;
				}, SiteDialog.resize_debounce_delay);
			}
		);
	}
};

SiteDialog.handleLayoutChange = function()
{
	for (var i = 0; i < SiteDialog.dialogs.length; i++) {
		SiteDialog.dialogs[i].handleLayoutChange();
	}
};

(function() {
	var Dom   = YAHOO.util.Dom;
	var Event = YAHOO.util.Event;
	var Anim  = YAHOO.util.Anim;

	var proto = SiteDialog.prototype;

	// {{{ Configuration

	var DEFAULT_CONFIG = {
		'USE_OVERLAY': {
			key: 'use_overlay',
			value: true,
			validator: YAHOO.lang.isBoolean
		},
		/**
		 * Allow clicking outside the dialog to close the dialog.
		 */
		'DISMISSABLE': {
			key: 'dismissable',
			value: true,
			validator: YAHOO.lang.isBoolean
		},
		/**
		 * Only show dialog in mobile layout. If switching back to desktop, the
		 * dialog is automatically closed.
		 */
		'MOBILE_ONLY': {
			key: 'mobile_only',
			value: false,
			validator: YAHOO.lang.isBoolean
		},
		/**
		 * Use pushState API if available to control opening and closing the
		 * dialog.
		 */
		'USE_PUSH_STATE': {
			key: 'use_push_state',
			value: true,
			validator: YAHOO.lang.isBoolean
		},
		'CLASS_NAME': {
			key: 'class_name',
			value: ''
		},
		'TOGGLE_ELEMENT': {
			key: 'toggle_element',
			value: null
		},
		'RELATIVE_CONTAINER': {
			key: 'relative_container',
			value: null
		},
		'FULL_SCREEN': {
			key: 'full_screen',
			value: true,
			validator: YAHOO.lang.isBoolean
		}
	};

	proto.initDefaultConfig = function()
	{
		this.config.addProperty(DEFAULT_CONFIG.USE_OVERLAY.key, {
			value: DEFAULT_CONFIG.USE_OVERLAY.value,
			validator: DEFAULT_CONFIG.USE_OVERLAY.validator
		});

		this.config.addProperty(DEFAULT_CONFIG.DISMISSABLE.key, {
			value: DEFAULT_CONFIG.DISMISSABLE.value,
			validator: DEFAULT_CONFIG.DISMISSABLE.validator
		});

		this.config.addProperty(DEFAULT_CONFIG.MOBILE_ONLY.key, {
			value: DEFAULT_CONFIG.MOBILE_ONLY.value,
			validator: DEFAULT_CONFIG.MOBILE_ONLY.validator
		});

		this.config.addProperty(DEFAULT_CONFIG.USE_PUSH_STATE.key, {
			value: DEFAULT_CONFIG.USE_PUSH_STATE.value,
			validator: DEFAULT_CONFIG.USE_PUSH_STATE.validator
		});

		this.config.addProperty(DEFAULT_CONFIG.CLASS_NAME.key, {
			value: DEFAULT_CONFIG.CLASS_NAME.value
		});

		this.config.addProperty(DEFAULT_CONFIG.TOGGLE_ELEMENT.key, {
			value: DEFAULT_CONFIG.TOGGLE_ELEMENT.value
		});

		this.config.addProperty(DEFAULT_CONFIG.FULL_SCREEN.key, {
			value: DEFAULT_CONFIG.FULL_SCREEN.value,
			validator: DEFAULT_CONFIG.FULL_SCREEN.validator
		});

		this.config.addProperty(DEFAULT_CONFIG.RELATIVE_CONTAINER.key, {
			value: DEFAULT_CONFIG.RELATIVE_CONTAINER.value
		});
	};

	proto.initConfig = function(user_config)
	{
		this.config = new YAHOO.util.Config(this);
		this.initDefaultConfig();

		// Merge user and default config values.
		if (user_config) {
			this.config.applyConfig(user_config, true);
		}

		// Flatten config object. We're not using events.
		this.config = this.config.getConfig();
	};

	// }}}

	proto.initElements = function(el)
	{
		this.header = this.drawHeader();
		this.body = this.drawBody();
		this.footer = this.drawFooter();
		this.container = this.drawContainer(this.header, this.body, this.footer);

		this.dialog = this.drawDialog(this.container, el);

		this.id = this.dialog.id;

		if (this.config.use_overlay) {
			this.overlay = this.drawOverlay();
		}

		this.state = SiteDialog.STATE_OPENED;
		this.close();

		if (this.config.use_overlay) {
			document.body.appendChild(this.overlay);
		}

		if (this.config.dismissable) {
			Event.on(
				document.body,
				'click',
				this.handleDocumentClick,
				this,
				true
			);
		}

		if (SiteDialog.is_desktop && this.config.relative_container) {
			this.config.relative_container.appendChild(this.dialog);
		} else {
			document.body.appendChild(this.dialog);
		}

		if (SiteDialog.has_push_state && this.config.use_push_state) {
			Event.on(window, 'popstate', this.handlePopState, this, true);
		}
	};

	proto.getPushStateId = function()
	{
		return this.id;
	};

	// {{{ Element draw methods

	proto.drawOverlay = function()
	{
		var overlay = document.createElement('div');
		overlay.className = 'site-dialog-overlay';
		return overlay;
	};

	proto.drawDialog = function(container, el)
	{
		var dialog;

		if (el) {
			dialog = Dom.get(el);
			if (!dialog) {
				dialog = document.createElement('div');
				dialog.id = el;
			}
		} else {
			dialog = document.createElement('div');
		}

		Dom.generateId(dialog, 'site-dialog');

		Dom.addClass(dialog, 'site-dialog-dialog');
		if (this.config.relative_container) {
			Dom.addClass(dialog, 'site-dialog-relative');
		}
		if (this.config.class_name + '' !== '') {
			Dom.addClass(dialog, this.config.class_name);
		}

		dialog.appendChild(container);

		return dialog;
	};

	proto.drawContainer = function(header, body, footer)
	{
		var container = document.createElement('div');
		container.className = 'site-dialog-container';

		container.appendChild(header);
		container.appendChild(body);
		container.appendChild(footer);

		return container;
	};

	proto.drawHeader = function()
	{
		var header = document.createElement('div');
		header.className = 'site-dialog-header';
		return header;
	};

	proto.drawBody = function()
	{
		var body = document.createElement('div');
		body.className = 'site-dialog-body';
		return body;
	};

	proto.drawFooter = function()
	{
		var footer = document.createElement('div');
		footer.className = 'site-dialog-footer';
		return footer;
	};

	// }}}
	// {{{ Opening and closing

	proto.open = function()
	{
		if (this.isOpened()) {
			return;
		}

		this.raise();

		Dom.removeClass(this.overlay, 'site-dialog-closed');
		Dom.removeClass(this.dialog, 'site-dialog-closed');

		this.handleResize();

		// bubble up opened stack if not desktop
		if (!SiteDialog.is_desktop) {
			SiteDialog.raiseDialog(this);
		}

		this.state = SiteDialog.STATE_OPENED;
	};

	proto.openWithAnimation = function()
	{
		this.open();
	};

	proto.close = function()
	{
		if (this.isClosed()) {
			return;
		}

		// remove from opened stack
		SiteDialog.lowerDialog(this);

		Dom.addClass(this.overlay, 'site-dialog-closed');
		Dom.addClass(this.dialog, 'site-dialog-closed');

		this.state = SiteDialog.STATE_CLOSED;
	};

	proto.closeWithAnimation = function()
	{
		this.close();
	};

	proto.isOpened = function()
	{
		return (this.state === SiteDialog.STATE_OPENED);
	};

	proto.isClosed = function()
	{
		return (!this.isOpened());
	};

	proto.raise = function()
	{
		if (this.overlay) {
			SwatZIndexManager.raiseElement(this.overlay);
		}
		SwatZIndexManager.raiseElement(this.dialog);
	};

	proto.toggle = function()
	{
		if (this.isOpened()) {
			this.close();
		} else {
			this.open();
		}
	};

	proto.toggleWithAnimation = function()
	{
		if (this.isOpened()) {
			this.closeWithAnimation();
		} else {
			this.openWithAnimation();
		}
	};

	// }}}
	// {{{ Content setup methods

	proto.appendToHeader = function(node)
	{
		this.header.appendChild(node);
	};

	proto.appendToBody = function(node)
	{
		this.body.appendChild(node);
	};

	proto.appendToFooter = function(node)
	{
		this.footer.appendChild(node);
	};

	// }}}

	proto.handleLayoutChange = function()
	{
		// switching from mobile to desktop
		if (SiteDialog.is_desktop) {
			// remove from stack if opened
			if (this.isOpened()) {
				SiteDialog.lowerDialog(this);
			}

			// close if mobile only
			if (this.config.mobile_only) {
				this.close();
			}

			// put dialog back in relative container
			if (this.config.relative_container) {
				this.config.relative_container.appendChild(this.dialog);
			}

		// switching from desktop to mobile
		} else {

			// put dialog in body
			if (this.config.relative_container) {
				document.body.appendChild(this.dialog);
			}

			// add to stack if opened
			if (this.isOpened()) {
				SiteDialog.raiseDialog(this);
			}
		}
	};

	proto.handleResize = function()
	{
		if (this.config.full_screen || !SiteDialog.is_desktop) {
			var header_region = Dom.getRegion(this.header);
			var footer_region = Dom.getRegion(this.footer);

			var margin = parseInt(Dom.getStyle(this.container, 'marginTop')) +
				parseInt(Dom.getStyle(this.container, 'marginBottom'));

			margin = (isNaN(margin)) ? 0 : margin;

			this.body.style.height = (
				Dom.getViewportHeight() -
				header_region.height -
				footer_region.height -
				margin
			) + 'px';

			this.dialog.style.height = Dom.getViewportHeight() + 'px';
		} else {
			this.dialog.style.height = 'auto';
			this.body.style.height = 'auto';
		}
	};

	proto.handleDocumentClick = function(e)
	{
		if (this.isOpened()) {
			var prevent_close = false;
			var target = Event.getTarget(e);
			while (target.parentNode && !prevent_close) {
				if (target === this.dialog ||
					target === this.config.toggle_element) {
					prevent_close = true;
				}
				target = target.parentNode;
			}

			if (!prevent_close) {
				this.closeWithAnimation();
			}
		}
	};

	proto.handlePopState = function(e)
	{
		// TODO: push/pop-state is not supported yet.
		if (e.state && e.state.id && e.state.id === this.getPushStateId()) {
			this.open();
		} else {
			this.close();
		}
	};

})();
