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
MARGINALIA_MOODLE_DIR = $(MOODLE_DIR)/blocks/marginalia
MARGINALIA_MOODLE_FILES = \
 $(MARGINALIA_MOODLE_DIR)/annotate.php \
 $(MARGINALIA_MOODLE_DIR)/annotation-styles.php \
 $(MARGINALIA_MOODLE_DIR)/annotation_summary_query.php \
 $(MARGINALIA_MOODLE_DIR)/block_marginalia.php \
 $(MARGINALIA_MOODLE_DIR)/config.php \
 $(MARGINALIA_MOODLE_DIR)/tags.css \
 $(MARGINALIA_MOODLE_DIR)/tags.js \
 $(MARGINALIA_MOODLE_DIR)/tags.php \
 $(MARGINALIA_MOODLE_DIR)/keywords.php \
 $(MARGINALIA_MOODLE_DIR)/keywords_db.php \
 $(MARGINALIA_MOODLE_DIR)/lib.php \
 $(MARGINALIA_MOODLE_DIR)/marginalia-config.js \
 $(MARGINALIA_MOODLE_DIR)/marginalia-strings.js \
 $(MARGINALIA_MOODLE_DIR)/MoodleMarginalia.js \
 $(MARGINALIA_MOODLE_DIR)/smartquote.js \
 $(MARGINALIA_MOODLE_DIR)/summary-styles.php \
 $(MARGINALIA_MOODLE_DIR)/summary.js \
 $(MARGINALIA_MOODLE_DIR)/summary.php \
 $(MARGINALIA_MOODLE_DIR)/user-preference.php
 
VERSION_FILE = $(MARGINALIA_MOODLE_DIR)/version.php

LANG_FILES = $(MARGINALIA_MOODLE_DIR)/lang/en_utf8/block_marginalia.php

HELP_FILES = \
 $(MARGINALIA_MOODLE_DIR)/lang/en_utf8/help/marginalia/annotate.html \
 $(MARGINALIA_MOODLE_DIR)/lang/en_utf8/help/marginalia/annotation_summary.html

DB_DIR = $(MARGINALIA_MOODLE_DIR)/db
DB_FILES = \
 $(DB_DIR)/access.php \
 $(DB_DIR)/install.xml \
 $(DB_DIR)/upgrade.php
 
FORUM_FILES = $(MOODLE_DIR)/mod/forum/permalink.php

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
  
release:  zipcontents docs
	cd $(OUTDIR); tar czf $(OUTNAME).tgz $(OUTNAME)

zipdir:
	mkdir -p $(ZIPDIR)/util
	mkdir -p $(ZIPDIR)/doc
	mkdir -p $(ZIPDIR)/moodle/blocks/marginalia/marginalia/3rd-party
	mkdir -p $(ZIPDIR)/moodle/blocks/marginalia/marginalia-php
	mkdir -p $(ZIPDIR)/moodle/blocks/marginalia/lang/en_utf8/help/marginalia
	mkdir -p $(ZIPDIR)/moodle/blocks/marginalia/db
	mkdir -p $(ZIPDIR)/moodle/mod/forum

zipcontents: zipdir $(ZIPDIR)/moodle19.patch $(ZIPDIR)/moodle18.patch $(UTIL_FILES) $(DOC_FILES) $(README_FILES) $(MARGINALIA_MOODLE_FILES) $(MARGINALIA_LIB_FILES) $(MARGINALIA_LIB_3RDPARTY_FILES) $(MARGINALIA_PHP_FILES) $(FORUM_FILES) $(LANG_FILES) $(HELP_FILES) $(VERSION_FILE) $(DB_FILES)
	cp $(MARGINALIA_MOODLE_FILES) $(ZIPDIR)/moodle/blocks/marginalia/
	cp $(MARGINALIA_LIB_FILES) $(ZIPDIR)/moodle/blocks/marginalia/marginalia/
	cp $(MARGINALIA_LIB_3RDPARTY_FILES) $(ZIPDIR)/moodle/blocks/marginalia/marginalia/3rd-party/
	cp $(MARGINALIA_PHP_FILES) $(ZIPDIR)/moodle/blocks/marginalia/marginalia-php/
	cp $(LANG_FILES) $(ZIPDIR)/moodle/blocks/marginalia/lang/en_utf8/
	cp $(HELP_FILES) $(ZIPDIR)/moodle/blocks/marginalia/lang/en_utf8/help/marginalia/
	cp $(DB_FILES) $(ZIPDIR)/moodle/blocks/marginalia/db/
	cp $(FORUM_FILES) $(ZIPDIR)/moodle/mod/forum/
	cp $(UTIL_FILES) $(ZIPDIR)/util/
	cp $(DOC_FILES) $(ZIPDIR)/doc/
	cp $(README_FILES) $(ZIPDIR)

$(VERSION_FILE): zipdir moodle.orig moodle18.orig moodle moodle18 $(UTIL_FILES) $(MARGINALIA_MOODLE_FILES) $(MARGINALIA_LIB_FILES) $(MARGINALIA_LIB_3RDPARTY_FILES) $(MARGINALIA_PHP_FILES) $(FORUM_FILES) $(LANG_FILES) $(HELP_FILES) $(DB_FILES)
	echo '<?php' >$@
	echo '    $$version = $(DATE)00;' >>$@

$(ZIPDIR)/moodle19.patch:  moodle.orig moodle
	mkdir -p $(ZIPDIR)
	-diff -Bbur -x .svn -x .DS_Store moodle.orig moodle >$(ZIPDIR)/moodle19.patch
	
$(ZIPDIR)/moodle18.patch:  moodle18.orig moodle18
	mkdir -p $(ZIPDIR)
	-diff -Bbur -x .svn -x .DS_Store moodle18.orig moodle18 >$(ZIPDIR)/moodle18.patch
	
%.txt: docs/%.html
	html2txt $< >$@
	
clean-zip:
	rm -r $(ZIPDIR)

