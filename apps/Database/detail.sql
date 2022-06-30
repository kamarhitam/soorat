CREATE TABLE IF NOT EXISTS `detail` (
  `id` bigint(20) AUTO_INCREMENT PRIMARY KEY,
  `idtarget` int(11) NOT NULL,
  `target` varchar(20) NOT NULL,
  `key` varchar(100) NOT NULL,
  `value` varchar(200),
  UNIQUE KEY `ref_detail_unique` (`idtarget`,`target`,`key`,`value`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
