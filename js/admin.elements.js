/*
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

/** global: OCA */
/** global: fts_admin_settings */
/** global: fts_tesseract_settings */



var fts_tesseract_elements = {
	tesseract_div: null,
	tesseract_ocr: null,
	tesseract_psm: null,
	tesseract_lang: null,
	tesseract_pdf: null,
	tesseract_pdf_limit: null,

	init: function () {
		fts_tesseract_elements.tesseract_div = $('#files_ocr-tesseract');
		fts_tesseract_elements.tesseract_psm = $('#tesseract_psm');
		fts_tesseract_elements.tesseract_lang = $('#tesseract_lang');
		fts_tesseract_elements.tesseract_ocr = $('#tesseract_ocr');
		fts_tesseract_elements.tesseract_pdf = $('#tesseract_pdf');
		fts_tesseract_elements.tesseract_pdf_limit = $('#tesseract_pdf_limit');

		fts_tesseract_elements.tesseract_ocr.on('change', fts_tesseract_elements.updateSettings);
		fts_tesseract_elements.tesseract_psm.on('change', fts_tesseract_elements.updateSettings);
		fts_tesseract_elements.tesseract_lang.on('change', fts_tesseract_elements.updateSettings);
		fts_tesseract_elements.tesseract_pdf.on('change', fts_tesseract_elements.updateSettings);
		fts_tesseract_elements.tesseract_pdf_limit.on('change', fts_tesseract_elements.updateSettings);
	},


	updateSettings: function () {
		fts_admin_settings.tagSettingsAsNotSaved($(this));
		fts_tesseract_settings.saveSettings();
	}


};


