<?php

	date_default_timezone_set('UTC');

	require "vendor/autoload.php";

	use PHPHtmlParser\Dom;
	 
	$url = 'https://www.havelland.de';

	// fetch the havelland start page
	$dom = new Dom;
	$dom->loadFromUrl($url);

	$content = $dom->find('.news')[1];

	$newsIndex = 0;
	$feed;

	// extract the preview snippets for the most recent news items
	foreach($content->find('.c-text-teaser a.c-link') as $newsLink)
	{
		// extract the URL to the news item
		$newsUrl = $url . $newsLink->href;
		$newsUrlParts = explode('/', $newsUrl);

		// fetch the whole content of the news item
		$newsDom = new Dom;
		$newsDom->loadFromUrl($newsUrl);

		// filter for the actual interesting contents
		$article = $newsDom->find('.article');
		$newsHeader = $article->find('.news-header h2');
		$newsContent = $article->find('.news-text-wrap');

		// extract the publish date
		$newsDate = strtotime($article->find('.news-list-date time')->getAttribute('datetime'));

		// build the feed array and extract all relevant data 
		$feed[$newsIndex]['id'] = $newsUrlParts[8];
		$feed[$newsIndex]['link'] = $newsUrl;
		$feed[$newsIndex]['title'] = $newsHeader->innerHtml;
		$feed[$newsIndex]['description'] = $newsContent[0]->find('p')->innerHtml;
		$feed[$newsIndex]['content'] = $newsContent[1]->innerHtml;
		$feed[$newsIndex]['date'] = $newsDate;

		$newsIndex ++;
	}

	// build the XML RSS2.0 document which will be rendered in the end
	$xml = new XMLWriter();
	$xml->openURI('php://output');
	$xml->setIndent(4);

	$xml->startDocument('1.0', 'UTF-8'); 

		$xml->startElement('rss');
			$xml->writeAttribute('xmlns:content', 'http://purl.org/rss/1.0/modules/content/');
			$xml->writeAttribute('xmlns:slash', 'http://purl.org/rss/1.0/modules/slash/');
			$xml->writeAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
			$xml->writeAttribute('version', '2.0');

			$xml->startElement("channel");
				$xml->writeElement('title', "Havelland Aktuell (inoffiziell)");
				$xml->writeElement('description', "Der inoffizielle Havelland Aktuell RSS Feed");
				$xml->writeElement('link', "https://www.havelland.de");
				$xml->writeElement('atom:link', "https://havelland.graviox.de");
				$xml->writeElement('lastBuildDate', date("D, d M Y H:i:s e", time()));
				$xml->writeElement('generator', "https://www.christian-putzke.de");
				$xml->writeElement('language', "de");

				foreach ($feed as $feedItem)
				{
					$xml->startElement("item");
						$xml->writeElement('title', $feedItem["title"]);
						$xml->writeElement('link', $feedItem["link"]);
						$xml->writeElement('description', $feedItem["description"]);
						$xml->writeElement('pubDate', date("D, d M Y H:i:s e", $feedItem["date"]));
						$xml->writeElement('guid', $feedItem["id"]);
						$xml->startElement("content:encoded");
							$xml->writeCData($feedItem["content"]);
						$xml->endElement();
					$xml->endElement();
				}
			$xml->endElement();

		$xml->endElement();

	$xml->endDocument();

	$xml->flush();