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


use OCA\Files_FullTextSearch_Tesseract\AppInfo\Application;
use OCP\Util;


Util::addScript(Application::APP_NAME, 'admin.elements');
Util::addScript(Application::APP_NAME, 'admin.settings');
Util::addScript(Application::APP_NAME, 'admin');

?>

<div id="files_ocr-tesseract" class="section">
	<h2><?php p($l->t('Files - Tesseract OCR')) ?></h2>

	<div class="div-table">
		<div class="div-table-row">
			<div class="div-table-col div-table-col-left">
				<span class="leftcol">Enable OCR:</span>
				<br/>
				<em>OCR your document with <i>Tesseract</i>.</em>
			</div>
			<div class="div-table-col">
				<input type="checkbox" id="tesseract_ocr" value="1"/>
			</div>
		</div>

		<div class="div-table-row tesseract_ocr_enabled">
			<div class="div-table-col div-table-col-left">
				<span class="leftcol">Page Segmentation Method</span>
				<br/>
				<em><a href="https://github.com/tesseract-ocr/tesseract/wiki/ImproveQuality#page-segmentation-method">link
						to Tesseract documentation</a></em>
			</div>
			<div class="div-table-col">
				<input type="text" class="small" id="tesseract_psm" value=""/>
			</div>
		</div>

		<div class="div-table-row tesseract_ocr_enabled">
			<div class="div-table-col div-table-col-left">
				<span class="leftcol">Languages</span>
				<br/>
				<em>list of installed language, separated by <b>,</b> (comma)</em>
			</div>
			<div class="div-table-col">
				<input type="text" class="big" id="tesseract_lang" value=""/>
			</div>
		</div>

		<div class="div-table-row tesseract_ocr_enabled">
			<div class="div-table-col div-table-col-left">
				<span class="leftcol">PDF</span>
				<br/>
				<em>enable the OCR of PDF (heavy on resource)</em>
			</div>
			<div class="div-table-col">
				<input type="checkbox" id="tesseract_pdf" value="1"/>
			</div>
		</div>

		<div class="div-table-row tesseract_ocr_enabled">
			<div class="div-table-col div-table-col-left">
				<span class="leftcol">Limit PDF pages</span>
				<br/>
				<em>limit the OCR of PDF to the first <i>n</i> pages</em>
			</div>
			<div class="div-table-col">
				<input type="text" class="big" id="tesseract_pdf_limit" value=""/>
			</div>
		</div>

	</div>


</div>
