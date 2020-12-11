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


use daita\MySmallPhpTools\Traits\Nextcloud\nc20\TNC20Logger;
use Exception;
use OC\Files\View;
use OCP\EventDispatcher\GenericEvent;
use OCP\Files\File;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files_FullTextSearch\Model\AFilesDocument;
use OCP\FullTextSearch\Model\IIndexDocument;
use OCP\FullTextSearch\Model\ISearchRequest;
use Spatie\PdfToImage\Exceptions\PageDoesNotExist;
use Spatie\PdfToImage\Pdf;
use thiagoalessio\TesseractOCR\TesseractOCR;
use thiagoalessio\TesseractOCR\TesseractOcrException;
use Throwable;


/**
 * Class TesseractService
 *
 * @package OCA\Files_FullTextSearch_Tesseract\Service
 */
class TesseractService {


	use TNC20Logger;


	/** @var ConfigService */
	private $configService;


	/**
	 * TesseractService constructor.
	 *
	 * @param ConfigService $configService
	 */
	public function __construct(ConfigService $configService) {
		$this->configService = $configService;

		$this->setup('app', 'files_fulltextsearch_tesseract');
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

			$this->debug(
				'extracting content using TesseractOCR',
				[
					'documentId' => $document->getId(),
					'path'       => $document->getPath(),
					'mime'       => $document->getMimetype(),
					'extension'  => $extension
				]
			);

			// TODO: How to set options so that the index can be reset if admin settings are changed
			//	$this->configService->setDocumentIndexOption($document, ConfigService::FILES_OCR);

			if ($this->ocrPdf($document, $file)) {
				return;
			}

			$content = $this->ocrFile($file);
		} catch (Exception $e) {
			return;
		} catch (Throwable $e) {
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
			$this->exception($e, self::$NOTICE);
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
		$this->debug('generating the TesseractOCR wrapper', ['path' => $path]);

		$ocr = new TesseractOCR($path);
		$ocr->psm($this->configService->getAppValue(ConfigService::TESSERACT_PSM));
		$lang = explode(',', $this->configService->getAppValue(ConfigService::TESSERACT_LANG));
		call_user_func_array([$ocr, 'lang'], array_map('trim', $lang));
		$this->debug('running the OCR command', ['command' => $ocr->command]);

		if ($this->configService->getLogLevel() > 0) {
			$ocr->command .= ' 2> /dev/null';
		}

		try {
			$result = $ocr->run();
			$this->debug('OCR command ran smoothly');
		} catch (Exception $e) {
			$this->exception($e, self::$NOTICE, ['path' => $path, 'cmd' => $ocr->command, 'lang' => $lang]);
			$result = '';
		}

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

		$this->debug('looks like we\'re working on a PDF file');

		try {
			$path = $this->getAbsolutePath($file);
			$this->debug('Absolute path', ['path' => $path]);
			$pdf = new Pdf($path);
		} catch (Exception $e) {
			$this->exception($e, self::$NOTICE, ['document' => $document]);
			throw new NotFoundException();
		}

		$content = '';
		$pages = $pdf->getNumberOfPages();
		$this->debug('PDF contains ' . $pages . ' page(s)');

		$limit = (int)$this->configService->getAppValue(ConfigService::TESSERACT_PDF_LIMIT);
		$pages = ($limit > 0 && $pages > $limit) ? $limit : $pages;
		$this->debug('App will now ocr ' . $pages . ' page(s)');


		for ($i = 1; $i <= $pages; $i++) {
			$this->debug('Creating a temp image file for page #' . $i);

			$tmpFile = tmpfile();
			$tmpPath = stream_get_meta_data($tmpFile)['uri'];
			$this->debug('temp image file: ' . $tmpPath . ' for page #' . $i);

			try {
				$this->debug('opening the PDF at the page #' . $i);
				$pdf->setPage($i);

				$this->debug('saving the current page as image', ['tmpPath' => $tmpPath]);
				$pdf->saveImage($tmpPath);

				$content .= $this->ocrFileFromPath($tmpPath);
			} catch (PageDoesNotExist $e) {
			}

			fclose($tmpFile);
		}

		$this->debug('Saving the data into the IndexDocument');
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

		return $view->getLocalFile($file->getPath());
	}


}
