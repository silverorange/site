/**
 * Control for selecting multiple tags from a array of tags
 *
 * @package   Site
 * @copyright 2007-2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */

// {{{ SiteTagEntry()

/**
 * Creates a new tag entry widget
 *
 * @param string id the unique identifier of this entry object.
 * @param DS_XHR|DS_JSArray The type of YUI datastore to use for values.
 * @param array initial_selected_tag_array an array of already selected tag
 *                                         strings.
 * @param boolean allow_adding_tags Whether new tags can be added.
 *
 * @see http://developer.yahoo.com/yui/autocomplete/
 */
function SiteTagEntry(id, data_store, initial_selected_tag_array,
	allow_adding_tags)
{
	this.id = id;
	this.data_store = data_store;
	this.initial_selected_tag_array = initial_selected_tag_array;

	this.allow_adding_tags = allow_adding_tags;
	this.display_shortname = true;

	this.selected_tag_array = [];
	this.new_tag_array = [];
	this.input_element = document.getElementById(this.id + '_value');
	this.main_container = document.getElementById(this.id);
	this.item_selected = false;
	this.maximum_tags = 0;
	this.query_delay = 0;
	this.minimum_query_length = 0;
	this.query_match_contains = true;

	YAHOO.util.Event.onContentReady(
		this.id, this.handleOnAvailable, this, true);
}

// }}}
// {{{ handleOnAvailable()

/**
 * Sets up the auto-complete widget used by this tag selection control
 */
SiteTagEntry.prototype.handleOnAvailable = function()
{
	// create list of selected tags
	this.array_element = document.createElement('ul');
	this.array_element.className = 'site-tag-array';
	this.array_element.id = this.id + '_array';

	this.main_container.insertBefore(this.array_element,
		this.main_container.firstChild);

	var that = this;

	// create auto-complete widget
	this.auto_complete = new YAHOO.widget.AutoComplete(
		this.input_element, this.id + '_container', this.data_store, {
		queryDelay:            this.query_delay,
		minQueryLength:        this.minimum_query_length,
		queryMatchContains:    this.query_match_contains,
		highlightClassName:    'site-tag-highlight',
		prehighlightClassName: 'site-tag-prehighlight',
		autoHighlight:         false,
		useShadow:             false,
		forceSelection:        false,
		animVert:              false,
		formatResult:
			function(item, query)
			{
				var title = item[0];
				var shortname = item[1];

				if (!that.display_shortname ||
					title.toLowerCase() == shortname.toLowerCase()) {

					return title;
				} else {
					return title + ' (' + shortname + ')';
				}
			}
	});

	this.auto_complete.itemSelectEvent.subscribe(
		this.addTagFromAutoComplete, this, true);

	if (this.allow_adding_tags) {
		this.a_tag = document.createElement('a');
		this.a_tag.className = 'site-tag-entry-add-tag';
		this.a_tag.href = '#';
		this.a_tag.title = SiteTagEntry.add_text;
		YAHOO.util.Event.addListener(this.a_tag, 'click',
			function(e, entry) {
				YAHOO.util.Event.preventDefault(e);
				entry.createTag();
			}, this);

		this.input_element.parentNode.insertBefore(this.a_tag,
			this.input_element.nextSibling);
	}

	// initialize values passed in
	for (var i = 0; i < this.initial_selected_tag_array.length; i++) {
		this.addTag(this.initial_selected_tag_array[i][1],
			this.initial_selected_tag_array[i][0]);
	}

	// capture selected events so we know whether pressing enter means adding a
	// tag or selecting one from the list
	this.auto_complete.itemArrowToEvent.subscribe(
		this.itemSelected, this, true);

	this.auto_complete.itemArrowFromEvent.subscribe(
		this.itemUnSelected, this, true);

	this.auto_complete.itemMouseOverEvent.subscribe(
		this.itemSelected, this, true);

	this.auto_complete.itemMouseOutEvent.subscribe(
		this.itemUnSelected, this, true);

	var self = this;

	YAHOO.util.Event.addListener(this.input_element, 'keydown',
		function(e, entry) {
			// capture enter key for new tags
			if (YAHOO.util.Event.getCharCode(e) == 13) {

				//alert(entry.auto_complete.isFocused());
				YAHOO.util.Event.stopEvent(e);

				if (!self.item_selected)
					entry.createTag();
			}
		}, this);

	// use key-up instead of key-down to prevent annoying problem where the
	// auto-complete container pops open after adding the tag
	YAHOO.util.Event.addListener(this.input_element, 'keyup',
		function(e, entry) {
			// add tag when "," or ";" is typed
			if (YAHOO.util.Event.getCharCode(e) == 188 ||
				YAHOO.util.Event.getCharCode(e) == 59) {

				var delimeter_pos = entry.input_element.value.indexOf(',');
				if (delimeter_pos == -1)
					delimeter_pos = entry.input_element.value.indexOf(';');

				var tag_name =
					entry.input_element.value.slice(0, delimeter_pos);

				entry.addTag(tag_name);
			}
		}, this);

	this.updateVisibility();
}

// }}}
// {{{ addTagFromAutoComplete()

SiteTagEntry.prototype.addTagFromAutoComplete = function(
	oSelf, elItem, oData)
{
	var tag_name = elItem[2][1];
	var tag_title = elItem[2][0];

	this.addTag(tag_name, tag_title);
}

// }}}
// {{{ addTag()

SiteTagEntry.prototype.addTag = function(tag_name, tag_title)
{
	var total_tags = this.selected_tag_array.length +
		this.new_tag_array.length;

	if (this.maximum_tags > 0 && total_tags >= this.maximum_tags)
		return;

	var found = false;
	this.item_selected = false;

	// trim tag string
	tag_name = tag_name.replace(/^\s+|\s+$/g, '');

	if (tag_name.length == 0)
		return;

	for (var i = 0; i < this.selected_tag_array.length; i++) {
		var tag = this.selected_tag_array[i][1];
		if (tag.toUpperCase() == tag_name.toUpperCase()) {
			this.input_element.value = '';
			return;
		}
	}

	for (var i = 0; i < this.new_tag_array.length; i++) {
		var tag = this.new_tag_array[i];
		if (tag.toUpperCase() == tag_name.toUpperCase()) {
			this.input_element.value = '';
			return;
		}
	}

	if (this.data_store.data) {
		for (var i = 0; i < this.data_store.data.length; i++) {
			var tag = this.data_store.data[i][1];
			if (tag.toUpperCase() == tag_name.toUpperCase()) {
				// get tag title
				var title = this.data_store.data[i][0];

				// remove row from data store
				var element = this.data_store.data.splice(i, 1);

				// add row to array of selected tags, splice returns an array
				this.selected_tag_array.push(element[0]);

				found = true;
				break;
			}
		}
	} else {
		if (tag_title) {
			found = true;
			var title = tag_title;
			this.selected_tag_array.push([tag_title, tag_name]);
		} else {
			found = false;
		}
	}

	// create new tag
	var new_tag = (!found);

	if (new_tag && !this.allow_adding_tags)
		return;

	if (new_tag)
		var title = tag_name;

	// create new array node
	var li_tag = document.createElement('li');
	li_tag.id = this.id + '_tag_' + tag_name;
	li_tag.className = 'site-tag-last';
	YAHOO.util.Dom.setStyle(li_tag, 'opacity', 0);

	var anchor_tag = document.createElement('a');
	anchor_tag.id = this.id + '_tag_remove_' + tag_name;
	anchor_tag.href = '#';
	anchor_tag.appendChild(document.createTextNode(
		SiteTagEntry.remove_text));

	YAHOO.util.Event.addListener(anchor_tag, 'click',
		function (e)
		{
			YAHOO.util.Event.preventDefault(e);
			this.removeTag(tag_name);
		},
		this, true);

	title = this.filterTitle(title);

	if (new_tag) {
		var hidden_tag = document.createElement('input');
		hidden_tag.type = 'hidden';
		hidden_tag.name = this.id + '_new[]';
		hidden_tag.value = tag_name;
		var title_node = document.createTextNode(
			title + ' ' + SiteTagEntry.new_text + ' ');

		YAHOO.util.Dom.addClass(li_tag, 'new-tag');
		li_tag.appendChild(hidden_tag);
		this.new_tag_array.push(tag_name);
	} else {
		var title_node = document.createTextNode(title + ' ');
		var hidden_tag = document.createElement('input');
		hidden_tag.type = 'hidden';
		hidden_tag.name = this.id + '[]';
		hidden_tag.value = tag_name;
		li_tag.appendChild(hidden_tag);
	}

	li_tag.appendChild(title_node);
	li_tag.appendChild(anchor_tag);

	// add array node
	if (this.array_element.lastChild) {
		YAHOO.util.Dom.removeClass(this.array_element.lastChild,
			'site-tag-last');
	}
	this.array_element.appendChild(li_tag);

	var in_attributes = { opacity: { from: 0, to: 1 } };
	var in_animation = new YAHOO.util.Anim(li_tag, in_attributes,
		0.5, YAHOO.util.Easing.easeIn);

	in_animation.animate();

	// clear input value once a value is chosen
	this.input_element.value = '';
	this.updateVisibility();
}

// }}}
// {{{ createTag()

SiteTagEntry.prototype.createTag = function()
{
	var tag_title = this.input_element.value;
	this.addTag(tag_title);
}

// }}}
// {{{ itemSelected()

SiteTagEntry.prototype.itemSelected = function(oSelf, elItem)
{
	this.item_selected = true;
}

// }}}
// {{{ itemUnSelected()

SiteTagEntry.prototype.itemUnSelected = function(oSelf, elItem)
{
	this.item_selected = false;
}

// }}}
// {{{ removeTag()

SiteTagEntry.prototype.removeTag = function(tag_name)
{
	// remove event arrayener
	var anchor_tag = document.getElementById(
		this.id + '_tag_remove_' + tag_name);

	if (anchor_tag)
		YAHOO.util.Event.purgeElement(anchor_tag);

	// remove array node
	var li_tag = document.getElementById(this.id + '_tag_' + tag_name);
	if (li_tag) {
		var out_attributes = { opacity: { from: 1, to: 0 } };
		var out_animation = new YAHOO.util.Anim(li_tag, out_attributes,
			0.25, YAHOO.util.Easing.easeOut);

		out_animation.onComplete.subscribe(function(e)
		{
			li_tag.parentNode.removeChild(li_tag);
		}, this, true);

		out_animation.animate();
	}

	for (var i = 0; i < this.selected_tag_array.length; i++) {
		if (this.selected_tag_array[i][1] == tag_name) {
			// remove row from selected array
			var element = this.selected_tag_array.splice(i, 1);

			if (this.data_store.data) {
				// add row back to data store, splice returns an array
				this.data_store.data.push(element[0]);
				this.data_store.data.sort();
			}

			break;
		}
	}

	for (var i = 0; i < this.new_tag_array.length; i++) {
		if (this.new_tag_array[i] == tag_name) {
			// remove row from selected array
			var element = this.new_tag_array.splice(i, 1);
			break;
		}
	}

	this.updateVisibility();
}

// }}}
// {{{ filterTitle()

SiteTagEntry.prototype.filterTitle = function(title)
{
	title = title.replace(/&amp;/g,  '&');
	title = title.replace(/&lt;/g,   '<');
	title = title.replace(/&gt;/g,   '>');
	title = title.replace(/&quot;/g, '"');
	return title;
}

// }}}
// {{{ updateVisibility()

SiteTagEntry.prototype.updateVisibility= function()
{
	var total_tags = this.selected_tag_array.length +
		this.new_tag_array.length;

	if (this.maximum_tags > 0 && total_tags >= this.maximum_tags)
		YAHOO.util.Dom.addClass(this.input_element.parentNode, 'swat-hidden');
	else
		YAHOO.util.Dom.removeClass(this.input_element.parentNode, 'swat-hidden');
}

// }}}
// {{{ static properties

/**
 * Remove string resource
 *
 * @var string
 */
SiteTagEntry.remove_text = 'remove';

/**
 * New string resource
 *
 * @var string
 */
SiteTagEntry.new_text = '(new)';

/**
 * Add tag resource
 *
 * @var string
 */
SiteTagEntry.add_text = 'Add Tag';

// }}}
