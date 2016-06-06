<?php

namespace Onyx\Http;

class JSONResponse extends Response
{
    public function set_content_type()
    {
        header('Content-Type:application/json;charset=utf-8');
    }

    public function serialize(): string
    {
        return json_encode($this->content);
    }
}
