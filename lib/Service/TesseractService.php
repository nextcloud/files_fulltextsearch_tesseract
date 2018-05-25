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
use OCA\Files_FullTextSearch\Exceptions\FileNotFoundException;
use OCP\AppFramework\IAppContainer;
use OCP\Files\File;
use thiagoalessio\TesseractOCR\TesseractOCR;

class TesseractService {

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;


	/**
	 * BookmarksService constructor.
	 *
	 * @param IAppContainer $container
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 */
	public function __construct(
		IAppContainer $container, ConfigService $configService, MiscService $miscService
	) {
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 * @param $file
	 *
	 * @return string
	 * @throws FileNotFoundException
	 */
	public function ocrFile(File $file) {

		try {
			$path = $this->getAbsolutePath($file);
		} catch (Exception $e) {
			throw new FileNotFoundException('file not found');
		}

		$ocr = new TesseractOCR($path);
		$ocr->psm($this->configService->getAppValue(ConfigService::TESSERACT_PSM));
		$lang = explode(',', $this->configService->getAppValue(ConfigService::TESSERACT_LANG));
		call_user_func_array([$ocr, 'lang'], array_map('trim', $lang));
		$result = $ocr->run();

		return $result;
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