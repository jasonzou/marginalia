/*
 * rest-keywords.js
 * Fetch a list of keywords from the server
 *
 * Marginalia has been developed with funding and support from
 * BC Campus, Simon Fraser University, and the Government of
 * Canada, the UNDESA Africa i-Parliaments Action Plan, and  
 * units and individuals within those organizations.  Many 
 * thanks to all of them.  See CREDITS.html for details.
 * Copyright (C) 2005-2007 Geoffrey Glass; the United Nations
 * http://www.geof.net/code/annotation
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * $Id$
 */

function Keyword( name, description )
{
	this.name = name;
	this.description = description;
	return this;
}

function RestKeywordService( serviceUrl )
{
	this.serviceUrl = serviceUrl;
	this.keywords = new Array();
	this.keywordHash = new Object();
	return this;
}

RestKeywordService.prototype.init = function( )
{
	var keywordService = this;
	_cacheKeywords = function( responseText )
	{
		keywordService.cacheKeywords( responseText );
	}
	this.listKeywords( _cacheKeywords );
}

RestKeywordService.prototype.cacheKeywords = function( responseText )
{
	var lines = responseText.split( "\n" );
	for ( var i = 0;  i < lines.length;  ++i )
	{
		var x = lines[ i ].indexOf( ':' );
		if ( -1 != x )
		{
			var name = lines[ i ].substr( 0, x );
			var description = lines[ i ].substr( x + 1 );
			this.keywords[ i ] = new Keyword( name, description );
			this.keywordHash[ name ] = this.keywords[ i ];
			trace( 'keywords', 'Keyword:  ' + name + ' (' + description + ') ' );
		}
	}
}

RestKeywordService.prototype.isKeyword = function( word )
{
	return this.keywordHash[ word ] ? true : false;
}

RestKeywordService.prototype.getKeyword = function( word )
{
	return this.keywordHash[ word ];
}

RestKeywordService.prototype.listKeywords = function( f )
{
	var xmlhttp = domutil.createAjaxRequest( );
	xmlhttp.open( 'GET', this.serviceUrl, true );
	xmlhttp.onreadystatechange = function( ) {
		if ( xmlhttp.readyState == 4 )
		{
			if ( 200 == xmlhttp.status )
			{
				if ( f )
					f( xmlhttp.responseText );
			}
			else
				alert( "listKeywords failed with code " + xmlhttp.status + "\n" + xmlhttp.responseText );
		}
	}
	xmlhttp.send( null );
}

