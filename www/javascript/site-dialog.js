const SiteDialog = (() => {
  const DEFAULT_CONFIG = {
    USE_OVERLAY: {
      key: 'use_overlay',
      value: true,
      validator: YAHOO.lang.isBoolean
    },
    /**
     * Allow clicking outside the dialog to close the dialog.
     */
    DISMISSABLE: {
      key: 'dismissable',
      value: true,
      validator: YAHOO.lang.isBoolean
    },
    /**
     * Only show dialog in mobile layout. If switching back to desktop, the
     * dialog is automatically closed.
     */
    MOBILE_ONLY: {
      key: 'mobile_only',
      value: false,
      validator: YAHOO.lang.isBoolean
    },
    /**
     * Use pushState API if available to control opening and closing the
     * dialog.
     */
    USE_PUSH_STATE: {
      key: 'use_push_state',
      value: true,
      validator: YAHOO.lang.isBoolean
    },
    CLASS_NAME: {
      key: 'class_name',
      value: ''
    },
    TOGGLE_ELEMENT: {
      key: 'toggle_element',
      value: null
    },
    RELATIVE_CONTAINER: {
      key: 'relative_container',
      value: null
    },
    RESIZE_MODE: {
      key: 'resize_mode',
      value: SiteDialog.RESIZE_FILL, // TODO
      validator: YAHOO.lang.isNumber
    }
  };

  return class {
    static STATE_OPENED = 1;
    static STATE_CLOSED = 2;
    static dialogs = [];
    static has_push_state = window.pushState;
    static opened_dialog_stack = [];
    static desktop_sentinel = null;
    static is_desktop = false;
    static scroll_top = null;
    static resize_debounce_delay = 30;

    static RESIZE_NONE = 0;
    static RESIZE_FILL = 1;
    static RESIZE_CENTER = 2;

    static id_counter = 0;

    constructor(el, user_config) {
      this.constructor.addSentinel();

      this.initConfig(user_config);
      this.initElements(el);

      this.constructor.dialogs.push(this);
    }

    static updateLayout() {
      if (this.constructor.opened_dialog_stack.length === 0) {
        document.body.children.forEach(childEl => {
          childEl.classList.removeClass('site-dialog-hidden');
        });

        // Also re-show all dialogs. Needed because relatively positioned
        // dialogs may not be children of the body element when updateLayout()
        // is called.
        this.constructor.dialogs.forEach(dialog => {
          dialog.classList.remove('site-dialog-hidden');
          if (dialog.overlay) {
            dialog.overlay.classList.remove('site-dialog-hidden');
          }
        });

        if (this.constructor.scroll_top !== null) {
          window.scrollTo(0, this.constructor.scroll_top);
          this.constructor.scroll_top = null;
        }
      } else {
        // save scroll position
        this.constructor.scroll_top = window.scrollY;

        window.scrollTo(0, 0);

        const top_index = this.constructor.opened_dialog_stack.length - 1;
        const top_dialog = this.constructor.opened_dialog_stack[top_index];
        document.body.childNodes.forEach(node => {
          if (node === top_dialog.dialog) {
            // don't hide the top-level opened dialog
            node.classList.remove('site-dialog-hidden');
          } else if (node !== this.constructor.desktop_sentinel) {
            // don't hide sentinel
            node.classList.add('site-dialog-hidden');
          }
        });
      }
    }

    static raiseDialog(dialog) {
      const index = this.constructor.opened_dialog_stack.findIndex(
        opened_dialog => opened_dialog === dialog
      );

      if (index !== -1) {
        this.constructor.opened_dialog_stack.splice(index, 1);
      }

      this.constructor.opened_dialog_stack.push(dialog);
      this.constructor.updateLayout();
    }

    static lowerDialog(dialog) {
      const index = this.constructor.opened_dialog_stack.findIndex(
        opened_dialog => opened_dialog === dialog
      );

      if (index !== -1) {
        this.constructor.opened_dialog_stack.splice(index, 1);
      }

      this.constructor.updateLayout();
    }

    static addSentinel() {
      if (this.constructor.desktop_sentinel === null) {
        this.constructor.desktop_sentinel = document.createElement('div');
        this.constructor.desktop_sentinel.className = 'site-dialog-sentinel';
        document.body.appendChild(this.constructor.desktop_sentinel);

        let timeout = null;

        const checkSentinel = () => {
          const display = window.getComputedStyle(
            this.constructor.desktop_sentinel
          ).display;

          if (display === 'none' && !this.constructor.is_desktop) {
            // changing from mobile to desktop
            this.constructor.is_desktop = true;
            this.constructor.handleLayoutChange();
          } else if (display === 'block' && this.constructor.is_desktop) {
            // changing from desktop to mobile
            this.constructor.is_desktop = false;
            this.constructor.handleLayoutChange();
          }
        };

        const handleResize = () => {
          this.constructor.dialogs.forEach(dialog => dialog.handleResize());
        };

        // Initialize layout state
        checkSentinel();

        window.addEventListener('resize', () => {
          // Debounce resize updates so they only fire every at most
          // every this.constructor.resize_debounce_delay ms.
          if (timeout) {
            clearTimeout(timeout);
          }
          timeout = setTimeout(() => {
            checkSentinel();
            handleResize();
            timeout = null;
          }, this.constructor.resize_debounce_delay);
        });
      }
    }

    static handleLayoutChange() {
      this.constructor.dialogs.forEach(dialog => dialog.handleLayoutChange());
    }

    static generateId(el) {
      this.constructor.id_counter++;
      return 'site-dialog' + this.constructor.id_counter;
    }

    initDefaultConfig() {
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

      this.config.addProperty(DEFAULT_CONFIG.RESIZE_MODE.key, {
        value: DEFAULT_CONFIG.RESIZE_MODE.value,
        validator: DEFAULT_CONFIG.RESIZE_MODE.validator
      });

      this.config.addProperty(DEFAULT_CONFIG.RELATIVE_CONTAINER.key, {
        value: DEFAULT_CONFIG.RELATIVE_CONTAINER.value
      });
    }

    initConfig(user_config) {
      this.config = new YAHOO.util.Config(this);
      this.initDefaultConfig();

      // Merge user and default config values.
      if (user_config) {
        this.config.applyConfig(user_config, true);
      }

      // Flatten config object. We're not using events.
      this.config = this.config.getConfig();
    }

    initElements(el) {
      this.header = this.drawHeader();
      this.body = this.drawBody();
      this.scroll = this.drawScroll(this.header, this.body);
      this.footer = this.drawFooter();
      this.container = this.drawContainer(this.scroll, this.footer);

      this.dialog = this.drawDialog(this.container, el);

      this.id = this.dialog.id;

      if (this.config.use_overlay) {
        this.overlay = this.drawOverlay();
      }

      this.state = this.constructor.STATE_OPENED;
      this.close();

      if (this.config.use_overlay) {
        document.body.appendChild(this.overlay);
      }

      if (this.config.dismissable) {
        document.body.addEventListener(
          'click',
          this.handleDocumentClick.bind(this)
        );
        document.body.addEventListener(
          'keydown',
          this.handleDocumentKeyDown.bind(this)
        );
      }

      if (this.constructor.is_desktop && this.config.relative_container) {
        this.config.relative_container.appendChild(this.dialog);
      } else {
        document.body.appendChild(this.dialog);
      }

      if (this.constructor.has_push_state && this.config.use_push_state) {
        window.addEventListener('popstate', this.handlePopState.bind(this));
      }
    }

    getPushStateId() {
      return this.id;
    }

    drawOverlay() {
      const overlay = document.createElement('div');
      overlay.className = 'site-dialog-overlay';
      return overlay;
    }

    drawDialog(container, el) {
      const dialog_id = el === null ? this.constructor.generateId() : el;

      // If el is null or not found, create a dialog element. Otherwise use the
      // element by id.
      const dialog =
        (el === null ? null : document.getElementById(el)) ??
        Object.assign(document.createElement('div'), { id: dialog_id });

      dialog.classList.add('site-dialog-dialog');
      if (this.config.relative_container) {
        dialog.classList.add('site-dialog-relative');
      }
      if (this.config.class_name + '' !== '') {
        dialog.classList.add(this.config.class_name);
      }

      dialog.appendChild(container);

      return dialog;
    }

    drawScroll(header, body) {
      const scroll = document.createElement('div');
      scroll.className = 'site-dialog-scroll';

      scroll.appendChild(header);
      scroll.appendChild(body);

      return scroll;
    }

    drawContainer(scroll, footer) {
      const container = document.createElement('div');
      container.className = 'site-dialog-container';

      container.appendChild(scroll);
      container.appendChild(footer);

      return container;
    }

    drawHeader() {
      const header = document.createElement('div');
      header.className = 'site-dialog-header';
      return header;
    }

    drawBody() {
      const body = document.createElement('div');
      body.className = 'site-dialog-body';
      return body;
    }

    drawFooter() {
      const footer = document.createElement('div');
      footer.className = 'site-dialog-footer';
      return footer;
    }

    open() {
      if (this.isOpened()) {
        return;
      }

      this.raise();

      this.overlay.classList.remove('site-dialog-closed');
      this.dialog.classList.remove('site-dialog-closed');

      // need to set state before doing initial positioning
      this.state = this.constructor.STATE_OPENED;

      this.handleResize();

      // bubble up opened stack if not desktop
      if (!this.constructor.is_desktop) {
        this.constructor.raiseDialog(this);
      }
    }

    openWithAnimation() {
      // TODO: implement animations
      this.open();
    }

    close() {
      if (this.isClosed()) {
        return;
      }

      // remove from opened stack
      this.constructor.lowerDialog(this);

      this.overlay.classList.add('site-dialog-closed');
      this.dialog.classList.add('site-dialog-closed');

      this.state = this.constructor.STATE_CLOSED;
    }

    closeWithAnimation() {
      // TODO: implement animations
      this.close();
    }

    isOpened() {
      return this.state === this.constructor.STATE_OPENED;
    }

    isClosed() {
      return !this.isOpened();
    }

    raise() {
      if (this.overlay) {
        SwatZIndexManager.raiseElement(this.overlay);
      }
      SwatZIndexManager.raiseElement(this.dialog);
    }

    toggle() {
      if (this.isOpened()) {
        this.close();
      } else {
        this.open();
      }
    }

    toggleWithAnimation() {
      if (this.isOpened()) {
        this.closeWithAnimation();
      } else {
        this.openWithAnimation();
      }
    }

    appendToHeader(node) {
      this.header.appendChild(node);
    }

    appendToBody(node) {
      this.body.appendChild(node);
    }

    appendToFooter(node) {
      this.footer.appendChild(node);
    }

    clearHeader() {
      while (this.header.firstChild) {
        this.header.removeChild(this.header.firstChild);
      }
    }

    clearBody() {
      while (this.body.firstChild) {
        this.body.removeChild(this.body.firstChild);
      }
    }

    clearFooter() {
      while (this.footer.firstChild) {
        this.footer.removeChild(this.footer.firstChild);
      }
    }

    handleLayoutChange() {
      // switching from mobile to desktop
      if (this.constructor.is_desktop) {
        // remove from stack if opened
        if (this.isOpened()) {
          this.constructor.lowerDialog(this);
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
          this.constructor.raiseDialog(this);
        }
      }
    }

    handleResize() {
      if (this.isClosed()) {
        return;
      }

      const container_style = window.getComputedStyle(this.container);

      if (
        this.config.resize_mode === this.constructor.RESIZE_FILL ||
        !this.constructor.is_desktop
      ) {
        const footer_region = this.footer.getBoundingClientRect();

        let margin =
          Math.parseInt(container_style.marginTop) +
          Math.parseInt(container_style.marginBottom);

        margin = isNaN(margin) ? 0 : margin;

        this.scroll.style.height =
          window.innerHeight - footer_region.height - margin + 'px';

        this.dialog.style.height = window.innerHeight + 'px';
        this.dialog.style.top = null;
      } else if (this.config.resize_mode === this.constructor.RESIZE_CENTER) {
        this.dialog.style.height = 'auto';
        this.scroll.style.height = 'auto';

        let margin =
          Math.parseInt(container_style.marginTop) +
          Math.parseInt(container_style.marginBottom);

        margin = isNaN(margin) ? 0 : margin;

        const region = this.container.getBoundingClientRect();
        const viewport = window.innerHeight;

        // center vertically in viewport
        this.dialog.style.top = (viewport - region.height - margin) / 2 + 'px';
      } else {
        this.dialog.style.height = 'auto';
        this.scroll.style.height = 'auto';
      }
    }

    handleDocumentClick(e) {
      if (this.isOpened()) {
        const prevent_close = false;
        let target = e.target;
        while (target.parentNode && !prevent_close) {
          if (target === this.dialog || target === this.config.toggle_element) {
            prevent_close = true;
          }
          target = target.parentNode;
        }

        if (!prevent_close) {
          this.closeWithAnimation();
        }
      }
    }

    handleDocumentKeyDown(e) {
      // allow escape key to close dialog
      if (this.isOpened() && e.keyCode === 27) {
        this.closeWithAnimation();
      }
    }

    handlePopState(e) {
      // TODO: push/pop-state is not supported yet.
      if (e.state && e.state.id && e.state.id === this.getPushStateId()) {
        this.open();
      } else {
        this.close();
      }
    }
  };
})();
