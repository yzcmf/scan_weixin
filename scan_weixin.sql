-- phpMyAdmin SQL Dump
-- version 4.2.6
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Aug 02, 2014 at 01:45 AM
-- Server version: 5.5.38-MariaDB
-- PHP Version: 5.5.15

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `scan_weixin`
--

-- --------------------------------------------------------

--
-- Table structure for table `reply_map`
--

CREATE TABLE IF NOT EXISTS `reply_map` (
`id` int(11) NOT NULL,
  `type` enum('php_reply','full_match','sub_match','fallback') NOT NULL,
  `rule_name` text NOT NULL,
  `uid` int(11) NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=16 ;

--
-- Dumping data for table `reply_map`
--

-- --------------------------------------------------------

--
-- Table structure for table `reply_meta`
--

CREATE TABLE IF NOT EXISTS `reply_meta` (
`index_key` int(11) NOT NULL,
  `reply_key` text NOT NULL,
  `reply_value` text NOT NULL,
  `id` int(11) NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=107 ;

--
-- Dumping data for table `reply_meta`
--

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
`uid` int(11) NOT NULL,
  `username` text NOT NULL,
  `password` text NOT NULL,
  `role` enum('administrator','common') NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `user`
--

-- --------------------------------------------------------

--
-- Table structure for table `user_meta`
--

CREATE TABLE IF NOT EXISTS `user_meta` (
`index_key` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `user_key` text NOT NULL,
  `user_value` text NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `user_meta`
--

--
-- Indexes for dumped tables
--

--
-- Indexes for table `reply_map`
--
ALTER TABLE `reply_map`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reply_meta`
--
ALTER TABLE `reply_meta`
 ADD PRIMARY KEY (`index_key`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
 ADD PRIMARY KEY (`uid`);

--
-- Indexes for table `user_meta`
--
ALTER TABLE `user_meta`
 ADD PRIMARY KEY (`index_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `reply_map`
--
ALTER TABLE `reply_map`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=16;
--
-- AUTO_INCREMENT for table `reply_meta`
--
ALTER TABLE `reply_meta`
MODIFY `index_key` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=107;
--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `user_meta`
--
ALTER TABLE `user_meta`
MODIFY `index_key` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
