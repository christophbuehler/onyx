<!DOCTYPE html>
<html>
<head>
{* page title *}
<title>{$title}</title>

{* global css *}
{foreach from=$css_files item=css}
	<link rel="stylesheet" href="{$css}">
{/foreach}

{* jQuery *}
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>

{* global js *}
{foreach from=$js_files item=js}
	<script src="{$js}"></script>
{/foreach}

</head>
<div id="container">