CREATE TABLE crossings (
    rangeID Int NOT NULL PRIMARY KEY AUTO_INCREMENT,
    crossStartDate DATETIME,
    crossType varchar(8),
    crossEndDate DATETIME
);

CREATE TABLE users (
    id Int NOT NULL PRIMARY KEY AUTO_INCREMENT,
    username varchar(50) NOT NULL UNIQUE,
    password varchar(255) NOT NULL,
    twitterHandle varchar(100),
    twitterID varchar(50),
    email varchar(100),
    emailVerify varchar(1),
    created DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE trips (
    tripID Int NOT NULL PRIMARY KEY AUTO_INCREMENT,
    userID Int,
    pcode varchar(50),
    warningTime Int,
    journeyTime Int,
    journeyDistance varchar(10),
    arrivalDate DATETIME,
    assignedRangeID Int,
    currentStatus varchar(40)
);

CREATE TABLE pcodes(
    pcode1 varchar(8),
    pcode2 varchar(8),
    pcode3 varchar(8),
    latitude varchar(10),
    longtitude varchar(10)
);

CREATE TABLE userCodes(
    id Int NOT NULL PRIMARY KEY AUTO_INCREMENT,
    userID Int,
    code varchar(6) NOT NULL,
    createdDate DATETIME,
    expiryDate DATETIME,
    status varchar(15)
);