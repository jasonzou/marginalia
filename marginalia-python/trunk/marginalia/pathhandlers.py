from xml.sax import ContentHandler
import unittest
import sys
from pymock import *

BREAKING_ELEMENT = 'breaking'
NONBREAKING_ELEMENT = 'nonbreaking'
IGNORE_ELEMENT = 'ignore-text'

class SaxPath:
	""" Used by PathHandler to track path information and pass it to callbacks """
	def __init__( self ):
		self.path = [ ]			# of ( namespace, name ) pairs
		self.counts = [ { } ]	# of { ( ns, name ) -> count } dicts
		self.prefixes = { }		# of ns -> [ prefix, prefix, ... ] (later prefixs are more recently declared)

	def startPrefixMapping( self, prefix, ns ):
		pass
#		try:  self.prefixes[ ns ].append( prefix )
#		except KeyError:  self.prefixes[ ns ] = [ prefix ]
#		
	def endPrefixMapping( self, prefix ):
		pass
#		if self.prefixes[ ns ] == [ prefix ]:
#			del self.prefixes[ ns ]
#		else:
#			prefixes = self.prefixes[ ns ]
#			prefixes.reverse( )		# remove the last one
#			prefixes.remove( prefix )
#			prefixes.reverse( )
		
	def startElementNS( self, name, qname ):
		self.path.append( name )
		try:  self.counts[ len( self.counts ) - 1 ][ name ] += 1
		except KeyError:  self.counts[ len( self.counts ) - 1 ][ name ] = 1
		self.counts.append( { } )
		
	def endElementNS( self, name, qname ):
		self.path.pop( )
		self.counts.pop( )

	# Return looks like this:  [ ( ns, qname, count ), ( ns, qname, count ), ... ]
	def getPath( self ):
		return [ ( name[0], name[1], count[name] ) for name, count in zip( self.path, self.counts ) ]
		
	def getDepth( self ):
		return len( self.path )
		
		
class PathHandler( ContentHandler ):
	""" SAX content handler builds up path information, then passes calls on to a child handler """
	
	def __init__( self, handler, pathInfo ):
		self.quoting = False
		self.handler = handler
		self.pathInfo = pathInfo
		
	def startDocument( self ):
		self.handler.startDocument( )
		
	def endDocument( self ):
		self.handler.endDocument( )
	
	def startPrefixMapping( self, prefix, ns ):
		self.pathInfo.startPrefixMapping( prefix, ns )
		self.handler.startPrefixMapping( prefix, ns )
		
	def endPrefixMapping( self, prefix ):
		self.handler.endPrefixMapping( prefix )
		self.pathInfo.endPrefixMapping( prefix )
	
	def notationDecl( self, a, b, c ):
		self.handler.notationDecl( a, b, c )
		
	def startElementNS( self, name, qname, attrs ):
		self.pathInfo.startElementNS( name, qname )
		self.handler.startElementNS( name, qname, attrs )
		
	def endElementNS( self, name, qname ):
		self.handler.endElementNS( name, qname )
		self.pathInfo.endElementNS( name, qname )
		
	def characters( self, chars ):
		self.handler.characters( chars )
		
		
class WordHandler:
	""" Provides additional callbacks for words and characters """
	
	def __init__( self, pathInfo, handler, elementTypes ):
		self.handler = handler
		self.pathInfo = pathInfo
		self.elementTypes = elementTypes
		self.inWord = False
		self.wordNum = 0	# Count from document start
		self.charNum = 0	# Count from start of most recent word
		self.word = ''
		self.ignoreDepth = 0
		
	def getPath( self ):
		return self.pathInfo.getPath( )
		
	def getDepth( self ):
		return self.pathInfo.getDepth( )
		
	def getWordNum( self ):
		return self.wordNum
		
	def getCharNum( self ):
		return self.charNum
		
	
	def startDocument( self ):
		self.handler.startDocument( )
			
	def endDocument( self ):
		if self.inWord:
			self.__endWord( )
		self.handler.endDocument( )
		
	def startPrefixMapping( self, prefix, namespace ):
		self.handler.startPrefixMapping( prefix, namespace )
			
	def endPrefixMapping( self, prefix ):
		self.handler.endPrefixMapping( prefix )
			
	def notationDecl( self, a, b, c ):
		self.handler.notationDecl( a, b, c )
		
	def startElementNS( self, name, qname, attrs ):
		elementType = self.elementTypes.get( name, NONBREAKING_ELEMENT )
		if BREAKING_ELEMENT == elementType:
			if self.inWord and self.ignoreDepth == 0:
				self.__endWord( )
		elif IGNORE_ELEMENT == elementType:
			self.ignoreDepth += 1
		self.handler.startElementNS( name, qname, attrs, elementType )
		
	def endElementNS( self, name, qname ):
		elementType = self.elementTypes.get( name, NONBREAKING_ELEMENT )
		if BREAKING_ELEMENT == elementType:
			if self.inWord and self.ignoreDepth == 0:
				self.__endWord( )
		elif IGNORE_ELEMENT == elementType:
			self.ignoreDepth -= 1
		self.handler.endElementNS( name, qname, elementType )
	
	def characters( self, chars ):
		if self.ignoreDepth == 0:
			for c in chars:
				if c.isspace( ):
					if self.inWord:
						self.__endWord( )
				else:
					if self.inWord:
						self.charNum += 1
						self.word += c
					else:
						self.__startWord( )
						self.word = c
				self.handler.char( c, self.charNum )
		else:
			self.handler.ignoreCharacters( chars )

	def __startWord( self ):
		self.wordNum += 1
		self.charNum = 1
		self.inWord = True
		self.handler.startWord( self.wordNum )
		
	def __endWord( self ):
		self.handler.endWord( self.wordNum, self.word )
		self.inWord = False
		self.charNum = 0
		
			
class TestWordHandler( unittest.TestCase ):
	pElement = ( 'n', 'p' )
	divElement = ( 'n', 'div' )
	styleElement = ( 'n', 'style' )
	spanElement = ( 'n', 'span' )
	
	elementTypes = {
		pElement : BREAKING_ELEMENT,
		divElement : BREAKING_ELEMENT,
		styleElement : IGNORE_ELEMENT
	}
	
	def setUp( self ):
		self.path = SaxPath( )
		self.controller = Controller( )
		self.handler = self.controller.mock( )
		self.words = WordHandler( self.path, self.handler, self.elementTypes)

	def testStartElement( self ):
		self.handler.startElementNS( self.divElement, 'n:p', { }, BREAKING_ELEMENT )
		self.controller.replay( )
		self.words.startElementNS( self.divElement, 'n:p', { } )
		self.controller.verify( )
		
	def testEndElement( self ):
		self.handler.endElementNS( self.divElement, 'n:p', BREAKING_ELEMENT )
		self.controller.replay( )
		self.words.endElementNS( self.divElement, 'n:p' )
		self.controller.verify( )
		
	def testCharacters( self ):
		s = '  the fox'
		self.handler.char( ' ', 0 )
		self.handler.char( ' ', 0 )
		self.handler.startWord( 1 )
		self.handler.char( 't', 1 )
		self.handler.char( 'h', 2 )
		self.handler.char( 'e', 3 )
		self.handler.endWord( 1, 'the' )
		self.handler.char( ' ', 0 )
		self.handler.startWord( 2 )
		self.handler.char( 'f', 1 )
		self.handler.char( 'o', 2 )
		self.handler.char( 'x', 3 )
	#	self.handler.endWord( 2, 'fox' )
		self.controller.replay( )
		self.words.characters( s )
		self.controller.verify( )
		
	def testBreakingElement( self ):
		self.handler.startWord( 1 )
		self.handler.char( 't', 1 )
		self.handler.char( 'h', 2 )
		self.handler.char( 'e', 3 )
		self.handler.endWord( 1, 'the' )
		self.handler.startElementNS( self.divElement, 'n:p', { }, BREAKING_ELEMENT )
		self.handler.startWord( 2 )
		self.handler.char( 'f', 1 )
		self.handler.char( 'o', 2 )
		self.handler.char( 'x', 3 )
		self.handler.endWord( 2, 'fox' )
		self.handler.endElementNS( self.divElement, 'n:p', BREAKING_ELEMENT )
		self.handler.startWord( 3 )
		self.handler.char( 'r', 1 )
		self.controller.replay( )
		self.words.characters( 'the' )
		self.words.startElementNS( self.divElement, 'n:p', { } )
		self.words.characters( 'fox' )
		self.words.endElementNS( self.divElement, 'n:p' )
		self.words.characters( 'r' )
		self.controller.verify( )
		
	def testNonbreakingElement( self ):
		self.handler.startWord( 1 )
		self.handler.char( 't', 1 )
		self.handler.char( 'h', 2 )
		self.handler.char( 'e', 3 )
		self.handler.startElementNS( self.spanElement, 'n:span', { }, NONBREAKING_ELEMENT )
		self.handler.char( 'f', 4 )
		self.handler.char( 'o', 5 )
		self.handler.char( 'x', 6 )
		self.handler.endElementNS( self.spanElement, 'n:span', NONBREAKING_ELEMENT )
		self.handler.char( 'r', 7 )
		self.controller.replay( )
		self.words.characters( 'the' )
		self.words.startElementNS( self.spanElement, 'n:span', { } )
		self.words.characters( 'fox' )
		self.words.endElementNS( self.spanElement, 'n:span' )
		self.words.characters( 'r' )
		self.controller.verify( )
		

class TestPathHandler( unittest.TestCase ):
	""" Commented out for now (broken and unused):
	def testSinglePrefix( self ):
		path = SaxPath( )
		path.startPrefixMapping( 'p1', 'n1' )
		self.assertTrue( path.prefixes == { 'n1' : [ 'p1' ] } )
			
	def testDuplicatePrefix( self ):
		path = SaxPath( )
		path.startPrefixMapping( 'p1', 'n1' )
		path.startPrefixMapping( 'p2', 'n1' )
		self.assertTrue( path.prefixes == { 'n1' : [ 'p1', 'p2' ] } )
		
	def testTwoPrefixes( self ):
		path = SaxPath( )
		path.startPrefixMapping( 'p1', 'n1' )
		path.startPrefixMapping( 'p2', 'n2' )
		self.assertTrue( path.prefixes == { 'n1' : [ 'p1' ], 'n2' : [ 'p2' ] } )
		
	def testPrefixRemoval( self ):
		path = SaxPath( )
		path.startPrefixMapping( 'p1', 'n1' )
		path.startPrefixMapping( 'p2', 'n2' )
		path.startPrefixMapping( 'p3', 'n1' )
		path.endPrefixMapping( 'p1' )
		self.assertTrue( path.prefixes == { 'n1' : [ 'p3' ], 'n2' : [ 'p2' ] } )
		path.endPrefixMapping( 'p3' )
		self.assertTrue( path.prefixes == { 'n2' : [ 'p2' ] } )
	"""		
	def testSingleElementStart( self ):
		path = SaxPath( )
		name = ( 'n1', 'e1' )
		path.startElementNS( name, 'p1:e1' )
		self.assertTrue( path.path == [ name ] )
		self.assertTrue( path.counts == [ { name : 1 }, { } ] )
		self.assertTrue( path.getPath( ) == [ ( 'n1', 'e1', 1 ) ] )
		
	def testSingleElementStartEnd( self ):
		path = SaxPath( )
		name = ( 'n1', 'e1' )
		path.startElementNS( name, 'p1:e1' )
		self.assertTrue( path.getPath( ) == [ ( 'n1', 'e1', 1 ) ] )
		path.endElementNS( name, 'p1:e1' )
		self.assertTrue( path.path == [ ] )
		self.assertTrue( path.counts == [ { name : 1 } ] )
		self.assertTrue( path.getPath( ) == [ ] )
		
	def testElementNesting( self ):
		path = SaxPath( )
		name1 = ( 'n1', 'e1' )
		name2 = ( 'n1', 'e2' )
		path.startElementNS( name1, 'p1:e1' )
		path.startElementNS( name2, 'p1:e2' )
		self.assertTrue( path.path == [ name1, name2 ] )
		self.assertTrue( path.counts == [ { name1 : 1 }, { name2 : 1 }, { } ] )
		self.assertTrue( path.getPath( ) == [ ( 'n1', 'e1', 1 ), ( 'n1', 'e2', 1 ) ] )
		path.endElementNS( name2, 'p1:e2' )
		self.assertTrue( path.path == [ name1 ] )
		self.assertTrue( path.counts == [ { name1 : 1 } , { name2 : 1 } ] )
		self.assertTrue( path.getPath( ) == [ ( 'n1', 'e1', 1 ) ] )
		path.endElementNS( name1, 'p1:e1' )
		self.assertTrue( path.path == [ ] )
		self.assertTrue( path.counts == [ { name1 : 1 }  ] )
		self.assertTrue( path.getPath( ) == [ ] )
		
	def testSecondElement( self ):
		path = SaxPath( )
		name = ( 'n', 'e' )
		path.startElementNS( name, 'p:e' )
		path.endElementNS( name, 'p:e' )
		path.startElementNS( name, 'p:e' )
		path.endElementNS( name, 'p:e' )
		self.assertTrue( path.path == [ ] )
		self.assertTrue( path.counts == [ { name : 2 } ] )
		self.assertTrue( path.getPath( ) == [ ] )
		
if __name__ == '__main__':
	unittest.main( )

