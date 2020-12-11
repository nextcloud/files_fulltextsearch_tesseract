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


namespace OCA\Files_FullTextSearch_Tesseract\Controller;


use OCA\Files_FullTextSearch_Tesseract\AppInfo\Application;
use OCA\Files_FullTextSearch_Tesseract\Service\ConfigService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;


/**
 * Class SettingsController
 *
 * @package OCA\Files_FullTextSearch_Tesseract\Controller
 */
class SettingsController extends Controller {


	/** @var ConfigService */
	private $configService;


	/**
	 * SettingsController constructor.
	 *
	 * @param IRequest $request
	 * @param ConfigService $configService
	 */
	public function __construct(IRequest $request, ConfigService $configService) {
		parent::__construct(Application::APP_NAME, $request);
		$this->configService = $configService;
	}


	/**
	 * @return DataResponse
	 */
	public function getSettingsAdmin(): DataResponse {
		$data = $this->configService->getConfig();

		return new DataResponse($data, Http::STATUS_OK);
	}


	/**
	 * @param $data
	 *
	 * @return DataResponse
	 */
	public function setSettingsAdmin(array $data): DataResponse {
		$this->configService->setConfig($data);

		return $this->getSettingsAdmin();
	}

}

