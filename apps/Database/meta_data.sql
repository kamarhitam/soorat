CREATE TABLE IF NOT EXISTS `meta_data` (
  `id` bigint(20) AUTO_INCREMENT PRIMARY KEY,
  `idmeta` int(11) NOT NULL,
  `num` int(11),
  `value` text,
  UNIQUE KEY `ref_meta_data_unique` (`idmeta`, `num`, `value`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
