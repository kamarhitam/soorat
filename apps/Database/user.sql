CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `code` varchar(20),
  `name` varchar(50),
  `email` varchar(150),
  `password` varchar(32),
  `image` varchar(250),
  `idtype` int(11),
  `active` int(11)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
