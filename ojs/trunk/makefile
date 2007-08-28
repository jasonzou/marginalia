#
# makefile for Marginalia for Moodle
#

MARGINALIA_PHP_DIR = $(shell pwd)/../marginalia-php/marginalia-php
MARGINALIA_LIB_DIR = $(shell pwd)/../marginalia-lib/marginalia
include ../marginalia-lib/marginalia-lib.mk
include ../marginalia-php/marginalia-php.mk

DATE = $(shell date +'%Y%m%d')

OUTDIR = releases
OUTNAME = marginalia-ojs-$(DATE)
ZIPDIR = $(OUTDIR)/$(OUTNAME)

OJS_DIR = .
MARGINALIA_OJS_DIR = $(OJS_DIR)/MarginaliaPlugin
MARGINALIA_OJS_FILES = \
 $(MARGINALIA_OJS_DIR)/Annotation.inc.php \
 $(MARGINALIA_OJS_DIR)/AnnotationDAO.inc.php \
 $(MARGINALIA_OJS_DIR)/data.xml \
 $(MARGINALIA_OJS_DIR)/index.php \
 $(MARGINALIA_OJS_DIR)/Keyword.inc.php \
 $(MARGINALIA_OJS_DIR)/KeywordDAO.inc.php \
 $(MARGINALIA_OJS_DIR)/marginalia-config.js \
 $(MARGINALIA_OJS_DIR)/marginalia.css \
 $(MARGINALIA_OJS_DIR)/MarginaliaHandler.inc.php \
 $(MARGINALIA_OJS_DIR)/MarginaliaPlugin.inc.php \
 $(MARGINALIA_OJS_DIR)/MarginaliaStrings.inc.php \
 $(MARGINALIA_OJS_DIR)/ojs-annotate.js \
 $(MARGINALIA_OJS_DIR)/Preference.inc.php \
 $(MARGINALIA_OJS_DIR)/PreferenceDAO.inc.php \
 $(MARGINALIA_OJS_DIR)/schema.xml
 
LANG_FILES = $(MARGINALIA_OJS_DIR)/locale/en_us/locale.xml

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
 util/keywords.pl \
 util/keywords.sql \
 util/keywords.txt \
 util/tables.sql \
  
release:  zipdir docs
	cd $(OUTDIR); tar czf $(OUTNAME).tgz $(OUTNAME)

zipdir: $(UTIL_FILES) $(DOC_FILES) $(README_FILES) $(MARGINALIA_OJS_FILES) $(MARGINALIA_LIB_FILES) $(MARGINALIA_LIB_3RDPARTY_FILES) $(MARGINALIA_PHP_FILES)
	mkdir -p $(ZIPDIR)/util
	mkdir -p $(ZIPDIR)/doc
	mkdir -p $(ZIPDIR)/MarginaliaPlugin/marginalia/3rd-party
	mkdir -p $(ZIPDIR)/MarginaliaPlugin/marginalia-php
	mkdir -p $(ZIPDIR)/MarginaliaPlugin/locale/en_us
	cp $(MARGINALIA_OJS_FILES) $(ZIPDIR)/MarginaliaPlugin/
	cp $(MARGINALIA_LIB_FILES) $(ZIPDIR)/MarginaliaPlugin/marginalia/
	cp $(MARGINALIA_LIB_3RDPARTY_FILES) $(ZIPDIR)/MarginaliaPlugin/marginalia/3rd-party/
	cp $(MARGINALIA_PHP_FILES) $(ZIPDIR)/MarginaliaPlugin/marginalia-php/
	cp $(LANG_FILES) $(ZIPDIR)/MarginaliaPlugin/locale/en_us/
	cp $(UTIL_FILES) $(ZIPDIR)/util/
	cp $(DOC_FILES) $(ZIPDIR)/doc/
	cp $(README_FILES) $(ZIPDIR)

%.txt: docs/%.html
	html2txt $< >$@
	
clean-zip:
	rm -r $(ZIPDIR)

