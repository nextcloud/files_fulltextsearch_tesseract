<?php

declare(strict_types=1);


/**
 * Files_FullTextSearch - Index the content of your files
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2020
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
