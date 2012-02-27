@echo off
set LOCALCLASSPATH=

set LIB_DIR=..\lib
for %%i in ("%LIB_DIR%\*.jar") do call "lcp.bat" %%i

set LIB_DIR=..\required
for %%i in ("%LIB_DIR%\*.jar") do call "lcp.bat" %%i

set LIB_DIR=..\optional
for %%i in ("%LIB_DIR%\*.jar") do call "lcp.bat" %%i

set LIB_DIR=..\example
for %%i in ("%LIB_DIR%\*.jar") do call "lcp.bat" %%i

java -classpath %LOCALCLASSPATH% -Djava.util.logging.config.file=logging.properties org.semanticdesktop.aperture.examples.ExampleBibsonomyCrawler %*
