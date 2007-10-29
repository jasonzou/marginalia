#
# makefile for Marginalia for Moodle
#

MARGINALIA_PHP_DIR = $(shell pwd)/../marginalia-php/marginalia-php
MARGINALIA_LIB_DIR = $(shell pwd)/../marginalia-lib/marginalia
include ../marginalia-lib/marginalia-lib.mk
include ../marginalia-php/marginalia-php.mk

DATE = $(shell date +'%Y%m%d')

OUTDIR = releases
OUTNAME = marginalia-moodle-$(DATE)
ZIPDIR = $(OUTDIR)/$(OUTNAME)

MOODLE_DIR = moodle
MARGINALIA_MOODLE_DIR = $(MOODLE_DIR)/annotation
MARGINALIA_MOODLE_FILES = \
 $(MARGINALIA_MOODLE_DIR)/annotate.php \
 $(MARGINALIA_MOODLE_DIR)/annotation-styles.php \
 $(MARGINALIA_MOODLE_DIR)/AnnotationGlobals.php \
 $(MARGINALIA_MOODLE_DIR)/AnnotationSummaryQuery.php \
 $(MARGINALIA_MOODLE_DIR)/config.php \
 $(MARGINALIA_MOODLE_DIR)/edit-keywords.css \
 $(MARGINALIA_MOODLE_DIR)/edit-keywords.js \
 $(MARGINALIA_MOODLE_DIR)/edit-keywords.php \
 $(MARGINALIA_MOODLE_DIR)/keywords.php \
 $(MARGINALIA_MOODLE_DIR)/KeywordsDB.php \
 $(MARGINALIA_MOODLE_DIR)/lib.php \
 $(MARGINALIA_MOODLE_DIR)/marginalia-config.js \
 $(MARGINALIA_MOODLE_DIR)/marginalia-strings.js \
 $(MARGINALIA_MOODLE_DIR)/MoodleMarginalia.js \
 $(MARGINALIA_MOODLE_DIR)/summary-styles.php \
 $(MARGINALIA_MOODLE_DIR)/summary.js \
 $(MARGINALIA_MOODLE_DIR)/summary.php \
 $(MARGINALIA_MOODLE_DIR)/user-preference.php
 
LANG_FILES = $(MOODLE_DIR)/lang/en_utf8/annotation.php

HELP_FILES = $(MOODLE_DIR)/lang/en_utf8/help/forum/annotate.html

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
 util/create-db.php \
 util/tables.sql \
  
release:  zipdir docs
	cd $(OUTDIR); tar czf $(OUTNAME).tgz $(OUTNAME)

zipdir: $(TMP_DIR)/moodle.patch $(UTIL_FILES) $(DOC_FILES) $(README_FILES) $(MARGINALIA_MOODLE_FILES) $(MARGINALIA_LIB_FILES) $(MARGINALIA_LIB_3RDPARTY_FILES) $(MARGINALIA_PHP_FILES)
	mkdir -p $(ZIPDIR)/util
	mkdir -p $(ZIPDIR)/doc
	mkdir -p $(ZIPDIR)/moodle/annotation/marginalia/3rd-party
	mkdir -p $(ZIPDIR)/moodle/annotation/marginalia-php
	mkdir -p $(ZIPDIR)/moodle/lang/en_utf8/help
	cp $(MARGINALIA_MOODLE_FILES) $(ZIPDIR)/moodle/annotation/
	cp $(MARGINALIA_LIB_FILES) $(ZIPDIR)/moodle/annotation/marginalia/
	cp $(MARGINALIA_LIB_3RDPARTY_FILES) $(ZIPDIR)/moodle/annotation/marginalia/3rd-party/
	cp $(MARGINALIA_PHP_FILES) $(ZIPDIR)/moodle/annotation/marginalia-php/
	cp $(LANG_FILES) $(ZIPDIR)/moodle/lang/en_utf8/
	cp $(HELP_FILES) $(ZIPDIR)/moodle/lang/en_utf8/help/
	cp $(UTIL_FILES) $(ZIPDIR)/util/
	cp $(DOC_FILES) $(ZIPDIR)/doc/
	cp $(README_FILES) $(ZIPDIR)

$(TMP_DIR)/moodle.patch:  moodle.orig moodle
	mkdir -p $(ZIPDIR)
	-diff -Bbur -x .svn -x .DS_Store moodle.orig moodle >$(ZIPDIR)/moodle.patch
	
%.txt: docs/%.html
	html2txt $< >$@
	
clean-zip:
	rm -r $(ZIPDIR)

