<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_FullTextSearch_Tesseract\Listeners;


use OCA\Files_FullTextSearch_Tesseract\Service\ConfigService;
use OCA\Files_FullTextSearch_Tesseract\Service\TesseractService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\GenericEvent;
use OCP\EventDispatcher\IEventListener;


/**
 * Class FileCreated
 *
 * @package OCA\Circles\Listeners
 */
class GenericListener implements IEventListener {


	/** @var ConfigService */
	private $configService;

	/** @var TesseractService */
	private $tesseractService;


	public function __construct(ConfigService $configService, TesseractService $tesseractService) {
		$this->configService = $configService;
		$this->tesseractService = $tesseractService;
	}


	/**
	 * @param Event $event
	 */
	public function handle(Event $event): void {
		if (!($event instanceof GenericEvent)) {
			return;
		}

		$subject = $event->getSubject();
		if (substr($subject, 0, 21) !== 'Files_FullTextSearch.') {
			return;
		}

		$action = substr($subject, 21);

		switch ($action) {
			case 'onGetConfig':
				$this->configService->onGetConfig($event);
				break;

			case 'onFileIndexing':
				$this->tesseractService->onFileIndexing($event);
				break;

			case 'onSearchRequest':
				$this->tesseractService->onSearchRequest($event);
				break;
		}

	}

}
