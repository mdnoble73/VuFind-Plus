create database packaging;
grant all privileges on packaging.* to vufind;

use packaging;
create table if not exists acs_packaging_log(
    packagingId INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
    distributorId INT NOT NULL, 
    copies INT NOT NULL DEFAULT 0, 
    filename VARCHAR(256) NOT NULL, 
    created TIMESTAMP NOT NULL DEFAULT NOW(), 
    lastUpdate DATETIME, 
    packagingStartTime DATETIME, 
    packagingEndTime DATETIME, 
    acsError VARCHAR(256), 
    acsId INT, 
    status VARCHAR(20)
);
