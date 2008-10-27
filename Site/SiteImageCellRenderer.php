<?php

require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatCellRenderer.php';
require_once 'Swat/SwatImageCellRenderer.php';

/**
 * A cell renderer for SiteImages
 *
 * @package   Site
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteImageCellRenderer extends SwatCellRenderer
{
	// {{{ class constants

	/**
	 * Title length in characters before it gets truncated.
	 */
	const MAX_TITLE_LENGTH = 30;

	// }}}
	// {{{ public properties

	/**
	 * The image to display
	 *
	 * @var SiteImage
	 */
	public $image;

	/**
	 * The shortname of the {@link SiteImageDimension} to display for the
	 * image
	 *
	 * @var string
	 */
	public $image_dimension;

	/**
	 * @var string
	 */
	public $path_prefix = null;

	/**
	 * The href attribute in the XHTML anchor tag
	 *
	 * Optionally uses vsprintf() syntax, for example:
	 * <code>
	 * $renderer->link = 'MySection/MyPage/%s?id=%s';
	 * </code>
	 *
	 * @var string
	 */
	public $link;

	/**
	 * A value or array of values to substitute into the link of this cell. The
	 * value will automatically be url encoded when it is included in the link.
	 *
	 * @var mixed
	 */
	public $link_value = null;

	/**
	 * Whether or not to display the image title
	 *
	 * If a title is displayed, it is truncated at
	 * {@link SiteImageCellRenderer::MAX_TITLE_LENGTH} characters. If set to
	 * true and the image has no title, an empty span tag is displayed.
	 *
	 * @var boolean
	 */
	public $display_title = true;

	/**
	 * Whether or not this cell renderer should occupy a square region
	 *
	 * If set to true, the region occupied by this cell renderer will be a
	 * square with the same dimensions for every image displayed. This is useful
	 * for displayimg images in a {@link SwatTileView} when the images do not
	 * all have the same dimensions.
	 *
	 * If set to false, this cell renderer will occupy a region of the actual
	 * image dimensions.
	 *
	 * @var boolean
	 */
	public $square = true;

	// }}}
	// {{{ private properties

	/**
	 * @var SwatImageCellRenderer
	 */
	private $image_cell_renderer;

	// }}}
	// {{{ public function __construct()

	public function __construct()
	{
		parent::__construct();

		$this->image_cell_renderer = new SwatImageCellRenderer();
		$this->image_cell_renderer->parent = $this;
	}

	// }}}
	// {{{ public function getHtmlHeadEntrySet()

	public function getHtmlHeadEntrySet()
	{
		$set = parent::getHtmlHeadEntrySet();

		$set->addEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/site/styles/site-image-cell-renderer.css',
			Site::PACKAGE_ID));

		$set->addEntrySet($this->image_cell_renderer->getHtmlHeadEntrySet());
		return $set;
	}

	// }}}
	// {{{ public function render()

	public function render()
	{
		if (!$this->visible)
			return;

		if ($this->link !== null) {
			$a_tag = new SwatHtmlTag('a');

			if ($this->link_value === null)
				$a_tag->href = $this->link;
			elseif (is_array($this->link_value))
				$a_tag->href = vsprintf($this->link, $this->link_value);
			else
				$a_tag->href = sprintf($this->link, $this->link_value);

			$a_tag->open();
		}

		$this->image_cell_renderer->image =
			$this->image->getUri($this->image_dimension, $this->path_prefix);

		$this->image_cell_renderer->width =
			$this->image->getWidth($this->image_dimension);

		$this->image_cell_renderer->height =
			$this->image->getHeight($this->image_dimension);

		$this->image_cell_renderer->alt = '';

		if ($this->square) {
			$occupy = max($this->image->getHeight($this->image_dimension),
				$this->image->getWidth($this->image_dimension));

			$this->image_cell_renderer->occupy_height = $occupy;
			$this->image_cell_renderer->occupy_width = $occupy;
		}

		$image_wrapper_tag = new SwatHtmlTag('span');
		$image_wrapper_tag->class = 'site-image-wrapper';

		$image_wrapper_tag->open();
		$this->image_cell_renderer->render();
		$image_wrapper_tag->close();

		if ($this->display_title) {
			$title = $this->image->getTitle();

			if ($title !== null)
				$title = SwatString::condense($title, self::MAX_TITLE_LENGTH);

			$span_tag = new SwatHtmlTag('span');
			$span_tag->class = 'title';

			if ($title === null)
				$span_tag->setContent(''); // prevent self-closing span tag
			else
				$span_tag->setContent($title);

			if (strlen($title) > self::MAX_TITLE_LENGTH)
				$span_tag->title = $title;

			$span_tag->display();
		}

		if ($this->link !== null)
			$a_tag->close();
	}

	// }}}
}

?>
