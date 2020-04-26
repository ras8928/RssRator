<?php

namespace ras8928\RssRator;

abstract class RssRatorGetterSetter
{
	/**
	 * Set the value of FeedTitle
	 *
	 */
	public function setFeedTitle($FeedTitle): self
	{
		$this->FeedTitle = cleanSpecial($FeedTitle);

		return $this;
	}

	/**
	 * Get the value of FeedDescription
	 */
	public function getFeedDescription()
	{
		return $this->FeedDescription ?? $this->getFeedTitle();;
	}

	/**
	 * Get the value of FeedTitle
	 */
	public function getFeedTitle()
	{
		return $this->FeedTitle;
	}

	/**
	 * Set the value of FeedDescription
	 *
	 */
	public function setFeedDescription($FeedDescription): self
	{
		$this->FeedDescription = cleanSpecial($FeedDescription);

		return $this;
	}

	/**
	 * Set the value of FeedOriginUrl
	 *
	 */
	public function setOriginUrl($FeedOriginUrl): self
	{
		$this->FeedOriginUrl = $FeedOriginUrl;

		return $this;
	}
}
