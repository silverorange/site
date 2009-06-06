function SiteCommentStatusSlider(id, options)
{
	this.id = id;
	this.options = options;
	this.width = 200;
	this.legend_labels = [];
	if (this.options.length < 2) {
		// prevent script execution errors when there are no or one option
		this.increment = 1;
	} else {
		this.increment = Math.floor(this.width / (this.options.length - 1));
	}
	this.label_width = this.increment;

	YAHOO.util.Event.onDOMReady(this.init, this, true);
}

SiteCommentStatusSlider.prototype.init = function()
{
	this.input = document.getElementById(this.id + '_value');

	// create slider object
	this.slider = YAHOO.widget.Slider.getHorizSlider(this.id,
		this.id + '_thumb', 0, this.width, this.increment);

	this.slider.subscribe('change', this.handleChange, this, true);

	this.createContextNote();

	// get initial selected value index
	var index = null;
	for (var i = 0; i < this.options.length; i++) {
		if (this.input.value == this.options[i][0]) {
			index = i;
			break;
		}
	}

	// select initial selected value
	var value = 0;
	if (index !== null) {
		value = index * this.increment;
	}

	this.slider.setValue(value, true, false, false);

	this.createLabels();
}

SiteCommentStatusSlider.prototype.createLabels = function()
{
	var container = document.getElementById(this.id);
	var pos = YAHOO.util.Dom.getXY(container);

	// 8 is pixel position of first tick mark in graphic + css offset
	var x_offset = -Math.floor(this.increment / 2) + 8;
	var y_offset = 30;

	for (var i = 0; i < this.options.length; i++) {
		var span = document.createElement('span');
		span.appendChild(document.createTextNode(this.options[i][1]));
		span.style.position = 'absolute';
		span.style.width = this.label_width + 'px';
		span.style.textAlign = 'center';
		span.style.overflow = 'hidden';
		YAHOO.util.Dom.addClass(span, 'site-comment-status-slider-legend');
		container.appendChild(span);
		YAHOO.util.Dom.setXY(span,
			[pos[0] + (this.increment * i) + x_offset, pos[1] + y_offset]);

		this.legend_labels[i] = span;
	}
}

SiteCommentStatusSlider.prototype.createContextNote = function()
{
	this.context_note = document.createElement('div');
	YAHOO.util.Dom.addClass(this.context_note, 'swat-note');
	YAHOO.util.Dom.addClass(this.context_note,
		'site-comment-status-slider-context-note');

	var container = document.getElementById(this.id);
	if (container.nextSibling) {
		container.parentNode.insertBefore(this.context_note,
			container.nextSibling);
	} else {
		container.parentNode.appendChild(this.context_note);
	}
}

SiteCommentStatusSlider.prototype.handleChange = function()
{
	var index = this.getIndex();
	if (this.options.length > 1) {
		this.input.value = this.options[index][0];
	}
	this.updateContextNote();
	this.updateLegendLabels();
}

SiteCommentStatusSlider.prototype.getIndex = function()
{
	return Math.floor(this.slider.getValue() / this.increment);
}

SiteCommentStatusSlider.prototype.updateContextNote = function()
{
	var index = this.getIndex();
	if (this.context_note.firstChild)
		this.context_note.removeChild(this.context_note.firstChild);

	if (this.options.length > 1) {
		this.context_note.appendChild(
			document.createTextNode(this.options[index][2]));
	}
}

SiteCommentStatusSlider.prototype.updateLegendLabels = function()
{
	var index = this.getIndex();
	for (var i = 0; i < this.options.length; i++) {
		if (i == index) {
			YAHOO.util.Dom.addClass(this.legend_labels[i],
				'site-comment-status-slider-legend-selected');
		} else {
			YAHOO.util.Dom.removeClass(this.legend_labels[i],
				'site-comment-status-slider-legend-selected');
		}
	}
}
