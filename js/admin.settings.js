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
			tesseract_pdf_limit: fts_tesseract_elements.tesseract_pdf_limit.val()
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
