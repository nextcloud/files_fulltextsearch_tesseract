/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	tesseract_pdf_skip_text: null,

	init: function () {
		fts_tesseract_elements.tesseract_div = $('#files_ocr-tesseract');
		fts_tesseract_elements.tesseract_psm = $('#tesseract_psm');
		fts_tesseract_elements.tesseract_lang = $('#tesseract_lang');
		fts_tesseract_elements.tesseract_ocr = $('#tesseract_ocr');
		fts_tesseract_elements.tesseract_pdf = $('#tesseract_pdf');
		fts_tesseract_elements.tesseract_pdf_limit = $('#tesseract_pdf_limit');
		fts_tesseract_elements.tesseract_pdf_skip_text = $('#tesseract_pdf_skip_text');

		fts_tesseract_elements.tesseract_ocr.on('change', fts_tesseract_elements.updateSettings);
		fts_tesseract_elements.tesseract_psm.on('change', fts_tesseract_elements.updateSettings);
		fts_tesseract_elements.tesseract_lang.on('change', fts_tesseract_elements.updateSettings);
		fts_tesseract_elements.tesseract_pdf.on('change', fts_tesseract_elements.updateSettings);
		fts_tesseract_elements.tesseract_pdf_limit.on('change', fts_tesseract_elements.updateSettings);
		fts_tesseract_elements.tesseract_pdf_skip_text.on('change', fts_tesseract_elements.updateSettings);
	},


	updateSettings: function () {
		fts_admin_settings.tagSettingsAsNotSaved($(this));
		fts_tesseract_settings.saveSettings();
	}


};


