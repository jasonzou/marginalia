<?php

class Annotation extends DataObject
{
	/**
	 * Constructor.
	 */
	function Annotation( )
	{
		parent::DataObject( );
	}
	
	function fromArray( $params )
	{
		return MarginaliaHelper::annotationFromParams( $this, $params );
	}

	function getAnnotationId( )
	{  return $this->getData( 'id' );  }
	
	function setAnnotationId( $annotationId )
	{  $this->setData( 'id', (int) $annotationId );  }
	
	function getUserId( )
	{  return $this->getData( 'userid' );  }
	
	function setUserId( $userid )
	{  $this->setData( 'userid', $userid );  }
	
	function getUrl( )
	{  return $this->getData( 'url' );  }
	
	/**
	 * Set the URL of the annotated resource
	 * In order to preclude the possibility of XSS attacks, only http or https
	 * schemes are accepted.
	 */
	function setUrl( $url )
	{
		if ( ! $url || MarginaliaHelper::isUrlSafe( $url ) )
			return $this->setData( 'url', $url );
		else
			return false;
	}
	
	function getSequenceRange( )
	{  return $this->getData( 'sequenceRange' );  }
	
	function setSequenceRange( $range )
	{  $this->setData( 'sequenceRange', $range );  }
	
	function getXPathRange( )
	{  return $this->getData( 'xpathRange' );  }
	
	function setXPathRange( $range )
	{  $this->setData( 'xpathRange', $range );  }
	
	function getNote( )
	{  return $this->getData( 'note' );  }
	
	function setNote( $note )
	{  $this->setData( 'note', $note );  }
	
	function getAccess( )
	{  return $this->getData( 'access' );  }
	
	function setAccess( $access )
	{
		if ( Annotation::isAccessValid( $access ) )
			$this->setData( 'access', $access );
	}
	
	function getAction( )
	{  return $this->getData( 'action' );  }
	
	function setAction( $action )
	{
		if ( Annotation::isActionValid( $action ) )
			$this->setData( 'action', $action );
	}
	
	function getQuote( )
	{  return $this->getData( 'quote' );  }
	
	function setQuote( $quote )
	{  $this->setData( 'quote', $quote );  }
	
	function getQuoteTitle( )
	{  return $this->getData( 'quote_title' );  }
	
	function setQuoteTitle( $quoteTitle )
	{  $this->setData( 'quote_title', $quoteTitle );  }

	function getQuoteAuthor( )
	{  return $this->getData( 'quote_author' );  }
	
	function setQuoteAuthor( $quoteAuthor )
	{  $this->setData( 'quote_author', $quoteAuthor );  }
	
	function getLink( )
	{  return $this->getData( 'link' );  }
	
	/**
	 * Set a link to another resource
	 * In order to preclude the possibility of XSS attacks, only http or https
	 * schemes are accepted.
	 */
	function setLink( $link )
	{
		if ( !$link || MarginaliaHelper::isUrlSafe( $link ) )
			return $this->setData( 'link', $link );
		else
			return false;
	}
	
	function getLinkTitle( )
	{  return $this->getData( 'link_title' );  }
	
	function setLinkTitle( $title )
	{  $this->setData( 'link_title', $title );  }
	
	function getCreated( )
	{  return $this->getData( 'created' );  }
	
	function setCreated( $created )
	{  $this->setData( 'created', is_string( $created ) ? strtotime( $created ) : $created );  }
	
	function getModified( )
	{  return $this->getData( 'modified' );  }
	
	function setModified( $modified )
	{  $this->setData( 'modified', is_string( $modified ) ? strtotime( $modified ) : $modified );  }

	/**
	 * Check whether an action value is valid
	 */
	function isActionValid( $action )
	{
		return null === $action || '' === $action || 'edit' == $action;
	}
	
	/**
	 * Check whether an access value is valid
	 */
	function isAccessValid( $access )
	{
		return ! $access || 'public' == $access || 'private' == $access;
	}
	
	/**
	 * Convert to an Atom entry
	 */
	function toAtom( $tagHost, $servicePath )
	{
		return MarginaliaHelper::annotationToAtom( $this, $tagHost, $servicePath );
	}
}

?>
