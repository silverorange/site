<?php

/**
 * A task that should be preformed to a CDN in the near future.
 *
 * @copyright 2010-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @property int      $id
 * @property string   $operation
 * @property string   $override_http_headers
 * @property string   $file_path
 * @property SwatDate $error_date
 */
abstract class SiteCdnTask extends SwatDBDataObject
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $operation;

    /**
     * A serialized string of http headers to use with the task.
     *
     * If set, these will override any headers set by the class.
     *
     * @var string
     */
    public $override_http_headers;

    /**
     * The remote file path for the content on the CDN.
     *
     * Only set and used for the delete/remove operations since the dataobjects
     * are already deleted and we can't rebuild the path.
     *
     * @var string
     */
    public $file_path;

    /**
     * @var SwatDate
     */
    public $error_date;

    /**
     * Runs a CDN Task Operation.
     *
     * @param SiteCdn $cdn the CDN this task is executed on
     */
    public function run(SiteCdnModule $cdn)
    {
        try {
            $transaction = new SwatDBTransaction($this->db);

            // always delete the object, will be rolled back by the transaction
            // if anything fails
            $this->delete();

            switch ($this->operation) {
                case 'copy':
                case 'update':
                    if ($this->checkLocalFile()) {
                        $this->copy($cdn);
                    }

                    break;

                case 'delete':
                case 'remove':
                    $this->remove($cdn);
                    break;

                default:
                    throw new SiteCdnException(
                        sprintf(
                            'Unknown operation ‘%s.’',
                            $this->operation
                        )
                    );

                    break;
            }

            $transaction->commit();
        } catch (SwatDBException $e) {
            $transaction->rollback();
            $e->processAndContinue();
        } catch (SiteCdnException $e) {
            $transaction->rollback();
            $e->processAndContinue();
            $this->error();
        } catch (Throwable $e) {
            $transaction->rollback();

            $e = new SiteCdnException($e);
            $e->processAndContinue();
            $this->error();
        }
    }

    /**
     * Gets a string describing the what this task is attempting to achieve.
     */
    abstract public function getAttemptDescription();

    /**
     * Gets a string describing the what this task did achieve.
     */
    public function getResultDescription()
    {
        return (($this->error_date instanceof SwatDate) ?
            Site::_('error.') :
            Site::_('done.')) . "\n";
    }

    protected function init()
    {
        $this->registerDateProperty('error_date');

        $this->id_field = 'integer:id';
    }

    /**
     * Checks to make sure the local file exists and is readable.
     */
    protected function checkLocalFile()
    {
        $file_path = $this->getLocalFilePath();

        if (!is_readable($file_path)) {
            throw new SiteCdnException(
                sprintf(
                    'Unable to open “%s” for reading.',
                    $file_path
                )
            );
        }

        return true;
    }

    /**
     * Gets the file path to the local file being referenced by the CDN task.
     */
    abstract protected function getLocalFilePath();

    /**
     * Updates the CDN with the file contained in this task.
     *
     * @param SiteCdn $cdn the CDN this task is executed on
     */
    abstract protected function copy(SiteCdnModule $cdn);

    /**
     * Removes the file contained in this task from the CDN.
     *
     * @param SiteCdn $cdn the CDN this task is executed on
     */
    abstract protected function remove(SiteCdnModule $cdn);

    protected function error()
    {
        $this->error_date = new SwatDate();
        $this->error_date->toUTC();
        $this->save();
    }

    /**
     * Gets a string to pass into sprintf() in getAttemptDescription(), which
     * uses numbered replacement markers as follows:
     * %1$s = Item type
     * %2$s = item id
     * %3$s = filepath
     * %4$s = operation.
     */
    protected function getAttemptDescriptionString()
    {
        return match ($this->operation) {
            'copy', 'update' => Site::_('Updating %1$s ‘%2$s’ ... '),
            'delete', 'remove' => Site::_('Removing ‘%3$s’ ... '),
            default => Site::_('Unknown operation ‘%4$s’ ... '),
        };
    }

    protected function getAccessType()
    {
        return 'private';
    }
}
