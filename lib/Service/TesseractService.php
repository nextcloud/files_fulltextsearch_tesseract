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
use Spatie\PdfToImage\Exceptions\PageDoesNotExist;
use Spatie\PdfToImage\Pdf;
use Symfony\Component\EventDispatcher\GenericEvent;
use thiagoalessio\TesseractOCR\TesseractOCR;


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
	public function isValidMimeType(string $mimeType): bool {
		$ocrMimes = explode(",", $this->configService->getAppValue(ConfigService::TESSERACT_MIMETYPES));

		foreach ($ocrMimes as $mime) {
			if ($mimeType === $mime) {
				return true;
			}
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

			if (!$this->isValidMimeType($document->getMimetype())) {
				return;
			}

			// TODO: How to set options so that the index can be reset if admin settings are changed
			//	$this->configService->setDocumentIndexOption($document, ConfigService::FILES_OCR);

			// Not sure why PDF goes into orc and non-pdf gets set as content?
			if ($document->getMimetype() === 'application/pdf') {
				$this->miscService->log("Processing pdf " . $this->getAbsolutePath($file), 1);
				$content = $this->ocrPdf($file);
				$document->addPart('ocr', $content);
			} else {
				$this->miscService->log("Processing other " . $this->getAbsolutePath($file), 1);
				$content = $this->ocrFile($file);
				$document->setContent(base64_encode($content), IIndexDocument::ENCODED_BASE64);
			}

		} catch (Exception $e) {
			return;
		}
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
	private function ocrPdf(File $file): string {
		try {
			$path = $this->getAbsolutePath($file);
			$pdf = new Pdf($path);
		} catch (Exception $e) {
			$this->miscService->log('Exception while ocr pdf file: ' . $e->getMessage(), 1);
			throw new NotFoundException();
		}

		$content = '';
		for ($i = 1; $i <= $pdf->getNumberOfPages(); $i++) {
			// we create a temp image file
			$tmpFile = tmpfile();
			$tmpPath = stream_get_meta_data($tmpFile)['uri'];

			try {
				$pdf->setPage($i);
				$pdf->saveImage($tmpPath);

				$content .= $this->ocrFileFromPath($tmpPath);
			} catch (PageDoesNotExist $e) {
			}
		}

		return $content;
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
