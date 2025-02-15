<?php

declare(strict_types=1);

namespace Behatch\Json;

use Symfony\Component\PropertyAccess\PropertyAccessor;

class Json
{
    protected $content;

    public function __construct($content)
    {
        $this->content = $this->decode((string) $content);
    }

    public function getContent()
    {
        return $this->content;
    }

    public function read($expression, PropertyAccessor $accessor)
    {
        if (\is_array($this->content)) {
            $expression = preg_replace('/^root/', '', $expression);
        } else {
            $expression = preg_replace('/^root./', '', $expression);
        }

        // If root asked, we return the entire content
        if ('' === trim($expression)) {
            return $this->content;
        }

        return $accessor->getValue($this->content, $expression);
    }

    public function encode($pretty = true)
    {
        $flags = \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE;

        if (true === $pretty && \defined('JSON_PRETTY_PRINT')) {
            $flags |= \JSON_PRETTY_PRINT;
        }

        return json_encode($this->content, $flags);
    }

    public function __toString()
    {
        return $this->encode(false);
    }

    private function decode($content)
    {
        $result = json_decode($content);

        if (\JSON_ERROR_NONE !== json_last_error()) {
            throw new \Exception("The string '$content' is not valid json");
        }

        return $result;
    }
}
