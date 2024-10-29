/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** global: OCA */
/** global: fts_tesseract_elements */
/** global: fts_tesseract_settings */


$(document).ready(function () {


	/**
	 * @constructs Fts_deck
	 */
	var Fts_tesseract = function () {
		$.extend(Fts_tesseract.prototype, fts_tesseract_elements);
		$.extend(Fts_tesseract.prototype, fts_tesseract_settings);

		fts_tesseract_elements.init();
		fts_tesseract_settings.refreshSettingPage();
	};

	OCA.FullTextSearchAdmin.fts_tesseract = Fts_tesseract;
	OCA.FullTextSearchAdmin.fts_tesseract.settings = new Fts_tesseract();

});
