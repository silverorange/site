<?php

require_once 'Site/pages/SiteXMLRPCServer.php';

/**
 * An XML-RPC server for upload status
 *
 * @package   Site
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteUploadStatusServer extends SiteXMLRPCServer
{
	// {{{ public function getStatus()

	/**
	 * Gets upload status for the given upload identifiers
	 *
	 * Statuses for the given identifiers are returned as follows:
	 *
	 * <code>
	 * {
	 *     sequence: sequence_number,
	 *     statuses:
	 *     {
	 *         client_id: status_struct,
	 *         client_id: status_struct,
	 *         client_id: status_struct
	 *     }
	 * }
	 * </code>
	 *
	 * If the uploadprogress extension is loaded and a file upload is in
	 * progress, the <i>status_struct</i> will contain detailed information
	 * about a single upload status. Otherwise, the <i>status_struct</i> will
	 * be the string 'none'.
	 *
	 * If there are no clients in the <i>$clients</i> array, the
	 * <i>statuses</i> field is returned as false.
	 *
	 * @param ineger $sequence the sequence id of this request to prevent race
	 *                          conditions.
	 * @param array $clients a struct containing upload identifiers indexed by
	 *                        client identifier.
	 *
	 * @return array a two member struct containing both the sequence number of
	 *                this request and the upload status information for all of
	 *                the given clients.
	 */
	public function getStatus($sequence, array $clients)
	{
		$response = array();
		$response['sequence'] = $sequence;

		if (count($clients) > 0) {
			$response['statuses'] = array();

			foreach ($clients as $client_id => $upload_id) {
				if (function_exists('uploadprogress_get_info') &&
					$status = uploadprogress_get_info($upload_id)) {
					$status_struct = array();
					foreach ($status as $key => $value)
						$status_struct[$key] = $value;

					$response['statuses'][$client_id] = $status_struct;
				} else {
					$response['statuses'][$client_id] = 'none';
				}
			}

		} else  {
			$response['statuses'] = false;
		}

		return $response;
	}

	// }}}
}

?>
