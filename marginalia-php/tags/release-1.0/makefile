#
# makefile for marginalia-php
#

MARGINALIA_PHP_DIR = $(shell pwd)/marginalia-php
include marginalia-php.mk

DATE = $(shell date +'%Y%m%d')

OUTDIR = /Users/geof/Work/Marginalia/Releases
OUTNAME = marginalia-php-$(DATE)
ZIPDIR = $(OUTDIR)/$(OUTNAME)

README_FILES = \
 README.txt \
 LICENSE.txt \
 CREDITS.txt

DOC_FILES = \
 docs/CREDITS.html \
 docs/README.html

release:  zipdir docs
	cd $(OUTDIR); tar czf $(OUTNAME).tgz $(OUTNAME)

zipdir: $(MARGINALIA_FILES) $(DOC_FILES) $(README_FILES)
	mkdir -p $(ZIPDIR)/marginalia
	mkdir -p $(ZIPDIR)/util
	mkdir -p $(ZIPDIR)/doc
	cp $(MARGINALIA_FILES) $(ZIPDIR)/marginalia
	cp $(DOC_FILES) $(ZIPDIR)/doc
	cp $(README_FILES) $(ZIPDIR)
	
%.txt: docs/%.html
	html2txt $< >$@
	
clean-zip:
	rm -r $(ZIPDIR)

