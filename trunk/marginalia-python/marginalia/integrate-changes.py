
import sys
import string
import codecs
import xml.sax
import xmlprinter
from xml.sax.handler import feature_namespaces

import pathhandlers
import odfhandlers
from pathhandlers import WordHandler, PathHandler, SaxPath
from odfhandlers import OdfHandler, HtmlRange

# Currently hardcoded with the intention to be run against the Jabberwocky test,
# but really could be run against any document with the requisite paragraphs
# (as it does't run against quotes etc).
# Format:
#  python intergrate-changes.py <content.xml
rangeStrings = [ 'p[1]/word(2)/char(0);p[1]/word(2)/char(7)' ]
ranges = [ HtmlRange( i, s ) for s, i in zip( rangeStrings, range( 0, len( rangeStrings ) ) ) ]

wrapper = codecs.lookup( 'utf-8' )[ 3 ]
parser = xml.sax.make_parser( )

pathInfo = SaxPath( )
printer = xmlprinter.xmlprinter( sys.stdout )
odfHandler = OdfHandler( pathInfo, printer, ranges )
wordHandler = WordHandler( pathInfo, odfHandler, odfhandlers.ELEMENT_TYPES )
pathHandler = PathHandler( wordHandler, pathInfo )

parser.setContentHandler( pathHandler )
parser.setFeature( feature_namespaces, 1 )
parser.parse( wrapper( sys.stdin ) )
