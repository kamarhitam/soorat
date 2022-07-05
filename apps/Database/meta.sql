CREATE TABLE IF NOT EXISTS `meta` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `type` varchar(20),
  `target` varchar(20),
  `slug` varchar(100),
  `name` varchar(100),
  `source` varchar(200),
  `parent` int(11),
  UNIQUE KEY `ref_meta_unique` (`type`, `target`, `slug`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
