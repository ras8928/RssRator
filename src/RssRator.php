<?php

namespace ras8928\RssRator;

use DOMDocument;
use DOMNode;

class RssRator extends RssRatorGetterSetter
{
	protected $FeedTitle;
	protected $FeedDescription;
	protected $FeedOriginUrl;

	public $LastBuildDate;
	public $Ttl;
	public $Favico;

	private $Items = [];
	private $dom;
	private $ChannelElement;

	public function __construct()
	{
		$this->dom = new DOMDocument;
		$this->dom->preserveWhiteSpace = false;
		$this->dom->formatOutput = true;

		$this->dom->loadXML($this->boilerPlateRss());

		$this->ChannelElement = $this->dom
			->getElementsByTagName('channel')
			->item(0);
	}

	/** @noinspection XmlUnusedNamespaceDeclaration */
	private function boilerPlateRss()
	{
		return '<?xml version="1.0" encoding="UTF-8"?>
		<rss xmlns:media="http://search.yahoo.com/mrss/" xmlns:webfeeds="http://webfeeds.org/rss/1.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" version="2.0">
		<channel></channel></rss>';
	}

	public function createItem()
	{
		return new ContentItem($this);
	}

	public function appendItem(ContentItem $item)
	{
		$this->Items[] = $item;
	}

	public function getRss()
	{
		if (!$this->getFeedTitle()) {
			throw new \Exception('Feed title not set');
		}

		$this->setFeedMeta();
		$this->createItems();

		return $this->dom->saveXML();
	}

	private function setFeedMeta()
	{
		$this->appendChild(
			$this->ChannelElement,
			'title',
			$this->getFeedTitle()
		);
		$this->appendChild(
			$this->ChannelElement,
			'description',
			$this->getFeedDescription()
		);
		$this->appendChild(
			$this->ChannelElement,
			'lastBuildDate',
			$this->LastBuildDate
		);
		$this->appendChild(
			$this->ChannelElement,
			'pubDate',
			gmdate(DATE_RSS)
		);
		$this->appendChild(
			$this->ChannelElement,
			'ttl',
			$this->Ttl ?? '60'
		);
	}

	/**
	 * @param DOMNode $ParentElement
	 * @param string $ElementName
	 * @param string|null $Content
	 * @param array $Attribs
	 * @return bool|DOMNode
	 */
	private function appendChild(DOMNode $ParentElement, string $ElementName, string $Content = null, array $Attribs = [])
	{
		if ($Content !== null) {
			$element = $this->dom->createElement($ElementName);

			if ($Content) {
				$inner_html = $element->ownerDocument->createDocumentFragment();
				$inner_html->appendXML($Content);
				$element->appendChild($inner_html);
			}

			foreach ($Attribs as $key => $value) {
				$element->setAttribute($key, $value);
			}

			return $ParentElement->appendChild($element);
		}
		return false;
	}


	private function createItems()
	{
		foreach ($this->Items as $ItemData) {

			// CREATE NEW ITEM IN CHANEL
			$ItemElement = $this->appendChild(
				$this->ChannelElement,
				'item',
				''
			);

			// SET PROPERTIES IN ITEM
			$this->setItem($ItemElement, $ItemData);
		}
	}

	private function setItem(DOMNode $ItemElement, ContentItem $ItemData): void
	{
		$this->appendChild(
			$ItemElement,
			'title',
			$ItemData->getTitle()
		);
		$this->appendChild(
			$ItemElement,
			'description',
			$ItemData->getDescription()
		);
		$this->appendChild(
			$ItemElement,
			'date',
			$ItemData->getDate()
		);
		$this->appendChild(
			$ItemElement,
			'author',
			$ItemData->getAuthor()
		);
		$this->appendChild(
			$ItemElement,
			'source',
			$ItemData->getSource()
		);
		$this->appendChild(
			$ItemElement,
			'link',
			$ItemData->getLink()
		);
		$this->appendChild(
			$ItemElement,
			'guid',
			$ItemData->getGUID(),
			['isPermaLink' => $ItemData->getGUID() == $ItemData->getLink() ? 'true' : 'false']
		);
	}
}
