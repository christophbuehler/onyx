<?php

/**
 * Copyright (c) 2016 The Onyx Project Authors. All rights reserved.
 * This project is licensed under GNU GPL found at http://gnu.org/licenses/gpl.txt
 * The Onyx project is a web-application-framework, designed and optimized
 * for simple usage and programmer efficiency.
 */

namespace Onyx\Http;

class JSONResponse extends Response
{
    /**
     * Set the content type of this request.
     */
    public function set_content_type()
    {
        header('Content-Type:application/json;charset=utf-8');
    }

    /**
     * Serialize this request.
     * @return string
     */
    public function serialize(): string
    {
        return json_encode($this->content);
    }
}
