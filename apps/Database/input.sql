CREATE TABLE IF NOT EXISTS `input` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `title` varchar(200),
  `slug` varchar(200),
  `type` varchar(200),
  `target` varchar(20),
  `source` varchar(200),
  `format` varchar(20),
  `parent` int(11),
  UNIQUE KEY `input_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
