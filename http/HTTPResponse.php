<?php

/**
 * Used for handling XHR requests.
 */
abstract class HTTPResponse
{
  private $content;
  private $code;

  abstract protected function serialize(): string;

  /**
   * Default HTTPResponse constructor.
   * @param  integer $code    the HTTP status code
   * @param  string  $content the response body
   */
  function __construct($content = '', int $code = 204) {
    $this.content = $content;
    $this.code = $code;
  }

  /**
   * Change the status code.
   * @param  int    $code the HTTP status code
   * @return HTTPResponse       this
   */
  public function with_status(int $code): HTTPResponse {
    this.$code = $code;
    return $this;
  }

  /**
   * Send this HTTP response.
   * @return [type] [description]
   */
  public function send() {
    http_response_code($this.code);
    echo $this->serialize();
    exit;
  }
}
