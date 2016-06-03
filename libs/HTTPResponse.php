<?php

class HTTPResponse
{
  private $code;
  private $content;

  /**
   * Default HTTPResponse constructor.
   * @param  integer $code    the HTTP status code
   * @param  string  $content the response body
   */
  function __constructor(int $code = 204, string $content = '') {
    this.$code = $code;
    this.$content = $content;
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
}
