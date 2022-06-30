
CREATE TABLE IF NOT EXISTS `letter` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `code` varchar(20),
  `idpost` int(11) NOT NULL,
  `createdate` datetime,
  `createby` int(11),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
