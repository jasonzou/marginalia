#
# makefile for Marginalia for Moodle
#

MARGINALIA_PHP_DIR = $(shell pwd)/../marginalia-php/marginalia-php
MARGINALIA_LIB_DIR = $(shell pwd)/../marginalia-lib/marginalia
include ../marginalia-lib/marginalia-lib.mk
include ../marginalia-php/marginalia-php.mk

TMP_DIR = tmp

DATE = $(shell date +'%Y%m%d')

OUTDIR = releases
OUTNAME = marginalia-moodle-$(DATE)
ZIPDIR = $(OUTDIR)/$(OUTNAME)

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

zipdir: $(TMP_DIR)/moodle.patch $(UTIL_FILES) $(DOC_FILES) $(README_FILES)
	mkdir -p $(ZIPDIR)/util
	mkdir -p $(ZIPDIR)/doc
	mkdir -p $(ZIPDIR)/marginalia
	mkdir -p $(ZIPDIR)/marginalia-php
	cp $(TMP_DIR)/moodle.patch $(ZIPDIR)
	cp $(MARGINALIA_LIB_FILES) $(ZIPDIR)/marginalia/
	cp $(MARGINALIA_PHP_FILES) $(ZIPDIR)/marginalia-php/
	cp $(UTIL_FILES) $(ZIPDIR)/util/
	cp $(DOC_FILES) $(ZIPDIR)/doc/
	cp $(README_FILES) $(ZIPDIR)

$(TMP_DIR)/moodle.patch:  moodle.orig moodle
diff -BburP -x .svn -x .DS_Store moodle.orig moodle $(ZIPDIR)/moodle.patch
	
%.txt: docs/%.html
	html2txt $< >$@
	
clean-zip:
	rm -r $(ZIPDIR)

