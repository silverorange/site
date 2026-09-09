<?php

declare(strict_types=1);

/**
 * An exception in Site package.
 *
 * @copyright 2004-2026 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteException extends SwatException
{
    public ?string $title = null;

    public int $http_status_code = 500;

    /**
     * Additional context to add to the exception.
     *
     * This can be used (for example) to log info to Sentry without
     * displaying it on the exception page, etc..
     *
     * @var array<string, array>
     */
    private array $context = [];

    /**
     * Tags to add to the exception.
     *
     * This can be used (for example) to log tags to Sentry which,
     * unlike context, are searchable and filterable.
     *
     * @var array<string, string>
     */
    private array $tags = [];

    public function __construct(string|Throwable|null $message = null, int $code = 0)
    {
        if ($message instanceof PEAR_Error) {
            $error = $message;
            $message = $error->getMessage();
            $message .= "\n" . $error->getUserInfo();
            $code = $error->getCode();
        }

        parent::__construct($message, $code);
    }

    final public function withContext(string $key, array $value): static
    {
        $this->context[$key] = $value;

        return $this;
    }

    final public function getContext(): array
    {
        return $this->context;
    }

    final public function withTag(string $key, string $value): static
    {
        $this->tags[$key] = $value;

        return $this;
    }

    final public function getTags(): array
    {
        return $this->tags;
    }
}
