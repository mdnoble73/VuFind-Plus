 ,----------------------------------------------------------------------------.
 |0. What is Aperture?                                                        |
 `----------------------------------------------------------------------------'

Aperture is a Java framework for extracting and querying full-text content
and metadata from various information systems (file systems, web sites, mail
boxes, ...) and the file formats (documents, images, ...) occurring in these
systems.

 ,----------------------------------------------------------------------------.
 |1. Using Aperture in your own application                                   |
 `----------------------------------------------------------------------------'

In order to use aperture you'll need four things. You'll find all of them 
in the lib subfolder of the distribution. Note that the jars in the 
supporting-libs subfolder are not needed to use the core functionality.

 1. Aperture itself - the aperture-core-<version>.jar in the lib folder.
    
 2. The required aperture dependencies. Located in the lib folder.
    If you need the whole aperture functionality - you need all those jars. 
    You can also choose to omit some jars from that folder. See the dependency-
    related documentation for details about which Aperture component needs which
    dependency.
      
  3. The optional aperture dependencies. Located in the optional folder. 
     You need a logging framework and an RDF store, but Aperture doesn't
     confine you to a single one, you may take advantage of the modular
     nature of RDF2Go and SLF4j frameworks and choose your own implementations.
     
     The 'optional' folder contains jars which use the built-in java.util.logging
     library, and Sesame 2.3 as the RDF database. You may choose different ones
     by ensuring that the jars from 'optional' folder do NOT come up on your
     classpath and adding different adapters. See 
     
     http://semanticweb.org/wiki/RDF2Go
     
     and
     
     http://www.slf4j.org
     
     for details.
  
Aperture has been compiled with java 5 and will not work on 1.4.2 or earlier 
JDK's.

 ,----------------------------------------------------------------------------.
 | 2. Improving Aperture                                                      |
 `----------------------------------------------------------------------------'

  1. Get the source code (if you don't have it yet) 
    * from SVN trunk
      http://aperture.svn.sourceforge.net/svnroot/aperture/aperture/trunk/
    * or from an SVN tag
      http://aperture.svn.sourceforge.net/svnroot/aperture/aperture/tags/<tag-name>
   2. Refer to the BUILD.txt file for building and testing instructions
   