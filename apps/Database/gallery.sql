CREATE TABLE IF NOT EXISTS `gallery` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `date` date,
  `code` varchar(32),
  `name` varchar(200),
  `ext` varchar(10),
  `tag` varchar(20),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
