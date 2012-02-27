#!/bin/sh

APERTURE_HOME=`dirname "${0}"`/..
for i in ${APERTURE_HOME}/lib*.jar ${APERTURE_HOME}/example*.jar ${APERTURE_HOME}/optional/*.jar 
do
  # if the directory is empty, then it will return the input string
  # this is stupid, so case for it
  if [ -f "$i" ] ; then
    if [ -z "$LOCALCLASSPATH" ] ; then
      LOCALCLASSPATH="$i"
    else
      LOCALCLASSPATH="$i":"$LOCALCLASSPATH"
    fi
  fi
done
