<?php

require_once 'Site/SiteCommandLineApplication.php';
require_once 'Site/SiteMailChimpList.php';

/**
 * Manually segments a MailChimp list into a arbitrary number of groups.
 *
 * @package   Site
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteMailChimpListSegmenter extends SiteCommandLineApplication
{
	// {{{ class constants

	const CHUNK_SIZE   = 15000;  // 100 is the api default, 15000 the max.
	const UPDATE_SIZE  = 250;  // to use updateMember() set this to one
	const ASCII_OFFSET = 65;   // to get us to A
	const RATINGS      = 5;    // why a constant?
	const USE_CACHE    = true; // using this for future simplification ease

	// }}}
	// {{{ protected properties

	/**
	 * The list we're segmenting.
	 *
	 * @var SiteMailChimpList
	 */
	protected $list;

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
	 * Whether or not to incrementally update the segments.
	 *
	 * If true, we only update members of the list that don't already have a
	 * segment set. If false, we update every subscriber.
	 *
	 * @var boolean
	 */
	protected $incremental = false;

	// }}}
	// {{{ public funtcion __construct()

	public function __construct($id, $filename, $title, $documentation)
	{
		parent::__construct($id, $filename, $title, $documentation);

		$this->verbosity = self::VERBOSITY_ALL;

		$number_of_segements = new SiteCommandLineArgument(
			array('-n', '--number-of-segments'),
			'setNumberOfSegments',
			'Sets the number of segments we want to split the list into.');

		$number_of_segements->addParameter('integer',
			'--number-of-segments expects a single paramater.');

		$this->addCommandLineArgument($number_of_segements);

		$incremental = new SiteCommandLineArgument(
			array('-i', '--incremental'),
			'setIncremental',
			'Sets whether to only use resources from s3.');

		$this->addCommandLineArgument($incremental);

		$this->initModules();
		$this->parseCommandLineArguments();
		$this->initList();
	}

	// }}}
	// {{{ public function setNumberOfSegments()

	public function setNumberOfSegments($number_of_segments)
	{
		$this->number_of_segments = $number_of_segments;
	}

	// }}}
	// {{{ public function setIncremental()

	public function setIncremental($incremental)
	{
		$this->incremental = $incremental;
	}

	// }}}

	// init phase
	// {{{ protected function initList()

	protected function initList()
	{
		$this->list = new MailChimpList($this);
	}

	// }}}

	// {{{ public function run()

	public function run()
	{
		$this->lock();

		$this->debug(sprintf("Segmenting %s list into %s segments.\n",
			($this->incremental) ? 'unsegmented members of the' : 'the entire',
			$this->number_of_segments));

		if (self::USE_CACHE === true && $this->incremental === false) {
			$sql = 'delete from MailingListSegmenterCache';
			SwatDB::exec($this->db, $sql);
			$this->debug("DB Cache Table cleared.\n");
		}

		$this->api_calls     = 0;
		$total_count   = $this->list->getMemberCount();
		$this->api_calls++;
		$segments      = $this->getSegments();
		$current_count = 0;
		$offset        = 0;
		$members       = $this->list->getMembers($offset, self::CHUNK_SIZE);
		$this->api_calls++;
		$chunk_count   = count($members);

		while ($chunk_count > 0) {
			$member_updates = array();
			$member_count   = 0;
//var_dump($members);
			foreach ($members as $member) {
				$needs_segment = true;
				$email         = $member['email'];
				$member_info   = $this->getMemberInfo($email);
				$member_rating = $member_info['member_rating'];
				$current_count++;
				$member_count++;
				$segments[$member_rating]['total_count']++;
//var_dump($member_info);

				if ($this->incremental === true &&
					strlen($member_info['merges']['SEGMENT']) > 0) {
					$needs_segment = false;
					$segments[$member_rating]['existing_count']++;
					$letter_segment = $member_info['merges']['SEGMENT'];
					$segments[$letter_segment]['total_count']++;
					$segments[$letter_segment]['existing_count']++;
					$segments[$letter_segment][$member_rating]++;

					// check for two elements in the array, since thats a hacky
					// way to know if we've grabbed the member from the cache
					// already, meaning we don't need to reinsert them.
					if (self::USE_CACHE === true && count($member_info) > 2) {
						$sql = 'insert into MailingListSegmenterCache
						(email, rating, segment) values (%s, %s, %s)';

						$sql = sprintf($sql,
							$this->db->quote($email, 'text'),
							$this->db->quote($member_rating, 'integer'),
							$this->db->quote($letter_segment, 'text'));

						SwatDB::exec($this->db, $sql);
					}
				}

				if ($needs_segment === true) {
					if ($segments[$member_rating]['last_segment'] ==
						$this->number_of_segments) {
						$segments[$member_rating]['last_segment'] = 0;
					}

					$letter_segment = chr(self::ASCII_OFFSET +
						$segments[$member_rating]['last_segment']);

					$segments[$member_rating]['segmented_count']++;
					$segments[$member_rating]['last_segment']++;
					$segments[$letter_segment]['total_count']++;
					$segments[$letter_segment]['segmented_count']++;
					$segments[$letter_segment][$member_rating]++;

echo $member_rating.' '.$letter_segment;
					// since we segfault after ~550 api calls, cut down on calls
					// by not updating every member, but grouping them together
					// into a batchSubscribe (which achieves the same thing).
					if (self::UPDATE_SIZE == 1) {
						$this->list->updateMemberInfo($email,
							array('segment' => $letter_segment));
					} else {
						$member_updates[] = array(
							'email'   => $email,
							'segment' => $letter_segment,
							// Rating is needed for the cache table, so keep
							// track of it here. It isn't a merge var, so will
							// safely be ignored.
							'rating'  => $member_rating,
						);
					}
				}
echo ' ',$email,' '.$current_count." current calls: ".$this->api_calls."\n";

				if ($member_count == self::UPDATE_SIZE) {
					if (count($member_updates)) {
echo sprintf("%s members batch updated.\n", count($member_updates));
						// this still segfaults, but cuts down on api calls
						// so the script can run longer without segfaulting.
						$this->list->batchSubscribe($member_updates);
						$this->api_calls = $this->api_calls + 2;

						if (self::USE_CACHE === true) {
							$sql = 'insert into MailingListSegmenterCache
								(email, rating, segment) values %s';

							foreach ($member_updates as $member) {
								$values[] = sprintf('(%s, %s, %s)',
									$this->db->quote($member['email'], 'text'),
									$this->db->quote($member['rating'],
										'integer'),
									$this->db->quote($member['segment'],
										'text'));
							}

							$sql = sprintf($sql,
								implode(',', $values));
//echo $sql;

							SwatDB::exec($this->db, $sql);
						}
					}
					$member_count   = 0;
					$member_updates = array();
				}
			}

			$offset++;
			$this->api_calls++;
			$members     = $this->list->getMembers($offset, self::CHUNK_SIZE);
			$chunk_count = count($members);
//unset($this->list);
//$this->initList();
//var_dump($segments);
		}
//var_dump($segments);

//		for ($rating = 1; $rating <= self::RATINGS; $rating++) {
//			$this->debug(sprintf("\n%s Members:\n", str_repeat('★', $rating)));
//		$total_member_count = $this->getMemberCount($rating);
//		$this->debug(sprintf("%s subscribers\n", $total_member_count)));
//		$members = $this->getMembers(self::CHUNK_SIZE, $rating);
//		$count   = count($members);
//		}

		$this->debug("\nAll done!\n\n", true);

		$this->unlock();
	}

	// }}}
	// {{{ protected function getSegments()

	protected function getSegments()
	{
		$segments = array();
		for ($rating = 1; $rating <= self::RATINGS; $rating++) {
			$segments[$rating]['total_count']     = 0;
			$segments[$rating]['existing_count']  = 0;
			$segments[$rating]['segmented_count'] = 0;

			$use_random_start = true;
			if (self::USE_CACHE === true && $this->incremental === true) {
				$sql = sprintf('select segment
					from MailingListSegmenterCache
					where rating = %s
					order by id desc
					limit 1',
					$this->db->quote($rating, 'integer')
					);

				$segment = SwatDB::queryOne($this->db, $sql);
				if ($segment !== null) {
					$use_random_start = false;
					$segments[$rating]['last_segment'] = ord($segment) -
						self::ASCII_OFFSET;
				}
			}

			// random the starting last segment, so as to not bias when running
			// incremental updates. is rand() good enough for this?
			if ($use_random_start === true) {
				$segments[$rating]['last_segment'] =
					rand(0, $this->number_of_segments);
			}
		}

		for ($segment = 0; $segment < $this->number_of_segments; $segment++) {
			$letter_segment = chr(self::ASCII_OFFSET + $segment);

			$segments[$letter_segment]['total_count']     = 0;
			$segments[$letter_segment]['existing_count']  = 0;
			$segments[$letter_segment]['segmented_count'] = 0;
			for ($rating = 1; $rating <= self::RATINGS; $rating++) {
				$segments[$letter_segment][$rating] = 0;
			}
		}
//var_dump($segments);

		return $segments;
	}

	// }}}
	// {{{ protected function getMemberInfo()

	protected function getMemberInfo($email)
	{
		$lookup = true;
		if (self::USE_CACHE === true) {
			$sql = 'select rating, segment
				from MailingListSegmenterCache
				where email = %s';

			$sql = sprintf($sql,
				$this->db->quote($email, 'text'));
//echo $sql;
			$member = SwatDB::queryRow($this->db, $sql);
//var_dump($member); exit;
			if ($member !== null) {
				$member_info['member_rating'] = $member->rating;
				$member_info['merges']['SEGMENT'] = $member->segment;
				$lookup = false;
			}
		}

		if ($lookup === true) {
			$member_info = $this->list->getMemberInfo($email);
			$this->api_calls++;
		}

		return $member_info;
	}

	// }}}

	// boilerplate
	// TODO: is this required?
	// {{{ protected function getDefaultModuleList()

	/**
	 * Gets the list of modules to load for this search indexer
	 *
	 * @return array the list of modules to load for this application.
	 *
	 * @see SiteApplication::getDefaultModuleList()
	 */
	protected function getDefaultModuleList()
	{
		return array(
			'config' => 'SiteConfigModule',
		);
	}

	// }}}
}

?>
