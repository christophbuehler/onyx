<?php

namespace Onyx\Http;

abstract class Response
{
    public $content;
    public $code;

    abstract protected function serialize(): string;

    /**
     * Default HTTPResponse constructor.
     * @param  integer $code the HTTP status code
     * @param  string $content the response body
     */
    function __construct($content = '', int $code = 200)
    {
        $this->content = $content;
        $this->code = $code;
    }

    /**
     * Change the status code.
     * @param  int $code the HTTP status code
     * @return Response       this
     */
    public function with_status(int $code): Response
    {
        $this->code = $code;
        return $this;
    }

    public function set_content_type()
    {
        header('Content-Type:text/html;charset=utf-8');
    }

    /**
     * Send this HTTP response.
     */
    public function send()
    {
        $this->set_content_type();
        http_response_code($this->code);
        return $this->serialize();
    }
}
