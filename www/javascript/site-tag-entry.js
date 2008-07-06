/**
 * Control for selecting multiple tags from a array of tags
 *
 * @package   Site
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */

// {{{ SiteTagEntry()

/**
 * Creates a new tag entry widget
 *
 * @param string id the unique identifier of this entry object.
 * @param array tag_array an array of tag strings and titles that are possible
 *                        to select using this control
 * @param array initial_selected_tag_array an array of already selected tag
 *                                         strings.
 */
function SiteTagEntry(id, tag_array, initial_selected_tag_array)
{
	this.id = id;
	this.tag_array = tag_array;
	this.initial_selected_tag_array = initial_selected_tag_array;

	this.selected_tag_array = [];
	this.new_tag_array = [];
	this.input_element = document.getElementById(this.id + '_value');
	this.data_store = new YAHOO.widget.DS_JSArray(this.tag_array);
	this.array_element = document.getElementById(this.id + '_array');

	YAHOO.util.Event.onContentReady(
		this.id + '_value', this.handleOnAvailable, this, true);
}

// }}}
// {{{ handleOnAvailable()

/**
 * Sets up the auto-complete widget used by this tag selection control
 */
SiteTagEntry.prototype.handleOnAvailable = function()
{
	// create auto-complete widget
	this.auto_complete = new YAHOO.widget.AutoComplete(
		this.input_element, this.id + '_container', this.data_store, {
		queryDelay:            0,
		minQueryLength:        0,
		highlightClassName:    'site-tag-highlight',
		prehighlightClassName: 'site-tag-prehighlight',
		autoHighlight:         true,
		useShadow:             false,
		forceSelection:        false,
		animVert:              false,
		formatResult:
			function(item, query)
			{
				// 0 is title, 1 is tag string
				return item[0] + ' (' + item[1] + ')';
			}
	});

	this.auto_complete.itemSelectEvent.subscribe(
		this.addTagFromAutoComplete, this, true);

	this.a_tag = document.createElement('a');
	this.a_tag.href = '#';
	YAHOO.util.Event.addListener(this.a_tag, 'click',
		function(e, entry) {
			YAHOO.util.Event.preventDefault(e);
			entry.createTag();
		}, this);

	var img_tag = document.createElement('img');
	img_tag.src = 'packages/swat/images/swat-tool-link-create.png';
	img_tag.title = 'Add Tag';
	img_tag.alt = '';
	img_tag.className = 'add-tag';
	this.a_tag.appendChild(img_tag);

	document.getElementById(this.id).insertBefore(this.a_tag,
		document.getElementById(this.id + '_container'));

	// initialize values passed in
	for (var i = 0; i < this.initial_selected_tag_array.length; i++) {
		this.addTag(this.initial_selected_tag_array[i]);
	}

	YAHOO.util.Event.addListener(this.input_element, 'keydown',
		function(e, entry) {
			// capture enter key for new tags
			if (YAHOO.util.Event.getCharCode(e) == 13 &&
				!entry.auto_complete.isContainerOpen()) {
				YAHOO.util.Event.stopEvent(e);
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

				entry.input_element.value =
					entry.input_element.value.slice(0, -1);

				entry.createTag();
			}
		}, this);
}

// }}}
// {{{ addTagFromAutoComplete()

SiteTagEntry.prototype.addTagFromAutoComplete = function(
	oSelf, elItem, oData)
{
	var tag_string = elItem[2][1];
	this.addTag(tag_string);
}

// }}}
// {{{ addTag()

SiteTagEntry.prototype.addTag = function(tag_string)
{
	var found = false;

	// trim tag string
	tag_string = tag_string.replace(/^\s+|\s+$/g, '');

	if (tag_string.length == 0)
		return;

	for (i = 0; i < this.selected_tag_array.length; i++) {
		var tag = this.selected_tag_array[i][1];
		if (tag.toUpperCase() == tag_string.toUpperCase()) {
			this.input_element.value = '';
			return;
		}
	}

	for (i = 0; i < this.new_tag_array.length; i++) {
		var tag = this.new_tag_array[i];
		if (tag.toUpperCase() == tag_string.toUpperCase()) {
			this.input_element.value = '';
			return;
		}
	}

	for (i = 0; i < this.data_store.data.length; i++) {
		var tag = this.data_store.data[i][1];
		if (tag.toUpperCase() == tag_string.toUpperCase()) {
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

	// create new tag
	var new_tag = (!found);

	if (new_tag)
		var title = tag_string;

	// create new array node
	var li_tag = document.createElement('li');
	li_tag.id = this.id + '_tag_' + tag_string;
	YAHOO.util.Dom.setStyle(li_tag, 'opacity', 0);

	var anchor_tag = document.createElement('a');
	anchor_tag.id = this.id + '_tag_remove_' + tag_string;
	anchor_tag.href = '#';
	anchor_tag.appendChild(document.createTextNode(
		SiteTagEntry.remove_text));

	YAHOO.util.Event.addListener(anchor_tag, 'click',
		function (e)
		{
			YAHOO.util.Event.preventDefault(e);
			this.removeTag(tag_string);
		},
		this, true);

	title = this.filterTitle(title);

	if (new_tag) {
		var hidden_tag = document.createElement('input');
		hidden_tag.type = 'hidden';
		hidden_tag.name = this.id + '_new[]';
		hidden_tag.value = tag_string;
		var title_node = document.createTextNode(
			title + ' ' + SiteTagEntry.new_text + ' ');

		li_tag.className = 'new-tag';
		li_tag.appendChild(hidden_tag);
		this.new_tag_array.push(tag_string);
	} else {
		var title_node = document.createTextNode(title + ' ');
		var hidden_tag = document.createElement('input');
		hidden_tag.type = 'hidden';
		hidden_tag.name = this.id + '[]';
		hidden_tag.value = tag_string;
		li_tag.appendChild(hidden_tag);
	}

	li_tag.appendChild(title_node);
	li_tag.appendChild(anchor_tag);

	// add array node
	this.array_element.appendChild(li_tag);

	var in_attributes = { opacity: { from: 0, to: 1 } };
	var in_animation = new YAHOO.util.Anim(li_tag, in_attributes,
		0.5, YAHOO.util.Easing.easeIn);

	in_animation.animate();

	// clear input value once a value is chosen
	this.input_element.value = '';
}

// }}}
// {{{ createTag()

SiteTagEntry.prototype.createTag = function()
{
	var tag_title = this.input_element.value;
	this.addTag(tag_title);
}

// }}}
// {{{ removeTag()

SiteTagEntry.prototype.removeTag = function(tag_string)
{
	// remove event arrayener
	var anchor_tag = document.getElementById(
		this.id + '_tag_remove_' + tag_string);

	if (anchor_tag)
		YAHOO.util.Event.purgeElement(anchor_tag);

	// remove array node
	var li_tag = document.getElementById(this.id + '_tag_' + tag_string);
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

	for (i = 0; i < this.selected_tag_array.length; i++) {
		if (this.selected_tag_array[i][1] == tag_string) {
			// remove row from selected array
			var element = this.selected_tag_array.splice(i, 1);

			// add row back to data store, splice returns an array
			this.data_store.data.push(element[0]);
			this.data_store.data.sort();
			break;
		}
	}
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

// }}}
