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

	public $defaults = [
		self::TESSERACT_ENABLED   => '0',
		self::TESSERACT_PSM       => '4',
		self::TESSERACT_LANG      => 'eng',
		self::TESSERACT_PDF       => '0',
		self::TESSERACT_PDF_LIMIT => '0'
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
				'version'   => $this->getAppValue('installed_version'),
				'enabled'   => $this->getAppValue(self::TESSERACT_ENABLED),
				'psm'       => $this->getAppValue(self::TESSERACT_PSM),
				'lang'      => $this->getAppValue(self::TESSERACT_LANG),
				'pdf'       => $this->getAppValue(self::TESSERACT_PDF),
				'pdf_limit' => $this->getAppValue(self::TESSERACT_PDF_LIMIT),
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

