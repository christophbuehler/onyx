<?php

class JSONResponse extends HTTPResponse
{
  public function serialize(): string {
    return json_encode($this.content);
  }
}
