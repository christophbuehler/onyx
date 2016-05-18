<?php

class OnyxException extends Exception
{
    function __construct($header, $content) {
      parent::__construct(sprintf('<!DOCTYPE html><html><title>%s - Error</title><head><link rel="stylesheet" href="global/css/error.css"></head><body><div class="onyx_error_box"><div class="onyx_error_header">%s</div><div class="onyx_error_content">%s</div></div></body></html>',
        SITE_NAME_SHORT,
        $header,
        $content));
    }
}

class PageLoadException extends OnyxException
{
  function __construct($msg) {
    parent::__construct('Page load failed', $msg);
  }
}

class MissingFileException extends OnyxException
{
  function __construct($msg) {
    parent::__construct('Missing file', $msg);
  }
}

class TemplateLoadException extends OnyxException
{
  function __construct($msg) {
    parent::__construct('Template load failed', $msg);
  }
}

class MissingParameterException extends OnyxException
{
  function __construct($paramName) {
    parent::__construct('Missing parameter', sprintf('Required parameter "%s" was not provided.', $paramName));
  }
}
