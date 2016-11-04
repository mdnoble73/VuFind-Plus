#!/usr/bin/env bash

if [[ $# -ne 1 ]]; then
# TODO: use actual description
	echo "To use, add the side load collection data directory for the first parameter (omitting /data/vufind-plus)."
	echo "$0 data_directory"
	echo "eg: $0 lynda/vail"
else

	SIDELOADDIR="/data/vufind-plus/$1"
#	SECONDPARAM=$2

	LOG="logger -t $0"
	if [ -d "$SIDELOADDIR/" ]; then
		if [ -d "$SIDELOADDIR/merge/marc" ]; then
			if [ "$(ls -A $SIDELOADDIR/merge/marc)" ]; then #TODO: check for deletes also
				if [ -r "$SIDELOADDIR/mergeConfig.ini" ]; then
					cd /usr/local/marcMergeUtility
					java -jar MarcMergeUtility.jar "$SIDELOADDIR/mergeConfig.ini"
				else
					echo    "Merge configuration file not readable: $SIDELOADDIR/mergeConfig.ini"
					$LOG "~~ Merge configuration file not readable: $SIDELOADDIR/mergeConfig.ini"
				fi
			else
				echo    "There are no files to merge"
				$LOG "~~ There are no files to merge"
			fi
		else
			echo    "Merge directory not found: $SIDELOADDIR/merge/marc"
			$LOG "~~ Merge directory not found: $SIDELOADDIR/merge/marc"
		fi
	else
		echo    "Specified directory not found: $SIDELOADDIR"
		$LOG "~~ Specified directory not found: $SIDELOADDIR"
	fi
fi