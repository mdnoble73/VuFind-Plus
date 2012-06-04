create database packaging;
grant all privileges on packaging.* to vufind;

use packaging;
create table if not exists acs_packaging_log(
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
    distributorId VARCHAR(128) NOT NULL, 
    copies INT NOT NULL DEFAULT 0, 
    filename VARCHAR(256) NOT NULL, 
    previousAcsId VARCHAR(128), 
    created INT(11) NOT NULL, 
    lastUpdate INT(11), 
    packagingStartTime INT(11), 
    packagingEndTime INT(11), 
    acsError MEDIUMTEXT, 
    acsId VARCHAR(128), 
    status ENUM('pending', 'sentToAcsServer', 'acsIdGenerated', 'acsError')
);
