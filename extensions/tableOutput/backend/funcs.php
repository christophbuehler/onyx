<?php

function object_to_array($object)
{
	if (!is_object($object) && !is_array($object)) return $object;

	if (is_object($object))
	{
		$object = get_object_vars($object);
	}

	return array_map('object_to_array', $object);
}