#!/bin/sh
. `dirname ${0}`/lcp.sh
java -classpath $LOCALCLASSPATH -Djava.util.logging.config.file=logging.properties org.semanticdesktop.aperture.examples.ExampleImapCrawler $*
