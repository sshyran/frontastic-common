<?php

namespace Frontastic\Common\HttpClient;

class Response extends \Kore\DataObject\DataObject
{
    /**
     * Response HTTP status code
     *
     * @var integer
     */
    public $status;

    /**
     * The HTTP headers from the response as a plain array
     *
     * @var string[]
     */
    public $headers;

    /**
     * Response body
     *
     * @var string
     */
    public $body;

    public function __toString()
    {
        $formattedHeaders = array();
        $maxNameLength = array_reduce(array_map('strlen', array_keys($this->headers)), 'max', 0);
        foreach ($this->headers as $name => $value) {
            $formattedHeaders[] = sprintf(
                "%{$maxNameLength}s: %s",
                ucwords($name),
                $value
            );
        }

        return sprintf(
            "HTTP/1.0 %d\n" .
            "%s\n\n" .
            '%s',
            $this->status,
            implode("\n", $formattedHeaders),
            is_string($this->body) ? $this->body : json_encode($this->body, JSON_PRETTY_PRINT)
        );
    }
}
