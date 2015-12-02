#---------------------------------- 1.0 -----------------------------------------
CREATE TABLE `page` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `text` text NOT NULL,
  `position` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `keywords` varchar(255) DEFAULT NULL,
  `description` text,
  `is_homepage` enum('yes','no') NOT NULL,
  `link` varchar(255) NOT NULL,
  `in_menu` enum('yes','no') NOT NULL DEFAULT 'yes',
  `parent_id` int(11) DEFAULT NULL,
  `module` varchar(255) DEFAULT NULL,
  `h1` varchar(255) DEFAULT NULL,
  `external` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
INSERT INTO `page` (`id`, `name`, `text`, `position`, `title`, `keywords`, `description`, `is_homepage`, `link`, `in_menu`, `parent_id`, `module`, `h1`, `external`) VALUES
(1, 'Home', '<p>Homepage</p>\n', 1, NULL, NULL, NULL, 'yes', 'home', 'yes', NULL, NULL, NULL, 0);
ALTER TABLE `page`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);
ALTER TABLE `page`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `page`
  ADD CONSTRAINT `page_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `page` (`id`) ON DELETE CASCADE;
