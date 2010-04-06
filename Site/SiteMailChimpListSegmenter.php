<?php

require_once 'Site/SiteMailChimpListMemberUpdater.php';

/**
 * Manually segments a MailChimp list into a arbitrary number of groups.
 *
 * @package   Site
 * @copyright 2009-2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @todo      flag to force updates, so that we can completely resegment
 */
class SiteMailChimpListSegmenter extends SiteMailChimpListMemberUpdater
{
	// {{{ class constants

	/**
	 *
	 *
	 * @var integer
	 */
	const ASCII_OFFSET = 65;   // to get us to A

	/**
	 * The number of segments to split the list into.
	 *
	 * If a list has previously been segmented, care should be taken to make
	 * sure this matches the previous segmentation, and if not to re-segment the
	 * entire list.
	 *
	 * @var integer
	 */
	protected $number_of_segements;

	/**
	 * Merge Var Array for the segment field
	 *
	 * @var array
	 */
	protected $field = 'segment';

	/**
	 * Merge Var Array for the segment field
	 *
	 * @var array
	 */
	protected $merge = 'SEGMENT';

	/**
	 * @var array
	 */
	protected $last_segment = array();

	// }}}
	// {{{ public funtcion __construct()

	public function __construct($id, $filename, $title, $documentation)
	{
		$number_of_segements = new SiteCommandLineArgument(
			array('-n', '--number-of-segments'),
			'setNumberOfSegments',
			'Sets the number of segments we want to split the list into.');

		$number_of_segements->addParameter('integer',
			'--number-of-segments expects a single paramater.');

		$this->addCommandLineArgument($number_of_segements);

		parent::__construct($id, $filename, $title, $documentation);

		$this->initLastSegments();
	}

	// }}}
	// {{{ public function setNumberOfSegments()

	public function setNumberOfSegments($number_of_segments)
	{
		$this->number_of_segments = $number_of_segments;
	}

	// }}}

	// init phase
	// {{{ protected function initLastSegments()

	protected function initLastSegments()
	{
		for ($rating = 1; $rating <= 5; $rating++) {
			$use_random_start = true;
			if ($this->incremental === true) {
				$sql = sprintf('select value
					from MailingListMemberUpdaterCache
					where rating = %s and field = %s
					order by id desc
					limit 1',
					$this->db->quote($rating, 'integer'),
					$this->db->quote($this->field, 'text'));

				$segment = SwatDB::queryOne($this->db, $sql);
				if ($segment !== null) {
					$use_random_start = false;
					$this->last_segment[$rating] =
						$this->getNumericSegment($segment);
				}
			}

			// random the starting last segment, so as to not bias when running
			// incremental updates. is rand() good enough for this?
			if ($use_random_start === true) {
				$this->last_segment[$rating] =
					rand(0, $this->number_of_segments);
			}
		}
	}

	// }}}

	// run phase
	// {{{ protected function getUpdatedField()

	protected function getUpdatedField(array $member_info)
	{
		$segment = $member_info['merges'][$this->merge];
		$rating  = $member_info['member_rating'];

		if (strlen($segment) == 0 ||
			$this->getNumericSegment($segment) > $this->number_of_segments) {
			// reset last_segment when we've reached the limit.
			if ($this->last_segment[$rating] == $this->number_of_segments) {
				$this->last_segment[$rating] = 0;
			}

			$segment = $this->getLetterSegment($this->last_segment[$rating]);
			$this->last_segment[$rating]++;
		}

		return $segment;
	}

	// }}}
	// {{{ protected function displaySummary()

	protected function displaySummary()
	{
		parent::displaySummary();

		$sql = 'select count(id) as count, rating, value
			from MailingListMemberUpdaterCache
			where field = %s
			group by value, rating
			order by value, rating';

		$sql = sprintf($sql,
			$this->db->quote($this->field, 'text'));

		$results = SwatDB::query($this->db, $sql);
		$current_segment = null;

		foreach($results as $result) {
			if ($result->value !== $current_segment) {
				$current_segment = $result->value;
				$this->debug(sprintf("\nSegment %s:\n", $current_segment));
			}

			$this->debug(sprintf("%s%s Members: %s\n",
				str_repeat(' ', 5-$result->rating), // for nice ws
				str_repeat('★', $result->rating),
				SwatString::numberFormat($result->count)
				));
		}
	}

	// }}}

	// helper methods
	// {{{ protected function getLetterSegment()

	protected function getLetterSegment($segment)
	{
		return chr(self::ASCII_OFFSET + $segment);
	}

	// }}}
	// {{{ protected function getNumericSegment()

	protected function getNumericSegment($segment)
	{
		return ord($segment) - self::ASCII_OFFSET;
	}

	// }}}

}

?>
