<?php

namespace ras8928\RssRator;

require_once('function_myFC.php');

class ContentItem extends ArrayAble
{

	protected $Title;
	protected $Date;
	protected $Description;

	protected $org_Image;
	protected $Image;
	protected $ImageCache = false;
	protected $ImageCaption;
	protected $Thumb;

	protected $GUID;
	protected $Link;
	protected $Source;

	public function __construct()
	{ }

	public function setTitle(string $Title): self
	{
		$this->Title = $Title;
		return $this;
	}

	public function setDate(string $Date): self
	{
		$this->Date = $Date;
		return $this;
	}

	public function setDescription(string $Description): self
	{
		$this->Description = $Description;
		return $this;
	}

	public function setImage(string $ImageUrl): self
	{
		$this->org_Image = $ImageUrl;
		$this->Image = $ImageUrl;
		return $this;
	}

	public function setImageCaption(string $ImageCaption): self
	{
		$this->ImageCaption = $ImageCaption;
		return $this;
	}

	public function ImageHasCache(bool $HasCache = true)
	{
		$this->ImageCache = true;
		return $this;
	}

	public function setThumb(string $ThumbUrl): self
	{
		$this->Thumb = $ThumbUrl;
		return $this;
	}

	public function setGUID(string $GUID): self
	{
		$this->GUID = $GUID;
		return $this;
	}

	public function setLink(string $Link): self
	{
		$this->Link = $Link;
		return $this;
	}

	public function setSource(string $Source): self
	{
		$this->Source = $Source;
		return $this;
	}
}
