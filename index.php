<?php

	date_default_timezone_set('UTC');
	libxml_use_internal_errors(true);
	
	$baseUrl = 'https://www.havelland.de';
	$url = $baseUrl . '/presse/';

	$doc = new DOMDocument();
	$doc->loadHTMLFile($url);

	$links = $doc->getElementsByTagName('a');
	$newsIndex = 0;
	$feed;

	foreach ($links as $link) {
		$linkClass = $link->getAttribute('class');

		// Only process news links
		if (strpos($linkClass, "c-news-list__link") !== false) {
			$newsUrl = $baseUrl . $link->getAttribute('href');
			$newsUrlParts = explode('/', $newsUrl);

			// Fetch the entire content of the news item
			$newsDoc = new DOMDocument();
			$newsDoc->loadHTMLFile($newsUrl);

			$newsContent = "";
			$image = "";

			$containers = $newsDoc->getElementsByTagName('div');
			foreach ($containers as $container) {
				$containerClass = $container->getAttribute('class');

				// Extract news content and description
				if ($containerClass == "news-text-wrap") {
					$newsContent = $newsDoc->saveXML($container);
					break;
				}
			}

			$newsLinks = $newsDoc->getElementsByTagName('a');
			foreach ($newsLinks as $newsLink) {
				$newsLinkClass = $newsLink->getAttribute('class');

				// Extract the first lightbox image url
				if ($newsLinkClass == "lightbox") {
					$imageUrl = $baseUrl . $newsLink->getAttribute('href');
					$newsContent = '<img src="' . $imageUrl . '" />' . $newsContent;
					break;
				}
			}

			// Extract news publish date
			$time = $newsDoc->getElementsByTagName('time');
			$newsDate = strtotime($time[0]->getAttribute('datetime'));

			// build the feed array and extract all relevant data
			$feed[$newsIndex]['id'] = $newsUrlParts[8];
			$feed[$newsIndex]['link'] = $newsUrl;
			$feed[$newsIndex]['title'] = $newsDoc->getElementsByTagName('h2')[0]->nodeValue;
			$feed[$newsIndex]['content'] = $newsContent;
			$feed[$newsIndex]['date'] = $newsDate;

			$newsIndex ++;
		}
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
				$xml->writeElement('title', "Havelland Aktuell (Presse)");
				$xml->writeElement('description', "Havelland Aktuell (Presse) RSS Feed");
				$xml->writeElement('link', "https://www.havelland.de");
				$xml->writeElement('lastBuildDate', date("D, d M Y H:i:s e", time()));
				$xml->writeElement('generator', "https://www.christian-putzke.de");
				$xml->writeElement('language', "de");

				foreach ($feed as $feedItem)
				{
					$xml->startElement("item");
						$xml->writeElement('title', $feedItem["title"]);
						$xml->writeElement('link', $feedItem["link"]);
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