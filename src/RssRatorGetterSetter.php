<?php

namespace ras8928\RssRator;

class RssRatorGetterSetter
{
	/**
	 * Set the value of FeedTitle.
	 *
	 * @param mixed $FeedTitle
	 */
	public function setFeedTitle($FeedTitle): self
	{
		$this->FeedTitle = cleanSpecial($FeedTitle);

		return $this;
	}

	/**
	 * Get the value of FeedDescription.
	 */
	public function getFeedDescription()
	{
		return $this->FeedDescription ?? $this->getFeedTitle();
	}

	/**
	 * Get the value of FeedTitle.
	 */
	public function getFeedTitle()
	{
		return $this->FeedTitle;
	}

	/**
	 * Set the value of FeedDescription.
	 *
	 * @param string $FeedDescription
	 */
	public function setFeedDescription(string $FeedDescription): self
	{
		$this->FeedDescription = cleanSpecial($FeedDescription);

		return $this;
	}

	/**
	 * Set the value of FeedOriginUrl.
	 *
	 * @param string $FeedOriginUrl
	 */
	public function setOriginUrl(string $FeedOriginUrl): self
	{
		$this->FeedOriginUrl = $FeedOriginUrl;

		return $this;
	}
}
