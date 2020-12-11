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


namespace OCA\Files_FullTextSearch_Tesseract\AppInfo;


use OCA\Files_FullTextSearch_Tesseract\Listeners\GenericListener;
use OCA\Files_FullTextSearch_Tesseract\Service\ConfigService;
use OCA\Files_FullTextSearch_Tesseract\Service\TesseractService;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\QueryException;
use OCP\EventDispatcher\GenericEvent;


require_once __DIR__ . '/../../vendor/autoload.php';


/**
 * Class Application
 *
 * @package OCA\Files_FullTextSearch_Tesseract\AppInfo
 */
class Application extends App implements IBootstrap {


	const APP_NAME = 'files_fulltextsearch_tesseract';

	/** @var TesseractService */
	private $tesseractService;

	/** @var ConfigService */
	private $configService;

	/**
	 * @param array $params
	 *
	 * @throws QueryException
	 */
	public function __construct(array $params = []) {
		parent::__construct(self::APP_NAME, $params);

		$c = $this->getContainer();
		$this->tesseractService = $c->query(TesseractService::class);
		$this->configService = $c->query(ConfigService::class);
	}


	/**
	 * @param IRegistrationContext $context
	 */
	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(GenericEvent::class, GenericListener::class);
	}


	/**
	 * @param IBootContext $context
	 */
	public function boot(IBootContext $context): void {
	}

}

