#
# makefile for Marginalia demo application
#

MARGINALIA_PHP_DIR = $(shell pwd)/../marginalia-php/marginalia-php
MARGINALIA_LIB_DIR = $(shell pwd)/../marginalia-lib/marginalia
MARGINALIA_UTIL_DIR =  $(shell pwd)/../marginalia-lib/util
MARGINALIA_LOCALE_DIR = $(shell pwd)/../marginalia-lib/locale

include ../marginalia-lib/marginalia-lib.mk
include ../marginalia-php/marginalia-php.mk

DATE = $(shell date +'%Y%m%d')

OUTDIR = releases
OUTNAME = marginalia-demo-$(DATE)
ZIPDIR = $(OUTDIR)/$(OUTNAME)

MARGINALIA_DEMO_DIR = www
MARGINALIA_DEMO_FILES = \
 $(MARGINALIA_DEMO_DIR)/annotate-db.php \
 $(MARGINALIA_DEMO_DIR)/annotate.php \
 $(MARGINALIA_DEMO_DIR)/bungeni-annotate.css \
 $(MARGINALIA_DEMO_DIR)/bungeni-annotate.js \
 $(MARGINALIA_DEMO_DIR)/config-example.php \
 $(MARGINALIA_DEMO_DIR)/index.css \
 $(MARGINALIA_DEMO_DIR)/index.js \
 $(MARGINALIA_DEMO_DIR)/index.php \
 $(MARGINALIA_DEMO_DIR)/keywords.txt \
 $(MARGINALIA_DEMO_DIR)/marginalia-strings.js \
 $(MARGINALIA_DEMO_DIR)/marker-tail.png \
 $(MARGINALIA_DEMO_DIR)/static-annotate.js \
 $(MARGINALIA_DEMO_DIR)/static-prefs.js

README_FILES = \
 README.txt \
 LICENSE.txt \
 CREDITS.txt \
 INSTALL.txt

DOC_FILES = \
 docs/CREDITS.html \
 docs/README.html \
 docs/INSTALL.html

UTIL_FILES = \
 util/tables.sql
  
release:  zipdir docs
	cd $(OUTDIR); tar czf $(OUTNAME).tgz $(OUTNAME)

zipdir: $(UTIL_FILES) $(DOC_FILES) $(README_FILES) $(MARGINALIA_DEMO_FILES) $(MARGINALIA_LIB_FILES) $(MARGINALIA_LIB_3RDPARTY_FILES) $(MARGINALIA_PHP_FILES)
	mkdir -p $(ZIPDIR)/util
	mkdir -p $(ZIPDIR)/doc
	mkdir -p $(ZIPDIR)/www/marginalia/3rd-party
	mkdir -p $(ZIPDIR)/www/marginalia-php
	mkdir -p $(ZIPDIR)/www/locale/en_us
	cp $(MARGINALIA_DEMO_FILES) $(ZIPDIR)/www/
	cp $(MARGINALIA_LIB_FILES) $(ZIPDIR)/www/marginalia/
	cp $(MARGINALIA_LIB_3RDPARTY_FILES) $(ZIPDIR)/www/marginalia/3rd-party/
	cp $(MARGINALIA_PHP_FILES) $(ZIPDIR)/www/marginalia-php/
	cp $(UTIL_FILES) $(ZIPDIR)/util/
	cp $(DOC_FILES) $(ZIPDIR)/doc/
	cp $(README_FILES) $(ZIPDIR)

$(MARGINALIA_DEMO_DIR)/marginalia-strings.js: $(MARGINALIA_LOCALE_DIR)/en_US.xml
	xsltproc --param output bungeni $(MARGINALIA_UTIL_DIR)/locale2js.xsl $< >$@
	
%.txt: docs/%.html
	html2txt $< >$@
	
clean-zip:
	rm -r $(ZIPDIR)

