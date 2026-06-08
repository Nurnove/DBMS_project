-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 08, 2026 at 08:15 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `soilsync`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_schedule`
--

CREATE TABLE `activity_schedule` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `farmer_crop_id` int(11) DEFAULT NULL,
  `day_number` int(11) NOT NULL,
  `activity` text NOT NULL,
  `weather_note` text DEFAULT NULL,
  `status` enum('pending','done','skipped') NOT NULL DEFAULT 'pending',
  `completed_at` datetime DEFAULT NULL,
  `completion_note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_schedule`
--

INSERT INTO `activity_schedule` (`id`, `user_id`, `farmer_crop_id`, `day_number`, `activity`, `weather_note`, `status`, `completed_at`, `completion_note`) VALUES
(2, 2, 5, 10, 'seed rupon kora', NULL, 'pending', NULL, NULL),
(3, 2, 6, 7, 'Apply fertilizer', NULL, 'pending', NULL, NULL),
(4, 2, 6, 15, 'Pest monitoring', NULL, 'pending', NULL, NULL),
(5, 2, 6, 25, 'Irrigation check', NULL, 'pending', NULL, NULL),
(6, 2, 6, 40, 'Weeding', NULL, 'pending', NULL, NULL),
(7, 2, 7, 7, 'Apply fertilizer', '', 'done', '2026-05-20 17:54:22', 'hbjkj'),
(8, 2, 7, 15, 'Pest monitoring', '', 'pending', NULL, NULL),
(9, 2, 7, 25, 'Irrigation check', '', 'pending', NULL, NULL),
(10, 2, 7, 40, 'Weeding', '', 'pending', NULL, NULL),
(11, 2, 7, 21, 'Pest monitoring', '', 'pending', NULL, NULL),
(12, 2, 7, 30, 'Irrigation check', '', 'pending', NULL, NULL),
(13, 2, 7, 45, 'Disease inspection', '', 'pending', NULL, NULL),
(14, 2, 3, 10, 'Blight monitoring', '', 'pending', NULL, NULL),
(15, 2, 3, 20, 'Fungicide spray', '', 'pending', NULL, NULL),
(16, 2, 7, 5, 'Soil leveling & water management check', '', 'skipped', '2026-05-20 17:38:39', 'heavy rain'),
(17, 2, 2, 7, 'Irrigation check', '', 'pending', NULL, NULL),
(18, 2, 2, 15, 'Apply fertilizer (NPK)', '', 'pending', NULL, NULL),
(19, 2, 8, 7, 'Apply fertilizer', '', 'skipped', '2026-06-07 16:49:29', 'fdcsxsdfghj'),
(20, 2, 8, 15, 'Pest monitoring', '', 'pending', NULL, NULL),
(21, 2, 8, 25, 'Irrigation check', '', 'pending', NULL, NULL),
(22, 2, 8, 40, 'Weeding', '', 'pending', NULL, NULL),
(23, 2, 7, 14, 'First weeding', '', 'skipped', '2026-06-07 00:16:02', 'heavy rain'),
(24, 2, 8, 21, 'Apply nitrogen fertilizer', '', 'skipped', '2026-06-07 21:28:50', '');

-- --------------------------------------------------------

--
-- Table structure for table `advisory_feed`
--

CREATE TABLE `advisory_feed` (
  `id` int(11) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `category` enum('weather','pest','market','general') DEFAULT 'general',
  `location_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_urgent` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `advisory_feed`
--

INSERT INTO `advisory_feed` (`id`, `title`, `content`, `category`, `location_id`, `created_at`, `is_urgent`) VALUES
(1, 'Heavy Rain Alert Bangladesh', 'Next 48 hours heavy rainfall expected in most regions. Avoid pesticide spraying and ensure drainage.', 'weather', NULL, '2026-05-11 18:27:02', 0),
(2, 'Rice Market Price Rising', 'Rice price increasing due to supply shortage. Farmers may get better price this week.', 'market', NULL, '2026-05-11 18:27:02', 0),
(3, 'Dhaka Pest Alert: Brown Plant Hopper', 'High risk of brown plant hopper in rice fields. Use recommended pesticide and monitor daily.', 'pest', 1, '2026-05-11 18:27:02', 0),
(4, 'Dhaka Irrigation Advice', 'Maintain regular irrigation due to high temperature conditions.', 'weather', 1, '2026-05-11 18:27:02', 0),
(5, 'Chittagong Heavy Rain Advisory', 'Possible flooding in lowland rice fields. Harvest early if mature.', 'weather', 2, '2026-05-11 18:27:02', 0),
(6, 'Rajshahi Wheat Disease Risk', 'High humidity may cause leaf rust in wheat fields.', 'pest', 3, '2026-05-11 18:27:02', 0),
(7, 'Shrimp Farming Advisory Khulna', 'Maintain salinity balance in shrimp ponds due to temperature change.', 'general', 4, '2026-05-11 18:27:02', 0),
(8, 'Sylhet Tea Garden Moisture Alert', 'High rainfall expected. Avoid fertilizer application for next 3 days.', 'weather', 5, '2026-05-11 18:27:02', 0),
(9, 'Barisal Flood Risk Advisory', 'River water level rising. Protect young crops from water logging.', 'weather', 6, '2026-05-11 18:27:02', 0),
(10, 'Rangpur Potato Disease Alert', 'Late blight risk increasing due to cold night temperature.', 'pest', 7, '2026-05-11 18:27:02', 0),
(11, 'Mymensingh Maize Growth Advice', 'Good weather for maize growth. Apply nitrogen fertilizer this week.', 'general', 8, '2026-05-11 18:27:02', 0),
(12, 'float', 'klk bristy hobe fertilizer die na', 'weather', 5, '2026-05-12 12:17:12', 1),
(13, 'varsity chuti', 'eid upolokkeh goru bikri hobe gorur dam barbe eid er agher edin bikri koiren', 'market', NULL, '2026-05-12 16:51:25', 1),
(14, 'kichu ekta', 'sadgthjhgfdcsxaz', 'weather', 5, '2026-05-13 04:01:49', 1);

-- --------------------------------------------------------

--
-- Table structure for table `answers`
--

CREATE TABLE `answers` (
  `id` int(11) NOT NULL,
  `question_id` int(11) DEFAULT NULL,
  `expert_id` int(11) DEFAULT NULL,
  `answer` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `answers`
--

INSERT INTO `answers` (`id`, `question_id`, `expert_id`, `answer`, `created_at`) VALUES
(1, 1, 6, 'fgdtbvgbrfdtgbv', '2026-05-18 06:12:10'),
(2, 2, 6, 'gbfdhbgfvhbkkk', '2026-05-19 10:30:06'),
(3, 3, 6, 'cfrsdegvrcd exgvbcfrdegv ', '2026-05-19 18:26:33'),
(4, 4, 6, 'fedfdsvcsdc', '2026-05-20 03:47:59');

-- --------------------------------------------------------

--
-- Table structure for table `banned_pesticides`
--

CREATE TABLE `banned_pesticides` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `banned_pesticides`
--

INSERT INTO `banned_pesticides` (`id`, `name`, `reason`) VALUES
(1, 'DDT', 'Highly toxic and environmentally harmful'),
(2, 'Endrin', 'Banned due to health hazards'),
(3, 'Heptachlor', 'Dangerous for soil and water');

-- --------------------------------------------------------

--
-- Table structure for table `crops`
--

CREATE TABLE `crops` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `crops`
--

INSERT INTO `crops` (`id`, `name`, `description`) VALUES
(1, 'Rice', 'Staple grain crop of Bangladesh'),
(2, 'Wheat', 'Winter cereal crop'),
(3, 'Potato', 'Root vegetable crop'),
(4, 'Jute', 'Golden fiber of Bangladesh'),
(5, 'Mustard', 'Oilseed crop'),
(6, 'Maize', 'Cereal grain'),
(7, 'Onion', 'Vegetable/spice crop'),
(8, 'Tomato', 'Vegetable crop'),
(9, 'Brinjal', 'Popular vegetable'),
(10, 'Chili', 'Spice crop');

-- --------------------------------------------------------

--
-- Table structure for table `crop_calendar`
--

CREATE TABLE `crop_calendar` (
  `id` int(11) NOT NULL,
  `crop_id` int(11) NOT NULL,
  `season` enum('Monsoon','Winter','Summer','Spring','Year-round') DEFAULT NULL,
  `suitability_score` int(11) DEFAULT 100,
  `reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `crop_calendar`
--

INSERT INTO `crop_calendar` (`id`, `crop_id`, `season`, `suitability_score`, `reason`) VALUES
(1, 1, 'Monsoon', 100, 'Best season for rice cultivation'),
(2, 1, 'Winter', 20, 'Too cold and dry for rice'),
(3, 1, 'Summer', 40, 'Needs heavy irrigation'),
(4, 2, 'Winter', 100, 'Ideal temperature for wheat'),
(5, 2, 'Monsoon', 25, 'Excess rainfall damages wheat'),
(6, 2, 'Summer', 15, 'Too hot for wheat'),
(7, 3, 'Winter', 100, 'Perfect season for potato'),
(8, 3, 'Monsoon', 30, 'High disease risk'),
(9, 3, 'Summer', 20, 'Too much heat'),
(10, 4, 'Monsoon', 100, 'Excellent humidity for jute'),
(11, 4, 'Winter', 10, 'Not suitable'),
(12, 4, 'Summer', 70, 'Possible with irrigation'),
(13, 5, 'Winter', 100, 'Best flowering season'),
(14, 5, 'Monsoon', 15, 'Too wet'),
(15, 5, 'Summer', 20, 'Too hot'),
(16, 6, 'Summer', 100, 'Best warm season crop'),
(17, 6, 'Monsoon', 75, 'Can grow with drainage'),
(18, 6, 'Winter', 40, 'Slow growth'),
(19, 7, 'Winter', 95, 'Cool weather preferred'),
(20, 7, 'Summer', 40, 'Needs irrigation'),
(21, 7, 'Monsoon', 20, 'Rot risk'),
(22, 8, 'Winter', 100, 'Best yield season'),
(23, 8, 'Summer', 45, 'Heat stress possible'),
(24, 8, 'Monsoon', 25, 'Fungal disease risk'),
(25, 9, 'Year-round', 90, 'Can grow throughout year'),
(26, 10, 'Monsoon', 80, 'Good rainfall support'),
(27, 10, 'Winter', 75, 'Good dry season production'),
(28, 10, 'Summer', 65, 'Needs watering');

-- --------------------------------------------------------

--
-- Table structure for table `crop_diseases`
--

CREATE TABLE `crop_diseases` (
  `id` int(11) NOT NULL,
  `crop_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `symptoms` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `crop_diseases`
--

INSERT INTO `crop_diseases` (`id`, `crop_id`, `name`, `symptoms`) VALUES
(1, 1, 'Blast Disease', 'Diamond-shaped lesions on leaves; whitish or grayish center'),
(2, 1, 'Bacterial Leaf Blight', 'Water-soaked lesions turning yellow; wilting'),
(3, 1, 'Sheath Blight', 'Oval or irregular lesions on leaf sheath'),
(4, 2, 'Rust', 'Orange-brown pustules on leaves'),
(5, 3, 'Late Blight', 'Dark brown lesions on leaves; white mold'),
(6, 3, 'Early Blight', 'Concentric ring lesions; yellowing'),
(7, 8, 'Tomato Mosaic Virus', 'Mottled yellowing; stunted growth'),
(8, 9, 'Bacterial Wilt', 'Sudden wilting; brown discoloration of stem');

-- --------------------------------------------------------

--
-- Table structure for table `crop_rotation_rules`
--

CREATE TABLE `crop_rotation_rules` (
  `id` int(11) NOT NULL,
  `crop_id` int(11) NOT NULL COMMENT 'The crop that was just harvested (FK → crops.id)',
  `next_crop_id` int(11) NOT NULL COMMENT 'The candidate next crop (FK → crops.id)',
  `relation` enum('good','avoid') NOT NULL COMMENT 'good = recommended, avoid = disease risk',
  `reason` text DEFAULT NULL COMMENT 'Why this rotation is good or bad',
  `icon` varchar(10) DEFAULT '?' COMMENT 'Emoji icon for the previous crop'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `crop_rotation_rules`
--

INSERT INTO `crop_rotation_rules` (`id`, `crop_id`, `next_crop_id`, `relation`, `reason`, `icon`) VALUES
(1, 1, 5, 'good', 'Rice leaves waterlogged soil. Mustard is a natural biofumigant and restores aeration after paddy.', '🌾'),
(2, 1, 2, 'good', 'Rice leaves waterlogged soil. Wheat thrives in the drained dry season field after rice harvest.', '🌾'),
(3, 1, 3, 'good', 'Rice leaves waterlogged soil. Potato benefits from the residual moisture and broken soil structure.', '🌾'),
(4, 1, 7, 'good', 'Rice leaves waterlogged soil. Onion prefers well-drained conditions — ideal after rice drainage.', '🌾'),
(5, 1, 10, 'good', 'Rice leaves waterlogged soil. Chili grows well in the season following rice with good drainage.', '🌾'),
(6, 1, 1, 'avoid', 'Continuous rice depletes nitrogen, waterlogging intensifies, and blast disease accumulates in soil.', '🌾'),
(7, 1, 4, 'avoid', 'Both rice and jute prefer waterlogged conditions — rotating between them does not break disease cycles.', '🌾'),
(8, 2, 1, 'good', 'Wheat depletes phosphorus and can carry rust. Rice restores moisture balance and breaks the disease cycle.', '🌿'),
(9, 2, 6, 'good', 'Wheat depletes phosphorus. Maize is a different family and helps restore organic matter.', '🌿'),
(10, 2, 3, 'good', 'Wheat depletes phosphorus. Potato is a root crop from a different family — ideal rotation choice.', '🌿'),
(11, 2, 8, 'good', 'Wheat depletes phosphorus. Tomato benefits from the broken soil structure left after wheat harvest.', '🌿'),
(12, 2, 9, 'good', 'Wheat depletes phosphorus. Brinjal follows well in the warm season after winter wheat.', '🌿'),
(13, 2, 2, 'avoid', 'Repeating wheat accumulates rust disease spores and severely depletes phosphorus.', '🌿'),
(14, 3, 1, 'good', 'Potato is a heavy feeder. Rice from a completely different family breaks the nightshade disease cycle.', '🥔'),
(15, 3, 2, 'good', 'Potato is a heavy feeder. Wheat is a cereal — totally different family, restores soil balance.', '🥔'),
(16, 3, 6, 'good', 'Potato is a heavy feeder. Maize is a cereal that helps restore nitrogen after heavy potato feeding.', '🥔'),
(17, 3, 7, 'good', 'Potato is a heavy feeder. Onion suppresses soil fungi left by potato — excellent rotation.', '🥔'),
(18, 3, 5, 'good', 'Potato is a heavy feeder. Mustard biofumigates soil and breaks soilborne pathogen cycles from potato.', '🥔'),
(19, 3, 3, 'avoid', 'Continuous potato causes Late Blight accumulation and severe soil nutrient depletion.', '🥔'),
(20, 3, 8, 'avoid', 'Potato and Tomato share Late Blight (Phytophthora) — planting one after the other spreads the disease.', '🥔'),
(21, 3, 9, 'avoid', 'Potato and Brinjal are both nightshades — rotating between them does not break the disease cycle.', '🥔'),
(22, 3, 10, 'avoid', 'Potato and Chili share Phytophthora blight — avoid planting chili after potato.', '🥔'),
(23, 4, 1, 'good', 'Jute enriches soil with organic matter from retting. Rice capitalises on the improved soil structure.', '🌿'),
(24, 4, 2, 'good', 'Jute enriches soil with organic matter. Wheat benefits in the dry season after jute.', '🌿'),
(25, 4, 5, 'good', 'Jute enriches soil with organic matter. Mustard grows well in the improved post-jute soil.', '🌿'),
(26, 4, 3, 'good', 'Jute enriches soil with organic matter. Potato thrives in the well-structured post-jute field.', '🌿'),
(27, 4, 4, 'avoid', 'Continuous jute causes stem rot accumulation and depletes specific soil micronutrients.', '🌿'),
(28, 5, 1, 'good', 'Mustard is a natural biofumigant that suppresses soil-borne pathogens. Rice benefits greatly.', '🌼'),
(29, 5, 2, 'good', 'Mustard is a natural biofumigant. Wheat follows well in the pathogen-suppressed soil.', '🌼'),
(30, 5, 6, 'good', 'Mustard is a natural biofumigant. Maize grows vigorously in cleansed post-mustard soil.', '🌼'),
(31, 5, 7, 'good', 'Mustard is a natural biofumigant. Onion benefits from reduced fungal load in soil.', '🌼'),
(32, 5, 3, 'good', 'Mustard is a natural biofumigant. Potato has lower blight risk following mustard biofumigation.', '🌼'),
(33, 5, 5, 'avoid', 'Continuous mustard causes clubroot and reduces biofumigant effectiveness over time.', '🌼'),
(34, 5, 10, 'avoid', 'Mustard and Chili can share Alternaria blight — rotating between them spreads the disease.', '🌼'),
(35, 6, 2, 'good', 'Maize is a heavy nitrogen consumer. Wheat restores balance and is from a compatible cereal family.', '🌽'),
(36, 6, 3, 'good', 'Maize is a heavy nitrogen consumer. Potato thrives in the broken loose soil after maize harvest.', '🌽'),
(37, 6, 5, 'good', 'Maize is a heavy nitrogen consumer. Mustard biofumigates and restores nutrients after maize.', '🌽'),
(38, 6, 7, 'good', 'Maize is a heavy nitrogen consumer. Onion is a light feeder — ideal to follow the heavy maize crop.', '🌽'),
(39, 6, 8, 'good', 'Maize is a heavy nitrogen consumer. Tomato follows well in warm season after maize harvest.', '🌽'),
(40, 6, 6, 'avoid', 'Continuous maize severely depletes nitrogen and accumulates stalk rot and corn borer populations.', '🌽'),
(41, 6, 1, 'avoid', 'Both maize and rice are heavy feeders — rotating between them does not restore nitrogen.', '🌽'),
(42, 7, 1, 'good', 'Onion suppresses many soil fungi and leaves soil well-drained. Rice benefits from the cleansed field.', '🧅'),
(43, 7, 6, 'good', 'Onion suppresses many soil fungi. Maize grows vigorously in the fungal-suppressed post-onion soil.', '🧅'),
(44, 7, 8, 'good', 'Onion suppresses many soil fungi. Tomato benefits from reduced Fusarium levels after onion.', '🧅'),
(45, 7, 9, 'good', 'Onion suppresses many soil fungi. Brinjal follows well in the well-drained post-onion field.', '🧅'),
(46, 7, 10, 'good', 'Onion suppresses many soil fungi. Chili benefits from the pathogen-suppressed post-onion soil.', '🧅'),
(47, 7, 7, 'avoid', 'Continuous onion causes pink root disease and Fusarium basal rot accumulation.', '🧅'),
(48, 8, 1, 'good', 'Tomato shares Bacterial Wilt with nightshades. Rice completely breaks the nightshade disease cycle.', '🍅'),
(49, 8, 2, 'good', 'Tomato shares Bacterial Wilt with nightshades. Wheat is a safe cereal rotation after tomato.', '🍅'),
(50, 8, 6, 'good', 'Tomato shares Bacterial Wilt with nightshades. Maize is from a different family — safe rotation.', '🍅'),
(51, 8, 7, 'good', 'Tomato shares Bacterial Wilt with nightshades. Onion suppresses wilt bacteria in soil.', '🍅'),
(52, 8, 5, 'good', 'Tomato shares Bacterial Wilt with nightshades. Mustard biofumigation reduces wilt pathogen load.', '🍅'),
(53, 8, 8, 'avoid', 'Continuous tomato causes rapid Bacterial Wilt and Mosaic Virus buildup in soil.', '🍅'),
(54, 8, 3, 'avoid', 'Tomato and Potato share Late Blight — planting one after the other guarantees disease spread.', '🍅'),
(55, 8, 9, 'avoid', 'Tomato and Brinjal are both nightshades — rotating between them does not break the disease cycle.', '🍅'),
(56, 8, 10, 'avoid', 'Tomato and Chili share Phytophthora blight and Mosaic Virus — avoid this rotation.', '🍅'),
(57, 9, 1, 'good', 'Brinjal accumulates stem-rot and wilt pathogens. Rice completely breaks the nightshade cycle.', '🫆'),
(58, 9, 2, 'good', 'Brinjal accumulates stem-rot and wilt pathogens. Wheat is a safe cereal rotation.', '🫆'),
(59, 9, 6, 'good', 'Brinjal accumulates stem-rot and wilt pathogens. Maize is from a different family — safe.', '🫆'),
(60, 9, 7, 'good', 'Brinjal accumulates stem-rot and wilt pathogens. Onion suppresses wilt bacteria in soil.', '🫆'),
(61, 9, 5, 'good', 'Brinjal accumulates stem-rot and wilt pathogens. Mustard biofumigation reduces pathogen load.', '🫆'),
(62, 9, 9, 'avoid', 'Continuous brinjal causes rapid stem-rot and Bacterial Wilt accumulation in soil.', '🫆'),
(63, 9, 8, 'avoid', 'Brinjal and Tomato are both nightshades — rotating between them spreads Bacterial Wilt.', '🫆'),
(64, 9, 3, 'avoid', 'Brinjal and Potato share Late Blight — avoid this rotation.', '🫆'),
(65, 9, 10, 'avoid', 'Brinjal and Chili share Phytophthora blight — avoid this nightshade-to-nightshade rotation.', '🫆'),
(66, 10, 1, 'good', 'Chili shares Phytophthora blight with nightshades. Rice completely breaks the disease cycle.', '🌶️'),
(67, 10, 2, 'good', 'Chili shares Phytophthora blight with nightshades. Wheat is a completely safe cereal rotation.', '🌶️'),
(68, 10, 6, 'good', 'Chili shares Phytophthora blight with nightshades. Maize is from a different family — safe rotation.', '🌶️'),
(69, 10, 7, 'good', 'Chili shares Phytophthora blight with nightshades. Onion suppresses blight spores in soil.', '🌶️'),
(70, 10, 5, 'good', 'Chili shares Phytophthora blight with nightshades. Mustard biofumigation cleanses the soil effectively.', '🌶️'),
(71, 10, 10, 'avoid', 'Continuous chili causes rapid Phytophthora blight and anthracnose accumulation in soil.', '🌶️'),
(72, 10, 8, 'avoid', 'Chili and Tomato share Phytophthora blight and Mosaic Virus — avoid this rotation.', '🌶️'),
(73, 10, 3, 'avoid', 'Chili and Potato share Phytophthora blight — avoid planting chili after potato.', '🌶️'),
(74, 10, 9, 'avoid', 'Chili and Brinjal share Phytophthora blight — avoid this nightshade-to-nightshade rotation.', '🌶️');

-- --------------------------------------------------------

--
-- Table structure for table `dae_offices`
--

CREATE TABLE `dae_offices` (
  `id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `upazila` varchar(100) NOT NULL,
  `address` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `hours` varchar(50) DEFAULT 'Sun–Thu 9am–5pm',
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dae_offices`
--

INSERT INTO `dae_offices` (`id`, `location_id`, `upazila`, `address`, `phone`, `hours`, `is_active`) VALUES
(1, 1, 'Savar', 'DAE Upazila Office, Savar Bazar, Dhaka-1340', '02-7789142', 'Sun–Thu 9am–5pm', 1),
(2, 20, 'Tangail Sadar', 'DAE Upazila Office, Deputy Commissioner Road, Tangail', '0921-63018', 'Sun–Thu 9am–5pm', 1),
(3, 14, 'Shibalaya', 'DAE Office, Shibalaya Upazila Complex, Manikganj', '06029-56214', 'Sun–Thu 9am–5pm', 1),
(4, 17, 'Belabo', 'DAE Office, Belabo Upazila, Narsingdi', '06229-75301', 'Sun–Thu 9am–5pm', 1),
(5, 9, 'Faridpur Sadar', 'DAE District Office, Circuit House Road, Faridpur', '0631-63425', 'Sun–Thu 9am–5pm', 1),
(6, 24, 'Comilla Sadar', 'DAE Upazila Office, Kandirpar, Comilla-3500', '081-68491', 'Sun–Thu 9am–5pm', 1),
(7, 25, 'Teknaf', 'DAE Office, Teknaf Upazila, Cox\'s Bazar', '03421-75612', 'Sun–Thu 9am–5pm', 1),
(8, 22, 'Brahmanbaria Sadar', 'DAE Office, Upazila Complex, Brahmanbaria', '0851-62014', 'Sun–Thu 9am–5pm', 1),
(9, 29, 'Begumganj', 'DAE Office, Begumganj Upazila, Noakhali', '0321-61803', 'Sun–Thu 9am–5pm', 1),
(10, 23, 'Matlab Uttar', 'DAE Office, Matlab Uttar, Chandpur', '0844-55201', 'Sun–Thu 9am–5pm', 1),
(11, 3, 'Rajshahi Sadar', 'DAE District Office, Shaheb Bazar, Rajshahi', '0721-772015', 'Sun–Thu 9am–5pm', 1),
(12, 31, 'Shibganj', 'DAE Upazila Office, Shibganj, Bogura', '05123-56301', 'Sun–Thu 9am–5pm', 1),
(13, 35, 'Baraigram', 'DAE Office, Baraigram Upazila, Natore', '07709-56104', 'Sun–Thu 9am–5pm', 1),
(14, 34, 'Mohadevpur', 'DAE Office, Mohadevpur, Naogaon', '07427-56013', 'Sun–Thu 9am–5pm', 1),
(15, 32, 'Shibganj', 'DAE Office, Shibganj Upazila, Chapainawabganj', '07822-56321', 'Sun–Thu 9am–5pm', 1),
(16, 4, 'Dumuria', 'DAE Office, Dumuria Upazila, Khulna', '041-763501', 'Sun–Thu 9am–5pm', 1),
(17, 40, 'Chaugachha', 'DAE Office, Chaugachha Upazila, Jashore', '0421-68203', 'Sun–Thu 9am–5pm', 1),
(18, 41, 'Shailkupa', 'DAE Office, Shailkupa Upazila, Jhenaidah', '04524-56102', 'Sun–Thu 9am–5pm', 1),
(19, 46, 'Assasuni', 'DAE Office, Assasuni Upazila, Satkhira', '0471-63104', 'Sun–Thu 9am–5pm', 1),
(20, 54, 'Nesarabad', 'DAE Office, Nesarabad (Swarupkathi), Pirojpur', '04628-56012', 'Sun–Thu 9am–5pm', 1),
(21, 6, 'Barisal Sadar', 'DAE District Office, Band Road, Barisal', '0431-64312', 'Sun–Thu 9am–5pm', 1),
(22, 51, 'Bhola Sadar', 'DAE Office, Upazila Complex, Bhola', '0491-62015', 'Sun–Thu 9am–5pm', 1),
(23, 53, 'Bauphal', 'DAE Office, Bauphal Upazila, Patuakhali', '04427-56203', 'Sun–Thu 9am–5pm', 1),
(24, 5, 'Sylhet Sadar', 'DAE District Office, Zindabazar, Sylhet', '0821-716034', 'Sun–Thu 9am–5pm', 1),
(25, 48, 'Juri', 'DAE Office, Juri Upazila, Moulvibazar', '08626-56401', 'Sun–Thu 9am–5pm', 1),
(26, 47, 'Chunarughat', 'DAE Office, Chunarughat, Habiganj', '08323-56012', 'Sun–Thu 9am–5pm', 1),
(27, 7, 'Rangpur Sadar', 'DAE District Office, Station Road, Rangpur', '0521-66203', 'Sun–Thu 9am–5pm', 1),
(28, 60, 'Panchagarh Sadar', 'DAE Office, Upazila Complex, Panchagarh', '05682-56104', 'Sun–Thu 9am–5pm', 1),
(29, 56, 'Sadullapur', 'DAE Office, Sadullapur Upazila, Gaibandha', '05424-56213', 'Sun–Thu 9am–5pm', 1),
(30, 62, 'Islampur', 'DAE Office, Islampur Upazila, Jamalpur', '09822-56302', 'Sun–Thu 9am–5pm', 1);

-- --------------------------------------------------------

--
-- Table structure for table `farmer_crops`
--

CREATE TABLE `farmer_crops` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `crop_id` int(11) NOT NULL,
  `field_id` int(11) DEFAULT NULL,
  `seed_id` int(11) DEFAULT NULL,
  `planted_date` date NOT NULL,
  `expected_harvest` date DEFAULT NULL,
  `status` enum('growing','harvested','failed') DEFAULT 'growing',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `farmer_crops`
--

INSERT INTO `farmer_crops` (`id`, `user_id`, `crop_id`, `field_id`, `seed_id`, `planted_date`, `expected_harvest`, `status`, `created_at`) VALUES
(2, 2, 10, 1, NULL, '2026-05-06', '2026-05-12', 'failed', '2026-05-11 15:33:13'),
(3, 2, 3, NULL, NULL, '2026-05-12', '2026-05-31', 'failed', '2026-05-12 02:53:42'),
(4, 2, 1, 4, NULL, '2026-05-12', NULL, 'growing', '2026-05-12 16:42:11'),
(5, 2, 1, 5, NULL, '2026-05-13', '2026-05-29', 'growing', '2026-05-13 03:46:31'),
(6, 2, 1, 4, 2, '2026-05-20', '2026-10-17', 'failed', '2026-05-20 03:25:17'),
(7, 2, 1, 1, 2, '2026-05-20', '2026-10-17', 'failed', '2026-05-20 03:40:57'),
(8, 2, 2, 3, 6, '2026-05-23', '2026-09-15', 'harvested', '2026-05-23 05:02:50');

-- --------------------------------------------------------

--
-- Table structure for table `fields`
--

CREATE TABLE `fields` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `area` decimal(10,2) DEFAULT NULL,
  `soil_type` enum('Sandy','Clay','Loamy','Silt','Peaty','Chalky') NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fields`
--

INSERT INTO `fields` (`id`, `user_id`, `name`, `area`, `soil_type`, `location_id`, `created_at`) VALUES
(1, 2, 'kichu ekta', 2.00, 'Chalky', 5, '2026-05-11 10:24:55'),
(3, 2, 'tyrg', 2.00, 'Silt', 5, '2026-05-12 02:53:16'),
(4, 2, 'kichu na', 3.00, 'Clay', 2, '2026-05-12 16:41:42'),
(5, 2, 'dhaner jomi', 2.00, 'Clay', 5, '2026-05-13 03:46:05');

-- --------------------------------------------------------

--
-- Table structure for table `irrigation_logs`
--

CREATE TABLE `irrigation_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `field_id` int(11) DEFAULT NULL,
  `date` date NOT NULL,
  `suggestion` enum('ON','OFF') NOT NULL,
  `rain_probability` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan_applications`
--

CREATE TABLE `loan_applications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `amount_needed` decimal(12,2) NOT NULL,
  `land_acres` decimal(6,2) DEFAULT NULL,
  `crop_type` varchar(100) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `status` enum('saved','submitted','approved','rejected') DEFAULT 'saved',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loan_applications`
--

INSERT INTO `loan_applications` (`id`, `user_id`, `product_id`, `amount_needed`, `land_acres`, `crop_type`, `purpose`, `status`, `notes`, `created_at`) VALUES
(1, 2, 3, 20000.00, 3.00, '556', 'fddd', 'saved', NULL, '2026-06-06 17:48:04');

-- --------------------------------------------------------

--
-- Table structure for table `loan_products`
--

CREATE TABLE `loan_products` (
  `id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `category` enum('crop','livestock','irrigation','equipment','general','emergency') DEFAULT 'crop',
  `min_amount` decimal(12,2) DEFAULT 10000.00,
  `max_amount` decimal(12,2) DEFAULT 500000.00,
  `interest_rate` decimal(5,2) DEFAULT NULL,
  `interest_type` enum('flat','reducing','subsidised') DEFAULT 'reducing',
  `duration_min_months` smallint(6) DEFAULT 6,
  `duration_max_months` smallint(6) DEFAULT 60,
  `repayment_type` enum('monthly','quarterly','seasonal','lump-sum') DEFAULT 'monthly',
  `min_land_acres` decimal(6,2) DEFAULT NULL,
  `eligible_crops` varchar(500) DEFAULT NULL,
  `collateral_required` tinyint(1) DEFAULT 0,
  `guarantor_required` tinyint(1) DEFAULT 0,
  `nid_required` tinyint(1) DEFAULT 1,
  `land_deed_required` tinyint(1) DEFAULT 0,
  `bank_statement_required` tinyint(1) DEFAULT 0,
  `farmers_card_required` tinyint(1) DEFAULT 0,
  `photo_required` tinyint(1) DEFAULT 1,
  `other_documents` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `eligibility_notes` text DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loan_products`
--

INSERT INTO `loan_products` (`id`, `provider_id`, `name`, `category`, `min_amount`, `max_amount`, `interest_rate`, `interest_type`, `duration_min_months`, `duration_max_months`, `repayment_type`, `min_land_acres`, `eligible_crops`, `collateral_required`, `guarantor_required`, `nid_required`, `land_deed_required`, `bank_statement_required`, `farmers_card_required`, `photo_required`, `other_documents`, `description`, `eligibility_notes`, `is_featured`, `is_active`, `created_at`) VALUES
(1, 1, 'Krishi Sarovar Crop Loan', 'crop', 10000.00, 300000.00, 4.00, 'subsidised', 6, 18, 'seasonal', 0.25, 'Rice,Wheat,Jute,Maize,Potato,Vegetables', 0, 0, 1, 1, 0, 0, 1, NULL, 'Subsidised seasonal crop loan for small and marginal farmers with flexible seasonal repayment.', 'Must own or lease at least 0.25 acres. NID and land deed required.', 1, 1, '2026-06-06 17:39:00'),
(2, 1, 'Agricultural Equipment Loan', 'equipment', 50000.00, 1000000.00, 7.00, 'reducing', 12, 60, 'monthly', 0.50, NULL, 1, 0, 1, 1, 0, 0, 1, NULL, 'For purchase of power tillers, irrigation pumps, tractors and other farm equipment.', 'Collateral required. Land deed and bank statement needed. Minimum 0.5 acres land ownership.', 0, 1, '2026-06-06 17:39:00'),
(3, 1, 'Emergency Crop Disaster Loan', 'emergency', 5000.00, 100000.00, 2.00, 'subsidised', 3, 12, 'seasonal', 0.00, NULL, 0, 0, 1, 0, 0, 1, 1, NULL, 'Fast-disbursement emergency loan for farmers affected by floods, cyclones, or crop failure.', 'Requires Farmers Card or official disaster declaration from local UP office.', 1, 1, '2026-06-06 17:39:00'),
(4, 2, 'RAKUB Crop Production Loan', 'crop', 15000.00, 500000.00, 5.00, 'reducing', 6, 24, 'seasonal', 0.33, 'Rice,Wheat,Mango,Litchi,Sugarcane', 0, 0, 1, 1, 0, 0, 1, NULL, 'Crop production loan for farmers in Rajshahi, Chapai, Bogura, and northern districts.', 'Available only in RAKUB service area (northern Bangladesh). Land deed required.', 0, 1, '2026-06-06 17:39:00'),
(5, 2, 'Fisheries & Livestock Loan', 'livestock', 20000.00, 400000.00, 6.00, 'reducing', 12, 36, 'monthly', 0.00, NULL, 1, 0, 1, 0, 0, 0, 1, NULL, 'Loan for fish farming, poultry, dairy, and goat rearing projects.', 'Project plan required. Guarantor needed for amounts over BDT 1 lakh.', 0, 1, '2026-06-06 17:39:00'),
(6, 3, 'BRAC Microfinance Agricultural Loan', 'crop', 5000.00, 150000.00, 24.00, 'flat', 6, 24, 'monthly', 0.00, 'Rice,Vegetables,Poultry,Fishery', 0, 0, 1, 0, 0, 0, 1, NULL, 'Accessible micro-loan for smallholder farmers with minimal documentation. No land collateral required.', 'Must be a BRAC member. Group guarantee system. No land required.', 1, 1, '2026-06-06 17:39:00'),
(7, 3, 'BRAC Poultry & Livestock Loan', 'livestock', 8000.00, 80000.00, 22.00, 'flat', 6, 18, 'monthly', 0.00, NULL, 0, 0, 1, 0, 0, 0, 1, NULL, 'Quick-disbursement loan for poultry, duck farming, goat rearing, and small dairy operations.', 'BRAC membership required. Group-based lending model.', 0, 1, '2026-06-06 17:39:00'),
(8, 4, 'Grameen Krishi Loan', 'crop', 3000.00, 100000.00, 20.00, 'reducing', 6, 24, 'monthly', 0.00, NULL, 0, 0, 1, 0, 0, 0, 1, NULL, 'Classic Grameen microfinance loan for rural farmers. Weekly repayment model with group support.', 'Grameen Bank membership required. Group of 5 borrowers. Weekly centre meeting attendance.', 0, 1, '2026-06-06 17:39:00'),
(9, 5, 'ASA General Agricultural Loan', 'general', 5000.00, 200000.00, 22.00, 'flat', 6, 24, 'monthly', 0.00, NULL, 0, 0, 1, 0, 0, 0, 1, NULL, 'Flexible agricultural loan for crop production, livestock, and small agri-businesses.', 'ASA membership required. Fast disbursement within 7 days.', 0, 1, '2026-06-06 17:39:00'),
(10, 6, 'PKSF Livelihood Promotion Loan', 'general', 10000.00, 300000.00, 10.00, 'reducing', 12, 48, 'monthly', 0.00, NULL, 0, 0, 1, 0, 0, 0, 1, NULL, 'Government-backed livelihood loan through PKSF partner MFIs. Lower interest than commercial MFIs.', 'Available through PKSF partner organizations. Must be below poverty line or small farmer.', 1, 1, '2026-06-06 17:39:00');

-- --------------------------------------------------------

--
-- Table structure for table `loan_providers`
--

CREATE TABLE `loan_providers` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `type` enum('bank','ngo','mfi','cooperative','government') DEFAULT 'bank',
  `logo_emoji` varchar(10) DEFAULT '?',
  `phone` varchar(20) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loan_providers`
--

INSERT INTO `loan_providers` (`id`, `name`, `type`, `logo_emoji`, `phone`, `website`, `description`, `is_active`, `created_at`) VALUES
(1, 'Bangladesh Krishi Bank', 'bank', '🏛️', '16151', 'https://www.krishibank.org.bd', 'State-owned bank dedicated to agricultural credit in Bangladesh.', 1, '2026-06-06 17:39:00'),
(2, 'Rajshahi Krishi Unnayan Bank', 'bank', '🌾', '0721-775277', 'https://www.rakub.org.bd', 'Specialized agricultural development bank serving northern Bangladesh.', 1, '2026-06-06 17:39:00'),
(3, 'BRAC', 'ngo', '🤝', '16345', 'https://www.brac.net', 'World largest NGO offering microfinance and agricultural loans to rural farmers.', 1, '2026-06-06 17:39:00'),
(4, 'Grameen Bank', 'mfi', '🏘️', '09604-116116', 'https://www.grameen.com', 'Nobel Prize-winning microfinance institution focused on rural poor and small farmers.', 1, '2026-06-06 17:39:00'),
(5, 'ASA Bangladesh', 'mfi', '💼', '02-9670672', 'https://www.asa.org.bd', 'One of the largest MFIs in Bangladesh offering farmer credit programs.', 1, '2026-06-06 17:39:00'),
(6, 'PKSF (Partner MFI)', 'government', '🏢', '02-55007071', 'https://www.pksf-bd.org', 'Government apex body funding agricultural microfinance through partner organizations.', 1, '2026-06-06 17:39:00');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `country` varchar(100) DEFAULT 'Bangladesh',
  `division` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `country`, `division`, `district`, `latitude`, `longitude`) VALUES
(1, 'Bangladesh', 'Dhaka', 'Dhaka', 23.8103000, 90.4125000),
(2, 'Bangladesh', 'Chittagong', 'Chittagong', 22.3569000, 91.7832000),
(3, 'Bangladesh', 'Rajshahi', 'Rajshahi', 24.3745000, 88.6042000),
(4, 'Bangladesh', 'Khulna', 'Khulna', 22.8456000, 89.5403000),
(5, 'Bangladesh', 'Sylhet', 'Sylhet', 24.8949000, 91.8687000),
(6, 'Bangladesh', 'Barisal', 'Barisal', 22.7010000, 90.3535000),
(7, 'Bangladesh', 'Rangpur', 'Rangpur', 25.7439000, 89.2752000),
(8, 'Bangladesh', 'Mymensingh', 'Mymensingh', 24.7471000, 90.4203000),
(9, 'Bangladesh', 'Dhaka', 'Faridpur', 23.6070000, 89.8429000),
(10, 'Bangladesh', 'Dhaka', 'Gazipur', 24.0023000, 90.4264000),
(11, 'Bangladesh', 'Dhaka', 'Gopalganj', 23.0051000, 89.8266000),
(12, 'Bangladesh', 'Dhaka', 'Kishoreganj', 24.4449000, 90.7766000),
(13, 'Bangladesh', 'Dhaka', 'Madaripur', 23.1641000, 90.1897000),
(14, 'Bangladesh', 'Dhaka', 'Manikganj', 23.8617000, 90.0003000),
(15, 'Bangladesh', 'Dhaka', 'Munshiganj', 23.5422000, 90.5305000),
(16, 'Bangladesh', 'Dhaka', 'Narayanganj', 23.6238000, 90.5000000),
(17, 'Bangladesh', 'Dhaka', 'Narsingdi', 23.9322000, 90.7154000),
(18, 'Bangladesh', 'Dhaka', 'Rajbari', 23.7574000, 89.6445000),
(19, 'Bangladesh', 'Dhaka', 'Shariatpur', 23.2423000, 90.4348000),
(20, 'Bangladesh', 'Dhaka', 'Tangail', 24.2513000, 89.9167000),
(21, 'Bangladesh', 'Chittagong', 'Bandarban', 22.1953000, 92.2184000),
(22, 'Bangladesh', 'Chittagong', 'Brahmanbaria', 23.9571000, 91.1119000),
(23, 'Bangladesh', 'Chittagong', 'Chandpur', 23.2333000, 90.6710000),
(24, 'Bangladesh', 'Chittagong', 'Comilla', 23.4607000, 91.1809000),
(25, 'Bangladesh', 'Chittagong', 'Coxs Bazar', 21.4272000, 92.0058000),
(26, 'Bangladesh', 'Chittagong', 'Feni', 23.0159000, 91.3976000),
(27, 'Bangladesh', 'Chittagong', 'Khagrachhari', 23.1193000, 91.9847000),
(28, 'Bangladesh', 'Chittagong', 'Lakshmipur', 22.9447000, 90.8282000),
(29, 'Bangladesh', 'Chittagong', 'Noakhali', 22.8696000, 91.0995000),
(30, 'Bangladesh', 'Chittagong', 'Rangamati', 22.7324000, 92.2985000),
(31, 'Bangladesh', 'Rajshahi', 'Bogura', 24.8510000, 89.3697000),
(32, 'Bangladesh', 'Rajshahi', 'Chapainawabganj', 24.5965000, 88.2775000),
(33, 'Bangladesh', 'Rajshahi', 'Joypurhat', 25.0968000, 89.0227000),
(34, 'Bangladesh', 'Rajshahi', 'Naogaon', 24.7936000, 88.9318000),
(35, 'Bangladesh', 'Rajshahi', 'Natore', 24.4206000, 89.0003000),
(36, 'Bangladesh', 'Rajshahi', 'Pabna', 24.0150000, 89.2372000),
(37, 'Bangladesh', 'Rajshahi', 'Sirajganj', 24.4534000, 89.7007000),
(38, 'Bangladesh', 'Khulna', 'Bagerhat', 22.6516000, 89.7859000),
(39, 'Bangladesh', 'Khulna', 'Chuadanga', 23.6402000, 88.8418000),
(40, 'Bangladesh', 'Khulna', 'Jashore', 23.1664000, 89.2081000),
(41, 'Bangladesh', 'Khulna', 'Jhenaidah', 23.5448000, 89.1539000),
(42, 'Bangladesh', 'Khulna', 'Kushtia', 23.9013000, 89.1205000),
(43, 'Bangladesh', 'Khulna', 'Magura', 23.4855000, 89.4198000),
(44, 'Bangladesh', 'Khulna', 'Meherpur', 23.7622000, 88.6318000),
(45, 'Bangladesh', 'Khulna', 'Narail', 23.1725000, 89.5127000),
(46, 'Bangladesh', 'Khulna', 'Satkhira', 22.7185000, 89.0705000),
(47, 'Bangladesh', 'Sylhet', 'Habiganj', 24.3749000, 91.4155000),
(48, 'Bangladesh', 'Sylhet', 'Moulvibazar', 24.4829000, 91.7774000),
(49, 'Bangladesh', 'Sylhet', 'Sunamganj', 25.0658000, 91.3950000),
(50, 'Bangladesh', 'Barisal', 'Barguna', 22.1592000, 90.1260000),
(51, 'Bangladesh', 'Barisal', 'Bhola', 22.6859000, 90.6482000),
(52, 'Bangladesh', 'Barisal', 'Jhalokati', 22.6406000, 90.1987000),
(53, 'Bangladesh', 'Barisal', 'Patuakhali', 22.3596000, 90.3299000),
(54, 'Bangladesh', 'Barisal', 'Pirojpur', 22.5791000, 89.9759000),
(55, 'Bangladesh', 'Rangpur', 'Dinajpur', 25.6279000, 88.6332000),
(56, 'Bangladesh', 'Rangpur', 'Gaibandha', 25.3288000, 89.5281000),
(57, 'Bangladesh', 'Rangpur', 'Kurigram', 25.8054000, 89.6362000),
(58, 'Bangladesh', 'Rangpur', 'Lalmonirhat', 25.9923000, 89.2847000),
(59, 'Bangladesh', 'Rangpur', 'Nilphamari', 25.9318000, 88.8560000),
(60, 'Bangladesh', 'Rangpur', 'Panchagarh', 26.3411000, 88.5542000),
(61, 'Bangladesh', 'Rangpur', 'Thakurgaon', 26.0337000, 88.4617000),
(62, 'Bangladesh', 'Mymensingh', 'Jamalpur', 24.9375000, 89.9378000),
(63, 'Bangladesh', 'Mymensingh', 'Netrokona', 24.8709000, 90.7279000),
(64, 'Bangladesh', 'Mymensingh', 'Sherpur', 25.0205000, 90.0153000);

-- --------------------------------------------------------

--
-- Table structure for table `market_prices`
--

CREATE TABLE `market_prices` (
  `id` int(11) NOT NULL,
  `crop_id` int(11) NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `unit` enum('kg','quintal','ton') DEFAULT 'kg',
  `source` varchar(150) DEFAULT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `market_prices`
--

INSERT INTO `market_prices` (`id`, `crop_id`, `location_id`, `price`, `unit`, `source`, `date`) VALUES
(1, 1, 10, 55.00, 'kg', 'DAE Market Survey', '2026-05-23'),
(2, 1, 16, 57.00, 'kg', 'Wholesaler', '2026-05-23'),
(3, 1, 17, 60.00, 'kg', 'Local Market', '2026-05-23'),
(4, 2, 37, 42.00, 'kg', 'BADC', '2026-05-23'),
(5, 2, 31, 40.00, 'kg', 'Local Market', '2026-05-23'),
(6, 2, 20, 45.00, 'kg', 'Trader Report', '2026-05-23'),
(7, 3, 16, 25.00, 'kg', 'Cold Storage', '2026-05-23'),
(8, 3, 31, 22.00, 'kg', 'Farm Gate', '2026-05-23'),
(9, 3, 37, 28.00, 'kg', 'Wholesale', '2026-05-23'),
(10, 7, 10, 85.00, 'kg', 'Import Market', '2026-05-23'),
(11, 7, 16, 90.00, 'kg', 'Retail', '2026-05-23'),
(12, 7, 31, 88.00, 'kg', 'Trader', '2026-05-23'),
(13, 8, 17, 65.00, 'kg', 'Local Market', '2026-05-23'),
(14, 8, 16, 70.00, 'kg', 'Wholesale', '2026-05-23'),
(15, 8, 20, 60.00, 'kg', 'Farm Gate', '2026-05-23'),
(16, 10, 10, 180.00, 'kg', 'Spice Market', '2026-05-23'),
(17, 10, 31, 170.00, 'kg', 'Retail', '2026-05-23'),
(18, 10, 37, 190.00, 'kg', 'Trader', '2026-05-23'),
(19, 1, 1, 72.00, 'kg', 'DAM', '2026-05-22'),
(20, 1, 2, 74.00, 'kg', 'DAM', '2026-05-21'),
(21, 1, 3, 70.00, 'kg', 'DAM', '2026-05-20'),
(22, 1, 4, 73.00, 'kg', 'Market Survey', '2026-05-19'),
(23, 1, 5, 75.00, 'kg', 'DAM', '2026-05-18'),
(24, 1, 6, 71.00, 'kg', 'DAM', '2026-05-17'),
(25, 1, 7, 69.00, 'kg', 'Market Survey', '2026-05-16'),
(26, 1, 8, 73.00, 'kg', 'DAM', '2026-05-20'),
(27, 2, 1, 58.00, 'kg', 'DAM', '2026-05-22'),
(28, 2, 2, 60.00, 'kg', 'DAM', '2026-05-21'),
(29, 2, 3, 57.00, 'kg', 'DAM', '2026-05-20'),
(30, 2, 4, 59.00, 'kg', 'Market Survey', '2026-05-19'),
(31, 2, 5, 61.00, 'kg', 'DAM', '2026-05-18'),
(32, 2, 6, 56.00, 'kg', 'DAM', '2026-05-17'),
(33, 2, 7, 55.00, 'kg', 'Market Survey', '2026-05-16'),
(34, 2, 8, 58.00, 'kg', 'DAM', '2026-05-20'),
(35, 3, 1, 28.00, 'kg', 'DAM', '2026-05-22'),
(36, 3, 2, 30.00, 'kg', 'DAM', '2026-05-21'),
(37, 3, 3, 27.00, 'kg', 'DAM', '2026-05-20'),
(38, 3, 4, 29.00, 'kg', 'Market Survey', '2026-05-19'),
(39, 3, 5, 31.00, 'kg', 'DAM', '2026-05-18'),
(40, 3, 6, 26.00, 'kg', 'DAM', '2026-05-17'),
(41, 3, 7, 25.00, 'kg', 'Market Survey', '2026-05-16'),
(42, 3, 8, 28.00, 'kg', 'DAM', '2026-05-21'),
(43, 7, 1, 65.00, 'kg', 'DAM', '2026-05-22'),
(44, 7, 2, 67.00, 'kg', 'DAM', '2026-05-21'),
(45, 7, 3, 63.00, 'kg', 'DAM', '2026-05-20'),
(46, 7, 4, 66.00, 'kg', 'Market Survey', '2026-05-19'),
(47, 7, 5, 68.00, 'kg', 'DAM', '2026-05-18'),
(48, 7, 6, 64.00, 'kg', 'DAM', '2026-05-17'),
(49, 7, 7, 62.00, 'kg', 'Market Survey', '2026-05-16'),
(50, 7, 8, 65.00, 'kg', 'DAM', '2026-05-20'),
(51, 8, 1, 120.00, 'kg', 'DAM', '2026-05-22'),
(52, 8, 2, 125.00, 'kg', 'DAM', '2026-05-21'),
(53, 8, 3, 118.00, 'kg', 'DAM', '2026-05-20'),
(54, 8, 4, 122.00, 'kg', 'Market Survey', '2026-05-19'),
(55, 8, 5, 127.00, 'kg', 'DAM', '2026-05-18'),
(56, 8, 6, 115.00, 'kg', 'DAM', '2026-05-17'),
(57, 8, 7, 110.00, 'kg', 'Market Survey', '2026-05-16'),
(58, 8, 8, 121.00, 'kg', 'DAM', '2026-05-20');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text DEFAULT NULL,
  `type` enum('pest','weather','advisory','system') DEFAULT 'system',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(21, 2, 'Pest Report Submitted', 'Your pest report has been submitted successfully.', 'pest', 1, '2026-05-18 05:23:26'),
(22, 2, 'Pest Report Submitted', 'Your pest report has been submitted successfully.', 'pest', 1, '2026-05-18 05:24:15'),
(23, 2, 'Pest Report Submitted', 'Your pest report has been submitted successfully.', 'pest', 1, '2026-05-18 05:24:33'),
(24, 2, 'Question Submitted', 'Your question has been submitted.', '', 1, '2026-05-18 06:10:44'),
(25, 2, 'Answer Received', 'Expert answered your question.', '', 1, '2026-05-18 06:12:10'),
(26, 2, 'Question Submitted', 'Your question has been submitted.', '', 1, '2026-05-19 10:29:36'),
(27, 2, 'Answer Received', 'Expert answered your question.', '', 1, '2026-05-19 10:30:06'),
(28, 2, 'Pest Report Submitted', 'Your pest report has been submitted successfully.', 'pest', 1, '2026-05-19 15:15:18'),
(29, 2, 'Pest Report Submitted', 'Your pest report has been submitted successfully.', 'pest', 1, '2026-05-19 15:57:37'),
(30, 2, 'Pest Report Submitted', 'Your pest report has been submitted for review. District: Dhaka District', '', 1, '2026-05-19 17:05:13'),
(31, 2, 'New Pest Report Submitted', 'Your pest report has been submitted successfully from Dhaka District.', '', 1, '2026-05-19 17:37:22'),
(32, 2, 'Pest Expert Advice', 'Expert has created advice on your pest report (Crop: Brinjal).', '', 1, '2026-05-19 17:51:10'),
(33, 2, 'Answer Received', 'Expert answered your question.', '', 1, '2026-05-19 18:22:46'),
(34, 2, 'Answer Received', 'Expert answered your question.', '', 1, '2026-05-19 18:22:57'),
(35, 2, 'Question Submitted', 'Your question has been submitted.', '', 1, '2026-05-19 18:25:55'),
(36, 2, 'Answer Received', 'Expert answered your question.', '', 1, '2026-05-19 18:26:33'),
(37, 2, 'New Pest Report Submitted', 'Your pest report has been submitted successfully from Dhaka District.', '', 1, '2026-05-19 19:11:57'),
(38, 2, 'New Pest Report Submitted', 'Your pest report has been submitted successfully from Dhaka District.', '', 1, '2026-05-19 19:12:35'),
(39, 2, 'New Pest Report Submitted', 'Your pest report has been submitted successfully from Dhaka District.', '', 1, '2026-05-19 19:12:48'),
(40, 2, 'New Pest Report Submitted', 'Your pest report has been submitted successfully from Dhaka District.', '', 1, '2026-05-19 19:13:13'),
(41, 2, 'Crop Planted', 'You planted a new crop.', 'advisory', 1, '2026-05-20 03:25:17'),
(42, 2, 'Crop Planted', 'You planted a new crop.', 'advisory', 1, '2026-05-20 03:40:57'),
(43, 2, 'Question Submitted', 'Your question has been submitted.', '', 1, '2026-05-20 03:47:40'),
(44, 2, 'Answer Received', 'Expert answered your question.', '', 1, '2026-05-20 03:47:59'),
(45, 2, 'New Pest Report Submitted', 'Your pest report has been submitted successfully from Dhaka District.', '', 1, '2026-05-20 03:50:18'),
(46, 2, '⏭ Skipped: Activity Logged', '⏭ Skipped — Soil leveling & water management check for Rice.', '', 1, '2026-05-20 11:38:39'),
(47, 2, '✅ Completed: Activity Logged', '✅ Completed — Apply fertilizer for Rice.', '', 1, '2026-05-20 11:38:52'),
(48, 2, '✅ Completed: Activity Logged', '✅ Completed — Apply fertilizer for Rice.', '', 1, '2026-05-20 11:54:22'),
(49, 2, 'New Pest Report Submitted', 'Your pest report has been submitted successfully from Dhaka District.', '', 1, '2026-05-20 12:08:53'),
(50, 2, 'Crop Planted', 'You planted a new crop.', 'advisory', 1, '2026-05-23 05:02:50'),
(51, 2, 'Farmers Card Linked', 'Your Bangladesh Farmers Card has been linked to SoilSync. You can now track all 10 government benefits.', 'advisory', 1, '2026-05-23 05:45:53'),
(52, 2, 'Farmers Card Linked', 'Your Bangladesh Farmers Card has been linked to SoilSync. You can now track all 10 government benefits.', 'advisory', 1, '2026-05-23 05:56:17'),
(53, 2, 'Farmers Card Linked', 'Your Bangladesh Farmers Card has been linked to SoilSync. You can now track all 10 government benefits.', 'advisory', 1, '2026-05-23 06:23:40'),
(54, 2, 'Farmers Card Linked', 'Your Bangladesh Farmers Card has been linked to SoilSync. You can now track all 10 government benefits.', 'advisory', 1, '2026-05-23 06:37:28'),
(55, 2, 'Farmers Card Linked', 'Your Bangladesh Farmers Card has been linked to SoilSync. You can now track all 10 government benefits.', 'advisory', 1, '2026-05-23 06:39:55'),
(56, 2, 'Question Submitted', 'Your question has been submitted.', '', 1, '2026-05-23 09:13:09'),
(57, 2, 'New Pest Report Submitted', 'Your pest report has been submitted successfully from Dhaka District.', '', 1, '2026-06-06 14:24:13'),
(58, 2, 'New Pest Report Submitted', 'Your pest report has been submitted successfully from Dhaka District.', '', 1, '2026-06-06 14:34:00'),
(59, 2, 'Question Submitted', 'Your question has been submitted successfully.', '', 1, '2026-06-06 14:46:19'),
(60, 2, 'Pest Expert Advice', 'Expert has created advice on your pest report (Crop: Brinjal).', '', 1, '2026-06-06 14:49:43'),
(61, 2, '⏭ Skipped: Activity Logged', '⏭ Skipped — First weeding for Rice.', '', 1, '2026-06-06 18:13:56'),
(62, 2, '⏭ Skipped: Activity Logged', '⏭ Skipped — First weeding for Rice.', '', 1, '2026-06-06 18:16:02'),
(63, 2, 'New Pest Report Submitted', 'Your pest report has been submitted successfully from Dhaka District.', '', 1, '2026-06-07 10:47:20'),
(64, 2, 'Pest Expert Advice', 'Expert has created advice on your pest report (Crop: Brinjal).', '', 1, '2026-06-07 10:47:49'),
(65, 2, '⏭ Skipped: Activity Logged', '⏭ Skipped — Apply fertilizer for Wheat.', '', 0, '2026-06-07 10:49:29'),
(66, 2, '⏭ Skipped: Activity Logged', '⏭ Skipped — Apply nitrogen fertilizer for Wheat.', '', 0, '2026-06-07 15:28:50'),
(67, 2, 'Farmers Card Linked', 'Your Bangladesh Farmers Card has been linked to SoilSync. You can now track all 10 government benefits.', 'advisory', 0, '2026-06-07 16:02:37');

-- --------------------------------------------------------

--
-- Table structure for table `pesticide_guidelines`
--

CREATE TABLE `pesticide_guidelines` (
  `id` int(11) NOT NULL,
  `pesticide_name` varchar(100) DEFAULT NULL,
  `safe_dosage` text DEFAULT NULL,
  `phi_days` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pesticide_guidelines`
--

INSERT INTO `pesticide_guidelines` (`id`, `pesticide_name`, `safe_dosage`, `phi_days`) VALUES
(1, 'Malathion', '20ml per liter water', 14),
(2, 'Neem Oil', '30ml per liter water', 3),
(3, 'Carbendazim', '10g per liter water', 7),
(4, 'Chlorpyrifos', '15ml per liter water', 21),
(5, 'Imidacloprid', '5ml per liter water', 10);

-- --------------------------------------------------------

--
-- Table structure for table `pests`
--

CREATE TABLE `pests` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pests`
--

INSERT INTO `pests` (`id`, `name`, `description`) VALUES
(1, 'Brown Planthopper', 'Sucks sap from rice plants causing hopperburn'),
(2, 'Stem Borer', 'Larvae bore into rice stems causing deadheart/whitehead'),
(3, 'Aphids', 'Small insects sucking plant juice causing stunted growth'),
(4, 'Whitefly', 'Transmits viral diseases; affects many crops'),
(5, 'Colorado Potato Beetle', 'Major potato pest; defoliates plants'),
(6, 'Armyworm', 'Caterpillar that feeds on leaves in large numbers'),
(7, 'Thrips', 'Tiny insects causing silver streaks on leaves'),
(8, 'Mites', 'Spider-like; cause yellowing and leaf drop');

-- --------------------------------------------------------

--
-- Table structure for table `pest_images`
--

CREATE TABLE `pest_images` (
  `id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `image_url` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pest_images`
--

INSERT INTO `pest_images` (`id`, `report_id`, `image_url`) VALUES
(6, 15, 'assets/images/pests/1779206257_alexander-london-mJaD10XeD7w-unsplash.jpg'),
(11, 81, 'assets/images/pests/1780756508_alexander-london-mJaD10XeD7w-unsplash.jpg'),
(12, 83, 'assets/images/pests/1780829240_amber-kipp-75715CVEJhI-unsplash.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `pest_reports`
--

CREATE TABLE `pest_reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `crop_id` int(11) NOT NULL,
  `pest_id` int(11) NOT NULL,
  `field_id` int(11) DEFAULT NULL,
  `severity` enum('Low','Medium','High') NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','reviewed') DEFAULT 'pending',
  `outbreak_group` int(11) DEFAULT NULL,
  `full_address` text DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pest_reports`
--

INSERT INTO `pest_reports` (`id`, `user_id`, `crop_id`, `pest_id`, `field_id`, `severity`, `description`, `created_at`, `status`, `outbreak_group`, `full_address`, `district`, `latitude`, `longitude`) VALUES
(15, 2, 7, 7, NULL, 'Medium', 'rtge5r4', '2026-05-19 15:57:37', 'pending', NULL, 'Bhandari Mor, East Nurer Chala, Nurer Chala, Dhaka, Dhaka Metropolitan, Dhaka District, Dhaka Division, 1212, Bangladesh', '', 23.794911515875746, 90.43556416235091),
(16, 2, 9, 3, NULL, 'Low', 'fgdbvtrf', '2026-05-19 16:07:25', 'pending', NULL, 'Bhandari Mor, East Nurer Chala, Nurer Chala, Dhaka, Dhaka Metropolitan, Dhaka District, Dhaka Division, 1212, Bangladesh', NULL, 23.794774142664718, 90.4355618415806),
(17, 2, 9, 3, NULL, 'Low', 'fgdbvtrf', '2026-05-19 16:08:19', 'pending', NULL, 'Bhandari Mor, East Nurer Chala, Nurer Chala, Dhaka, Dhaka Metropolitan, Dhaka District, Dhaka Division, 1212, Bangladesh', NULL, 23.794774142664718, 90.4355618415806),
(18, 2, 9, 3, NULL, 'Low', 'fgdbvtrf', '2026-05-19 16:10:53', 'pending', NULL, 'Bhandari Mor, East Nurer Chala, Nurer Chala, Dhaka, Dhaka Metropolitan, Dhaka District, Dhaka Division, 1212, Bangladesh', NULL, 23.794774142664718, 90.4355618415806),
(19, 2, 1, 3, NULL, 'Medium', 'erfgvvrged', '2026-05-19 16:40:08', 'pending', NULL, 'Bhandari Mor, East Nurer Chala, Nurer Chala, Dhaka, Dhaka Metropolitan, Dhaka District, Dhaka Division, 1212, Bangladesh', 'Dhaka District', 23.7949166462841, 90.43563135759642),
(20, 2, 1, 3, NULL, 'Low', '', '2026-05-19 16:41:44', 'pending', NULL, 'B Block, East Nurer Chala, Nurer Chala, Dhaka, Dhaka Metropolitan, Dhaka District, Dhaka Division, 1212, Bangladesh', 'Dhaka District', 23.79463444908724, 90.43555403129402),
(21, 2, 9, 3, NULL, 'Low', 'ftghyhbtrgff', '2026-05-19 16:57:14', 'pending', NULL, 'Bhandari Mor, East Nurer Chala, Nurer Chala, Dhaka, Dhaka Metropolitan, Dhaka District, Dhaka Division, 1212, Bangladesh', 'Dhaka District', 23.794772961631224, 90.43559008563692),
(22, 2, 9, 3, NULL, 'Low', '', '2026-05-19 17:05:13', 'pending', NULL, 'Bhandari Mor, East Nurer Chala, Nurer Chala, Dhaka, Dhaka Metropolitan, Dhaka District, Dhaka Division, 1212, Bangladesh', 'Dhaka District', 23.794911005983067, 90.43560808213765),
(23, 2, 9, 3, NULL, 'Low', '', '2026-05-19 17:37:22', 'pending', NULL, 'Bhandari Mor, East Nurer Chala, Nurer Chala, Dhaka, Dhaka Metropolitan, Dhaka District, Dhaka Division, 1212, Bangladesh', 'Dhaka District', 23.794905098103204, 90.43562296000388),
(24, 2, 1, 7, NULL, 'High', 'vgfb bhtfgrv', '2026-05-19 19:11:57', 'pending', NULL, 'Bhandari Mor, East Nurer Chala, Nurer Chala, Dhaka, Dhaka Metropolitan, Dhaka District, Dhaka Division, 1212, Bangladesh', 'Dhaka District', 23.79494354636444, 90.43557229967617),
(25, 2, 1, 3, NULL, 'High', '', '2026-05-19 19:12:35', 'pending', NULL, 'Bhandari Mor, East Nurer Chala, Nurer Chala, Dhaka, Dhaka Metropolitan, Dhaka District, Dhaka Division, 1212, Bangladesh', 'Dhaka District', 23.794668024870322, 90.43555319494959),
(26, 2, 1, 2, NULL, 'High', '', '2026-05-19 19:12:48', 'pending', NULL, 'Bhandari Mor, East Nurer Chala, Nurer Chala, Dhaka, Dhaka Metropolitan, Dhaka District, Dhaka Division, 1212, Bangladesh', 'Dhaka District', 23.794668024870322, 90.43555319494959),
(27, 2, 1, 7, NULL, 'High', '', '2026-05-19 19:13:13', 'pending', NULL, 'Bhandari Mor, East Nurer Chala, Nurer Chala, Dhaka, Dhaka Metropolitan, Dhaka District, Dhaka Division, 1212, Bangladesh', 'Dhaka District', 23.794911005983067, 90.43560808213765),
(28, 2, 1, 7, NULL, 'High', 'edfedf', '2026-05-20 03:50:18', 'pending', NULL, 'UIU Entrance Road, United City, Badda, Kathaldia, Dhaka Metropolitan, Dhaka District, Dhaka Division, 1212, Bangladesh', 'Dhaka District', 23.796913, 90.450005),
(29, 2, 9, 3, NULL, 'Low', '', '2026-05-20 12:08:53', 'pending', NULL, 'Bhandari Mor, East Nurer Chala, Nurer Chala, Dhaka, Dhaka Metropolitan, Dhaka District, Dhaka Division, 1212, Bangladesh', 'Dhaka District', 23.794742755853022, 90.43556585154629),
(81, 2, 9, 3, NULL, 'Low', 'fcdgvv', '2026-06-06 14:24:13', 'pending', NULL, 'Bhandari Mor, East Nurer Chala, Nurer Chala, Dhaka, Dhaka Metropolitan, Dhaka District, Dhaka Division, 1212, Bangladesh', 'Dhaka District', 23.794931212446347, 90.43563876287554),
(83, 2, 9, 6, NULL, 'Low', 'rtgvtrgvr', '2026-06-07 10:47:20', 'pending', NULL, 'United International University, Madani Avenue, United City, East Nurer Chala, Badda, Kathaldia, Dhaka Metropolitan, Dhaka District, Dhaka Division, 1229, Bangladesh', 'Dhaka District', 23.79841922759344, 90.44996943758977);

-- --------------------------------------------------------

--
-- Table structure for table `pest_reviews`
--

CREATE TABLE `pest_reviews` (
  `id` int(11) NOT NULL,
  `report_id` int(11) DEFAULT NULL,
  `expert_id` int(11) DEFAULT NULL,
  `advice` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pest_reviews`
--

INSERT INTO `pest_reviews` (`id`, `report_id`, `expert_id`, `advice`, `created_at`) VALUES
(5, 23, 6, 'frtgedgvrefdtg', '2026-05-19 17:51:10'),
(6, 81, 6, 'frgedgvcxvdsc', '2026-06-06 14:49:43'),
(7, 83, 6, 'rfguiedwjcpsxzuyhgf', '2026-06-07 10:47:49');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `question` text DEFAULT NULL,
  `tags` varchar(200) DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category` enum('pest','crop','irrigation','other') DEFAULT 'other',
  `image_url` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `user_id`, `question`, `tags`, `is_public`, `created_at`, `category`, `image_url`) VALUES
(1, 2, 'gftrhbgfbnt', '', 1, '2026-05-18 06:10:44', 'pest', NULL),
(2, 2, 'hgfthbnfg', '', 1, '2026-05-19 10:29:36', 'pest', NULL),
(3, 2, 'drdtgrfytfrhybbgfvhb ', 'rice', 1, '2026-05-19 18:25:55', 'crop', NULL),
(4, 2, 'hgbtfgbfdgrv', 'rice', 1, '2026-05-20 03:47:40', 'crop', NULL),
(5, 2, '5t4tgnfjkrnelkcew\';.c', 'rice', 1, '2026-05-23 09:13:09', 'pest', NULL),
(6, 2, 'gv hjbjkm', 'rice', 1, '2026-06-06 14:46:19', 'crop', 'assets/images/questions/1780757179_alec-favale-Ivzo69e18nk-unsplash.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `seeds`
--

CREATE TABLE `seeds` (
  `id` int(11) NOT NULL,
  `crop_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `yield_info` text DEFAULT NULL,
  `pest_resistance` tinyint(1) DEFAULT 0,
  `harvest_days` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `seeds`
--

INSERT INTO `seeds` (`id`, `crop_id`, `name`, `type`, `yield_info`, `pest_resistance`, `harvest_days`) VALUES
(2, 1, 'BRRI dhan29', 'HYV', '7-8 ton/ha', 1, 150),
(3, 1, 'BRRI Hybrid 1', 'Hybrid', '8-9 ton/ha', 1, 135),
(4, 1, 'Local Swarna', 'Local', '4-5 ton/ha', 0, 145),
(5, 2, 'Shatabdi', 'HYV', '4-5 ton/ha', 1, 110),
(6, 2, 'BARI Wheat-26', 'HYV', '5-6 ton/ha', 1, 115),
(7, 3, 'Cardinal', 'HYV', '25-30 ton/ha', 1, 90),
(8, 3, 'Diamant', 'Hybrid', '30-35 ton/ha', 1, 95),
(9, 3, 'Local Red', 'Local', '15-20 ton/ha', 0, 100),
(10, 8, 'BARI Tomato-2', 'HYV', '40-50 ton/ha', 1, 75),
(11, 8, 'Hybrid Ratan', 'Hybrid', '50-60 ton/ha', 1, 70),
(12, 1, 'BRRI dhan28', 'HYV', '6-7 ton/ha', 1, 140),
(13, 1, 'BRRI dhan29', 'HYV', '7-8 ton/ha', 1, 150),
(14, 1, 'BRRI Hybrid 1', 'Hybrid', '8-9 ton/ha', 1, 135),
(15, 1, 'Local Swarna', 'Local', '4-5 ton/ha', 0, 145),
(16, 2, 'Shatabdi', 'HYV', '4-5 ton/ha', 1, 110),
(17, 2, 'BARI Wheat-26', 'HYV', '5-6 ton/ha', 1, 115),
(18, 2, 'Prodip', 'HYV', '4.5-5 ton/ha', 1, 112),
(19, 2, 'Sufi', 'Local', '3-4 ton/ha', 0, 120),
(20, 3, 'Cardinal', 'HYV', '25-30 ton/ha', 1, 90),
(21, 3, 'Diamant', 'Hybrid', '30-35 ton/ha', 1, 95),
(22, 3, 'Local Red', 'Local', '15-20 ton/ha', 0, 100),
(23, 3, 'Granola', 'HYV', '28-32 ton/ha', 1, 92),
(24, 4, 'BJC-7370', 'HYV', '2.5-3 ton/ha fiber', 1, 125),
(25, 4, 'O-9897', 'HYV', '2-2.5 ton/ha fiber', 1, 120),
(26, 4, 'Deshi Pat', 'Local', '1.5-2 ton/ha fiber', 0, 135),
(27, 5, 'BARI Sarisha-14', 'HYV', '1.5-2 ton/ha', 1, 85),
(28, 5, 'Tori-7', 'HYV', '1.2-1.8 ton/ha', 1, 80),
(29, 5, 'Local Mustard', 'Local', '0.8-1 ton/ha', 0, 95),
(30, 6, 'NK-40', 'Hybrid', '10-12 ton/ha', 1, 115),
(31, 6, 'Pacific-984', 'Hybrid', '11-13 ton/ha', 1, 118),
(32, 6, 'BARI Bhutta-9', 'HYV', '8-10 ton/ha', 1, 120),
(33, 6, 'Local Maize', 'Local', '5-6 ton/ha', 0, 125),
(34, 7, 'Taherpuri', 'HYV', '18-22 ton/ha', 1, 105),
(35, 7, 'BARI Piaz-1', 'HYV', '20-25 ton/ha', 1, 100),
(36, 7, 'Faridpuri', 'Local', '12-15 ton/ha', 0, 115),
(37, 8, 'BARI Tomato-2', 'HYV', '40-50 ton/ha', 1, 75),
(38, 8, 'Hybrid Ratan', 'Hybrid', '50-60 ton/ha', 1, 70),
(39, 8, 'Roma VF', 'HYV', '35-45 ton/ha', 1, 80),
(40, 8, 'Local Tomato', 'Local', '20-25 ton/ha', 0, 90),
(41, 9, 'BARI Begun-8', 'HYV', '35-45 ton/ha', 1, 110),
(42, 9, 'Hybrid Purple King', 'Hybrid', '45-55 ton/ha', 1, 100),
(43, 9, 'Local Begun', 'Local', '20-30 ton/ha', 0, 120),
(44, 10, 'BARI Morich-2', 'HYV', '10-12 ton/ha', 1, 95),
(45, 10, 'Hybrid Fire', 'Hybrid', '12-15 ton/ha', 1, 90),
(46, 10, 'Local Chili', 'Local', '6-8 ton/ha', 0, 105);

-- --------------------------------------------------------

--
-- Table structure for table `soil_crop_bonus`
--

CREATE TABLE `soil_crop_bonus` (
  `id` int(11) NOT NULL,
  `soil_type` enum('Sandy','Clay','Loamy','Silt','Peaty','Chalky') NOT NULL,
  `crop_id` int(11) NOT NULL COMMENT 'FK → crops.id',
  `bonus` int(11) NOT NULL DEFAULT 0 COMMENT 'Score bonus added to rotation score (1-20)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `soil_crop_bonus`
--

INSERT INTO `soil_crop_bonus` (`id`, `soil_type`, `crop_id`, `bonus`) VALUES
(1, 'Clay', 1, 15),
(2, 'Clay', 4, 12),
(3, 'Clay', 9, 8),
(4, 'Clay', 8, 8),
(5, 'Clay', 10, 5),
(6, 'Loamy', 2, 15),
(7, 'Loamy', 3, 15),
(8, 'Loamy', 6, 12),
(9, 'Loamy', 7, 12),
(10, 'Loamy', 8, 10),
(11, 'Loamy', 10, 10),
(12, 'Loamy', 5, 8),
(13, 'Sandy', 7, 8),
(14, 'Sandy', 10, 8),
(15, 'Sandy', 5, 5),
(16, 'Silt', 1, 12),
(17, 'Silt', 4, 12),
(18, 'Silt', 6, 8),
(19, 'Silt', 2, 8),
(20, 'Silt', 5, 6),
(21, 'Peaty', 1, 15),
(22, 'Peaty', 4, 10),
(23, 'Peaty', 6, 5),
(24, 'Chalky', 2, 12),
(25, 'Chalky', 5, 10),
(26, 'Chalky', 7, 8);

-- --------------------------------------------------------

--
-- Table structure for table `solutions`
--

CREATE TABLE `solutions` (
  `id` int(11) NOT NULL,
  `disease_id` int(11) NOT NULL,
  `solution_text` text DEFAULT NULL,
  `pesticide_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `solutions`
--

INSERT INTO `solutions` (`id`, `disease_id`, `solution_text`, `pesticide_name`) VALUES
(1, 1, 'Apply fungicide at early stage. Drain water from field for 3-4 days.', 'Tricyclazole 75WP'),
(2, 1, 'Use resistant varieties. Maintain proper spacing.', NULL),
(3, 2, 'Apply copper-based bactericide. Remove and burn infected plants.', 'Copper Oxychloride'),
(4, 3, 'Apply fungicide. Avoid excessive nitrogen.', 'Hexaconazole 5SC'),
(5, 4, 'Apply appropriate fungicide at early stage.', 'Propiconazole'),
(6, 5, 'Destroy infected plants. Apply fungicide.', 'Mancozeb 80WP'),
(7, 6, 'Remove infected tubers. Apply fungicide before planting.', 'Metalaxyl'),
(8, 7, 'Remove infected plants. Control insect vectors.', NULL),
(9, 8, 'Soil solarization. Crop rotation.', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('farmer','expert','admin') DEFAULT 'farmer',
  `location_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `fc_card_number` varchar(20) DEFAULT NULL,
  `fc_category` enum('landless','marginal','small','medium','large') DEFAULT NULL,
  `fc_land_size` decimal(8,2) DEFAULT NULL,
  `fc_bank_account` varchar(30) DEFAULT NULL,
  `fc_registered_at` datetime DEFAULT NULL,
  `fc_phase` enum('pre_pilot','pilot','national') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `phone`, `password`, `role`, `location_id`, `created_at`, `fc_card_number`, `fc_category`, `fc_land_size`, `fc_bank_account`, `fc_registered_at`, `fc_phase`) VALUES
(2, 'Md. Nurnove', '01727493660', '$2y$10$iAIcshKS2xpuf4FfXCBr1.rPN8rFcnr0fuw1ds0.8Ix.E/6yfFQgC', 'farmer', 5, '2026-05-11 09:47:45', 'FCedw223', 'medium', 8.96, '2071000', '2026-05-23 11:45:52', 'pilot'),
(6, 'Hridoy', '01846866454', '$2y$10$z7MjRQ98oNtk2OgfhPyfXeajs9c8fKQz/UhOBYhUyEs8FDyrHe0R2', 'expert', 1, '2026-05-12 11:28:39', NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'admin', '01727698790', '$2y$10$oh.jfOJDNA19/ILV18AKXeAQwTNnQP6JkfDofgHd67vL5B4HH3qly', 'admin', 1, '2026-05-23 06:47:37', NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `weather_api_config`
--

CREATE TABLE `weather_api_config` (
  `id` int(11) NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `api_provider` varchar(100) DEFAULT NULL,
  `api_key` text DEFAULT NULL,
  `update_interval` int(11) DEFAULT 30,
  `last_updated` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `weather_data`
--

CREATE TABLE `weather_data` (
  `id` int(11) NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `temperature` decimal(5,2) DEFAULT NULL,
  `rainfall` decimal(5,2) DEFAULT NULL,
  `humidity` int(11) DEFAULT NULL,
  `rain_probability` int(11) DEFAULT 0,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `weather_data`
--

INSERT INTO `weather_data` (`id`, `location_id`, `temperature`, `rainfall`, `humidity`, `rain_probability`, `recorded_at`) VALUES
(1, 1, 32.50, 0.00, 75, 20, '2026-05-11 09:44:03'),
(2, 2, 31.00, 5.20, 82, 65, '2026-05-11 09:44:03'),
(3, 3, 30.00, 0.00, 70, 15, '2026-05-11 09:44:03'),
(4, 4, 29.50, 2.10, 80, 45, '2026-05-11 09:44:03'),
(5, 5, 28.00, 8.50, 88, 70, '2026-05-11 09:44:03'),
(6, 23, 32.19, 0.00, 64, 30, '2026-05-18 10:15:37'),
(7, 24, 33.36, 0.00, 57, 30, '2026-05-18 10:15:37'),
(8, 25, 30.81, 0.00, 72, 55, '2026-05-18 10:15:37'),
(9, 26, 31.83, 0.00, 63, 30, '2026-05-18 10:15:38'),
(10, 27, 34.29, 0.00, 45, 0, '2026-05-18 10:15:39'),
(11, 28, 31.43, 0.00, 68, 30, '2026-05-18 10:15:39'),
(12, 29, 30.41, 0.00, 74, 55, '2026-05-18 10:15:40'),
(13, 30, 34.71, 0.00, 45, 0, '2026-05-18 10:15:40'),
(14, 31, 36.61, 0.00, 44, 0, '2026-05-18 10:15:41'),
(15, 32, 43.33, 0.00, 15, 0, '2026-05-18 10:15:41'),
(16, 33, 39.31, 0.00, 31, 0, '2026-05-18 10:15:41'),
(17, 34, 42.00, 0.00, 18, 0, '2026-05-18 10:15:42'),
(18, 35, 42.85, 0.00, 16, 0, '2026-05-18 10:15:42'),
(19, 36, 42.81, 0.00, 16, 0, '2026-05-18 10:15:43'),
(20, 37, 34.32, 0.00, 55, 30, '2026-05-18 10:15:44'),
(21, 38, 35.99, 0.00, 44, 0, '2026-05-18 10:15:44'),
(22, 39, 44.43, 0.00, 9, 0, '2026-05-18 10:15:45'),
(23, 40, 42.87, 0.00, 17, 0, '2026-05-18 10:15:45'),
(24, 41, 44.00, 0.00, 12, 0, '2026-05-18 10:15:45'),
(25, 42, 43.85, 0.00, 12, 0, '2026-05-18 10:15:46'),
(26, 43, 42.50, 0.00, 16, 0, '2026-05-18 10:15:46'),
(27, 44, 44.52, 0.00, 9, 0, '2026-05-18 10:15:47'),
(28, 45, 42.25, 0.00, 19, 0, '2026-05-18 10:15:47'),
(29, 46, 38.98, 0.00, 34, 0, '2026-05-18 10:15:48'),
(30, 47, 33.25, 0.00, 58, 30, '2026-05-18 10:15:48'),
(31, 48, 30.84, 0.00, 72, 55, '2026-05-18 10:15:49'),
(32, 49, 31.68, 0.00, 66, 30, '2026-05-18 10:15:49'),
(33, 50, 33.31, 0.00, 57, 30, '2026-05-18 10:15:50'),
(34, 51, 30.71, 0.00, 71, 55, '2026-05-18 10:15:50'),
(35, 52, 31.50, 0.00, 62, 30, '2026-05-18 10:15:51'),
(36, 53, 33.90, 0.00, 52, 30, '2026-05-18 10:15:51'),
(37, 54, 32.78, 0.00, 56, 30, '2026-05-18 10:15:52'),
(38, 55, 36.46, 0.00, 45, 0, '2026-05-18 10:15:52'),
(39, 56, 34.53, 0.00, 53, 30, '2026-05-18 10:15:53'),
(40, 57, 31.97, 0.00, 67, 30, '2026-05-18 10:15:53'),
(41, 58, 32.87, 0.00, 63, 30, '2026-05-18 10:15:54'),
(42, 59, 33.32, 0.00, 60, 30, '2026-05-18 10:15:54'),
(43, 60, 32.67, 0.00, 58, 30, '2026-05-18 10:15:55'),
(44, 61, 33.07, 0.00, 60, 30, '2026-05-18 10:15:55'),
(45, 62, 34.08, 0.00, 57, 30, '2026-05-18 10:15:56'),
(46, 63, 33.00, 0.00, 60, 30, '2026-05-18 10:15:56'),
(47, 64, 33.43, 0.00, 59, 30, '2026-05-18 10:15:57'),
(48, 1, 31.82, 0.00, 62, 35, '2026-05-18 10:21:43'),
(49, 2, 29.60, 0.00, 77, 60, '2026-05-18 10:21:44'),
(50, 3, 43.46, 0.00, 14, 10, '2026-05-18 10:21:45'),
(51, 4, 39.86, 0.00, 31, 10, '2026-05-18 10:21:47'),
(52, 5, 31.15, 0.00, 65, 35, '2026-05-18 10:21:48'),
(53, 6, 30.94, 0.00, 67, 35, '2026-05-18 10:21:49'),
(54, 7, 33.63, 0.00, 58, 35, '2026-05-18 10:21:50'),
(55, 8, 33.40, 0.00, 58, 35, '2026-05-18 10:21:51'),
(56, 9, 39.20, 0.00, 30, 10, '2026-05-18 10:21:53'),
(57, 10, 37.98, 0.00, 37, 10, '2026-05-18 10:21:54'),
(58, 11, 37.95, 0.00, 37, 10, '2026-05-18 10:21:55'),
(59, 12, 34.13, 0.00, 54, 35, '2026-05-18 10:21:56'),
(60, 13, 33.64, 0.00, 55, 35, '2026-05-18 10:21:57'),
(61, 14, 38.53, 0.00, 35, 10, '2026-05-18 10:21:59'),
(62, 15, 27.66, 0.00, 85, 80, '2026-05-18 10:22:00'),
(63, 16, 28.16, 0.00, 81, 60, '2026-05-18 10:22:01'),
(64, 17, 36.00, 0.00, 45, 10, '2026-05-18 10:22:02'),
(65, 18, 41.50, 0.00, 20, 10, '2026-05-18 10:22:03'),
(66, 19, 29.85, 0.41, 73, 60, '2026-05-18 10:22:05'),
(67, 20, 35.45, 0.00, 46, 10, '2026-05-18 10:22:06'),
(68, 21, 34.44, 0.00, 46, 10, '2026-05-18 10:22:07'),
(69, 22, 32.46, 0.00, 62, 35, '2026-05-18 10:22:08'),
(70, 23, 32.19, 0.00, 64, 35, '2026-05-18 10:22:09'),
(71, 24, 33.36, 0.00, 57, 35, '2026-05-18 10:22:11'),
(72, 25, 30.81, 0.00, 72, 60, '2026-05-18 10:22:12'),
(73, 26, 31.83, 0.00, 63, 35, '2026-05-18 10:22:13'),
(74, 27, 34.29, 0.00, 45, 10, '2026-05-18 10:22:14'),
(75, 28, 31.43, 0.00, 68, 35, '2026-05-18 10:22:15'),
(76, 29, 30.41, 0.00, 74, 60, '2026-05-18 10:22:16'),
(77, 30, 34.71, 0.00, 45, 10, '2026-05-18 10:22:18'),
(78, 31, 36.61, 0.00, 44, 10, '2026-05-18 10:22:19'),
(79, 32, 43.33, 0.00, 15, 10, '2026-05-18 10:22:20'),
(80, 33, 39.31, 0.00, 31, 10, '2026-05-18 10:22:21'),
(81, 34, 42.00, 0.00, 18, 10, '2026-05-18 10:22:22'),
(82, 35, 42.85, 0.00, 16, 10, '2026-05-18 10:22:24'),
(83, 36, 42.81, 0.00, 16, 10, '2026-05-18 10:22:25'),
(84, 37, 34.32, 0.00, 55, 35, '2026-05-18 10:22:26'),
(85, 38, 35.99, 0.00, 44, 10, '2026-05-18 10:22:27'),
(86, 39, 44.43, 0.00, 9, 10, '2026-05-18 10:22:28'),
(87, 40, 42.87, 0.00, 17, 10, '2026-05-18 10:22:30'),
(88, 41, 44.00, 0.00, 12, 10, '2026-05-18 10:22:31'),
(89, 42, 43.85, 0.00, 12, 10, '2026-05-18 10:22:32'),
(90, 43, 42.50, 0.00, 16, 10, '2026-05-18 10:22:33'),
(91, 44, 44.52, 0.00, 9, 10, '2026-05-18 10:22:34'),
(92, 45, 42.25, 0.00, 19, 10, '2026-05-18 10:22:36'),
(93, 46, 38.98, 0.00, 34, 10, '2026-05-18 10:22:37'),
(94, 47, 33.25, 0.00, 58, 35, '2026-05-18 10:22:38'),
(95, 48, 30.84, 0.00, 72, 60, '2026-05-18 10:22:39'),
(96, 49, 31.68, 0.00, 66, 35, '2026-05-18 10:22:40'),
(97, 50, 33.31, 0.00, 57, 35, '2026-05-18 10:22:41'),
(98, 51, 30.71, 0.00, 71, 60, '2026-05-18 10:22:43'),
(99, 52, 31.50, 0.00, 62, 35, '2026-05-18 10:22:44'),
(100, 53, 33.90, 0.00, 52, 35, '2026-05-18 10:22:45'),
(101, 54, 32.78, 0.00, 56, 35, '2026-05-18 10:22:46'),
(102, 55, 36.46, 0.00, 45, 10, '2026-05-18 10:22:47'),
(103, 56, 34.53, 0.00, 53, 35, '2026-05-18 10:22:49'),
(104, 57, 31.97, 0.00, 67, 35, '2026-05-18 10:22:50'),
(105, 58, 32.87, 0.00, 63, 35, '2026-05-18 10:22:51'),
(106, 59, 33.32, 0.00, 60, 35, '2026-05-18 10:22:52'),
(107, 60, 32.67, 0.00, 58, 35, '2026-05-18 10:22:53'),
(108, 61, 33.07, 0.00, 60, 35, '2026-05-18 10:22:54'),
(109, 62, 34.08, 0.00, 57, 35, '2026-05-18 10:22:56'),
(110, 63, 33.00, 0.00, 60, 35, '2026-05-18 10:22:57'),
(111, 64, 33.43, 0.00, 59, 35, '2026-05-18 10:22:58'),
(112, 1, 29.99, 0.00, 84, 60, '2026-05-19 18:27:09'),
(113, 2, 29.97, 0.00, 89, 80, '2026-05-19 18:27:11'),
(114, 3, 31.78, 0.00, 61, 35, '2026-05-19 18:27:12'),
(115, 4, 28.84, 0.00, 83, 60, '2026-05-19 18:27:13'),
(116, 5, 26.44, 0.00, 86, 80, '2026-05-19 18:27:14'),
(117, 6, 28.10, 0.00, 84, 60, '2026-05-19 18:27:15'),
(118, 7, 26.48, 0.00, 89, 80, '2026-05-19 18:27:16'),
(119, 8, 26.98, 0.00, 85, 80, '2026-05-19 18:27:18'),
(120, 9, 27.66, 0.00, 76, 60, '2026-05-19 18:27:19'),
(121, 10, 30.02, 0.00, 84, 60, '2026-05-19 18:27:20'),
(122, 11, 27.25, 0.00, 83, 60, '2026-05-19 18:27:21'),
(123, 12, 26.92, 0.00, 80, 60, '2026-05-19 18:27:22'),
(124, 13, 26.84, 0.00, 83, 60, '2026-05-19 18:27:23'),
(125, 14, 26.87, 0.00, 81, 60, '2026-05-19 18:27:25'),
(126, 15, 26.77, 0.00, 80, 60, '2026-05-19 18:27:26'),
(127, 16, 30.01, 0.00, 76, 60, '2026-05-19 18:27:27'),
(128, 17, 26.25, 0.00, 76, 60, '2026-05-19 18:27:28'),
(129, 18, 28.87, 0.00, 73, 60, '2026-05-19 18:27:29'),
(130, 19, 27.39, 0.00, 84, 60, '2026-05-19 18:27:31'),
(131, 20, 27.81, 0.00, 80, 60, '2026-05-19 18:27:32'),
(132, 21, 25.93, 0.00, 89, 80, '2026-05-19 18:27:33'),
(133, 22, 28.05, 0.00, 94, 80, '2026-05-19 18:27:34'),
(134, 23, 27.15, 0.00, 86, 80, '2026-05-19 18:27:35'),
(135, 24, 26.47, 0.00, 91, 80, '2026-05-19 18:27:36'),
(136, 25, 27.06, 0.00, 87, 80, '2026-05-19 18:27:38'),
(137, 26, 27.30, 0.00, 88, 80, '2026-05-19 18:27:39'),
(138, 27, 25.18, 0.00, 93, 80, '2026-05-19 18:27:40'),
(139, 28, 28.59, 0.00, 82, 60, '2026-05-19 18:27:41'),
(140, 29, 28.55, 0.00, 83, 60, '2026-05-19 18:27:42'),
(141, 30, 25.40, 0.00, 94, 80, '2026-05-19 18:27:43'),
(142, 31, 28.50, 0.00, 80, 60, '2026-05-19 18:27:45'),
(143, 32, 32.30, 0.00, 58, 35, '2026-05-19 18:27:46'),
(144, 33, 28.76, 0.00, 80, 60, '2026-05-19 18:27:47'),
(145, 34, 29.32, 0.00, 74, 60, '2026-05-19 18:27:48'),
(146, 35, 30.37, 0.00, 68, 35, '2026-05-19 18:27:49'),
(147, 36, 30.13, 0.00, 70, 60, '2026-05-19 18:27:50'),
(148, 37, 28.60, 0.00, 78, 60, '2026-05-19 18:27:52'),
(149, 38, 28.32, 0.00, 87, 80, '2026-05-19 18:27:53'),
(150, 39, 30.18, 0.00, 73, 60, '2026-05-19 18:27:54'),
(151, 40, 29.34, 0.00, 79, 60, '2026-05-19 18:27:55'),
(152, 41, 30.00, 0.00, 75, 60, '2026-05-19 18:27:56'),
(153, 42, 30.40, 0.00, 70, 60, '2026-05-19 18:27:57'),
(154, 43, 29.57, 0.00, 75, 60, '2026-05-19 18:27:59'),
(155, 44, 30.47, 0.00, 71, 60, '2026-05-19 18:28:00'),
(156, 45, 29.23, 0.00, 78, 60, '2026-05-19 18:28:01'),
(157, 46, 29.49, 0.00, 78, 60, '2026-05-19 18:28:02'),
(158, 47, 26.65, 0.00, 87, 80, '2026-05-19 18:28:03'),
(159, 48, 26.68, 0.00, 87, 80, '2026-05-19 18:28:05'),
(160, 49, 26.38, 0.00, 86, 80, '2026-05-19 18:28:06'),
(161, 50, 28.88, 0.00, 86, 80, '2026-05-19 18:28:07'),
(162, 51, 28.74, 0.00, 81, 60, '2026-05-19 18:28:09'),
(163, 52, 28.01, 0.00, 85, 80, '2026-05-19 18:28:10'),
(164, 53, 28.90, 0.00, 83, 60, '2026-05-19 18:28:11'),
(165, 54, 28.28, 0.00, 86, 80, '2026-05-19 18:28:12'),
(166, 55, 27.90, 0.00, 83, 60, '2026-05-19 18:28:14'),
(167, 56, 27.21, 0.00, 87, 80, '2026-05-19 18:28:15'),
(168, 57, 26.77, 0.00, 88, 80, '2026-05-19 18:28:16'),
(169, 58, 26.95, 0.00, 87, 80, '2026-05-19 18:28:17'),
(170, 59, 27.51, 0.00, 86, 80, '2026-05-19 18:28:18'),
(171, 60, 27.11, 0.00, 81, 60, '2026-05-19 18:28:19'),
(172, 61, 27.52, 0.00, 83, 60, '2026-05-19 18:28:21'),
(173, 62, 27.56, 0.00, 85, 80, '2026-05-19 18:28:22'),
(174, 63, 27.02, 0.00, 86, 80, '2026-05-19 18:28:23'),
(175, 64, 27.05, 0.00, 87, 80, '2026-05-19 18:28:25'),
(176, 1, 28.99, 0.00, 89, 80, '2026-05-19 20:00:42'),
(177, 2, 27.58, 0.00, 86, 80, '2026-05-19 20:00:43'),
(178, 3, 30.80, 0.00, 68, 35, '2026-05-19 20:00:44'),
(179, 4, 28.20, 0.00, 87, 80, '2026-05-19 20:00:46'),
(180, 5, 25.46, 6.48, 89, 80, '2026-05-19 20:00:47'),
(181, 6, 27.59, 0.00, 89, 80, '2026-05-19 20:00:48'),
(182, 7, 25.67, 0.00, 93, 80, '2026-05-19 20:00:49'),
(183, 8, 26.25, 0.00, 85, 80, '2026-05-19 20:00:50'),
(184, 9, 26.97, 0.00, 81, 60, '2026-05-19 20:00:52'),
(185, 10, 29.02, 0.00, 89, 80, '2026-05-19 20:00:53'),
(186, 11, 27.45, 0.00, 90, 80, '2026-05-19 20:00:54'),
(187, 12, 26.10, 0.00, 80, 60, '2026-05-19 20:00:55'),
(188, 13, 27.03, 0.00, 89, 80, '2026-05-19 20:00:57'),
(189, 14, 26.84, 0.00, 79, 60, '2026-05-19 20:00:58'),
(190, 15, 26.79, 0.00, 88, 80, '2026-05-19 20:00:59'),
(191, 16, 29.01, 0.00, 84, 60, '2026-05-19 20:01:00'),
(192, 17, 26.24, 0.00, 80, 60, '2026-05-19 20:01:01'),
(193, 18, 28.03, 0.00, 76, 60, '2026-05-19 20:01:03'),
(194, 19, 27.67, 0.00, 87, 80, '2026-05-19 20:01:04'),
(195, 20, 27.28, 0.00, 78, 60, '2026-05-19 20:01:05'),
(196, 21, 25.20, 0.00, 92, 80, '2026-05-19 20:01:06'),
(197, 22, 25.62, 0.00, 88, 80, '2026-05-19 20:01:08'),
(198, 23, 27.34, 0.00, 88, 80, '2026-05-19 20:01:09'),
(199, 24, 26.34, 0.00, 93, 80, '2026-05-19 20:01:10'),
(200, 25, 26.80, 0.00, 86, 80, '2026-05-19 20:01:11'),
(201, 26, 26.84, 0.00, 89, 80, '2026-05-19 20:01:13'),
(202, 27, 24.74, 0.00, 95, 80, '2026-05-19 20:01:14'),
(203, 28, 27.78, 0.00, 85, 80, '2026-05-19 20:01:15'),
(204, 29, 28.14, 0.00, 84, 60, '2026-05-19 20:01:16'),
(205, 30, 24.98, 0.00, 96, 80, '2026-05-19 20:01:18'),
(206, 31, 27.23, 0.00, 83, 60, '2026-05-19 20:01:19'),
(207, 32, 31.35, 0.00, 64, 35, '2026-05-19 20:01:20'),
(208, 33, 28.35, 0.00, 79, 60, '2026-05-19 20:01:21'),
(209, 34, 28.70, 0.00, 77, 60, '2026-05-19 20:01:22'),
(210, 35, 29.30, 0.00, 74, 60, '2026-05-19 20:01:24'),
(211, 36, 29.14, 0.00, 75, 60, '2026-05-19 20:01:25'),
(212, 37, 28.13, 0.00, 79, 60, '2026-05-19 20:01:26'),
(213, 38, 27.85, 0.00, 89, 80, '2026-05-19 20:01:27'),
(214, 39, 29.53, 0.00, 77, 60, '2026-05-19 20:01:29'),
(215, 40, 28.65, 0.00, 83, 60, '2026-05-19 20:01:30'),
(216, 41, 29.14, 0.00, 79, 60, '2026-05-19 20:01:31'),
(217, 42, 29.61, 0.00, 76, 60, '2026-05-19 20:01:32'),
(218, 43, 28.84, 0.00, 80, 60, '2026-05-19 20:01:33'),
(219, 44, 29.66, 0.00, 75, 60, '2026-05-19 20:01:35'),
(220, 45, 28.37, 0.00, 84, 60, '2026-05-19 20:01:36'),
(221, 46, 28.86, 0.00, 81, 60, '2026-05-19 20:01:37'),
(222, 47, 25.85, 0.00, 90, 80, '2026-05-19 20:01:38'),
(223, 48, 25.76, 0.00, 91, 80, '2026-05-19 20:01:40'),
(224, 49, 25.87, 0.00, 87, 80, '2026-05-19 20:01:41'),
(225, 50, 28.32, 0.00, 88, 80, '2026-05-19 20:01:42'),
(226, 51, 28.42, 0.00, 85, 80, '2026-05-19 20:01:43'),
(227, 52, 27.63, 0.00, 90, 80, '2026-05-19 20:01:45'),
(228, 53, 28.48, 0.00, 86, 80, '2026-05-19 20:01:46'),
(229, 54, 27.96, 0.00, 90, 80, '2026-05-19 20:01:47'),
(230, 55, 27.75, 0.00, 83, 60, '2026-05-19 20:01:48'),
(231, 56, 26.07, 0.00, 91, 80, '2026-05-19 20:01:49'),
(232, 57, 26.48, 0.00, 90, 80, '2026-05-19 20:01:51'),
(233, 58, 26.30, 0.00, 89, 80, '2026-05-19 20:01:52'),
(234, 59, 27.15, 0.00, 87, 80, '2026-05-19 20:01:53'),
(235, 60, 26.37, 0.00, 84, 60, '2026-05-19 20:01:54'),
(236, 61, 26.99, 0.00, 86, 80, '2026-05-19 20:01:56'),
(237, 62, 26.88, 0.00, 87, 80, '2026-05-19 20:01:57'),
(238, 63, 26.30, 0.00, 88, 80, '2026-05-19 20:01:58'),
(239, 64, 26.34, 0.00, 89, 80, '2026-05-19 20:01:59'),
(240, 1, 30.99, 0.00, 79, 60, '2026-05-20 03:43:34'),
(241, 2, 31.97, 0.00, 74, 60, '2026-05-20 03:43:35'),
(242, 3, 35.82, 0.00, 46, 10, '2026-05-20 03:43:36'),
(243, 4, 36.19, 0.00, 49, 10, '2026-05-20 03:43:37'),
(244, 5, 31.23, 0.00, 67, 35, '2026-05-20 03:43:38'),
(245, 6, 34.66, 0.00, 54, 35, '2026-05-20 03:43:39'),
(246, 7, 32.01, 0.00, 63, 35, '2026-05-20 03:43:41'),
(247, 8, 32.43, 0.00, 62, 35, '2026-05-20 03:43:42'),
(248, 9, 34.29, 0.00, 55, 35, '2026-05-20 03:43:43'),
(249, 10, 31.02, 0.00, 79, 60, '2026-05-20 03:43:44'),
(250, 11, 34.95, 0.00, 54, 35, '2026-05-20 03:43:45'),
(251, 12, 32.74, 0.00, 60, 35, '2026-05-20 03:43:46'),
(252, 13, 34.39, 0.00, 55, 35, '2026-05-20 03:43:47'),
(253, 14, 34.01, 0.00, 56, 35, '2026-05-20 03:43:49'),
(254, 15, 33.58, 0.00, 59, 35, '2026-05-20 03:43:50'),
(255, 16, 31.01, 0.00, 56, 35, '2026-05-20 03:43:51'),
(256, 17, 33.86, 0.00, 56, 35, '2026-05-20 03:43:52'),
(257, 18, 34.30, 0.00, 56, 35, '2026-05-20 03:43:53'),
(258, 19, 32.34, 0.00, 65, 35, '2026-05-20 03:43:54'),
(259, 20, 33.22, 0.00, 60, 35, '2026-05-20 03:43:56'),
(260, 21, 37.23, 0.00, 41, 10, '2026-05-20 03:43:57'),
(261, 22, 32.05, 0.00, 79, 60, '2026-05-20 03:43:58'),
(262, 23, 33.19, 0.00, 60, 35, '2026-05-20 03:43:59'),
(263, 24, 33.76, 0.00, 59, 35, '2026-05-20 03:44:01'),
(264, 25, 31.72, 0.00, 66, 35, '2026-05-20 03:44:02'),
(265, 26, 33.31, 0.00, 59, 35, '2026-05-20 03:44:03'),
(266, 27, 35.19, 0.00, 49, 10, '2026-05-20 03:44:04'),
(267, 28, 32.93, 0.00, 60, 35, '2026-05-20 03:44:05'),
(268, 29, 31.95, 0.00, 65, 35, '2026-05-20 03:44:07'),
(269, 30, 32.94, 0.00, 58, 35, '2026-05-20 03:44:08'),
(270, 31, 33.18, 0.00, 59, 35, '2026-05-20 03:44:09'),
(271, 32, 36.46, 0.14, 42, 10, '2026-05-20 03:44:10'),
(272, 33, 34.16, 0.00, 52, 35, '2026-05-20 03:44:11'),
(273, 34, 34.64, 0.00, 52, 35, '2026-05-20 03:44:12'),
(274, 35, 35.30, 0.00, 50, 35, '2026-05-20 03:44:14'),
(275, 36, 34.83, 0.00, 53, 35, '2026-05-20 03:44:15'),
(276, 37, 31.15, 0.00, 71, 60, '2026-05-20 03:44:16'),
(277, 38, 35.74, 0.00, 50, 35, '2026-05-20 03:44:17'),
(278, 39, 35.03, 0.00, 53, 35, '2026-05-20 03:44:18'),
(279, 40, 35.73, 0.00, 51, 35, '2026-05-20 03:44:19'),
(280, 41, 35.20, 0.00, 53, 35, '2026-05-20 03:44:21'),
(281, 42, 34.98, 0.00, 53, 35, '2026-05-20 03:44:22'),
(282, 43, 35.23, 0.00, 52, 35, '2026-05-20 03:44:23'),
(283, 44, 34.82, 0.00, 54, 35, '2026-05-20 03:44:24'),
(284, 45, 35.66, 0.00, 51, 35, '2026-05-20 03:44:25'),
(285, 46, 36.53, 0.00, 47, 10, '2026-05-20 03:44:26'),
(286, 47, 32.34, 0.00, 62, 35, '2026-05-20 03:44:28'),
(287, 48, 31.67, 0.00, 65, 35, '2026-05-20 03:44:29'),
(288, 49, 31.33, 0.42, 68, 35, '2026-05-20 03:44:30'),
(289, 50, 34.58, 0.00, 53, 35, '2026-05-20 03:44:31'),
(290, 51, 33.04, 0.00, 61, 35, '2026-05-20 03:44:32'),
(291, 52, 35.02, 0.00, 52, 35, '2026-05-20 03:44:33'),
(292, 53, 35.58, 0.00, 49, 10, '2026-05-20 03:44:35'),
(293, 54, 35.54, 0.00, 50, 35, '2026-05-20 03:44:36'),
(294, 55, 33.22, 0.00, 56, 35, '2026-05-20 03:44:37'),
(295, 56, 32.08, 0.87, 63, 35, '2026-05-20 03:44:38'),
(296, 57, 30.61, 0.00, 69, 35, '2026-05-20 03:44:39'),
(297, 58, 31.66, 1.00, 66, 35, '2026-05-20 03:44:40'),
(298, 59, 32.14, 0.00, 61, 35, '2026-05-20 03:44:42'),
(299, 60, 31.96, 0.00, 62, 35, '2026-05-20 03:44:43'),
(300, 61, 32.76, 0.00, 57, 35, '2026-05-20 03:44:44'),
(301, 62, 32.17, 0.00, 63, 35, '2026-05-20 03:44:45'),
(302, 63, 32.48, 0.12, 61, 35, '2026-05-20 03:44:46'),
(303, 64, 32.18, 0.00, 62, 35, '2026-05-20 03:44:47'),
(304, 1, 32.99, 0.00, 70, 60, '2026-05-20 12:04:24'),
(305, 2, 32.97, 0.00, 70, 60, '2026-05-20 12:04:25'),
(306, 3, 34.96, 0.00, 45, 10, '2026-05-20 12:04:26'),
(307, 4, 33.48, 0.00, 56, 35, '2026-05-20 12:04:27'),
(308, 5, 32.35, 0.00, 63, 35, '2026-05-20 12:04:28'),
(309, 6, 32.71, 0.00, 60, 35, '2026-05-20 12:04:29'),
(310, 7, 32.78, 0.00, 64, 35, '2026-05-20 12:04:31'),
(311, 8, 30.63, 0.00, 72, 60, '2026-05-20 12:04:32'),
(312, 9, 32.99, 0.00, 61, 35, '2026-05-20 12:04:33'),
(313, 10, 33.02, 0.00, 70, 60, '2026-05-20 12:04:34'),
(314, 11, 32.09, 0.00, 63, 35, '2026-05-20 12:04:35'),
(315, 12, 32.09, 0.00, 67, 35, '2026-05-20 12:04:36'),
(316, 13, 32.52, 0.00, 62, 35, '2026-05-20 12:04:38'),
(317, 14, 33.26, 0.00, 59, 35, '2026-05-20 12:04:39'),
(318, 15, 31.96, 0.00, 65, 35, '2026-05-20 12:04:40'),
(319, 16, 33.01, 0.00, 59, 35, '2026-05-20 12:04:41'),
(320, 17, 32.09, 0.00, 65, 35, '2026-05-20 12:04:42'),
(321, 18, 33.51, 0.00, 58, 35, '2026-05-20 12:04:43'),
(322, 19, 31.17, 0.00, 70, 60, '2026-05-20 12:04:44'),
(323, 20, 32.35, 0.00, 64, 35, '2026-05-20 12:04:46'),
(324, 21, 31.02, 0.00, 63, 35, '2026-05-20 12:04:47'),
(325, 22, 33.05, 0.00, 70, 60, '2026-05-20 12:04:48'),
(326, 23, 31.51, 0.00, 66, 35, '2026-05-20 12:04:49'),
(327, 24, 31.52, 0.00, 67, 35, '2026-05-20 12:04:50'),
(328, 25, 29.97, 0.00, 77, 60, '2026-05-20 12:04:51'),
(329, 26, 30.53, 0.00, 70, 60, '2026-05-20 12:04:53'),
(330, 27, 31.16, 0.00, 62, 35, '2026-05-20 12:04:54'),
(331, 28, 31.46, 0.00, 67, 35, '2026-05-20 12:04:55'),
(332, 29, 30.69, 0.00, 72, 60, '2026-05-20 12:04:56'),
(333, 30, 31.27, 0.00, 64, 35, '2026-05-20 12:04:57'),
(334, 31, 32.39, 0.00, 63, 35, '2026-05-20 12:04:59'),
(335, 32, 37.27, 0.24, 39, 10, '2026-05-20 12:05:00'),
(336, 33, 34.54, 0.00, 53, 35, '2026-05-20 12:05:01'),
(337, 34, 35.36, 0.00, 47, 10, '2026-05-20 12:05:02'),
(338, 35, 32.49, 0.00, 57, 35, '2026-05-20 12:05:03'),
(339, 36, 34.50, 0.00, 51, 35, '2026-05-20 12:05:04'),
(340, 37, 32.30, 0.00, 69, 35, '2026-05-20 12:05:06'),
(341, 38, 31.29, 0.00, 67, 35, '2026-05-20 12:05:07'),
(342, 39, 36.62, 0.00, 43, 10, '2026-05-20 12:05:08'),
(343, 40, 34.17, 0.00, 52, 35, '2026-05-20 12:05:09'),
(344, 41, 36.11, 0.00, 46, 10, '2026-05-20 12:05:10'),
(345, 42, 35.21, 0.00, 48, 10, '2026-05-20 12:05:11'),
(346, 43, 34.59, 0.00, 52, 35, '2026-05-20 12:05:12'),
(347, 44, 36.79, 0.00, 43, 10, '2026-05-20 12:05:14'),
(348, 45, 33.71, 0.00, 55, 35, '2026-05-20 12:05:15'),
(349, 46, 35.38, 0.00, 49, 10, '2026-05-20 12:05:16'),
(350, 47, 32.57, 1.78, 68, 35, '2026-05-20 12:05:17'),
(351, 48, 31.46, 0.00, 72, 60, '2026-05-20 12:05:18'),
(352, 49, 30.59, 0.00, 72, 60, '2026-05-20 12:05:19'),
(353, 50, 31.96, 0.00, 62, 35, '2026-05-20 12:05:21'),
(354, 51, 31.63, 0.00, 67, 35, '2026-05-20 12:05:22'),
(355, 52, 33.07, 0.00, 57, 35, '2026-05-20 12:05:24'),
(356, 53, 33.07, 0.00, 56, 35, '2026-05-20 12:05:25'),
(357, 54, 32.29, 0.00, 60, 35, '2026-05-20 12:05:26'),
(358, 55, 32.33, 0.00, 61, 35, '2026-05-20 12:05:28'),
(359, 56, 32.82, 0.00, 63, 35, '2026-05-20 12:05:29'),
(360, 57, 32.08, 0.00, 67, 35, '2026-05-20 12:05:30'),
(361, 58, 32.19, 0.00, 66, 35, '2026-05-20 12:05:31'),
(362, 59, 32.01, 0.00, 67, 35, '2026-05-20 12:05:32'),
(363, 60, 31.62, 0.00, 66, 35, '2026-05-20 12:05:34'),
(364, 61, 32.27, 0.00, 64, 35, '2026-05-20 12:05:35'),
(365, 62, 31.59, 0.00, 68, 35, '2026-05-20 12:05:36'),
(366, 63, 31.26, 0.00, 71, 60, '2026-05-20 12:05:37'),
(367, 64, 31.68, 0.00, 68, 35, '2026-05-20 12:05:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_schedule`
--
ALTER TABLE `activity_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `farmer_crop_id` (`farmer_crop_id`);

--
-- Indexes for table `advisory_feed`
--
ALTER TABLE `advisory_feed`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `answers`
--
ALTER TABLE `answers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_question_answer` (`question_id`),
  ADD UNIQUE KEY `question_id` (`question_id`),
  ADD KEY `expert_id` (`expert_id`);
ALTER TABLE `answers` ADD FULLTEXT KEY `ft_answer` (`answer`);

--
-- Indexes for table `banned_pesticides`
--
ALTER TABLE `banned_pesticides`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `crops`
--
ALTER TABLE `crops`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `crop_calendar`
--
ALTER TABLE `crop_calendar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `crop_id` (`crop_id`);

--
-- Indexes for table `crop_diseases`
--
ALTER TABLE `crop_diseases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `crop_id` (`crop_id`);

--
-- Indexes for table `crop_rotation_rules`
--
ALTER TABLE `crop_rotation_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `crop_id` (`crop_id`),
  ADD KEY `next_crop_id` (`next_crop_id`);

--
-- Indexes for table `dae_offices`
--
ALTER TABLE `dae_offices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_location` (`location_id`);

--
-- Indexes for table `farmer_crops`
--
ALTER TABLE `farmer_crops`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `crop_id` (`crop_id`),
  ADD KEY `field_id` (`field_id`),
  ADD KEY `fk_farmer_seed` (`seed_id`);

--
-- Indexes for table `fields`
--
ALTER TABLE `fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `irrigation_logs`
--
ALTER TABLE `irrigation_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `field_id` (`field_id`);

--
-- Indexes for table `loan_applications`
--
ALTER TABLE `loan_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `loan_products`
--
ALTER TABLE `loan_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `provider_id` (`provider_id`);

--
-- Indexes for table `loan_providers`
--
ALTER TABLE `loan_providers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `market_prices`
--
ALTER TABLE `market_prices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `crop_id` (`crop_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `pesticide_guidelines`
--
ALTER TABLE `pesticide_guidelines`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pests`
--
ALTER TABLE `pests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pest_images`
--
ALTER TABLE `pest_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_id` (`report_id`);

--
-- Indexes for table `pest_reports`
--
ALTER TABLE `pest_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `crop_id` (`crop_id`),
  ADD KEY `pest_id` (`pest_id`),
  ADD KEY `field_id` (`field_id`),
  ADD KEY `fk_pest_user` (`user_id`),
  ADD KEY `idx_outbreak_group` (`outbreak_group`);

--
-- Indexes for table `pest_reviews`
--
ALTER TABLE `pest_reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `report_id` (`report_id`),
  ADD KEY `expert_id` (`expert_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);
ALTER TABLE `questions` ADD FULLTEXT KEY `ft_question` (`question`);

--
-- Indexes for table `seeds`
--
ALTER TABLE `seeds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `crop_id` (`crop_id`);

--
-- Indexes for table `soil_crop_bonus`
--
ALTER TABLE `soil_crop_bonus`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_soil_crop` (`soil_type`,`crop_id`),
  ADD KEY `crop_id` (`crop_id`);

--
-- Indexes for table `solutions`
--
ALTER TABLE `solutions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `disease_id` (`disease_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `weather_api_config`
--
ALTER TABLE `weather_api_config`
  ADD PRIMARY KEY (`id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `weather_data`
--
ALTER TABLE `weather_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `location_id` (`location_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_schedule`
--
ALTER TABLE `activity_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `advisory_feed`
--
ALTER TABLE `advisory_feed`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `answers`
--
ALTER TABLE `answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `banned_pesticides`
--
ALTER TABLE `banned_pesticides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `crops`
--
ALTER TABLE `crops`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `crop_calendar`
--
ALTER TABLE `crop_calendar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `crop_diseases`
--
ALTER TABLE `crop_diseases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `crop_rotation_rules`
--
ALTER TABLE `crop_rotation_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `dae_offices`
--
ALTER TABLE `dae_offices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `farmer_crops`
--
ALTER TABLE `farmer_crops`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `fields`
--
ALTER TABLE `fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `irrigation_logs`
--
ALTER TABLE `irrigation_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_applications`
--
ALTER TABLE `loan_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `loan_products`
--
ALTER TABLE `loan_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `loan_providers`
--
ALTER TABLE `loan_providers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `market_prices`
--
ALTER TABLE `market_prices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `pesticide_guidelines`
--
ALTER TABLE `pesticide_guidelines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `pests`
--
ALTER TABLE `pests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `pest_images`
--
ALTER TABLE `pest_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `pest_reports`
--
ALTER TABLE `pest_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `pest_reviews`
--
ALTER TABLE `pest_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `seeds`
--
ALTER TABLE `seeds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `soil_crop_bonus`
--
ALTER TABLE `soil_crop_bonus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `solutions`
--
ALTER TABLE `solutions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `weather_api_config`
--
ALTER TABLE `weather_api_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `weather_data`
--
ALTER TABLE `weather_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=368;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_schedule`
--
ALTER TABLE `activity_schedule`
  ADD CONSTRAINT `activity_schedule_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `activity_schedule_ibfk_2` FOREIGN KEY (`farmer_crop_id`) REFERENCES `farmer_crops` (`id`);

--
-- Constraints for table `answers`
--
ALTER TABLE `answers`
  ADD CONSTRAINT `answers_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`),
  ADD CONSTRAINT `answers_ibfk_2` FOREIGN KEY (`expert_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `crop_calendar`
--
ALTER TABLE `crop_calendar`
  ADD CONSTRAINT `crop_calendar_ibfk_1` FOREIGN KEY (`crop_id`) REFERENCES `crops` (`id`);

--
-- Constraints for table `crop_diseases`
--
ALTER TABLE `crop_diseases`
  ADD CONSTRAINT `crop_diseases_ibfk_1` FOREIGN KEY (`crop_id`) REFERENCES `crops` (`id`);

--
-- Constraints for table `crop_rotation_rules`
--
ALTER TABLE `crop_rotation_rules`
  ADD CONSTRAINT `crr_crop_fk` FOREIGN KEY (`crop_id`) REFERENCES `crops` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `crr_next_crop_fk` FOREIGN KEY (`next_crop_id`) REFERENCES `crops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `dae_offices`
--
ALTER TABLE `dae_offices`
  ADD CONSTRAINT `fk_dae_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `farmer_crops`
--
ALTER TABLE `farmer_crops`
  ADD CONSTRAINT `farmer_crops_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `farmer_crops_ibfk_2` FOREIGN KEY (`crop_id`) REFERENCES `crops` (`id`),
  ADD CONSTRAINT `farmer_crops_ibfk_3` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`),
  ADD CONSTRAINT `fk_farmer_seed` FOREIGN KEY (`seed_id`) REFERENCES `seeds` (`id`);

--
-- Constraints for table `fields`
--
ALTER TABLE `fields`
  ADD CONSTRAINT `fields_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fields_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`);

--
-- Constraints for table `irrigation_logs`
--
ALTER TABLE `irrigation_logs`
  ADD CONSTRAINT `irrigation_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `irrigation_logs_ibfk_2` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`);

--
-- Constraints for table `loan_applications`
--
ALTER TABLE `loan_applications`
  ADD CONSTRAINT `loan_applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loan_applications_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `loan_products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loan_products`
--
ALTER TABLE `loan_products`
  ADD CONSTRAINT `loan_products_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `loan_providers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `market_prices`
--
ALTER TABLE `market_prices`
  ADD CONSTRAINT `market_prices_ibfk_1` FOREIGN KEY (`crop_id`) REFERENCES `crops` (`id`),
  ADD CONSTRAINT `market_prices_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `pest_images`
--
ALTER TABLE `pest_images`
  ADD CONSTRAINT `pest_images_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `pest_reports` (`id`);

--
-- Constraints for table `pest_reports`
--
ALTER TABLE `pest_reports`
  ADD CONSTRAINT `fk_pest_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `pest_reports_ibfk_2` FOREIGN KEY (`crop_id`) REFERENCES `crops` (`id`),
  ADD CONSTRAINT `pest_reports_ibfk_3` FOREIGN KEY (`pest_id`) REFERENCES `pests` (`id`),
  ADD CONSTRAINT `pest_reports_ibfk_5` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`);

--
-- Constraints for table `pest_reviews`
--
ALTER TABLE `pest_reviews`
  ADD CONSTRAINT `pest_reviews_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `pest_reports` (`id`),
  ADD CONSTRAINT `pest_reviews_ibfk_2` FOREIGN KEY (`expert_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `seeds`
--
ALTER TABLE `seeds`
  ADD CONSTRAINT `seeds_ibfk_1` FOREIGN KEY (`crop_id`) REFERENCES `crops` (`id`);

--
-- Constraints for table `soil_crop_bonus`
--
ALTER TABLE `soil_crop_bonus`
  ADD CONSTRAINT `scb_crop_fk` FOREIGN KEY (`crop_id`) REFERENCES `crops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `solutions`
--
ALTER TABLE `solutions`
  ADD CONSTRAINT `solutions_ibfk_1` FOREIGN KEY (`disease_id`) REFERENCES `crop_diseases` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`);

--
-- Constraints for table `weather_api_config`
--
ALTER TABLE `weather_api_config`
  ADD CONSTRAINT `weather_api_config_ibfk_1` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`);

--
-- Constraints for table `weather_data`
--
ALTER TABLE `weather_data`
  ADD CONSTRAINT `weather_data_ibfk_1` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
