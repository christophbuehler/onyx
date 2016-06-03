<?php

/**
 * Used for handling XHR requests.
 */
abstract class HTTPResponse
{
  protected static $content;
  protected static $code;

  abstract protected function serialize(): string;

  /**
   * Default HTTPResponse constructor.
   * @param  integer $code    the HTTP status code
   * @param  string  $content the response body
   */
  function __construct($content = '', int $code = 204) {
    echo "test" . $content;
    echo "test" . $code;
    $this->content = $content;
    $this->code = $code;
  }

  /**
   * Change the status code.
   * @param  int    $code the HTTP status code
   * @return HTTPResponse       this
   */
  public function with_status(int $code): HTTPResponse {
    $this->code = $code;
    return $this;
  }

  /**
   * Send this HTTP response.
   */
  public function send() {
    http_response_code($this->code);
    echo $this->serialize();
    exit;
  }
}
