<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_FullTextSearch_Tesseract\Service;


use Exception;
use OC\Files\View;
use OCP\EventDispatcher\GenericEvent;
use OCP\Files\File;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files_FullTextSearch\Model\AFilesDocument;
use OCP\FullTextSearch\Model\IIndexDocument;
use OCP\FullTextSearch\Model\ISearchRequest;
use Psr\Log\LoggerInterface;
use Spatie\PdfToImage\Exceptions\PageDoesNotExist;
use Spatie\PdfToImage\Pdf as PdfToImage_Pdf;
use Spatie\PdfToText\Pdf as PdfToText_Pdf;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Throwable;



/**
 * Class TesseractService
 *
 * @package OCA\Files_FullTextSearch_Tesseract\Service
 */
class TesseractService {

	public function __construct(
		private ConfigService $configService,
		private LoggerInterface $logger
	) {
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
	public function onFileIndexing(GenericEvent $e): void {
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
	private function extractContentUsingTesseractOCR(AFilesDocument &$document, File $file): void {
		try {
			if ($this->configService->getAppValue(ConfigService::TESSERACT_ENABLED) !== '1') {
				return;
			}

			$extension = pathinfo($document->getPath(), PATHINFO_EXTENSION);

			if (!$this->parsedMimeType($document->getMimetype(), $extension)) {
				return;
			}

			$this->logger->debug(
				'extracting content using TesseractOCR',
				[
					'documentId' => $document->getId(),
					'path' => $document->getPath(),
					'mime' => $document->getMimetype(),
					'extension' => $extension
				]
			);

			// TODO: How to set options so that the index can be reset if admin settings are changed
			//	$this->configService->setDocumentIndexOption($document, ConfigService::FILES_OCR);

			if ($this->ocrPdf($document, $file)) {
				return;
			}

			$content = $this->ocrFile($file);
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
			$this->logger->notice('issue during ocrFile()', ['exception' => $e]);
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
		$this->logger->debug('generating the TesseractOCR wrapper', ['path' => $path]);

		$ocr = new TesseractOCR($path);
		$ocr->psm($this->configService->getAppValue(ConfigService::TESSERACT_PSM));
		$lang = explode(',', $this->configService->getAppValue(ConfigService::TESSERACT_LANG));
		call_user_func_array([$ocr, 'lang'], array_map('trim', $lang));
		$this->logger->debug('running the OCR command', ['command' => $ocr->command]);

//		if ($this->configService->getLogLevel() > 0) {
//			$ocr->command .= ' 2> /dev/null';
//		}

		try {
			$result = $ocr->run();
			$this->logger->debug('OCR command ran smoothly');
		} catch (Exception $e) {
			$this->logger->notice('failed to OCR', [
				'exception' => $e,
				'path' => $path,
				'cmd' => $ocr->command,
				'lang' => $lang
			]);
			$result = '';
		}

		return $result;
	}


	/**
	 * @param string $path
	 *
	 * @return bool
	 */
	private function pdfContainsText(string $path): bool {
		try {
			$text = (new PdfToText_Pdf())->setPdf($path)->text();
			return $text !== '';
		} catch (Exception $e) {
			$this->logger->notice('extracting text from PDF failed', ['exception' => $e, 'path' => $path]);
		}
		return false;
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

		$this->logger->debug('looks like we\'re working on a PDF file');

		try {
			$path = $this->getAbsolutePath($file);
			$this->logger->debug('Absolute path', ['path' => $path]);

			if ($this->configService->optionIsSelected(ConfigService::TESSERACT_PDF_SKIP_TEXT)
				&& $this->pdfContainsText($path)) {
				$this->logger->debug('PDF file contains text, skipping OCR');
				return true;
			}

			$pdf = new PdfToImage_Pdf($path);
		} catch (Exception $e) {
			$this->logger->notice('failed to ocrPdf', ['exception' => $e, 'document' => $document]);
			throw new NotFoundException();
		}

		$content = '';
		$pages = $pdf->getNumberOfPages();
		$this->logger->debug('PDF contains ' . $pages . ' page(s)');

		$limit = (int)$this->configService->getAppValue(ConfigService::TESSERACT_PDF_LIMIT);
		$pages = ($limit > 0 && $pages > $limit) ? $limit : $pages;
		$this->logger->debug('App will now ocr ' . $pages . ' page(s)');


		for ($i = 1; $i <= $pages; $i++) {
			$this->logger->debug('Creating a temp image file for page #' . $i);

			$tmpFile = tmpfile();
			$tmpPath = stream_get_meta_data($tmpFile)['uri'];
			$this->logger->debug('temp image file: ' . $tmpPath . ' for page #' . $i);

			try {
				$this->logger->debug('opening the PDF at the page #' . $i);
				$pdf->setPage($i);

				$this->logger->debug('saving the current page as image', ['tmpPath' => $tmpPath]);
				$pdf->saveImage($tmpPath);

				$content .= $this->ocrFileFromPath($tmpPath);
			} catch (PageDoesNotExist $e) {
			}

			fclose($tmpFile);
		}

		$this->logger->debug('Saving the data into the IndexDocument');
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
