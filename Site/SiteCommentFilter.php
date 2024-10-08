<?php

/**
 * Parses the bodytext of a comment.
 *
 * Allowed tags are <i>&lt;a href=""&gt;</i>, <i>&lt;strong&gt;</i>,
 * <i>&lt;em&gt;</i> and <i>&lt;code&gt;</i>. Missing closing tags are
 * automatically closed and closing tags with missing opening tags are
 * displayed as plain text.
 *
 * Example:
 *
 * <code>
 * <?php
 * $comment = ' ... ';
 * echo SiteCommentFilter::parse($comment);
 * ?>
 * </code>
 *
 * The first method, parse(), cleans and filters any included inline tags.
 * The second method, toXhtml(), genetrates XHTML output in paragraphs.
 *
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SiteCommentFilter
{
    /**
     * @var array
     */
    protected static $tags = ['a' => ['tag' => 'a', 'self_closing' => false, 'attributes' => ['title="[^"]+?"', 'href="http[^"]+?"']], 'em' => ['tag' => 'em', 'self_closing' => false, 'attributes' => []], 'strong' => ['tag' => 'strong', 'self_closing' => false, 'attributes' => []], 'code' => ['tag' => 'code', 'self_closing' => false, 'attributes' => []]];

    /**
     * @var array
     */
    protected static $tag_stack = [];

    /**
     * Prevent instantiation of static class.
     */
    private function __construct() {}

    /**
     * @param string $comment
     * @param bool   $strip_invalid_tags if true, invalid tags are stripped,
     *                                   and if false, invalid tags are
     *                                   displayed with entities minimized
     *
     * @return string
     */
    public static function parse($comment, $strip_invalid_tags = false)
    {
        self::$tag_stack = [];

        ob_start();
        self::parseInternal($comment, $strip_invalid_tags);

        return ob_get_clean();
    }

    /**
     * @param string $comment
     * @param bool   $strip_invalid_tags if true, invalid tags are stripped,
     *                                   and if false, invalid tags are
     *                                   displayed with entities minimized
     *
     * @return string
     */
    public static function toXhtml($comment, $strip_invalid_tags = false)
    {
        $comment = self::parse($comment, $strip_invalid_tags);

        $comment = str_replace("\r\n", "\n", $comment);
        $comment = str_replace("\r", "\n", $comment);
        $comment = preg_replace(
            '/[\x0a\s]*\n\n[\x0a\s]*/s',
            '</p><p>',
            $comment
        );

        $comment = preg_replace(
            '/[\x0a\s]*\n[\x0a\s]*/s',
            '<br />',
            $comment
        );

        return '<p>' . $comment . '</p>';
    }

    /**
     * @param string $tag
     * @param bool   $self_closing
     */
    public static function addTag(
        $tag,
        $self_closing = false,
        ?array $attributes = null
    ) {
        if ($attributes === null) {
            $attributes = [];
        }

        self::$tags[$tag] = ['tag' => $tag, 'self_closing' => $self_closing, 'attributes' => $attributes];
    }

    protected static function startTag($data, $tag_name)
    {
        array_push(self::$tag_stack, $tag_name);
        echo $data;
    }

    protected static function endTag($data, $tag_name)
    {
        if (end(self::$tag_stack) === $tag_name) {
            array_pop(self::$tag_stack);
            echo $data;
        } else {
            echo SwatString::minimizeEntities($data);
        }
    }

    protected static function selfClosingTag($data, $tag_name)
    {
        echo $data;
    }

    protected static function characterData($data, $strip_invalid_tags)
    {
        if ($strip_invalid_tags) {
            $data = strip_tags($data);
        }

        echo SwatString::minimizeEntities($data);
    }

    protected static function parseInternal($comment, $strip_invalid_tags)
    {
        $matches = [];
        // Note: PHP PCRE always returns offsets in bytes, not characters
        preg_match_all(
            self::getExpression(),
            $comment,
            $matches,
            PREG_OFFSET_CAPTURE | PREG_SET_ORDER
        );

        $offset = 0;
        foreach ($matches as $match) {
            // get leading character data before tag
            if ($match[0][1] !== $offset) {
                $data = self::getByteSubstring(
                    $comment,
                    $offset,
                    $match[0][1]
                );

                self::characterData($data, $strip_invalid_tags);
            }

            foreach (self::$tags as $tag) {
                if ($tag['self_closing'] === false) {
                    // check if it is an opening tag
                    if (array_key_exists($tag['tag'], $match)
                        && $match[$tag['tag']][1] != -1) {
                        self::startTag($match[0][0], $tag['tag']);
                    } elseif (array_key_exists('n' . $tag['tag'], $match)
                        && $match['n' . $tag['tag']][1] != -1) {
                        // check if it is a closing tag
                        self::endTag($match[0][0], $tag['tag']);
                    }
                } elseif (array_key_exists($tag['tag'], $match)
                    && $match[$tag['tag']][1] != -1) {
                    // check if it is a self-closing tag
                    self::selfClosingTag($match[0][0], $tag['tag']);
                }
            }

            $offset = $match[0][1] + self::getByteLength($match[0][0]);
        }

        // get trailing character data
        $length = self::getByteLength($comment);
        if ($offset < $length) {
            $data = self::getByteSubstring($comment, $offset, $length);
            self::characterData($data, $strip_invalid_tags);
        }

        // close unclosed tags
        while (count(self::$tag_stack) > 0) {
            $tag = array_pop(self::$tag_stack);
            echo '</', $tag, '>';
        }
    }

    protected static function getExpression()
    {
        $tag_tokens = [];
        foreach (self::$tags as $tag) {
            $attributes = [];
            foreach ($tag['attributes'] as $attribute) {
                $attributes[] = '\s+' . $attribute;
            }

            if (count($attributes) > 0) {
                $attribute_tokens = '(' . implode("\n\t|\n", $attributes) . ')*';
            } else {
                $attribute_tokens = '';
            }

            if ($tag['self_closing']) {
                $tag_tokens[] = sprintf(
                    '<(?P<%1$s>%1$s)%2$s\ \/>',
                    $tag['tag'],
                    $attribute_tokens
                );
            } else {
                $tag_tokens[] = sprintf(
                    '<(?P<%1$s>%1$s)%2$s>',
                    $tag['tag'],
                    $attribute_tokens
                );

                $tag_tokens[] = sprintf(
                    '<\/(?P<n%1$s>%1$s)>',
                    $tag['tag']
                );
            }
        }

        return '/(' . implode("\n|\n", $tag_tokens) . ') /uix';
    }

    protected static function getByteSubstring($string, $from, $to)
    {
        $start = $from;
        $length = $to - $from;

        return mb_substr($string, $start, $length, '8bit');
    }

    protected static function getByteLength($string)
    {
        return mb_strlen($string, '8bit');
    }
}
