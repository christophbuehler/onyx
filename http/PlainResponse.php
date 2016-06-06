<?php

namespace Onyx\Http;

class PlainResponse extends Response
{
  public function serialize(): string
  {
    return $this->content;
  }
}
