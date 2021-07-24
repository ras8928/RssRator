<?php

namespace ras8928\RssRator;

use Carbon\Carbon;

require_once('function_myFC.php');

class ContentItem extends ArrayAble
{
	private $Title;
	private $Date;
	private $Description;
	private $Author;

	private $org_Image;
	private $Image;
	private $ImageCache = false;
	private $ImageCaption;
	private $ImageInDescription = true;
	private $Thumb;

	private $GUID;
	private $Link;
	private $Source;

	private $rss;

	public function __construct(RssRator $rss = null)
	{
		$this->rss = $rss;
	}

	public function getTitle()
	{
		return $this->encloseInCDATA($this->Title);
	}

	public function setTitle(string $Title = null): self
	{
		$this->Title = $Title;
		return $this;
	}

	private function encloseInCDATA($string)
	{
		return empty($string)
			? null
			: "<![CDATA[" . $string . ']]>';
		//		return $string;
	}

	public function getDate()
	{
		return $this->encloseInCDATA($this->Date);
	}

	public function setDate(string $Date = null): self
	{
		@$this->Date = Carbon::parse($Date);
		$this->Date = $this->Date
			? $this->Date->format(DATE_RSS)
			: null;
		return $this;
	}

	public function getDescription()
	{
		$description = $this->Description;

		if ($this->getImage() && $this->HasImageInDescription()) {
			$img = '<p><img alt="image" src="'
				. $this->getImage()
				. '" '
				. ($this->hasImageCaption()
					? 'title="' . $this->ImageCaption . '" '
					: '')
				. '/></p>'
				. ($this->hasImageCaption() ? "<small><em>{$this->ImageCaption}</em></small><br><br>" : '');

			$description = $img . $description;
		}

		return $this->encloseInCDATA($description);
	}

	public function setDescription(string $Description = null): self
	{
		$this->Description = $Description;
		return $this;
	}

	public function getImage()
	{
		return $this->Image;
	}

	public function setImage(string $ImageUrl = null): self
	{
		if (filter_var($ImageUrl, FILTER_VALIDATE_URL)) {
			$this->Image = $ImageUrl;
			$this->org_Image = $ImageUrl;
		}
		return $this;
	}

	public function hasImageCaption(): bool
	{
		return !empty($this->ImageCaption);
	}

	public function getAuthor()
	{
		return $this->encloseInCDATA($this->Author);
	}

	public function setAuthor(string $Author = null): self
	{
		$this->Author = $Author;
		return $this;
	}

	public function getImageCaption()
	{
		return $this->ImageCaption;
	}

	public function setImageCaption(string $ImageCaption = null): self
	{
		$this->ImageCaption = $ImageCaption;
		return $this;
	}

	public function hasThumb(): bool
	{
		return !empty($this->Thumb);
	}

	public function getThumb()
	{
		return $this->Thumb;
	}

	public function setThumb(string $ThumbUrl = null): self
	{
		$this->Thumb = $ThumbUrl;
		return $this;
	}

	public function getGUID()
	{
		return $this->GUID
			?? ($this->getLink()
				?? preg_replace('/\W+/', '', strtolower($this->Title)));
	}

	public function setGUID(string $GUID = null): self
	{
		$this->GUID = $GUID;
		return $this;
	}

	public function getLink()
	{
		return $this->Link;
	}

	public function setLink(string $Link = null): self
	{
		if (filter_var($Link, FILTER_VALIDATE_URL)) {
			$this->Link = $Link;
		}

		return $this;
	}

	public function getSource()
	{
		return $this->Source;
	}

	public function setSource(string $Source = null): self
	{
		$this->Source = $Source;
		return $this;
	}

	public function CacheImage(bool $HasCache = true)
	{
		$this->ImageCache = $HasCache;
		return $this;
	}

	public function IsImageCached(): bool
	{
		return !!$this->ImageCache;
	}

	public function HideImageInDescription(bool $Hide = true)
	{
		$this->ImageInDescription = $Hide;
		return $this;
	}

	public function HasImageInDescription(): bool
	{
		return !!$this->ImageInDescription;
	}

	public function append()
	{
		if (!$this->rss) {
			throw new \Exception('RssRator object not provided during intialization');
		}

		$this->rss->appendItem($this);
	}
}
