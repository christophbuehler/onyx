<?php

/**
 * Copyright (c) 2016 The Onyx Project Authors. All rights reserved.
 * This project is licensed under GNU GPL found at http://gnu.org/licenses/gpl.txt
 * The Onyx project is a web-application-framework, designed and optimized
 * for simple usage and programmer efficiency.
 */

namespace Onyx\Http;

class PlainResponse extends Response
{
    /**
     * Serialize this request.
     * @return string
     */
    public function serialize(): string
    {
        return $this->content;
    }
}
