<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_FullTextSearch_Tesseract\Service;


use OCA\Files_FullTextSearch_Tesseract\AppInfo\Application;
use OCP\EventDispatcher\GenericEvent;
use OCP\IConfig;


/**
 * Class ConfigService
 *
 * @package OCA\Files_FullTextSearch_Tesseract\Service
 */
class ConfigService {


	const TESSERACT_ENABLED = 'tesseract_enabled';
	const TESSERACT_PSM = 'tesseract_psm';
	const TESSERACT_LANG = 'tesseract_lang';
	const TESSERACT_PDF = 'tesseract_pdf';
	const TESSERACT_PDF_LIMIT = 'tesseract_pdf_limit';
	const TESSERACT_PDF_SKIP_TEXT = 'tesseract_pdf_skip_text';

	public $defaults = [
		self::TESSERACT_ENABLED       => '0',
		self::TESSERACT_PSM           => '4',
		self::TESSERACT_LANG          => 'eng',
		self::TESSERACT_PDF           => '0',
		self::TESSERACT_PDF_LIMIT     => '0',
		self::TESSERACT_PDF_SKIP_TEXT => '0',
	];


	/** @var IConfig */
	private $config;


	/**
	 * ConfigService constructor.
	 *
	 * @param IConfig $config
	 */
	public function __construct(IConfig $config) {
		$this->config = $config;
	}


	public function onGetConfig(GenericEvent $e) {
		/** @var array $config */
		$config = $e->getArgument('config');
		$config['files_fulltextsearch_tesseract'] =
			[
				'version'       => $this->getAppValue('installed_version'),
				'enabled'       => $this->getAppValue(self::TESSERACT_ENABLED),
				'psm'           => $this->getAppValue(self::TESSERACT_PSM),
				'lang'          => $this->getAppValue(self::TESSERACT_LANG),
				'pdf'           => $this->getAppValue(self::TESSERACT_PDF),
				'pdf_limit'     => $this->getAppValue(self::TESSERACT_PDF_LIMIT),
				'pdf_skip_text' => $this->getAppValue(self::TESSERACT_PDF_SKIP_TEXT),
			];
		$e->setArgument('config', $config);
	}


	/**
	 * @return array
	 */
	public function getConfig(): array {
		$keys = array_keys($this->defaults);
		$data = [];

		foreach ($keys as $k) {
			$data[$k] = $this->getAppValue($k);
		}

		return $data;
	}


	/**
	 * @param array $save
	 */
	public function setConfig(array $save) {
		$keys = array_keys($this->defaults);

		foreach ($keys as $k) {
			if (array_key_exists($k, $save)) {
				$this->setAppValue($k, $save[$k]);
			}
		}
	}


	/**
	 * Get a value by key
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function getAppValue(string $key): string {
		$defaultValue = null;
		if (array_key_exists($key, $this->defaults)) {
			$defaultValue = $this->defaults[$key];
		}

		return $this->config->getAppValue(Application::APP_NAME, $key, $defaultValue);
	}

	/**
	 * Set a value by key
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function setAppValue(string $key, string $value) {
		$this->config->setAppValue(Application::APP_NAME, $key, $value);
	}

	/**
	 * remove a key
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function deleteAppValue(string $key): string {
		return $this->config->deleteAppValue(Application::APP_NAME, $key);
	}


	/**
	 * return if option is enabled.
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function optionIsSelected(string $key): bool {
		return ($this->getAppValue($key) === '1');
	}

	/**
	 * @return int
	 */
	public function getLogLevel(): int {
		return (int)$this->config->getSystemValue('loglevel', 1);
	}

}

