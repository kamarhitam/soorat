CREATE TABLE IF NOT EXISTS `post_input` (
  `idpost` int(11) NOT NULL,
  `idinput` int(11) NOT NULL,
  `num` int(11) NOT NULL,
   UNIQUE( `idpost`, `idinput`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
