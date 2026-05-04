<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# files_fulltextsearch_tesseract

[![REUSE status](https://api.reuse.software/badge/github.com/nextcloud/files_fulltextsearch_tesseract)](https://api.reuse.software/info/github.com/nextcloud/files_fulltextsearch_tesseract)

OCR your documents before index

### Installation / Setup

- install Tesseract

- install Ghostscript

- install ImageMagick with PDF support (might be an additional package)

- download language files from: https://github.com/tesseract-ocr/tessdata

- copy language files into /usr/share/tessdata/ (or /usr/share/tesseract-ocr/tessdata/, depends on our distribution)

- configure this app in the Full text search Admin panel

- report bugs


### troubleshooting


Verify ImageMagick PDF support with `identify -list format | grep PDF`.


### more

devblog about PDF and OCR: https://daita.github.io/files-fulltextsearch-tesseract-ocr-pdf/

In nextcloud all-in-one you can enable the required packages using NEXTCLOUD_ADDITIONAL_APKS, e.g for English language and PDF support:

```
NEXTCLOUD_ADDITIONAL_APKS=imagemagick imagemagick-pdf ghostscript tesseract-ocr tesseract-ocr-data-eng
```
