#!/bin/bash

cd /data/vufind-plus/hoopla/marc

# It should be possible to use a directory listing to get all the files,
# but I haven't gotten it to work yet. plb 12-18-2015

wget -N -nv http://venus.marmot.org/hooplamarc/USA_AB.mrc
wget -N -nv http://venus.marmot.org/hooplamarc/USA_ALL_AB.mrc
wget -N -nv http://venus.marmot.org/hooplamarc/USA_ALL_Comic.mrc
wget -N -nv http://venus.marmot.org/hooplamarc/USA_ALL_eBook.mrc
wget -N -nv http://venus.marmot.org/hooplamarc/USA_ALL_Music.mrc
wget -N -nv http://venus.marmot.org/hooplamarc/USA_ALL_TV_Video.mrc
wget -N -nv http://venus.marmot.org/hooplamarc/USA_ALL_Video.mrc
wget -N -nv http://venus.marmot.org/hooplamarc/USA_Comic.mrc
wget -N -nv http://venus.marmot.org/hooplamarc/USA_eBook.mrc
wget -N -nv http://venus.marmot.org/hooplamarc/USA_No_PA_Music.mrc
wget -N -nv http://venus.marmot.org/hooplamarc/USA_Only_PA_Music_.mrc
wget -N -nv http://venus.marmot.org/hooplamarc/USA_Only_PA_Music.mrc
wget -N -nv http://venus.marmot.org/hooplamarc/USA_TV_Video.mrc
wget -N -nv http://venus.marmot.org/hooplamarc/USA_Video.mrc
