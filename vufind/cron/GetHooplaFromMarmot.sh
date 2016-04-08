#!/bin/bash

cd /data/vufind-plus/hoopla/marc

# It should be possible to use a directory listing to get all the files,
# but I haven't gotten it to work yet. plb 12-18-2015

wget -N -nv http://venus.marmot.org/hooplamarc/USA_AB.mrc
#wget -N -nv http://venus.marmot.org/hooplamarc/USA_ALL_AB.mrc
wget -N -nv http://venus.marmot.org/hooplamarc/USA_ALL_Comic.mrc
wget -N -nv http://venus.marmot.org/hooplamarc/USA_ALL_eBook.mrc
wget -N -nv http://venus.marmot.org/hooplamarc/USA_ALL_Music.mrc
#wget -N -nv http://venus.marmot.org/hooplamarc/USA_ALL_TV_Video.mrc
#wget -N -nv http://venus.marmot.org/hooplamarc/USA_ALL_Video.mrc
#wget -N -nv http://venus.marmot.org/hooplamarc/USA_Comic.mrc
#wget -N -nv http://venus.marmot.org/hooplamarc/USA_eBook.mrc
wget -N -nv http://venus.marmot.org/hooplamarc/USA_No_PA_Music.mrc
wget -N -nv http://venus.marmot.org/hooplamarc/USA_Only_PA_Music.mrc
wget -N -nv http://venus.marmot.org/hooplamarc/USA_TV_Video.mrc
wget -N -nv http://venus.marmot.org/hooplamarc/USA_Video.mrc
# only fetch the files processed the Hoopla Processor Looks for. plb 4-7-2016

# Check that the Hoopla Marc is updating monthly
OLDHOOPLA=$(find /data/vufind-plus/hoopla/marc/ -name "*.mrc" -mtime +30)
if [ -n "$OLDHOOPLA" ]
then
	echo "There are Hoopla Marc files older than 30 days : "
	echo "$OLDHOOPLA"
fi
