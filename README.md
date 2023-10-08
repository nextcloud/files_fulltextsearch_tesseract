# files_fulltextsearch_tesseract
OCR your documents before index

### Installation / Setup

#### Tesseract
install Tesseract

#### Tesseract Languages
In addition to Tesseract, you need to install languages packages for tesseract recognition to work.

There is two ways to install Tesseract languages :
- download and install language files, by copying them into (depending on your distribution):
  - /usr/share/tessdata/
  - OR /usr/share/tesseract-ocr/tessdata/
  - OR /usr/share/tesseract-ocr/<tesseract version number, i.e 4.00>/tessdata/
- installing tesseract language packages
  - i.e: `apt-get install tesseract-ocr-eng`

### Configuration
- configure this app in the Full text search Admin panel

### Other
- report bugs


### more

devblog about PDF and OCR: https://daita.github.io/files-fulltextsearch-tesseract-ocr-pdf/
