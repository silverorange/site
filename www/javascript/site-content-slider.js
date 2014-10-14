/**
 * Initializes sliders on this page after the document has been loaded
 *
 * To set up a slider, use:
 * <div class="site-content-slider">
 * 	<div class="slider-page">Page 1</div>
 * 	<div class="slider-page">Page 2</div>
 * </div>
 *
 * Optional properties on the .site-content-slider div:
 *  - data-nav: display "dot" type navigation between pages
 *  - data-next-prev: display next/prev links
 *  - data-random-start: choose the first page at random
 *  - data-text-next: next button text
 *  - data-text-prev: previous button text
 *
 *  The .site-content-slider div can optionally have height set in CSS, or
 *  if left without height, the pages' height will be set to the height
 *  of the tallest page.
 */
YAHOO.util.Event.onDOMReady(function ()
{
	var sliders = YAHOO.util.Dom.getElementsByClassName('site-content-slider');
	for (var i = 0; i < sliders.length; i++) {
		new SiteContentSlider(sliders[i]);
	}
});

(function () {

	var Dom    = YAHOO.util.Dom;
	var Event  = YAHOO.util.Event;
	var Anim   = YAHOO.util.Anim;
	var Easing = YAHOO.util.Easing;

	// {{{ SiteContentSlider = function()

	/**
	 * Pager widget
	 *
	 * @param DOMElement container
	 */
	SiteContentSlider = function(container)
	{
		this.container = container;

		// stop automatic switching of pages on click
		Event.on(this.container, 'click', function (e) {
			this.clearInterval();
		}, this, true);

		this.page_container = document.createElement('div');
		Dom.addClass(this.page_container, 'page-container');
		Dom.setStyle(this.page_container, 'position', 'absolute');
		Dom.setStyle(this.page_container, 'top', 0);
		Dom.setStyle(this.page_container, 'left', 0);
		this.container.appendChild(this.page_container);

		this.auto_height = (this.container.style.height == '');

		this.pages         = [];
		this.current_page  = null;

		this.touch_x = null;
		this.touch_start_x = null;
		this.touch_start_y = null;
		this.touch_end_x = null;

		this.initSettings();

		var pages = Dom.getElementsByClassName('slider-page', 'div', container);

		for (var i = 0; i < pages.length; i++) {
			this.pages.push(new SiteContentSliderPage(pages[i], i));
		}

		if (this.pages.length > 1 && this.getSetting('nav', false)) {
			this.drawNav();
		} else {
			this.nav = null;
		}

		if (this.pages.length > 1 && this.getSetting('next-prev', false)) {
			this.drawNextPrev();
		} else {
			this.next_prev = null;
		}

		// initialize current page
		if (this.pages.length > 0) {
			var page;

			var random_start_page = this.getSetting('random-start', false);

			if (random_start_page) {
				page = this.getPseudoRandomPage();
			} else {
				page = this.pages[0];
			}

			this.initPages();
			this.setPage(page);
		}

		this.addTouchEvents();
		this.setInterval();

		Event.on(window, 'resize', function() {
			this.initPages();
			this.setPage(this.current_page);
		}, this, true);
	};

	// }}}

	var _interval = null;

	var proto = SiteContentSlider.prototype;

	// {{{ proto.initSettings

	proto.initSettings = function()
	{
		// settings set with "data-x" properties on the container tag
		this.page_click_duration = this.getSetting('page-click-duration', 0.25);
		this.page_auto_duration = this.getSetting('page-auto-duration', 1);
		this.page_interval = this.getSetting('page-interval', 10);
		this.text_next = this.getSetting('text-next', 'Next');
		this.text_prev = this.getSetting('text-prev', 'Previous');
	};

	// }}}
	// {{{ proto.getSetting

	proto.getSetting = function(name, default_value)
	{
		var value = this.container.getAttribute('data-' + name);
		return (typeof value === 'undefined' || value === null)
			? default_value : value;
	};

	// }}}
	// {{{ proto.initPages

	proto.initPages = function()
	{
		var region = Dom.getRegion(this.container);
		var width = region.width;
		var max_height = 0;
		var pos = 0;

		for (var i = 0; i < this.pages.length; i++) {
			Dom.setStyle(this.pages[i].element, 'width', width + 'px');

			max_height = Math.max(max_height,
				Dom.getRegion(this.pages[i].element).height);

			Dom.setStyle(this.pages[i].element, 'top', 0);
			Dom.setStyle(this.pages[i].element, 'left', (pos + -width) + 'px');
			Dom.setStyle(this.pages[i].element, 'position', 'absolute');
			pos += width;

			this.page_container.appendChild(this.pages[i].element);
		}

		if (this.auto_height) {
			Dom.setStyle(this.container, 'height', max_height + 'px');
		} else {
			max_height = Dom.getRegion(this.container).height;
		}

		for (var i = 0; i < this.pages.length; i++) {
			Dom.setStyle(this.pages[i].element, 'height', max_height + 'px');
		}
	};

	// }}}
	// {{{ proto.setPage

	proto.setPage = function(page)
	{
		if (this.current_page !== page) {
			Dom.addClass(page.element, 'slider-current');
			Dom.addClass(page.nav, 'selected');

			if (this.current_page !== null) {
				Dom.removeClass(this.current_page.element, 'slider-current');
				Dom.removeClass(this.current_page.nav, 'selected');
			}

			this.current_page = page;
		}

		var width = Dom.getRegion(this.container).width;
		this.setSpeed(0);
		this.positionPages(-width * page.index);
		this.updateNextPrev();
	};

	// }}}
	// {{{ proto.setPageWithAnimation

	proto.setPageWithAnimation = function(page, speed, start_pos)
	{
		var width = Dom.getRegion(this.container).width;
		this.setSpeed(speed);
		this.positionPages(-width * page.index);
		this.setPage(page);
	};

	// }}}
	// {{{ proto.setSpeed

	proto.setSpeed = function(speed)
	{
		var s = 'left ' + speed + 's';
		Dom.setStyle(this.page_container, '-moz-transition', s);
		Dom.setStyle(this.page_container, '-webkit-transition', s);
		Dom.setStyle(this.page_container, '-o-transition', s);
		Dom.setStyle(this.page_container, 'transition', s);
	};

	// }}}
	// {{{ proto.positionPages

	proto.positionPages = function(pos)
	{
		var region = Dom.getRegion(this.container);
		var width = region.width;
		Dom.setStyle(this.page_container, 'left', (pos + width) + 'px');
	};

	// }}}
	// {{{ proto.setInterval

	proto.setInterval = function()
	{
		var that = this;
		_interval = setInterval(
			function ()
			{
				that.nextPageWithAnimation(that.page_auto_duration);
			},
			that.page_interval * 1000
		);
	};

	// }}}
	// {{{ proto.clearInterval

	proto.clearInterval = function()
	{
		if (_interval) {
			clearInterval(_interval);
		}
	};

	// }}}
	// {{{ proto.getPseudoRandomPage

	proto.getPseudoRandomPage = function()
	{
		var page = null;

		if (this.pages.length > 0) {
			var now = new Date();
			page = this.pages[now.getSeconds() % this.pages.length];
		}

		return page;
	};

	// }}}
	// {{{ proto.prevPageWithAnimation

	proto.prevPageWithAnimation = function(speed)
	{
		var index = this.current_page.index - 1;
		if (index < 0) {
			index = this.pages.length - 1;
		}

		this.setPageWithAnimation(this.pages[index], speed);
	};

	// }}}
	// {{{ proto.nextPageWithAnimation

	proto.nextPageWithAnimation = function(speed)
	{
		var index = this.current_page.index + 1;
		if (index >= this.pages.length) {
			// TODO: add "infinite" scrolling so it scrolls to the first
			// page to the left
			if (this.pages.length > 2) {
				this.setPage(this.pages[0], speed);
			} else {
				this.setPageWithAnimation(this.pages[0], speed);
			}
		} else {
			this.setPageWithAnimation(this.pages[index], speed);
		}
	};

	// }}}
	// {{{ proto.addTouchEvents

	proto.addTouchEvents = function()
	{
		var that = this;

		Event.on(this.container, 'touchstart', function (e) {
			var touch = e.touches[0];
			that.touch_start_x = touch.pageX;
			that.touch_start_y = touch.pageY;
		});

		Event.on(this.container, 'touchend', function (e) {
			var width = Dom.getRegion(that.container).width;

			// only move the page if the drag was more that 1/5 width
			if (Math.abs(that.touch_end_x - that.touch_start_x) < (width / 5)) {
				var new_index = that.current_page.index;
			} else if (that.touch_end_x < that.touch_start_x) {
				var new_index = that.current_page.index + 1;
			} else {
				var new_index = that.current_page.index - 1;
			}

			new_index = Math.max(0, new_index);
			new_index = Math.min(that.pages.length - 1, new_index);

			that.setPageWithAnimation(that.pages[new_index],
				that.page_click_duration,
				that.touch_x);

			that.touch_start_x = null;
			that.touch_start_y = null;
		});

		Event.on(window, 'touchmove', function (e) {
			if (that.touch_start_x === null) {
				return;
			}

			var touch = e.touches[0];

			// Prevent vertical scrolling if the movement is mostly horizontal.
			// This keeps the page from bouncing around.
			if (Math.abs(that.touch_start_x - touch.pageX) > 10) {
				Event.preventDefault(e);

				// also stop the automatical animation of pages
				that.clearInterval();
			}

			var width = Dom.getRegion(that.container).width;
			that.touch_end_x = touch.pageX;

			if (Math.abs(that.touch_start_x - that.touch_end_x) > width) {
				if (that.touch_start_x > that.touch_end_x) {
					var drag_width = width;
				} else {
					var drag_width = -width;
				}
			} else {
				var drag_width = that.touch_start_x - that.touch_end_x;
			}

			that.touch_x = Math.min(width,
				(that.current_page.index * -width) - drag_width);

			// if at the end/beginning, slow down drag
			if (that.touch_x > 0 ||
				that.touch_x < (that.pages.length - 1) * -width) {
				that.touch_x = that.touch_x + (drag_width / 2);
			}

			that.positionPages(that.touch_x);
		});
	};

	// }}}
	// {{{ proto.drawNav

	proto.drawNav = function()
	{
		this.nav = document.createElement('div');
		Dom.addClass(this.nav, 'slider-nav');
		this.container.appendChild(this.nav);

		var that = this;

		for (var i = 0; i < this.pages.length; i++) {
			Event.on(this.pages[i].nav, 'click', function (e) {
				Event.preventDefault(e);
				that.clearInterval();

				that.setPageWithAnimation(this,
					that.page_click_duration);

			}, this.pages[i], true);

			this.nav.appendChild(this.pages[i].nav);
		}
	};

	// }}}
	// {{{ proto.drawNextPrev

	proto.drawNextPrev = function()
	{
		// create previous link
		this.prev = document.createElement('a');
		this.prev.href = '#previous-page';
		Dom.addClass(this.prev, 'slider-prev');
		this.prev.appendChild(
			document.createTextNode(this.text_prev)
		);

		this.prev_insensitive = document.createElement('span');
		Dom.addClass(this.prev_insensitive, 'swat-hidden');
		Dom.addClass(this.prev_insensitive, 'slider-prev-insensitive');
		this.prev_insensitive.appendChild(
			document.createTextNode(this.text_prev)
		);

		Event.on(this.prev, 'click', function (e) {
			Event.preventDefault(e);
			this.clearInterval();
			this.prevPageWithAnimation(this.page_click_duration);
		}, this, true);

		Event.on(this.prev, 'dblclick', function (e) {
			Event.preventDefault(e);
		}, this, true);


		// create next link
		this.next = document.createElement('a');
		this.next.href = '#next-page';
		Dom.addClass(this.next, 'slider-next');
		this.next.appendChild(
			document.createTextNode(this.text_next)
		);

		this.next_insensitive = document.createElement('span');
		Dom.addClass(this.next_insensitive, 'swat-hidden');
		Dom.addClass(this.next_insensitive, 'slider-next-insensitive');
		this.next_insensitive.appendChild(
			document.createTextNode(this.text_next)
		);

		Event.on(this.next, 'click', function (e) {
			Event.preventDefault(e);
			this.clearInterval();
			this.nextPageWithAnimation(this.page_click_duration);
		}, this, true);

		Event.on(this.next, 'dblclick', function (e) {
			Event.preventDefault(e);
		}, this, true);

		// create navigation element
		this.next_prev = document.createElement('div');
		Dom.addClass(this.next_prev, 'slider-next-prev');
		this.next_prev.appendChild(this.prev_insensitive);
		this.next_prev.appendChild(this.prev);
		this.next_prev.appendChild(this.next);
		this.next_prev.appendChild(this.next_insensitive);

		this.container.appendChild(this.next_prev);
	};

	// }}}
	// {{{ proto.setPrevSensitivity

	proto.setPrevSensitivity = function(sensitive)
	{
		if (this.prev) {
			if (sensitive) {
				Dom.addClass(this.prev_insensitive, 'swat-hidden');
				Dom.removeClass(this.prev, 'swat-hidden');
			} else {
				Dom.addClass(this.prev, 'swat-hidden');
				Dom.removeClass(this.prev_insensitive, 'swat-hidden');
			}
		}
	};

	// }}}
	// {{{ proto.setNextSensitivity

	proto.setNextSensitivity = function(sensitive)
	{
		if (this.next) {
			if (sensitive) {
				Dom.addClass(this.next_insensitive, 'swat-hidden');
				Dom.removeClass(this.next, 'swat-hidden');
			} else {
				Dom.addClass(this.next, 'swat-hidden');
				Dom.removeClass(this.next_insensitive, 'swat-hidden');
			}
		}
	};

	// }}}
	// {{{ proto.updateNextPrev

	proto.updateNextPrev = function()
	{
		var page_number = this.current_page.index + 1;
		var page_count  = this.pages.length;

		this.setPrevSensitivity(page_number != 1);
		this.setNextSensitivity(page_number != page_count);
	};

	// }}}

	/**
	 * Page in a slider
	 *
	 * @param DOMElement element
	 */
	SiteContentSliderPage = function(element, index)
	{
		this.element = element;
		this.index = index;

		this.nav = document.createElement('a');
		this.nav.href = '#';
		this.nav.appendChild(document.createElement('span'));
	};

})();
