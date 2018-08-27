<?php
/**
 * Files_FullTextSearch_OCR - OCR your documents before index
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2018
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

namespace OCA\Files_FullTextSearch_Tesseract\Service;


use Exception;
use OC\Files\View;
use OCA\Files_FullTextSearch\Model\FilesDocument;
use OCP\Files\File;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use Symfony\Component\EventDispatcher\GenericEvent;
use thiagoalessio\TesseractOCR\TesseractOCR;

class TesseractService {

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;


	/**
	 * BookmarksService constructor.
	 *
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		ConfigService $configService, MiscService $miscService
	) {
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 * @param string $mimeType
	 * @param string $extension
	 *
	 * @return bool
	 */
	public function parsedMimeType($mimeType, $extension) {
		$ocrMimes = [
			'image/png',
			'image/jpeg',
			'image/tiff',
			'image/vnd.djvu'
		];

		foreach ($ocrMimes as $mime) {
			if (strpos($mimeType, $mime) === 0) {
				return true;
			}
		}

		if ($mimeType === 'application/octet-stream') {
			return $this->parsedExtension($extension);
		}

		return false;
	}


	/**
	 * @param GenericEvent $e
	 */
	public function onFileIndexing(GenericEvent $e) {
		/** @var Node $file */
		$file = $e->getArgument('file');

		if (!$file instanceof File) {
			return;
		}

		/** @var FilesDocument $document */
		$document = $e->getArgument('document');

		$this->extractContentUsingTesseractOCR($document, $file);
	}


	/**
	 * @param GenericEvent $e
	 */
	public function onSearchRequest(GenericEvent $e) {
//		/** @var SearchRequest $request */
//		$request = $e->getArgument('request');
//		$meta = $request->getOption('meta', []);
//		foreach ($meta as $m) {
//			$request->addMetaTag($m);
//		}
	}


	/**
	 * @param FilesDocument $document
	 * @param File $file
	 */
	private function extractContentUsingTesseractOCR(FilesDocument &$document, File $file) {

		try {
			if ($this->configService->getAppValue(ConfigService::TESSERACT_ENABLED) !== '1') {
				return;
			}

			$extension = pathinfo($document->getPath(), PATHINFO_EXTENSION);
			if (!$this->parsedMimeType($document->getMimetype(), $extension)) {
				return;
			}

			// TODO: How to set options so that the index can be reset if admin settings are changed
			//	$this->configService->setDocumentIndexOption($document, ConfigService::FILES_OCR);

			$content = $this->ocrFile($file);
		} catch (Exception $e) {
			return;
		}

		$document->setContent(base64_encode($content), FilesDocument::ENCODED_BASE64);
	}


	/**
	 * @param $file
	 *
	 * @return string
	 * @throws NotFoundException
	 */
	private function ocrFile(File $file) {

		try {
			$path = $this->getAbsolutePath($file);
		} catch (Exception $e) {
			throw new NotFoundException('file not found');
		}

		$ocr = new TesseractOCR($path);
		$ocr->psm($this->configService->getAppValue(ConfigService::TESSERACT_PSM));
		$lang = explode(',', $this->configService->getAppValue(ConfigService::TESSERACT_LANG));
		call_user_func_array([$ocr, 'lang'], array_map('trim', $lang));
		$result = $ocr->run();

		return $result;
	}


	/**
	 * @param string $extension
	 *
	 * @return bool
	 */
	private function parsedExtension($extension) {
		$ocrExtensions = [
//					'djvu'
		];

		if (in_array($extension, $ocrExtensions)) {
			return true;
		}

		return false;
	}


	/**
	 * @param File $file
	 *
	 * @return string
	 * @throws Exception
	 */
	private function getAbsolutePath(File $file) {
//		$userId = $file->getOwner()
//					   ->getUID();
//
//		$view = new View($userId . '/files/');
		$view = new View('');
		$absolutePath = $view->getLocalFile($file->getPath());

		return $absolutePath;
	}


}