#!/usr/bin/perl

while(<>)
{
	s/'/''/g;
	($name,$desc) = /^([^:]*):(.*)$/;
	print "INSERT INTO keywords ( name, description ) VALUES ( '$name', '$desc' );\n";
}
