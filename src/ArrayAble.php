<?php

namespace ras8928\RssRator;

use ArrayAccess;

class ArrayAble implements ArrayAccess
{
	public function offsetExists($offset)
	{
		return isset($this->$offset);
	}

	public function offsetGet($offset)
	{
		return isset($this->$offset) ? $this->$offset : null;
	}

	public function offsetSet($offset, $value)
	{
		$this->$offset = $value;
	}

	public function offsetUnset($offset)
	{
		unset($this->$offset);
	}
}
