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
 $(MARGINALIA_MOODLE_DIR)/annotation_globals.php \
 $(MARGINALIA_MOODLE_DIR)/annotation_summary_query.php \
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
 $(MARGINALIA_MOODLE_DIR)/user-preference.php \
 $(MARGINALIA_MOODLE_DIR)/version.txt 
 
LANG_FILES = $(MOODLE_DIR)/local/lang/en_utf8/annotation.php

HELP_FILES = $(MOODLE_DIR)/local/lang/en_utf8/help/forum/annotate.html

FORUM_FILES = $(MOODLE_DIR)/mod/forum/permalink.php

MOODLEROOT_FILES = $(MOODLE_DIR)/help.php

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

zipdir: $(ZIPDIR)/moodle19.patch $(ZIPDIR)/moodle18.patch $(UTIL_FILES) $(DOC_FILES) $(README_FILES) $(MARGINALIA_MOODLE_FILES) $(MARGINALIA_LIB_FILES) $(MARGINALIA_LIB_3RDPARTY_FILES) $(MARGINALIA_PHP_FILES) $(FORUM_FILES) $(LANG_FILES) $(HELP_FILES) $(MOODLEROOT_FILES)
	mkdir -p $(ZIPDIR)/util
	mkdir -p $(ZIPDIR)/doc
	mkdir -p $(ZIPDIR)/moodle/annotation/marginalia/3rd-party
	mkdir -p $(ZIPDIR)/moodle/annotation/marginalia-php
	mkdir -p $(ZIPDIR)/moodle/local/lang/en_utf8/help
	mkdir -p $(ZIPDIR)/moodle/mod/forum
	cp $(MARGINALIA_MOODLE_FILES) $(ZIPDIR)/moodle/annotation/
	cp $(MARGINALIA_LIB_FILES) $(ZIPDIR)/moodle/annotation/marginalia/
	cp $(MARGINALIA_LIB_3RDPARTY_FILES) $(ZIPDIR)/moodle/annotation/marginalia/3rd-party/
	cp $(MARGINALIA_PHP_FILES) $(ZIPDIR)/moodle/annotation/marginalia-php/
	cp $(MOODLEROOT_FILES) $(ZIPDIR)/moodle/
	cp $(LANG_FILES) $(ZIPDIR)/moodle/local/lang/en_utf8/
	cp $(HELP_FILES) $(ZIPDIR)/moodle/local/lang/en_utf8/help
	cp $(FORUM_FILES) $(ZIPDIR)/moodle/mod/forum/
	cp $(UTIL_FILES) $(ZIPDIR)/util/
	cp $(DOC_FILES) $(ZIPDIR)/doc/
	cp $(README_FILES) $(ZIPDIR)

$(MARGINALIA_MOODLE_DIR)/version.txt:
	echo $(OUTNAME) >$@

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

