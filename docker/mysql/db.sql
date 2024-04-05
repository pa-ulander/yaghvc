-- phpMyAdmin SQL Dump
-- version 5.0.1
-- https://www.phpmyadmin.net/
--
-- Host: mysql-db:3306
-- Generation Time: May 23, 2020 at 01:23 AM
-- Server version: 5.7.29
-- PHP Version: 7.4.1

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db`
--
CREATE DATABASE IF NOT EXISTS `db` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `db`;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT '0 = root category',
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `keywords` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `parent_id` (`parent_id`,`name`,`slug`),
  KEY `categories_parent_id_index` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `categories`
--

TRUNCATE TABLE `categories`;
--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `description`, `keywords`, `active`, `created_at`, `updated_at`) VALUES
(1, 0, 'Tapeter', 'Tapeter', 'Text om kategorin tapeter', NULL, 1, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(2, 0, 'Fondtapeter', 'Fondtapeter', 'Text om kategorin fondtapeter', NULL, 1, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(3, 0, 'Vikskärmar', 'Vikskärmar', 'Text om kategorin vikskärmar', NULL, 1, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(4, 1, '1600', '1600', 'Tapeter från 1600-talet', NULL, 1, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(5, 0, 'Bårder', 'Bårder', 'Text om kategorin bårder', NULL, 1, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(6, 1, '1700-1750', '1700-1750', 'Tapeter från 1700-1750', NULL, 1, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(7, 5, '1750-1800', '1750-1800', 'Bårder från 1750-1800', NULL, 1, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(8, 5, '1800-1850', '1800-1850', 'Bårder från 1800-1850', NULL, 1, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(9, 0, 'Rullgardiner', 'Rullgardiner', 'Text om kategorin rullgardiner', NULL, 1, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(10, 0, 'Linoleumgolv', 'Linoleumgolv', 'Text om kategorin linoleumgolv', NULL, 1, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(19, 1, '1750-1800', '1750-1800', 'Tapeter från 1750-1800', NULL, 1, '2020-03-22 22:13:06', '2020-03-22 22:13:06'),
(20, 1, '1800-1850', '1800-1850', 'Tapeter  från1800-1850', NULL, 1, '2020-03-22 22:58:21', '2020-03-22 22:58:21'),
(21, 1, '1850-1900', '1850-1900', 'Tapeter från 1850-1900', NULL, 1, '2020-03-22 22:59:22', '2020-03-22 22:59:22'),
(22, 1, '1900-1930', '1900-1930', 'Tapeter från 1900-1930', NULL, 1, '2020-03-22 23:00:34', '2020-03-22 23:00:34'),
(23, 1, '1930-1960', '1930-1960', 'Tapeter från 1930-1960', NULL, 1, '2020-03-22 23:01:02', '2020-03-22 23:01:02'),
(24, 1, '1960-2000', '1960-2000', 'Tapeter från 1960-2000', NULL, 1, '2020-03-22 23:01:34', '2020-03-22 23:01:34'),
(25, 5, '1850-1900', '1850-1900', 'Bårder från 1850-1900', NULL, 1, '2020-03-22 23:05:11', '2020-03-22 23:05:11'),
(26, 5, '1900-1930', '1900-1930', 'Bårder från 1900-1930', NULL, 1, '2020-03-22 23:05:36', '2020-03-22 23:05:36'),
(27, 5, '1930-1960', '1930-1960', 'Bårder från 1930-1960', NULL, 1, '2020-03-22 23:06:42', '2020-03-22 23:06:42'),
(28, 5, '1960-1980', '1960-1980', 'Bårder från 1960-1980', NULL, 1, '2020-03-22 23:07:08', '2020-03-22 23:07:08');

-- --------------------------------------------------------

--
-- Table structure for table `category_product`
--

DROP TABLE IF EXISTS `category_product`;
CREATE TABLE IF NOT EXISTS `category_product` (
  `product_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`product_id`,`category_id`),
  UNIQUE KEY `category_product_product_id_category_id_unique` (`product_id`,`category_id`),
  KEY `category_product_category_id_foreign` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `category_product`
--

TRUNCATE TABLE `category_product`;
--
-- Dumping data for table `category_product`
--

INSERT INTO `category_product` (`product_id`, `category_id`) VALUES
(1, 1),
(2, 1),
(3, 1),
(4, 1);

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `migrations`
--

TRUNCATE TABLE `migrations`;
--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2018_01_01_000001_users_table', 1),
(2, '2018_01_01_000002_news_table', 1),
(3, '2018_01_01_000003_products_table', 1),
(4, '2018_01_01_000004_categories_table', 1),
(5, '2018_01_01_000005_tags_table', 1),
(6, '2018_01_01_000006_products_tags_table', 1),
(7, '2018_01_01_000007_subcategories_table', 1),
(8, '2018_01_01_000008_productimages_table', 1),
(9, '2020_03_08_191245_create_category_product_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

DROP TABLE IF EXISTS `news`;
CREATE TABLE IF NOT EXISTS `news` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `draft` tinyint(1) NOT NULL DEFAULT '1',
  `published` tinyint(1) NOT NULL DEFAULT '0',
  `published_from` datetime DEFAULT NULL,
  `published_to` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `news`
--

TRUNCATE TABLE `news`;
--
-- Dumping data for table `news`
--

INSERT INTO `news` (`id`, `name`, `body`, `draft`, `published`, `published_from`, `published_to`, `created_at`, `updated_at`) VALUES
(1, 'hic', 'Rem fugiat rerum omnis porro nulla expedita. Ex numquam et cumque eum dolorum. Vero molestiae voluptatem distinctio qui corporis nesciunt tenetur nesciunt.', 1, 0, '1973-01-05 00:00:00', '2009-01-19 00:00:00', '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(2, 'eveniet', 'Quisquam deserunt id dolorem illo culpa. Aliquam ut eos fugit quo natus aut aut. Et et qui qui a nobis.', 1, 0, '2015-04-05 00:00:00', '2006-06-22 00:00:00', '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(3, 'iste', 'Distinctio consequuntur maiores nobis perferendis voluptatem aut asperiores officia. Ut nisi numquam asperiores optio aut voluptas tempora. Ut itaque est quas laborum.', 1, 0, '1978-02-23 00:00:00', '2010-10-01 00:00:00', '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(4, 'placeat', 'Distinctio tempore quo nihil qui odit non at perferendis. Molestiae sed quidem impedit. Quia nisi repellendus et assumenda consequuntur sunt.', 1, 0, '2007-06-11 00:00:00', '2008-02-12 00:00:00', '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(5, 'autem', 'Explicabo modi eligendi velit totam rerum quibusdam incidunt. Accusantium id id cumque cumque. Voluptatem architecto quia et voluptas et.', 1, 0, '2010-10-03 00:00:00', '2001-11-11 00:00:00', '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(6, 'amet', 'Tempora vel recusandae architecto voluptas omnis eveniet. Corporis quos ipsum cupiditate distinctio illo. Aut praesentium qui similique iusto ipsum ut. Sunt quam est velit aliquam. Molestiae et dolorem fuga unde.', 1, 0, '1997-11-24 00:00:00', '1973-03-16 00:00:00', '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(7, 'molestiae', 'Eveniet nulla est error similique iure. Sapiente ut blanditiis hic incidunt. Saepe laudantium nam explicabo omnis ut repellendus ullam. Repudiandae voluptatem molestias repudiandae explicabo.', 1, 0, '2003-08-27 00:00:00', '1986-07-15 00:00:00', '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(8, 'commodi', 'Quia perferendis saepe vitae animi praesentium et. Nisi et voluptatem praesentium autem. Inventore tempore magnam occaecati rerum et.', 1, 0, '2014-08-01 00:00:00', '2011-12-29 00:00:00', '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(9, 'voluptatem', 'Quis dolore veniam quia quae. Quis provident modi aut voluptate facere odit enim non. Sint voluptatem adipisci rerum doloribus.', 1, 0, '1996-12-20 00:00:00', '1970-01-11 00:00:00', '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(10, 'iste', 'A et eos ratione reprehenderit. Laudantium quae omnis qui dolorem. Mollitia labore aut dolorem non enim earum sint.', 1, 0, '1977-07-28 00:00:00', '1979-01-29 00:00:00', '2020-03-12 23:58:24', '2020-03-12 23:58:24');

-- --------------------------------------------------------

--
-- Table structure for table `productimages`
--

DROP TABLE IF EXISTS `productimages`;
CREATE TABLE IF NOT EXISTS `productimages` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default.jpg',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `productimages_product_id_foreign` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `productimages`
--

TRUNCATE TABLE `productimages`;
--
-- Dumping data for table `productimages`
--

INSERT INTO `productimages` (`id`, `product_id`, `name`, `created_at`, `updated_at`) VALUES
(1, 2, 'default.jpg', '2020-03-13 00:09:41', '2020-03-13 00:09:41'),
(2, 2, 'default.jpg', '2020-03-13 00:09:41', '2020-03-13 00:09:41'),
(3, 2, 'default.jpg', '2020-03-13 00:09:41', '2020-03-13 00:09:41'),
(4, 2, 'default.jpg', '2020-03-13 00:09:41', '2020-03-13 00:09:41'),
(5, 1, 'default.jpg', '2020-03-13 00:10:38', '2020-03-13 00:10:38'),
(6, 1, 'default.jpg', '2020-03-13 00:10:38', '2020-03-13 00:10:38'),
(7, 1, 'default.jpg', '2020-03-13 00:10:38', '2020-03-13 00:10:38'),
(8, 1, 'default.jpg', '2020-03-13 00:10:38', '2020-03-13 00:10:38'),
(9, 3, 'default.jpg', '2020-03-13 00:10:45', '2020-03-13 00:10:45'),
(10, 3, 'default.jpg', '2020-03-13 00:10:45', '2020-03-13 00:10:45'),
(11, 3, 'default.jpg', '2020-03-13 00:10:45', '2020-03-13 00:10:45'),
(12, 3, 'default.jpg', '2020-03-13 00:10:45', '2020-03-13 00:10:45'),
(13, 4, 'default.jpg', '2020-03-13 00:10:50', '2020-03-13 00:10:50'),
(14, 4, 'default.jpg', '2020-03-13 00:10:50', '2020-03-13 00:10:50'),
(15, 4, 'default.jpg', '2020-03-13 00:10:50', '2020-03-13 00:10:50'),
(16, 4, 'default.jpg', '2020-03-13 00:10:50', '2020-03-13 00:10:50');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `length` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `width` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `price` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `draft` tinyint(1) NOT NULL DEFAULT '0',
  `in_stock` tinyint(1) NOT NULL DEFAULT '1',
  `adjustable` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `products`
--

TRUNCATE TABLE `products`;
--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `description`, `length`, `width`, `price`, `draft`, `in_stock`, `adjustable`, `created_at`, `updated_at`) VALUES
(1, 0, 'beatae', 'beatae', 'Necessitatibus unde molestiae accusantium beatae consectetur sunt. Ut vero beatae quam distinctio sed at velit. Quia illum non quis ipsam id hic optio. Quia facilis natus eius commodi. Sed sit eligendi ipsam itaque voluptatem alias distinctio molestiae.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(2, 0, 'est', 'est', 'Sapiente voluptatum ut reprehenderit temporibus. Adipisci at quo et. Rerum totam dolorum excepturi tempore.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(3, 0, 'cupiditate', 'cupiditate', 'Voluptas aut autem ipsam aliquid. Quisquam nostrum id ipsa quasi. Consequatur quia voluptatem exercitationem odio quisquam.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(4, 0, 'voluptas', 'voluptas', 'Aspernatur inventore odit porro. Maiores cumque autem nesciunt cumque deserunt consequatur. Laboriosam ut quos ipsa enim sunt fuga quod. Voluptatem unde quis vel corporis at voluptas recusandae. Repellat assumenda voluptatem perspiciatis aut nostrum dolor.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(5, 0, 'voluptatibus', 'voluptatibus', 'Quibusdam velit minus est quod. Vel similique omnis illo ut. Qui facere fugiat qui voluptatem quis vel delectus. Odit magni quod quas ad vero doloremque eligendi. Perspiciatis illo quasi voluptate ullam delectus quo.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(6, 0, 'incidunt', 'incidunt', 'Minima nulla tempora quasi minima aut. Dolor et et doloribus itaque in. Iste porro doloribus enim numquam dolores praesentium.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(7, 0, 'sapiente', 'sapiente', 'Et nulla at adipisci possimus sed est earum mollitia. Sequi voluptas molestiae est quia eligendi. Nisi vel minima est hic quis dolorem.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(8, 0, 'sunt', 'sunt', 'Similique esse rerum quibusdam fugit aliquam exercitationem eligendi ullam. Ut harum doloremque vel necessitatibus maiores dolores nam. Eos enim eos eum iste aut eaque ut aut. Voluptatum pariatur at a in ut.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(9, 0, 'facilis', 'facilis', 'Incidunt nesciunt omnis sed ratione accusamus eveniet officia. Quis ad labore quo fuga non alias omnis. Repellat at occaecati dolor quaerat laborum eligendi. Harum repellendus veniam qui sed rerum dicta illo ad.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(10, 0, 'amet', 'amet', 'Dolores aliquid consequuntur nihil quo qui quia placeat sunt. Eius et aut quo voluptatem id. Ut quidem dolorem nulla recusandae quia qui. Tempore est nihil deserunt maxime. Soluta pariatur rerum occaecati eligendi minus ab eos.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(11, 0, 'possimus', 'possimus', 'Non et voluptas vitae sed. Optio et et maiores aut quo reprehenderit. Error doloremque ea architecto. Quod maiores qui earum quidem in et.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(12, 0, 'dolor', 'dolor', 'Voluptas accusantium aliquid repellat quam. Nihil enim quis sit quia dolor consequuntur et. Quidem in modi distinctio sunt nemo. Quos qui cupiditate nesciunt perspiciatis et a.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(13, 0, 'quis', 'quis', 'Non omnis sit pariatur debitis amet. Molestiae asperiores aut veniam doloremque vel et ut. Neque dolores expedita impedit consequuntur. Natus omnis nulla cum deleniti soluta enim et.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(14, 0, 'sunt', 'sunt', 'Sunt quo et mollitia quo quia non ipsum. Consequatur dolores qui earum voluptas molestias impedit. Praesentium inventore dolores veniam voluptas officia tempore id. Culpa voluptatem qui pariatur eos tempora repellat non.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(15, 0, 'rem', 'rem', 'Sunt velit consequuntur velit enim. Eligendi magnam est blanditiis occaecati molestias rerum illo. Natus eveniet vel velit est et sed.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(16, 0, 'quas', 'quas', 'Deserunt qui distinctio nihil perferendis. Vel dolores minima voluptatem voluptate est rerum. Rerum quibusdam non vel ut.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(17, 0, 'sunt', 'sunt', 'Dolorem ullam nam reiciendis rerum. Mollitia velit ab incidunt non. Omnis et incidunt et quasi repellendus quos impedit doloribus.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(18, 0, 'est', 'est', 'Animi qui perspiciatis autem. Nostrum quidem odit numquam unde illo. Facilis eligendi et in iusto fugiat. Voluptatem alias vel voluptas rem ipsam earum voluptatum.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(19, 0, 'at', 'at', 'Esse error labore commodi qui ipsum quia. Iusto sit sequi eius repellat voluptatem quam harum. Rerum ut suscipit ratione rerum. Vel in voluptates a pariatur atque. Nobis qui et recusandae dicta corrupti molestias.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(20, 0, 'nam', 'nam', 'Aliquam consequuntur voluptate non et. Sit omnis in voluptatibus. Aliquam recusandae voluptatem minus repellat quisquam.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(21, 0, 'consectetur', 'consectetur', 'Numquam aut et voluptatibus qui aperiam temporibus rerum reprehenderit. Necessitatibus ut atque sit sit eveniet culpa. Inventore sed adipisci id id.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(22, 0, 'qui', 'qui', 'Dolor et quaerat nesciunt explicabo illum ea vitae aut. Molestias consectetur et qui maxime blanditiis quae quisquam. Dolores quisquam sunt dolorem non.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(23, 0, 'quia', 'quia', 'Eaque quo nisi nostrum doloribus reprehenderit repellat enim dolor. Velit aut nesciunt sed dolor ipsum ea. Voluptatibus ea ut illo debitis. Quasi sint voluptatem aspernatur.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(24, 0, 'aut', 'aut', 'Modi est vitae ratione qui earum esse nemo. Alias eum delectus voluptatem molestiae. Nesciunt est ab autem est in. Suscipit ex sit assumenda cum nulla.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(25, 0, 'voluptatem', 'voluptatem', 'Eum nesciunt consequatur excepturi rem. Voluptates ipsum sunt et ipsa ullam ut qui. Qui voluptatum eum dolor ut qui repudiandae.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(26, 0, 'voluptatem', 'voluptatem', 'Consectetur suscipit et vel. Optio rerum sint praesentium enim voluptatem illo dolores. Rerum dolorem sit quia rerum beatae quo velit. Labore laboriosam molestiae corrupti aut aut vitae.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(27, 0, 'aut', 'aut', 'Qui quia blanditiis vel qui impedit unde id. Eum vel voluptatem ut voluptas earum est. Ad modi quidem in modi expedita.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(28, 0, 'est', 'est', 'Illo provident asperiores doloremque at minus. Accusamus sunt aut ut quia. Quae maxime ea soluta dolorem quo. Eaque sed quia quae sed ipsam nesciunt. Commodi nostrum error enim pariatur.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(29, 0, 'est', 'est', 'Dolorem nulla nam nam autem magni dolores pariatur. Accusantium assumenda eum tenetur nobis. Repudiandae tempora tempora vel dolore fuga ut dolorum animi.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(30, 0, 'itaque', 'itaque', 'Omnis doloribus qui minima ut earum nesciunt. Velit quaerat qui autem velit. Recusandae vel nostrum et. Quae qui molestiae earum recusandae. Officia quo aut accusamus ut.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(31, 0, 'maiores', 'maiores', 'Et aspernatur itaque eius ut aliquid est ea. Inventore officia sequi amet qui dolorem. Eaque cum qui sit eaque impedit dolore.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(32, 0, 'repellat', 'repellat', 'Deserunt quod omnis vitae omnis qui. Sequi dolor praesentium sed quas a dicta explicabo. Blanditiis est quisquam illo itaque animi est.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(33, 0, 'dicta', 'dicta', 'Qui molestiae ea est ut repellendus autem. Qui voluptas tempore asperiores neque reiciendis.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(34, 0, 'porro', 'porro', 'Voluptatibus alias rerum quas voluptas voluptas temporibus. Illo voluptas sed vero autem id. Facere eaque doloribus sunt quisquam accusantium soluta quo fugiat.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(35, 0, 'excepturi', 'excepturi', 'Et et non et ducimus vitae. Sapiente dolore ad qui nisi sunt tempore. Architecto et dolores omnis ex.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(36, 0, 'consequuntur', 'consequuntur', 'Pariatur omnis neque eveniet sapiente. Sed pariatur atque nam aut tenetur ipsum modi. At suscipit et voluptas ab repudiandae.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(37, 0, 'nisi', 'nisi', 'Quia adipisci assumenda facilis mollitia dignissimos qui. Officiis perspiciatis nulla qui mollitia non quia. Quidem eaque voluptates est eos et ut. Et ab quam necessitatibus ut asperiores doloremque et.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(38, 0, 'hic', 'hic', 'Et magni quibusdam totam quia iste exercitationem. Soluta occaecati explicabo ullam vitae quae et. Voluptates saepe velit mollitia eos. Et sit dolor et tempore ut.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(39, 0, 'est', 'est', 'Et qui magnam in tempora consequatur. Error cupiditate omnis excepturi commodi molestias. Saepe nesciunt consequatur ab quaerat ut et et. Ratione sunt molestias qui est explicabo illum ut. In alias modi qui sapiente eveniet quo.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(40, 0, 'voluptas', 'voluptas', 'Tempore quas consequuntur minima vitae culpa error. Rerum quaerat eos voluptatem. Sed non quas inventore asperiores maxime.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(41, 0, 'praesentium', 'praesentium', 'Inventore harum placeat omnis ut repellat. A dolorem quia consequuntur quisquam vitae sit. Dolorum quia nisi nobis in debitis autem.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(42, 0, 'perspiciatis', 'perspiciatis', 'Nostrum nihil vitae culpa laboriosam commodi reprehenderit. Accusamus quia est nihil earum eaque iure qui. Odit odio suscipit non quos fugiat velit.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(43, 0, 'repellat', 'repellat', 'Consequuntur ea accusamus earum id doloremque distinctio. Occaecati laboriosam illo quaerat rerum dolor nisi et. Expedita sed molestiae voluptas nihil nobis. Cum fugit ullam quaerat eaque sit quo asperiores nobis.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(44, 0, 'voluptatem', 'voluptatem', 'Aut consequuntur quidem libero dolorem. Dolorem qui ipsum et soluta. Eveniet ea placeat nam aut tempore. Molestias vitae placeat qui ut autem aut impedit quo.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(45, 0, 'laboriosam', 'laboriosam', 'Delectus sit laborum autem autem. Natus unde ullam doloremque nostrum unde quas. Et debitis eos et vel vel quae.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(46, 0, 'voluptas', 'voluptas', 'Suscipit tempora sint dolorem numquam nemo aut. Nihil dolore est et vel est nisi voluptas. Eaque quasi non quos et qui. Doloribus culpa ratione quaerat culpa optio quia ipsam.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(47, 0, 'voluptas', 'voluptas', 'Mollitia illo accusamus voluptas. Laudantium neque est sapiente. Ducimus suscipit dolores sunt eum qui consequatur.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(48, 0, 'ullam', 'ullam', 'Nostrum quod soluta doloribus consequatur. Et non facere earum et voluptatem saepe. Enim error excepturi quia omnis dolores labore.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(49, 0, 'velit', 'velit', 'Quo est adipisci omnis dignissimos rerum harum qui. Voluptatum labore suscipit qui nam ullam. Vitae atque dolores id laudantium.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(50, 0, 'quisquam', 'quisquam', 'Impedit natus ut ad quo accusamus laborum. In repellat voluptas quam est velit sit. Sed sit ut aliquid quos ut saepe dicta.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(51, 0, 'laborum', 'laborum', 'Odit quas dicta enim maxime. Porro exercitationem corrupti repellat enim adipisci ratione. Nihil non dolorum est laboriosam magnam eum eaque. Placeat id porro numquam distinctio.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(52, 0, 'corporis', 'corporis', 'Tempora ducimus illum est dolores. Soluta aut sed omnis impedit fugiat. Non magni sed sed possimus ad. Soluta est ducimus sunt temporibus.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(53, 0, 'dolorem', 'dolorem', 'Nostrum at sit beatae. Ut et officia debitis voluptatem commodi earum. Est illo et accusantium. Sit aut consequuntur sint vero aut cum eos.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(54, 0, 'sapiente', 'sapiente', 'Provident modi id porro pariatur et. Optio libero reiciendis accusamus beatae explicabo qui.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(55, 0, 'iste', 'iste', 'Et repudiandae velit odit sint autem. Quia sed aspernatur mollitia voluptatibus atque suscipit. Cumque sequi beatae quis repellat officia eum ipsa.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(56, 0, 'et', 'et', 'Dicta optio nobis nostrum quasi nisi sit culpa. Animi molestiae quod et quae blanditiis autem qui. Ea ut quae ipsum quis ipsum.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(57, 0, 'enim', 'enim', 'Facere aut quo et minima odit sunt laboriosam. Molestiae quia voluptatum aspernatur sint eveniet cupiditate doloribus. Non praesentium non voluptas est ex omnis non quaerat.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(58, 0, 'voluptatum', 'voluptatum', 'Ut sed harum ipsum qui est illum adipisci. Mollitia voluptates quia vel ea quis est quod. Tenetur inventore nihil ad numquam minima eaque ut.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(59, 0, 'animi', 'animi', 'Ut deserunt id aut saepe exercitationem est deserunt iste. Eveniet mollitia id aliquam dolores. Consectetur rem debitis est porro laudantium. Voluptatibus eius dolores rem voluptatem sed.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(60, 0, 'ad', 'ad', 'Animi error nam amet repellat. Non dolor vel culpa aut pariatur recusandae ratione accusamus. Ex provident voluptas temporibus qui quidem. Et ut quam et dolor aut.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(61, 0, 'nihil', 'nihil', 'Eligendi assumenda eius eos aperiam nam error. Et omnis ipsam neque sapiente impedit a. In nostrum et et molestias voluptatem quasi.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(62, 0, 'aut', 'aut', 'Nam ratione sed voluptas ut. Natus velit reiciendis animi minima sequi eum. Aliquid nobis enim officia aut voluptate quas. Occaecati sunt ex iusto rerum et est. Et temporibus dolorum id.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(63, 0, 'laboriosam', 'laboriosam', 'Tenetur dolorem odit recusandae repellat earum aut. Atque iure molestiae cum dolore reprehenderit tenetur molestias saepe. Beatae ratione ut ut voluptas saepe.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(64, 0, 'provident', 'provident', 'Deleniti exercitationem consequatur cumque ducimus in aut. Dolor repudiandae quos consequatur numquam nemo. Rerum in quia provident. Porro earum provident inventore id.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(65, 0, 'in', 'in', 'Exercitationem pariatur dolor voluptates maxime atque qui qui. At ab velit in voluptas. Ut aperiam eligendi et.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(66, 0, 'esse', 'esse', 'Fugit in unde sed dolorum. Eius qui doloremque harum porro iure totam deleniti. Id numquam quam aliquid. Corrupti id eum laboriosam accusamus et tempora.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(67, 0, 'perferendis', 'perferendis', 'Nisi aperiam voluptatem minus qui magnam sed consequatur nostrum. At vel et nemo est.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(68, 0, 'et', 'et', 'Eveniet dolorum eligendi accusamus fuga. Natus voluptates officiis est aut facere vel. Qui dolore natus et voluptatem.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(69, 0, 'voluptas', 'voluptas', 'Aut consequatur rerum quod quae consequatur. Repellendus quasi ab et voluptatum ratione iusto veritatis velit. Aliquid et aut magnam nobis non.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(70, 0, 'ducimus', 'ducimus', 'Laudantium dolorem suscipit ea voluptate ut est est reprehenderit. Vel inventore distinctio ratione ab officiis laboriosam at. Minus atque voluptatem velit temporibus quia.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(71, 0, 'vel', 'vel', 'Odio odio atque blanditiis. Quam et eos non hic cum nam facilis. Explicabo eaque quia voluptatem deleniti tenetur iste dolore ut. Ut accusantium aut dicta tenetur cupiditate.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(72, 0, 'repudiandae', 'repudiandae', 'Hic ipsa sapiente ut ea. Assumenda ut nesciunt voluptatem doloremque cumque voluptate. Commodi quia quidem sit voluptas tenetur reiciendis earum qui.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(73, 0, 'voluptatibus', 'voluptatibus', 'Aut nobis molestiae quasi enim voluptatem. Ea consequatur consequuntur ipsam mollitia itaque sed. Consequatur saepe cumque explicabo fugit illum ut.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(74, 0, 'veritatis', 'veritatis', 'Deleniti quisquam aliquid maiores repellendus aut nihil libero. Sit ea exercitationem reiciendis sint id enim.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(75, 0, 'voluptas', 'voluptas', 'Nemo id illum voluptatibus laboriosam eum reprehenderit magnam. Voluptatibus accusamus expedita doloremque deserunt incidunt. Aperiam incidunt rem unde et debitis consequatur.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(76, 0, 'facilis', 'facilis', 'Voluptas molestiae eos aspernatur maxime. Ea fugiat id et et laborum ex ullam. Illum dicta ipsam ab rem. Qui vel quod nam dolores. Cum in dolores facilis rerum.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(77, 0, 'quos', 'quos', 'Ea temporibus asperiores aliquam aut numquam. Est enim optio omnis qui ad voluptatem.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(78, 0, 'dolorem', 'dolorem', 'Facere sint est consequuntur rerum inventore. Aut dolorem natus officiis. Voluptatem quo qui ea illum consequatur doloribus. Numquam accusamus distinctio ipsam dolor deleniti sit est.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(79, 0, 'dolores', 'dolores', 'Quidem quasi ipsa ut fugit porro. Rem reiciendis perferendis culpa autem rerum. Atque repellat voluptas ipsa veritatis qui ad.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(80, 0, 'nulla', 'nulla', 'In animi pariatur omnis pariatur ullam neque. Totam velit odit vero animi. Aliquid iusto magni voluptatem aut cum voluptatem natus.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(81, 0, 'labore', 'labore', 'Corrupti consequatur suscipit voluptatum est illo. Rerum distinctio labore rem voluptate odit non. Est voluptatum occaecati similique ullam beatae placeat blanditiis nam.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(82, 0, 'ipsam', 'ipsam', 'Et ut reprehenderit et omnis laudantium. Ipsum eveniet ipsam atque ratione qui. Libero consequatur qui illum aperiam nesciunt illo tempore expedita.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(83, 0, 'ex', 'ex', 'Quidem sint laudantium et non ex nesciunt. Fugit dolores vel voluptate eveniet ut cum soluta. Eveniet possimus repellat id perspiciatis veritatis rerum assumenda. Debitis laudantium in tempore hic non.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(84, 0, 'sint', 'sint', 'Labore quia temporibus nam similique magnam qui quam. Est saepe consequatur nostrum qui.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(85, 0, 'cupiditate', 'cupiditate', 'Quam quasi aperiam rerum et asperiores dolores ullam. Voluptatem quae blanditiis culpa rerum. Et reprehenderit nemo assumenda perferendis id. Quia eligendi quasi asperiores perspiciatis.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(86, 0, 'quo', 'quo', 'Debitis nihil ut autem aut quas aut pariatur. Molestias libero velit ipsam deserunt voluptatem. Voluptas nemo incidunt fuga omnis.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(87, 0, 'consectetur', 'consectetur', 'Repudiandae sit ipsum et sit sit. Facere non cum reprehenderit omnis expedita voluptatem et eveniet. Fuga itaque ut sit illum et qui ipsam. Voluptatem illo reiciendis totam dolor similique. Officia deserunt et debitis soluta numquam.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(88, 0, 'quo', 'quo', 'Nisi expedita itaque distinctio. Reiciendis eos soluta maiores omnis ut quos. Quidem molestias autem voluptas tenetur quo nam est.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(89, 0, 'molestiae', 'molestiae', 'Tempora deleniti cum mollitia fugiat. Et magni ea ut dignissimos deserunt ducimus. Quam sit esse id autem. Nemo commodi aspernatur assumenda rerum laudantium.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(90, 0, 'qui', 'qui', 'Ipsum accusamus id ducimus ut. Facere iusto earum quia qui enim. Nam velit assumenda saepe est omnis animi.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(91, 0, 'architecto', 'architecto', 'Rem quis et voluptatibus commodi iste quia. Quod illum quidem ut itaque ut commodi. Optio est alias ut in dolores.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(92, 0, 'aperiam', 'aperiam', 'Nisi quod quasi in enim qui. Dolorum eos cumque molestiae. Vitae ex molestiae voluptatum necessitatibus saepe.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(93, 0, 'asperiores', 'asperiores', 'Vel dolorem doloribus sit totam. Corporis veritatis aut saepe tempora possimus aspernatur non. Voluptate molestiae explicabo quis mollitia enim delectus porro. Voluptatem facere nulla explicabo molestiae rerum voluptates. Ratione eaque voluptas est magnam magni non.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(94, 0, 'earum', 'earum', 'Placeat fugit quos iusto at. Ipsa earum et repellat animi et et aut reprehenderit. Inventore similique quasi velit qui. Perspiciatis est ad nam delectus cumque voluptatem omnis.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(95, 0, 'quisquam', 'quisquam', 'A ut rerum dolor laboriosam similique sequi debitis dicta. Officia ea repellat ipsum molestias reiciendis. Inventore assumenda cupiditate aut tempore suscipit. Ducimus incidunt rerum corrupti soluta.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(96, 0, 'tempora', 'tempora', 'Nemo soluta omnis enim. Similique impedit optio qui sit eligendi ratione. Ut similique sint autem. Quisquam adipisci rem provident.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(97, 0, 'impedit', 'impedit', 'Voluptatem et nihil dolor ea est. Aliquam asperiores ipsa quisquam assumenda maiores saepe praesentium. Enim excepturi et ea. Quibusdam ut ut occaecati praesentium.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(98, 0, 'iusto', 'iusto', 'Omnis veritatis distinctio id ipsam. Velit est minima iure consequatur. Veniam corrupti consequatur qui enim modi odit nostrum voluptatem. Debitis amet magnam ad quo voluptatem nemo.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(99, 0, 'dolores', 'dolores', 'Perspiciatis consequatur fugiat dignissimos consectetur repellat itaque quam. Distinctio assumenda similique temporibus cum modi sunt voluptas sequi. Aliquam atque sequi quae quisquam alias unde. Officia sit aliquam aut quo eveniet. Reprehenderit sunt inventore autem consectetur corrupti perspiciatis.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(100, 0, 'quas', 'quas', 'Et blanditiis voluptatem qui cumque numquam aut iusto. Veritatis aliquam sunt amet aut ut aut. Possimus enim ab est vel commodi commodi neque. Aut aliquid dolorum sequi consequatur aliquam.', 1234, 1234, 1234, 0, 1, 0, '2020-03-12 23:58:25', '2020-03-12 23:58:25'),
(101, 0, 'hhkuhkjh', NULL, '5465656565', 1, 2, 300, 1, 0, 1, '2020-05-13 03:46:28', '2020-05-13 03:46:28');

-- --------------------------------------------------------

--
-- Table structure for table `products_tags`
--

DROP TABLE IF EXISTS `products_tags`;
CREATE TABLE IF NOT EXISTS `products_tags` (
  `product_id` int(10) UNSIGNED NOT NULL,
  `tag_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `products_tags`
--

TRUNCATE TABLE `products_tags`;
-- --------------------------------------------------------

--
-- Table structure for table `sub_categories`
--

DROP TABLE IF EXISTS `sub_categories`;
CREATE TABLE IF NOT EXISTS `sub_categories` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `sub_categories`
--

TRUNCATE TABLE `sub_categories`;
-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
CREATE TABLE IF NOT EXISTS `tags` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tag` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tags_tag_unique` (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `tags`
--

TRUNCATE TABLE `tags`;
-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '123456',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `locked` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Truncate table before insert `users`
--

TRUNCATE TABLE `users`;
--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `active`, `locked`, `created_at`, `updated_at`) VALUES
(1, 'Berenice McCullough', 'leslie07@gmail.com', '$2y$10$g3E.bAKZInx.PsA5JI/mqegB68x7/8eedFo/ggZcE4HoWZqr5r0NS', 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(2, 'Miss Tamara Gulgowski', 'lpaucek@hotmail.com', '$2y$10$H33zCP2uuDUIXHlSIOzCiecoT9AmoF8UO7NNUolMZhPiXPQSFmsuy', 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(3, 'Davin Cassin', 'americo.schumm@rohan.info', '$2y$10$jQs19ild30/8mhct3TRdWeYiAnNQLyfU1Y4K3ieW3A7Rdn1Qlq8sK', 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(4, 'Nikko Hahn', 'joanie.zieme@flatley.info', '$2y$10$IGYWAdri9nEwQgfXE0llkuqRHWjO9tEJJ0gqWvBumN91ME2bX2voK', 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(5, 'Prof. Tyrell Lakin Sr.', 'king.jordy@yahoo.com', '$2y$10$qk2I55N9jEmBcTX7HhedZeYkP41mX5VLGjeDsjv8d7NksoGElCy1C', 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(6, 'Giuseppe Mosciski', 'hettinger.cleora@gmail.com', '$2y$10$bdC9KIQZGutZjHJXn3YUFedFoCROZUGT0tpIUyDkTwqHLaOtHcf.2', 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(7, 'Cameron Altenwerth', 'ymarvin@adams.org', '$2y$10$GC1gRMBCKuRJPhjq3AxCqO/F2x6epuFwSJZ4p.4mxGtV1zlMnNXX.', 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(8, 'Nikko Schoen I', 'hillard.lehner@yahoo.com', '$2y$10$bYzG0NSA/fNyBHkPg2kDguJQD5J5rQaJn32FTcrxckQAepNGlHJBu', 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(9, 'Ms. Kaci Runolfsson DVM', 'vincenza.grady@kessler.info', '$2y$10$wnYhAK279SLljzbM0vjyHOF5WAYliiHu0nVfqoxyXn6vzRtEq3khe', 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24'),
(10, 'Roel Mraz', 'eichmann.yolanda@blanda.com', '$2y$10$G0w6yLViFf0Rwogla1sdUeBeybs2Q3KPMv3RFCnwISHCCMPzGh.hG', 1, 0, '2020-03-12 23:58:24', '2020-03-12 23:58:24');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `category_product`
--
ALTER TABLE `category_product`
  ADD CONSTRAINT `category_product_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `category_product_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `productimages`
--
ALTER TABLE `productimages`
  ADD CONSTRAINT `productimages_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
