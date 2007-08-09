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

MARGINALIA_MOODLE_DIR = moodle/annotation
MARGINALIA_MOODLE_FILES = \
 $(MARGINALIA_MOODLE_DIR)/annotate.php \
 $(MARGINALIA_MOODLE_DIR)/annotation-styles.php \
 $(MARGINALIA_MOODLE_DIR)/AnnotationGlobals.php \
 $(MARGINALIA_MOODLE_DIR)/AnnotationSummaryQuery.php \
 $(MARGINALIA_MOODLE_DIR)/lib.php \
 $(MARGINALIA_MOODLE_DIR)/marginalia-config.js \
 $(MARGINALIA_MOODLE_DIR)/marginalia-strings.js \
 $(MARGINALIA_MOODLE_DIR)/MoodleMarginalia.js \
 $(MARGINALIA_MOODLE_DIR)/summary-styles.php \
 $(MARGINALIA_MOODLE_DIR)/summary.js \
 $(MARGINALIA_MOODLE_DIR)/summary.php \
 $(MARGINALIA_MOODLE_DIR)/user-preference.php
 
README_FILES = \
 README.txt \
 LICENSE.txt \
 CREDITS.txt

DOC_FILES = \
 docs/CREDITS.html \
 docs/README.html

UTIL_FILES = \
 util/create-db.php \
 util/tables.sql \
  
release:  zipdir docs
	cd $(OUTDIR); tar czf $(OUTNAME).tgz $(OUTNAME)

zipdir: $(TMP_DIR)/moodle.patch $(UTIL_FILES) $(DOC_FILES) $(README_FILES) $(MARGINALIA_LIB_FILES) $(MARGINALIA_LIB_3RDPARTY_FILES) $(MARGINALIA_PHP_FILES)
	mkdir -p $(ZIPDIR)/util
	mkdir -p $(ZIPDIR)/doc
	mkdir -p $(ZIPDIR)/annotation/marginalia/3rd-party
	mkdir -p $(ZIPDIR)/annotation/marginalia-php
	cp $(MARGINALIA_MOODLE_FILES) $(ZIPDIR)/annotation/
	cp $(MARGINALIA_LIB_FILES) $(ZIPDIR)/annotation/marginalia/
	cp $(MARGINALIA_LIB_3RDPARTY_FILES) $(ZIPDIR)/annotation/marginalia/3rd-party/
	cp $(MARGINALIA_PHP_FILES) $(ZIPDIR)/annotation/marginalia-php/
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

