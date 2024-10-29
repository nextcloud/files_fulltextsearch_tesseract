<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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

