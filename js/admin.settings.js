/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** global: OC */
/** global: fts_tesseract_elements */
/** global: fts_admin_settings */



var fts_tesseract_settings = {

	config: null,

	refreshSettingPage: function () {

		$.ajax({
			method: 'GET',
			url: OC.generateUrl('/apps/files_fulltextsearch_tesseract/admin/settings')
		}).done(function (res) {
			fts_tesseract_settings.updateSettingPage(res);
		});

	},


	updateSettingPage: function (result) {
		fts_tesseract_elements.tesseract_ocr.prop('checked', (result.tesseract_enabled === '1'));
		fts_tesseract_elements.tesseract_psm.val(result.tesseract_psm);
		fts_tesseract_elements.tesseract_lang.val(result.tesseract_lang);
		fts_tesseract_elements.tesseract_pdf.prop('checked', (result.tesseract_pdf === '1'));
		fts_tesseract_elements.tesseract_pdf_limit.val(result.tesseract_pdf_limit);
		fts_tesseract_elements.tesseract_pdf_skip_text.prop('checked', (result.tesseract_pdf_skip_text === '1'));

		fts_admin_settings.tagSettingsAsSaved(fts_tesseract_elements.tesseract_div);

		if (result.tesseract_enabled === '1') {
			fts_tesseract_elements.tesseract_div.find('.tesseract_ocr_enabled').fadeTo(300, 1);
			fts_tesseract_elements.tesseract_div.find('.tesseract_ocr_enabled').find('*').prop(
				'disabled', false);
		} else {
			fts_tesseract_elements.tesseract_div.find('.tesseract_ocr_enabled').fadeTo(300, 0.6);
			fts_tesseract_elements.tesseract_div.find('.tesseract_ocr_enabled').find('*').prop(
				'disabled', true);
		}
	},


	saveSettings: function () {

		var data = {
			tesseract_enabled: (fts_tesseract_elements.tesseract_ocr.is(':checked')) ? 1 : 0,
			tesseract_psm: fts_tesseract_elements.tesseract_psm.val(),
			tesseract_lang: fts_tesseract_elements.tesseract_lang.val(),
			tesseract_pdf: (fts_tesseract_elements.tesseract_pdf.is(':checked')) ? 1 : 0,
			tesseract_pdf_limit: fts_tesseract_elements.tesseract_pdf_limit.val(),
			tesseract_pdf_skip_text: (fts_tesseract_elements.tesseract_pdf_skip_text.is(':checked')) ? 1 : 0
		};

		$.ajax({
			method: 'POST',
			url: OC.generateUrl('/apps/files_fulltextsearch_tesseract/admin/settings'),
			data: {
				data: data
			}
		}).done(function (res) {
			fts_tesseract_settings.updateSettingPage(res);
		});
	}


};
