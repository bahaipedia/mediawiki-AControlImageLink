<?php

/**
 * Extension:AControlImageLink - MediaWiki extension.
 * Copyright (C) 2020 Edward Chernenko.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

/**
 * @file
 * Hooks of Extension:AControlImageLink.
 */

use MediaWiki\Config\Config;
use MediaWiki\Content\TextContent;
use MediaWiki\Linker\LinkRendererFactory;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Parser\Parser;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;

class AControlImageLinkHooks {

	private Config $config;
	private WikiPageFactory $wikiPageFactory;
	private LinkRendererFactory $linkRendererFactory;

	public function __construct(
		Config $config,
		WikiPageFactory $wikiPageFactory,
		LinkRendererFactory $linkRendererFactory
	) {
		$this->config = $config;
		$this->wikiPageFactory = $wikiPageFactory;
		$this->linkRendererFactory = $linkRendererFactory;
	}

	/**
	 * Prevent thumbnail from rendering if image has <accesscontrol> tag,
	 * and the article that includes it either doesn't or has a different <accesscontrol> tag.
	 * @param mixed &$dummyLinker
	 * @param Title &$title
	 * @param mixed &$file
	 * @param array &$frameParams
	 * @param array &$handlerParams
	 * @param string|bool &$time
	 * @param string|null &$result
	 * @param Parser $parser
	 * @param string &$query
	 * @param int|null &$widthOption
	 * @return bool
	 */
	public function onImageBeforeProduceHTML(
		&$dummyLinker, Title &$title, &$file, array &$frameParams, array &$handlerParams,
		&$time, &$result, Parser $parser, string &$query, &$widthOption
	) {
		$restrictedExtensions = $this->config->get( 'AControlImageLinkRestrictedExtensions' );

		$filenameParts = explode( '.', $title->getText() );
		$fileExtension = array_pop( $filenameParts );
		if ( !in_array( $fileExtension, $restrictedExtensions ) ) {
			// Images with this extension are not restricted from thumbnailing.
			return true;
		}

		$imageRestriction = $this->findAccessControlTag( $title );
		if ( !$imageRestriction ) {
			// No <accesscontrol> on image page.
			return true;
		}

		$articleRestriction = $this->findAccessControlTag( $parser->getTitle() );
		if ( $articleRestriction === $imageRestriction ) {
			// Contents of <accesscontrol> tags are the same,
			// so thumbnailing is allowed.
			return true;
		}

		// Not allowed. Make a simple link instead of thumbnail.
		$linkRenderer = $this->linkRendererFactory->create();
		$result = $linkRenderer->makeLink( $title );

		// Suppress normal handling of [[File:]] syntax.
		return false;
	}

	/**
	 * @param Title $title Page where to look for <accesscontrol> tags
	 * @return string|null Text inside <accesscontrol> tag.
	 */
	protected function findAccessControlTag( Title $title ) {
		$page = $this->wikiPageFactory->newFromLinkTarget( $title );
		$content = $page->getContent( RevisionRecord::RAW );
		if ( !$content instanceof TextContent ) {
			// Page doesn't exist or is not text content.
			return null;
		}

		$text = $content->getText();

		$matches = false;
		if ( !preg_match( '/<accesscontrol>(.*)<\/accesscontrol>/', $text, $matches ) ) {
			// No <accesscontrol> tag.
			return null;
		}

		return trim( $matches[1] );
	}
}
