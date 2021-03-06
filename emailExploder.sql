CREATE TABLE exploderLists (
  `ID` VARCHAR(200) NOT NULL,
  `NAME` TINYTEXT NOT NULL,
  `DESCRIPTION` TINYTEXT NOT NULL,
  `MAIL` VARCHAR(254) NOT NULL,
  `TYPE` ENUM('AD','GOOGLE'),
  PRIMARY KEY(ID),
  KEY(MAIL),
  UNIQUE INDEX (MAIL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE listMembers (
  EID VARCHAR(200) NOT NULL,
  MAIL VARCHAR(254) NOT NULL,
  UNIQUE INDEX (EID, MAIL),
  FOREIGN KEY fk_list (EID) REFERENCES exploderLists (ID) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


