CREATE TABLE IF NOT EXISTS `post` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `type` int(11),
  `date` datetime,
  `title` varchar(200),
  `slug` varchar(200),
  `content` text,
  UNIQUE KEY `post_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
