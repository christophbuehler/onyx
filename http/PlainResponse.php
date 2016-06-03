<?php

class PlainResponse extends HTTPResponse
{
  public function serialize(): string {
    echo "test";
    echo parent::$content;
    return parent::$content;
  }
}
