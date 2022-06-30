
CREATE TABLE IF NOT EXISTS `role` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `idmenu` int(11) NOT NULL,
  `idaction` int(11) NOT NULL,
  `name` varchar(50),
  `description` varchar(200)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
