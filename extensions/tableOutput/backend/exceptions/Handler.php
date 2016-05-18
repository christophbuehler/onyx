<?php

class Handler
{
    // if the code is 0, no error occurred
    private $code;
    private $msg;
    public function __construct($code, $msg)
    {
        $this->code = $code;
        $this->msg = $msg;
    }
}
