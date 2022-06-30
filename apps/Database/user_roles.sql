
CREATE TABLE IF NOT EXISTS `user_roles` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `iduser` int(11) NOT NULL,
  `idrole` int(11) NOT NULL,
  UNIQUE KEY `user_roles_unique` (`iduser`,`idrole`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

