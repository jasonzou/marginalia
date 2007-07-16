
import re
from pathhandlers import *

OFFICE_NS = "urn:oasis:names:tc:opendocument:xmlns:office:1.0"
TEXT_NS = "urn:oasis:names:tc:opendocument:xmlns:text:1.0"

ELEMENT_TYPES = {
	( TEXT_NS, 'p' ) : BREAKING_ELEMENT,
	( TEXT_NS, 'section' ) : BREAKING_ELEMENT,
	( OFFICE_NS, 'body' ) : BREAKING_ELEMENT,
	( TEXT_NS, 'list' ) : BREAKING_ELEMENT,
	( TEXT_NS, 'list-item' ) : BREAKING_ELEMENT
}


class OdfHandler:
	def __init__( self, pathInfo, printer, ranges ):
		self.pathInfo = pathInfo
		self.ranges = ranges
		self.printer = printer
		for r in ranges:
			r.setPrinter( printer )
		
	def getPathInfo( self ):
		return self.pathInfo
	
	def startDocument( self ):
		self.printer.startDocument( )
		
	def endDocument( self ):
		self.printer.endDocument( )
		
	def startPrefixMapping( self, prefix, namespace ):
		self.printer.startPrefixMapping( prefix, namespace )
			
	def endPrefixMapping( self, prefix ):
		self.printer.endPrefixMapping( prefix )
			
	def notationDecl( self, a, b, c ):
		self.printer.notationDecl( a, b, c )
		
	def startElementNS( self, name, qname, attrs, elementType ):
		self.printer.startElementNS( name, qname, attrs )
		for r in self.ranges:
			r.startElementNS( self.pathInfo, name, qname, attrs, elementType )
			
	def endElementNS( self, name, qname, elementType ):
		for r in self.ranges:
			r.endElementNS( self.pathInfo, name, qname, elementType )
		self.printer.endElementNS( name, qname )
	
	def ignoreCharacters( self, chars ):
		self.printer.characters( chars )
		
	def startWord( self, wordNum ):
		for r in self.ranges:
			r.startWord( self.pathInfo, wordNum )
			
	def endWord( self, wordNum, word ):
		for r in self.ranges:
			r.endWord( self.pathInfo, wordNum, word )
			
	def char( self, c, charNum ):
		self.printer.characters( c )
		for r in self.ranges:
			r.char( self.pathInfo, c, charNum )
		
		
class HtmlRange:
	def __init__( self, id, rangeStr ):
		self.id = id
		parts = rangeStr.split( ';' )
		self.start = HtmlPoint( parts[ 0 ] )
		self.end = HtmlPoint( parts[ 1 ] )
		
	def setPrinter( self, printer ):
		self.printer = printer
		
	def startElementNS( self, pathInfo, name, qname, attrs, elementType ):
		self.start.startElementNS( pathInfo, name, qname, attrs, elementType )
		self.end.startElementNS( pathInfo, name, qname, attrs, elementType )
		
	def endElementNS( self, pathInfo, name, qname, elementType ):
		self.start.endElementNS( pathInfo, name, qname, elementType )
		self.end.endElementNS( pathInfo, name, qname, elementType )
		
	def startWord( self, pathInfo, wordNum ):
		if self.start.startWord( pathInfo, wordNum ):
			self.__markChangeStart( )
		if self.end.startWord( pathInfo, wordNum ):
			self.__markChangeEnd( )
		
	def endWord( self, pathInfo, wordNum, word ):
		self.start.endWord( pathInfo, wordNum, word )
		self.end.endWord( pathInfo, wordNum, word )
		
	def char( self, pathInfo, c, charNum ):
		if self.start.char( pathInfo, c, charNum ):
			self.__markChangeStart( )
		if self.end.char( pathInfo, c, charNum ):
			self.__markChangeEnd( )
			
	def __markChangeStart( self ):
		print >>sys.stderr, 'Mark Start'
		name = ( TEXT_NS, 'change-start' )
		attrs = { ( TEXT_NS, 'change-id' ) : 'change-' + str( self.id ) }
		self.printer.startElementNS( name, None, attrs )
		self.printer.endElementNS( name, None )
		
	def __markChangeEnd( self ):
		print >>sys.stderr, 'Mark End'
		name = ( TEXT_NS, 'change-end' )
		attrs = { ( TEXT_NS, 'change-id' ) : 'change-' + str( self.id ) }
		self.printer.startElementNS( name, None, attrs )
		self.printer.endElementNS( name, None )
		
		
class HtmlPoint:
	""" An HTML point isn't just a description of a point, it actively tries to
	find that point in the document """
	
	WHITESPACE_RE = re.compile( r'\s+' )
	OFFSET_RE = re.compile( r'^(.*)/word\((\d+)\)/char\((\d+)\)' )
	XPATHID_RE = re.compile( r'^\.//\*\s*\[\s*@id\s*=\s*\'([a-zA-Z][a-zA-Z0-9_-]*)\'\s*\]/?(.*)$' )
	SIBLING_RE = re.compile( r'^following-sibling::([a-zA-Z0-9:_-]+)\s*\[\s*(\d+)\s*\]' )
	ELEMENT_RE = re.compile( r'^([a-zA-Z0-9:_-]+)\s*\[\s*(\d+)\s*\]' )
	BODY = [ ( OFFICE_NS, 'document-content', 1 ), ( OFFICE_NS, 'body', 1 ) ]
	TAG_MAPPINGS = {
		'p' : [ ( TEXT_NS, 'p' ) ],
		'div' : [ ( TEXT_NS, 'section' ) ],
		'ul' : [ ( TEXT_NS, 'list' ) ],
		'li' : [ ( TEXT_NS, 'list-item' ) ]
	}
	STATE_INIT = 'init'
	STATE_INROOT = 'in-root'
	STATE_INREL = 'in-rel'
	STATE_INWORD = 'in-word'
	STATE_DONE = 'done'
	STATE_FAILED = 'failed'
	
	def __init__( self, pointStr ):
		self.state = self.STATE_INIT
		self.saxDepth = 0		# depth of sax parser (in breaking elements)
		self.matchDepth = 0		# depth of matches (between path and actual sax nodes)
		self.matchCount = 0		# number of matches at matchDepth
		self.curWord = 0
		self.curChar = 0
		self.quote = ''
		self.fromString( pointStr )
		
	def fromString( self, pointStr ):
		matches = self.OFFSET_RE.match( pointStr )
		if matches:
			xpath = matches.group(1)
			self.words = int( matches.group(2) )
			self.chars = int( matches.group(3) )
		else:
			xpath, self.words, self.chars = pointStr, None, None
		self.path = [ ]
		
		matches = self.XPATHID_RE.match( xpath )
		if matches:
			self.rootId, xpath = matches.groups( )
			self.followingId = xpath.startswith( 'following-sibling::' )
			if self.followingId:
				xpath = xpath[ len( 'following-sibling::' ) : ]
		else:
			self.rootId = self.followingId = None
		
		parts = xpath.split( '/' )
		for part in parts:
			matches = self.ELEMENT_RE.match( part )
			if not matches:
				raise 'Bad HTML Path'
			# Append ( tagname, childindex )
			self.path.append( ( matches.group( 1 ), int( matches.group( 2 ) ) ) )

	def startElementNS( self, pathInfo, name, qname, attrs, elementType ):
		if BREAKING_ELEMENT == elementType:
			# Have we found the root element (id or first breaking element)
			if self.STATE_INIT == self.state:
				if self.rootId:
					if attrs.has_key( ( TEXT_NS, 'name' ) ) and attrs[ ( TEXT_NS, 'name' ) ] == self.rootId :
						self.state = self.STATE_INROOT
				elif self.BODY == pathInfo.getPath( ):
					self.state = self.STATE_INROOT
				if self.STATE_INROOT == self.state:
					self.saxDepth = 0
					
			# Look for the rel element
			elif self.STATE_INROOT == self.state:
				if self.saxDepth == self.matchDepth:
						
					# What are we looking for, and how many of them?
					needName, needCount = self.path[ self.matchDepth ]
					
					# This node is of the type we need
					if name in self.TAG_MAPPINGS[ needName ]:
						
						# Well great, then count it!
						self.matchCount += 1
						
						# Are we done at this depth?
						if self.matchCount == needCount:
							
							self.matchDepth += 1
							
							# Are we all done?
							if self.matchDepth == len( self.path ):
								self.state = self.STATE_INREL
								self.curWord = 0
							else:
								self.matchCount = 0
				self.saxDepth += 1
		return False
		
		# Otherwise we don't care, because the caller will take care of word counting
		
	def endElementNS( self, pathInfo, name, qname, elementType ):
		if BREAKING_ELEMENT == elementType and self.state == self.STATE_INROOT:
			# may need to truncate matches
			self.saxDepth -= 1
			# didn't find a match where it was expected, so never will
			if self.saxDepth < self.matchDepth:
				self.state = self.STATE_FAIL
		return False
		
	def startWord( self, pathInfo, wordNum ):
		if self.STATE_INREL == self.state:
			self.curWord += 1
			if self.curWord == self.words:
				self.state = self.STATE_INWORD
				self.curChar = 0
				if self.curChar == self.chars:
					self.state = self.STATE_DONE
					return True
		return False
		
	def endWord( self, pathInfo, wordNum, word ):
		if self.STATE_INWORD == self.state:
			self.quote = ' '.join( [ self.quote, word ] )
		return False
		
	def char( self, pathInfo, c, charNum ):
		if self.STATE_INWORD == self.state:
			self.curChar += 1
			if self.curChar == self.chars:
				self.state = self.STATE_DONE
				return True
		return False

#
# Unit tests
# Only include tests if test modules are available
#
try:
	import unittest
	from pymock import *

	class TestHtmlPoint( unittest.TestCase ):
		samplePaths = (
			(	'p[1]/word(2)/char(0)',
				'p[1]/word(2)/char(3)',
				'office:document-content[1]/office:body[1]/office:text[1]/text:p[1]',
				'office:document-content[1]/office:body[1]/office:text[1]/text:p[1]',
				'bri' ),
			(	".//*[@id='section1']/p[1]/word(4)/char(3)",
				".//*[@id='section1']/p[1]/word(4)/char(8)",
				"office:document-content[1]/office:body[1]/office:text[1]/text:section[1]/text:p[1]",
				"office:document-content[1]/office:body[1]/office:text[1]/text:section[1]/text:p[1]",
				"fish" ),
			(	".//*[@id='section1']/following-sibling::p[1]/word(2)/char(0)",
				".//*[@id='section1']/following-sibling::p[1]/word(4)/char(4)",
				"office:document-content[1]/office:body[1]/office:text[1]/text:p[3]",
				"office:document-content[1]/office:body[1]/office:text[1]/text:p[3]",
				"to my arms" ),
			(	"p[2]/word(2)/char(0)",
				"p[2]/word(2)/char(3)"
				'office:document-content[1]/office:body[1]/office:text[1]/text:p[2]',
				'office:document-content[1]/office:body[1]/office:text[1]/text:p[2]',
				'gyr' ),
			(	"ul[2]/li[2]/word(1)/char(1)",
				"ul[2]/li[2]/word(1)/char(4)",
				"office:document-content[1]/office:body[1]/office:text[1]/text:list[2]/text:list-item[2]",
				"office:document-content[1]/office:body[1]/office:text[1]/text:list[2]/text:list-item[2]",
				"rum" )
		)
		
		P_NAME = ( TEXT_NS, 'p' )
		SECTION_NAME = ( TEXT_NS, 'section' )
		LIST_NAME = ( TEXT_NS, 'list' )
		LISTITEM_NAME = ( TEXT_NS, 'list-item' )
		
		def setUp( self ):
			self.controller = Controller( )
			self.point1 = HtmlPoint( self.samplePaths[0][0] )
			self.point2 = HtmlPoint( self.samplePaths[1][0] )
			self.point3 = HtmlPoint( self.samplePaths[2][0] )
			self.point4 = HtmlPoint( self.samplePaths[3][0] )
			self.point5 = HtmlPoint( self.samplePaths[4][0] )
			self.pathInfo = SaxPath( )
			self.pathInfo.startPrefixMapping( 'office', OFFICE_NS )
			self.pathInfo.startElementNS( ( OFFICE_NS, 'document-content' ), 'office:document-content' )
			self.pathInfo.startElementNS( ( OFFICE_NS, 'body' ), 'office:body' )
			self.handler = self.controller.mock( )
			self.wordHandler = WordHandler( self.pathInfo, self.handler, ELEMENT_TYPES )
			
		def testSimplePoint( self ):
			self.assertFalse( self.point1.rootId )
			self.assertTrue( self.point1.path == [ ( 'p', 1 ) ] )
			self.assertTrue( self.point1.words == 2 )
			self.assertTrue( self.point1.chars == 0 )
			
		def testIdPoint( self ):
			self.assertTrue( self.point2.rootId == 'section1' )
			self.assertFalse( self.point2.followingId )
			self.assertTrue( self.point2.path == [ ( 'p', 1 ) ] )
			self.assertTrue( self.point2.words == 4 )
			self.assertTrue( self.point2.chars == 3 )
			
		def testSiblingIdPoint( self ):
			self.assertTrue( self.point3.rootId == 'section1' )
			self.assertTrue( self.point3.followingId )
			self.assertTrue( self.point3.path == [ ( 'p', 1 ) ] )
			self.assertTrue( self.point3.words == 2 )
			self.assertTrue( self.point3.chars == 0 )
			
		def testSimpleRoot( self ):
			self.point1.startElementNS( self.wordHandler, ( OFFICE_NS, 'body' ), 'office:body', { }, BREAKING_ELEMENT )
			self.assertTrue( self.point1.state == self.point1.STATE_INROOT )
			
		def testIdRoot( self ):
			self.point2.startElementNS( self.wordHandler, ( OFFICE_NS, 'body' ), 'office:body', { }, BREAKING_ELEMENT )
			self.assertFalse( self.point2.state == self.point2.STATE_INROOT )
			self.point2.startElementNS( self.wordHandler, ( TEXT_NS, 'section' ), 'text:section', { ( TEXT_NS, 'name' ) : 'section1' }, BREAKING_ELEMENT )
			
		def testRel1( self ):
			point = self.point1
			point.startElementNS( self.wordHandler, ( OFFICE_NS, 'body' ), 'office:body', { }, BREAKING_ELEMENT )
			point.startElementNS( self.wordHandler, self.P_NAME, 'text:p', { }, BREAKING_ELEMENT )
			self.assertTrue( point.state == point.STATE_INREL )
			
		def testRel2( self ):
			point = self.point4
			point.startElementNS( self.wordHandler, ( OFFICE_NS, 'body' ), 'office:body', { }, BREAKING_ELEMENT )
			point.startElementNS( self.wordHandler, self.P_NAME, 'text:p', { }, BREAKING_ELEMENT )
			self.assertTrue( point.state == point.STATE_INROOT )
			point.endElementNS( self.wordHandler, self.P_NAME, 'text:p', BREAKING_ELEMENT )
			point.startElementNS( self.wordHandler, ( TEXT_NS, 'p' ), 'text:p', { }, BREAKING_ELEMENT )
			self.assertTrue( point.state == point.STATE_INREL )
			
		def testNestedRel( self ):
			point = self.point5
			point.startElementNS( self.wordHandler, ( OFFICE_NS, 'body' ), 'office:body', { }, BREAKING_ELEMENT )
			point.startElementNS( self.wordHandler, self.P_NAME, 'text:p', { }, BREAKING_ELEMENT )
			self.assertTrue( point.state == point.STATE_INROOT )
			point.endElementNS( self.wordHandler, self.P_NAME, 'text:p', BREAKING_ELEMENT )
			point.startElementNS( self.wordHandler, self.P_NAME, 'text:p', { }, BREAKING_ELEMENT )
			self.assertTrue( point.state == point.STATE_INROOT )
			point.endElementNS( self.wordHandler, self.P_NAME, 'text:p', BREAKING_ELEMENT )
			point.startElementNS( self.wordHandler, self.LIST_NAME, 'text:list', { }, BREAKING_ELEMENT )
			self.assertTrue( point.state == point.STATE_INROOT )
			point.startElementNS( self.wordHandler, self.LISTITEM_NAME, 'text:list-item', { }, BREAKING_ELEMENT )
			self.assertTrue( point.state == point.STATE_INROOT )
			point.endElementNS( self.wordHandler, self.LISTITEM_NAME, 'text:list-item', BREAKING_ELEMENT )
			point.startElementNS( self.wordHandler, self.LISTITEM_NAME, 'text:list-item', { }, BREAKING_ELEMENT )
			self.assertTrue( point.state == point.STATE_INROOT )
			point.endElementNS( self.wordHandler, self.LISTITEM_NAME, 'text:list-item', BREAKING_ELEMENT )
			point.endElementNS( self.wordHandler, self.LIST_NAME, 'text:list', BREAKING_ELEMENT )
			point.startElementNS( self.wordHandler, self.LIST_NAME, 'text:list', { }, BREAKING_ELEMENT )
			self.assertTrue( point.state == point.STATE_INROOT )
			point.startElementNS( self.wordHandler, self.LISTITEM_NAME, 'text:list-item', { }, BREAKING_ELEMENT )
			self.assertTrue( point.state == point.STATE_INROOT )
			point.endElementNS( self.wordHandler, self.LISTITEM_NAME, 'text:list-item', BREAKING_ELEMENT )
			point.startElementNS( self.wordHandler, self.LISTITEM_NAME, 'text:list-item', { }, BREAKING_ELEMENT )
			self.assertTrue( point.state == point.STATE_INREL )
			
		def testWords( self ):
			point = HtmlPoint( self.samplePaths[0][1] )
			pathInfo = self.wordHandler
			point.startElementNS( pathInfo, ( OFFICE_NS, 'body' ), 'office:body', { }, BREAKING_ELEMENT )
			point.startElementNS( pathInfo, self.P_NAME, 'text:p', { }, BREAKING_ELEMENT )
			point.startWord( pathInfo, 1 )
			point.char( pathInfo, 'a', 1 )
			self.assertTrue( point.state == point.STATE_INREL )
			point.endWord( pathInfo, 1, 'a' )
			point.char( pathInfo, ' ', 0 )
			point.startWord( pathInfo, 2 )
			self.assertTrue( point.state == point.STATE_INWORD )
			point.char( pathInfo, 'b', 1 )
			
		def testChars( self ):
			point = HtmlPoint( self.samplePaths[0][1] )
			pathInfo = self.wordHandler
			point.startElementNS( pathInfo, ( OFFICE_NS, 'body' ), 'office:body', { }, BREAKING_ELEMENT )
			point.startElementNS( pathInfo, self.P_NAME, 'text:p', { }, BREAKING_ELEMENT )
			self.assertTrue( point.curWord == 0 )
			self.assertFalse( point.startWord( pathInfo, 1 ) )
			self.assertFalse( point.char( pathInfo, 'a', 1 ) )
			point.endWord( pathInfo, 1, 'a' )
			self.assertFalse( point.char( pathInfo, ' ', 0 ) )
			point.startWord( pathInfo, 2 )
			self.assertFalse( point.char( pathInfo, 'b', 1 ) )
			self.assertFalse( point.char( pathInfo, 'r', 2 ) )
			self.assertTrue( point.char( pathInfo, 'i', 3 ) )
			self.assertTrue( point.state == point.STATE_DONE )
	
	if __name__ == '__main__':
		unittest.main( )

except ImportError:
	pass

