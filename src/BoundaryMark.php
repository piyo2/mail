<?php

namespace piyo2\mail;

class BoundaryMark
{
	/** @var string */
	protected $prefix;

	/** @var int */
	protected $index = 0;

	public function __construct(string $prefix = 'NextPart_')
	{
		$this->prefix = $prefix;
	}

	public function next(): string
	{
		$this->index++;
		return $this->prefix . \dechex($this->index);
	}
}
