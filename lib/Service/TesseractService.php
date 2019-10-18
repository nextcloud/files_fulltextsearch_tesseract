<?php
declare(strict_types=1);


/**
 * Files_FullTextSearch_OCR - OCR your files before index
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
use OCP\Files\File;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files_FullTextSearch\Model\AFilesDocument;
use OCP\FullTextSearch\Model\IIndexDocument;
use OCP\FullTextSearch\Model\ISearchRequest;
use Symfony\Component\EventDispatcher\GenericEvent;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Wb\PdfImages\PdfImages;


/**
 * Class TesseractService
 *
 * @package OCA\Files_FullTextSearch_Tesseract\Service
 */
class TesseractService {


	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;


	/**
	 * TesseractService constructor.
	 *
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(ConfigService $configService, MiscService $miscService) {
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 * @param string $mimeType
	 * @param string $extension
	 *
	 * @return bool
	 */
	public function parsedMimeType(string $mimeType, string $extension): bool {
		$ocrMimes = [
			'image/png',
			'image/jpeg',
			'image/tiff',
			'image/vnd.djvu',
			'application/pdf'
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

		/** @var \OCP\Files_FullTextSearch\Model\AFilesDocument $document */
		$document = $e->getArgument('document');

		$this->extractContentUsingTesseractOCR($document, $file);
	}


	/**
	 * @param GenericEvent $e
	 */
	public function onSearchRequest(GenericEvent $e) {
		/** @var ISearchRequest $file */
		$request = $e->getArgument('request');
		$request->addPart('ocr');
	}


	/**
	 * @param AFilesDocument $document
	 * @param File $file
	 */
	private function extractContentUsingTesseractOCR(AFilesDocument &$document, File $file) {

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

			if ($this->ocrPdf($document, $file)) {
				return;
			}

			$content = $this->ocrFile($file);
		} catch (Exception $e) {
			return;
		}

		$document->setContent(base64_encode($content), IIndexDocument::ENCODED_BASE64);
	}


	/**
	 * @param File $file
	 *
	 * @return string
	 * @throws NotFoundException
	 */
	private function ocrFile(File $file): string {

		try {
			$path = $this->getAbsolutePath($file);
		} catch (Exception $e) {
			$this->miscService->log('Exception while ocr file: ' . $e->getMessage(), 1);
			throw new NotFoundException();
		}

		return $this->ocrFileFromPath($path);
	}


	/**
	 * @param string $path
	 *
	 * @return string
	 */
	private function ocrFileFromPath(string $path): string {
		$ocr = new TesseractOCR($path);
		$ocr->psm($this->configService->getAppValue(ConfigService::TESSERACT_PSM));
		$lang = explode(',', $this->configService->getAppValue(ConfigService::TESSERACT_LANG));
		call_user_func_array([$ocr, 'lang'], array_map('trim', $lang));
		$ocr->command .= ' 2> /dev/null';
		$result = $ocr->run();

		return $result;
	}


	/**
	 * @param AFilesDocument $document
	 * @param File $file
	 *
	 * @return bool
	 * @throws NotFoundException
	 */
	private function ocrPdf(AFilesDocument $document, File $file): bool {
		if ($document->getMimetype() !== 'application/pdf') {
			return false;
		}

		if ($this->configService->getAppValue(ConfigService::TESSERACT_PDF) !== '1') {
			return true;
		}

		
		try {
		    $path = $this->getAbsolutePath($file);
		    $pdfImages = PdfImages::create()->extractImages($path);
		} catch (Exception $e) {
			$this->miscService->log('Exception while ocr pdf file: ' . $e->getMessage(), 1);
			throw new NotFoundException();
		}

		$content = '';
		foreach ($pdfImages as $pdfImage) {
		    $imageText = $this->ocrFileFromPath($pdfImage->getPathname());
		    unlink($pdfImage->getPathname());
		    $content .= $imageText;
		}
		rmdir($pdfImages->getPath());
		
		$document->addPart('ocr', $content);

		return true;
	}


	/**
	 * @param string $extension
	 *
	 * @return bool
	 */
	private function parsedExtension(string $extension): bool {
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
	private function getAbsolutePath(File $file): string {
		$view = new View('');
		$absolutePath = $view->getLocalFile($file->getPath());

		return $absolutePath;
	}


}
