<?php

class PlainResponse extends HTTPResponse
{
  public function serialize(): string {
    return $this->content;
  }
}