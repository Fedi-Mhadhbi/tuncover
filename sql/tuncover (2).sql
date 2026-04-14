-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 14, 2026 at 04:44 PM
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
-- Database: `tuncover`
--

-- --------------------------------------------------------

--
-- Table structure for table `activites`
--

CREATE TABLE `activites` (
  `id_activite` int(11) NOT NULL,
  `id_artisan` int(11) NOT NULL,
  `id_categorie` int(11) DEFAULT NULL,
  `titre` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `prix` decimal(10,2) NOT NULL,
  `duree_heures` int(11) DEFAULT 2,
  `max_participants` int(11) DEFAULT 8,
  `ville` varchar(100) DEFAULT NULL,
  `statut` enum('brouillon','publie','pause','supprime') DEFAULT 'brouillon',
  `date_creation` datetime DEFAULT current_timestamp(),
  `image_url` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activites`
--

INSERT INTO `activites` (`id_activite`, `id_artisan`, `id_categorie`, `titre`, `description`, `prix`, `duree_heures`, `max_participants`, `ville`, `statut`, `date_creation`, `image_url`) VALUES
(1, 1, 1, 'Traditional Pottery Workshop — Beginner', 'Step into the world of Nabeul ceramics. You will shape, glaze and decorate your own piece under the guidance of master potter Islem. All materials included. Take your creation home.', 60.00, 3, 8, 'Nabeul', 'publie', '2026-04-01 11:59:44', 'https://images.unsplash.com/photo-1565193566173-7a0ee3dbe261?w=800&q=80'),
(2, 1, 1, 'Advanced Glazing Masterclass', 'For those with some pottery experience. Explore traditional Nabeul glazing patterns, learn colour mixing techniques and create a fully glazed decorative piece.', 90.00, 4, 6, 'Nabeul', 'publie', '2026-04-01 12:00:03', 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=800&q=80'),
(3, 2, 4, 'Kairouan Carpet Weaving Experience', 'Learn the ancient art of mergoum rug weaving from master weaver Nour. You will work on a traditional loom, understand geometric symbolism and leave with a handwoven piece.', 75.00, 3, 6, 'Kairouan', 'publie', '2026-04-01 12:00:25', 'https://images.unsplash.com/photo-1606722590583-6951b5ea92ad?w=800&q=80'),
(4, 2, 2, 'Kairouan Medina & Great Mosque Tour', 'Explore the spiritual heart of Tunisia with local guide Nour. Walk through the ancient medina, visit the Great Mosque of Kairouan and discover hidden artisan workshops.', 45.00, 2, 10, 'Kairouan', 'publie', '2026-04-01 12:00:26', 'https://images.unsplash.com/photo-1548013146-72479768bada?w=800&q=80'),
(5, 1, 9, 'Traditional Tunisian Cooking Class', 'Learn to cook authentic Tunisian dishes including brik, couscous and makroudh with local chef islem. Shop at the market then cook and eat together.', 55.00, 3, 6, 'Nabeul', 'supprime', '2026-04-02 12:25:15', 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800&q=80'),
(6, 2, 8, 'Arabic Calligraphy Morning Session', 'Discover the meditative art of Arabic calligraphy with master Nour. Learn the basics of Naskh script, write your name in Arabic, and take your artwork home.', 50.00, 2, 8, 'Kairouan', 'publie', '2026-04-02 12:25:33', 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=800&q=80'),
(7, 2, 10, 'Kairouan Heritage Half-Day Tour', 'Walk through 13 centuries of Islamic history. Visit the Great Mosque, the Aghlabid Basins, and hidden medina gems with expert local guide Nour.', 40.00, 4, 12, 'Kairouan', 'publie', '2026-04-02 12:25:34', 'https://images.unsplash.com/photo-1548013146-72479768bada?w=800&q=80'),
(8, 2, 7, 'Mergoum Rug Weaving — Beginner Class', 'Sit at a traditional loom and begin weaving a mergoum rug under Nour\'s guidance. Learn the geometric symbolism behind each pattern. Small keepsake included.', 65.00, 3, 5, 'Kairouan', 'publie', '2026-04-02 12:25:53', 'https://images.unsplash.com/photo-1606722590583-6951b5ea92ad?w=800&q=80'),
(9, 4, 6, 'Djerban Silver Jewellery Workshop', 'Learn the ancient craft of Djerban silver jewellery with master artisan Leila Mansour. You will hammer, shape and polish your own silver piece using techniques unchanged for centuries. All tools and materials provided. Take your creation home.', 85.00, 3, 6, 'Djerba', 'publie', '2026-04-03 11:13:41', 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=800&q=80'),
(10, 4, 6, 'Berber Gold & Silver Jewellery Tour', 'Explore Djerba\'s legendary jewellery district with Leila as your guide. Visit family workshops, learn to distinguish real Berber silver, and finish with a hands-on engraving session.', 55.00, 2, 10, 'Djerba', 'publie', '2026-04-03 11:13:41', 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w=800&q=80'),
(11, 5, 11, 'Medina Leather Craft Workshop', 'Step into Youssef\'s workshop in the heart of the Tunis Medina and learn to stitch your own leather wallet or key holder. You will work with naturally tanned leather and traditional tools passed down three generations.', 70.00, 2, 8, 'Tunis', 'publie', '2026-04-03 11:14:04', 'https://images.unsplash.com/photo-1473496169904-658ba7574b0d?w=800&q=80'),
(12, 5, 2, 'Tunis Medina Souk Walking Tour', 'Navigate the ancient leather, spice and textile souks of the Tunis Medina with expert guide Youssef. Learn the history of each craft district, meet artisans at work, and find the best authentic souvenirs.', 45.00, 3, 12, 'Tunis', 'publie', '2026-04-03 11:14:04', 'https://images.unsplash.com/photo-1598300042247-d088f8ab3a91?w=800&q=80'),
(13, 6, 8, 'Islamic Calligraphy Masterclass', 'Spend a meditative morning with master calligrapher Khaled Mabrouk learning the flowing Naskh script. Write your name and a short phrase in Arabic, then frame your work to take home. No experience needed.', 60.00, 3, 8, 'Kairouan', 'publie', '2026-04-03 11:14:20', 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=800&q=80'),
(14, 6, 10, 'Great Mosque & Medina Heritage Tour', 'Discover 13 centuries of Islamic history with calligrapher and guide Khaled. Walk the Great Mosque of Kairouan, explore the Aghlabid Basins, and end at his studio for a calligraphy demonstration.', 50.00, 4, 10, 'Kairouan', 'publie', '2026-04-03 11:14:20', 'https://images.unsplash.com/photo-1548013146-72479768bada?w=800&q=80'),
(15, 7, 7, 'Gafsa Carpet Weaving Experience', 'Sit at a traditional Gafsa loom with master weaver Fatma Ben Ali and begin weaving a small carpet using techniques over 2,000 years old. Learn the symbolic meaning of each geometric motif. Take your woven piece home.', 75.00, 3, 6, 'Gafsa', 'publie', '2026-04-03 11:14:38', 'https://images.unsplash.com/photo-1606722590583-6951b5ea92ad?w=800&q=80'),
(16, 7, 7, 'Advanced Mergoum Pattern Design', 'For those with weaving experience. Fatma will guide you through the creation of a complex mergoum pattern — choosing colours, planning the geometric layout, and executing a full section of a traditional rug.', 95.00, 4, 4, 'Gafsa', 'publie', '2026-04-03 11:14:38', 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800&q=80'),
(17, 8, 12, 'Contemporary Ceramic Art Workshop', 'Join young ceramic artist Amira Khelifi for a modern take on Nabeul pottery. Mix traditional clay techniques with contemporary design principles. Create a decorative piece you will glaze and take home.', 65.00, 3, 8, 'Nabeul', 'publie', '2026-04-03 11:14:54', 'https://images.unsplash.com/photo-1474649107449-ea4f014b7e9f?w=800&q=80'),
(18, 9, 7, 'Kairouan Mergoum Rug Introduction', 'Learn the iconic Kairouan mergoum from internationally collected weaver Tahar Salhi. Understand the centuries-old symbolism behind the geometric patterns and begin weaving your own small keepsake piece.', 80.00, 3, 5, 'Kairouan', 'publie', '2026-04-03 11:15:10', 'https://images.unsplash.com/photo-1606722590583-6951b5ea92ad?w=800&q=80'),
(19, 1, 1, 'test', 'test for new listing by my backend', 150.00, 2, 6, 'maouba', 'supprime', '2026-04-07 20:39:31', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `artisans`
--

CREATE TABLE `artisans` (
  `id_artisan` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `specialite` varchar(150) DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `statut_validation` enum('en_attente','valide','rejete') DEFAULT 'en_attente',
  `note_moyenne` decimal(3,2) DEFAULT 0.00,
  `avatar_url` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `artisans`
--

INSERT INTO `artisans` (`id_artisan`, `id_utilisateur`, `specialite`, `ville`, `bio`, `statut_validation`, `note_moyenne`, `avatar_url`) VALUES
(1, 2, 'tradionall', 'tunis', NULL, 'valide', 4.00, 'https://images.unsplash.com/photo-1607799279861-4dd421887fb3?w=200&q=80'),
(2, 3, 'jewelry', 'Nabeul', NULL, 'valide', 0.00, 'https://images.unsplash.com/photo-1531746020798-e6953c6e8e04?w=200&q=80'),
(4, 8, 'Traditional Jewellery', 'Djerba', 'Specialises in traditional Djerban gold and silver jewellery using ancient Berber techniques. Her pieces are inspired by Carthaginian archaeological motifs.', 'valide', 0.00, 'https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?w=200&q=80'),
(5, 9, 'Leather Craft', 'Tunis', 'Master leather craftsman working in the heart of the Tunis Medina. Produces hand-stitched bags, belts and slippers using century-old tanning methods.', 'valide', 0.00, 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&q=80'),
(6, 10, 'Islamic Calligraphy', 'Kairouan', 'Trained calligrapher in the Kairouan tradition. Offers immersive morning workshops teaching Naskh and Thuluth scripts to beginners and advanced students.', 'valide', 0.00, 'https://images.unsplash.com/photo-1560250097-0b93528c311a?w=200&q=80'),
(7, 11, 'Carpet Weaving', 'Gafsa', 'Master weaver specialising in the ancient Gafsa carpet tradition, with geometric motifs passed down through seven generations. Her work is exhibited in national museums.', 'valide', 0.00, 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=200&q=80'),
(8, 12, 'Ceramic Art', 'Nabeul', 'Young ceramic artist blending traditional Nabeul forms with contemporary design. Graduated from the Nabeul School of Applied Arts, now running her own workshop.', 'valide', 0.00, 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=200&q=80'),
(9, 13, 'Mergoum Weaving', 'Kairouan', 'Specialist in the famous Kairouan mergoum rugs — geometric tapestries with centuries of cultural symbolism. His pieces are collected internationally.', 'valide', 0.00, 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=200&q=80');

-- --------------------------------------------------------

--
-- Table structure for table `avis`
--

CREATE TABLE `avis` (
  `id_avis` int(11) NOT NULL,
  `id_reservation` int(11) NOT NULL,
  `id_touriste` int(11) NOT NULL,
  `id_activite` int(11) NOT NULL,
  `note` tinyint(4) DEFAULT NULL CHECK (`note` between 1 and 5),
  `commentaire` text DEFAULT NULL,
  `date_avis` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `avis`
--

INSERT INTO `avis` (`id_avis`, `id_reservation`, `id_touriste`, `id_activite`, `note`, `commentaire`, `date_avis`) VALUES
(6, 4, 7, 2, 4, 'good i like it', '2026-04-13 15:39:21'),
(7, 14, 7, 1, 4, 'good one i likeit', '2026-04-14 13:19:40');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id_categorie` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id_categorie`, `nom`, `description`) VALUES
(1, 'Workshop', NULL),
(2, 'Guided Tour', NULL),
(3, 'Cultural Event', NULL),
(4, 'Craft', NULL),
(5, 'Adventure', NULL),
(6, 'Jewellery', NULL),
(7, 'Weaving', NULL),
(8, 'Calligraphy', NULL),
(9, 'Cooking', NULL),
(10, 'Heritage', NULL),
(11, 'Leather', NULL),
(12, 'Ceramic', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id_chat` int(11) NOT NULL,
  `id_reservation` int(11) NOT NULL,
  `id_sender` int(11) NOT NULL,
  `id_receiver` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id_chat`, `id_reservation`, `id_sender`, `id_receiver`, `message`, `is_read`, `created_at`) VALUES
(1, 16, 7, 2, 'hello i have a question', 1, '2026-04-14 13:16:46'),
(2, 16, 2, 7, 'hello sure how can i  help you', 1, '2026-04-14 13:17:30');

-- --------------------------------------------------------

--
-- Table structure for table `evenements`
--

CREATE TABLE `evenements` (
  `id_evenement` int(11) NOT NULL,
  `id_artisan` int(11) NOT NULL,
  `titre` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime DEFAULT NULL,
  `lieu` varchar(200) DEFAULT NULL,
  `prix_entree` decimal(10,2) DEFAULT 0.00,
  `statut` enum('a_venir','en_cours','termine','annule') DEFAULT 'a_venir'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `favoris`
--

CREATE TABLE `favoris` (
  `id_favori` int(11) NOT NULL,
  `id_touriste` int(11) NOT NULL,
  `id_activite` int(11) NOT NULL,
  `date_ajout` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id_notification` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `type` varchar(40) NOT NULL DEFAULT 'general',
  `title` varchar(180) NOT NULL,
  `message` text NOT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id_notification`, `id_utilisateur`, `type`, `title`, `message`, `link_url`, `reference_id`, `is_read`, `created_at`) VALUES
(1, 2, 'booking_created', 'New Reservation Request', 'You received a new reservation for \"Traditional Pottery Workshop — Beginner\" on 2026-04-15.', 'tuncover-artisan-dashboard.html', 14, 1, '2026-04-14 13:09:29'),
(2, 7, 'booking_confirmed', 'Reservation Confirmed', 'Your reservation for \"Traditional Pottery Workshop — Beginner\" is now confirmed.', 'tuncover-tourist.html', 14, 0, '2026-04-14 13:10:04'),
(3, 7, 'payment_completed', 'Payment Successful', 'Your payment for \"Traditional Pottery Workshop — Beginner\" was completed successfully.', 'tuncover-tourist.html', 14, 0, '2026-04-14 13:10:24'),
(4, 2, 'payment_received', 'Booking Paid', 'A tourist completed payment for \"Traditional Pottery Workshop — Beginner\".', 'tuncover-artisan-dashboard.html', 14, 1, '2026-04-14 13:10:25'),
(5, 2, 'booking_created', 'New Reservation Request', 'You received a new reservation for \"Advanced Glazing Masterclass\" on 2026-04-18.', 'tuncover-artisan-dashboard.html', 15, 1, '2026-04-14 13:11:19'),
(6, 7, 'payment_completed', 'Payment Successful', 'Your payment for \"Advanced Glazing Masterclass\" was completed successfully.', 'tuncover-tourist.html', 15, 0, '2026-04-14 13:11:24'),
(7, 2, 'payment_received', 'Booking Paid', 'A tourist completed payment for \"Advanced Glazing Masterclass\".', 'tuncover-artisan-dashboard.html', 15, 1, '2026-04-14 13:11:24'),
(8, 2, 'booking_created', 'New Reservation Request', 'You received a new reservation for \"Advanced Glazing Masterclass\" on 2026-04-17.', 'tuncover-artisan-dashboard.html', 16, 0, '2026-04-14 13:13:44'),
(9, 7, 'booking_confirmed', 'Reservation Confirmed', 'Your reservation for \"Advanced Glazing Masterclass\" is now confirmed.', 'tuncover-tourist.html', 16, 0, '2026-04-14 13:13:53'),
(10, 2, 'new_message', 'New Chat Message', 'You received a new message in reservation #16.', NULL, 16, 1, '2026-04-14 13:16:46'),
(11, 7, 'new_message', 'New Chat Message', 'You received a new message in reservation #16.', NULL, 16, 1, '2026-04-14 13:17:31'),
(12, 2, 'new_review', 'New Review Received', 'A tourist left a new 4-star review on your activity.', 'tuncover-artisan-dashboard.html', 14, 1, '2026-04-14 13:19:40'),
(13, 2, 'booking_created', 'New Reservation Request', 'You received a new reservation for \"Traditional Pottery Workshop — Beginner\" on 2026-04-24.', 'tuncover-artisan-dashboard.html', 17, 1, '2026-04-14 13:51:40'),
(14, 7, 'booking_confirmed', 'Reservation Confirmed', 'Your reservation for \"Traditional Pottery Workshop — Beginner\" is now confirmed.', 'tuncover-tourist.html', 17, 0, '2026-04-14 13:52:01'),
(15, 7, 'payment_completed', 'Payment Successful', 'Your payment for \"Traditional Pottery Workshop — Beginner\" was completed successfully.', 'tuncover-tourist.html', 17, 0, '2026-04-14 13:52:11'),
(16, 2, 'payment_received', 'Booking Paid', 'A tourist completed payment for \"Traditional Pottery Workshop — Beginner\".', 'tuncover-artisan-dashboard.html', 17, 0, '2026-04-14 13:52:11'),
(17, 7, 'booking_completed', 'Reservation Completed', 'Admin marked your reservation for \"Traditional Pottery Workshop — Beginner\" as completed.', 'tuncover-tourist.html', 17, 0, '2026-04-14 13:54:35'),
(18, 7, 'booking_cancelled', 'Reservation Cancelled', 'Admin cancelled your reservation for \"Advanced Glazing Masterclass\".', 'tuncover-tourist.html', 15, 0, '2026-04-14 13:55:16'),
(19, 2, 'booking_cancelled', 'Reservation Cancelled', 'Admin cancelled reservation #15 for \"Advanced Glazing Masterclass\".', 'tuncover-artisan-dashboard.html', 15, 0, '2026-04-14 13:55:16'),
(20, 7, 'payment_refunded', 'Payment Refunded', 'Admin refunded your payment for \"Advanced Glazing Masterclass\".', 'tuncover-tourist.html', 15, 0, '2026-04-14 13:55:16'),
(21, 7, 'booking_cancelled', 'Reservation Cancelled', 'Admin cancelled your reservation for \"Traditional Pottery Workshop — Beginner\".', 'tuncover-tourist.html', 14, 0, '2026-04-14 13:55:38'),
(22, 2, 'booking_cancelled', 'Reservation Cancelled', 'Admin cancelled reservation #14 for \"Traditional Pottery Workshop — Beginner\".', 'tuncover-artisan-dashboard.html', 14, 0, '2026-04-14 13:55:38'),
(23, 7, 'payment_refunded', 'Payment Refunded', 'Admin refunded your payment for \"Traditional Pottery Workshop — Beginner\".', 'tuncover-tourist.html', 14, 0, '2026-04-14 13:55:39'),
(24, 7, 'payment_refunded', 'Payment Refunded', 'Admin refunded your payment for \"Advanced Glazing Masterclass\".', 'tuncover-tourist.html', 9, 0, '2026-04-14 13:55:55');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id_payment` int(11) NOT NULL,
  `id_reservation` int(11) NOT NULL,
  `id_artisan` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `platform_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `artisan_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(8) NOT NULL DEFAULT 'TND',
  `payment_method` varchar(50) NOT NULL DEFAULT 'fake_card',
  `card_holder` varchar(120) DEFAULT NULL,
  `card_last4` varchar(4) DEFAULT NULL,
  `transaction_ref` varchar(60) NOT NULL,
  `payment_status` varchar(30) NOT NULL DEFAULT 'completed',
  `paid_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id_payment`, `id_reservation`, `id_artisan`, `amount`, `platform_fee`, `artisan_amount`, `currency`, `payment_method`, `card_holder`, `card_last4`, `transaction_ref`, `payment_status`, `paid_at`, `created_at`, `updated_at`) VALUES
(1, 10, 1, 132.00, 13.20, 118.80, 'TND', 'fake_card', 'Rania Rh', '4242', 'TC-20260413195106-000010', 'refunded', '2026-04-13 18:51:06', '2026-04-13 18:51:06', '2026-04-14 11:37:41'),
(2, 11, 1, 132.00, 13.20, 118.80, 'TND', 'fake_card', 'Demo Tourist Four', '9424', 'TC-20260413200221-000011', 'completed', '2026-04-13 19:02:21', '2026-04-13 19:02:21', '2026-04-13 19:02:21'),
(3, 9, 1, 198.00, 19.80, 178.20, 'TND', 'fake_card', 'ramzi teber', '0014', 'TC-20260413202136-000009', 'refunded', '2026-04-13 19:21:36', '2026-04-13 19:21:36', '2026-04-14 13:55:55'),
(4, 12, 1, 66.00, 6.60, 59.40, 'TND', 'fake_card', 'mayssa mh', '0016', 'TC-20260414115639-000012', 'completed', '2026-04-14 10:56:39', '2026-04-14 10:56:39', '2026-04-14 10:56:39'),
(5, 13, 1, 132.00, 13.20, 118.80, 'TND', 'fake_card', 'rania Rh', '0007', 'TC-20260414124110-000013', 'completed', '2026-04-14 11:41:10', '2026-04-14 11:41:10', '2026-04-14 11:41:10'),
(6, 4, 1, 198.00, 19.80, 178.20, 'TND', 'fake_card', 'rania Rh', '0007', 'TC-20260414124133-000004', 'completed', '2026-04-14 11:41:33', '2026-04-14 11:41:33', '2026-04-14 11:41:33'),
(7, 3, 1, 198.00, 19.80, 178.20, 'TND', 'fake_card', 'mayssa mh', '0016', 'TC-20260414124212-000003', 'completed', '2026-04-14 11:42:12', '2026-04-14 11:42:12', '2026-04-14 11:42:12'),
(8, 2, 1, 66.00, 6.60, 59.40, 'TND', 'fake_card', 'bilel rh', '0015', 'TC-20260414124222-000002', 'completed', '2026-04-14 11:42:22', '2026-04-14 11:42:22', '2026-04-14 11:42:22'),
(9, 14, 1, 264.00, 26.40, 237.60, 'TND', 'fake_card', 'rania Rh', '0007', 'TC-20260414141024-000014', 'refunded', '2026-04-14 13:10:24', '2026-04-14 13:10:24', '2026-04-14 13:55:38'),
(10, 15, 1, 198.00, 19.80, 178.20, 'TND', 'fake_card', 'rania Rh', '0007', 'TC-20260414141124-000015', 'refunded', '2026-04-14 13:11:24', '2026-04-14 13:11:24', '2026-04-14 13:55:16'),
(11, 17, 1, 66.00, 6.60, 59.40, 'TND', 'fake_card', 'rania Rh', '0007', 'TC-20260414145211-000017', 'completed', '2026-04-14 13:52:11', '2026-04-14 13:52:11', '2026-04-14 13:52:11');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id_reservation` int(11) NOT NULL,
  `id_activite` int(11) NOT NULL,
  `id_touriste` int(11) NOT NULL,
  `date_reservation` datetime DEFAULT current_timestamp(),
  `date_activite` date NOT NULL,
  `heure_activite` time DEFAULT NULL,
  `nb_participants` int(11) DEFAULT 1,
  `montant_total` decimal(10,2) NOT NULL,
  `statut` enum('en_attente','confirmee','annulee','terminee') DEFAULT 'en_attente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id_reservation`, `id_activite`, `id_touriste`, `date_reservation`, `date_activite`, `heure_activite`, `nb_participants`, `montant_total`, `statut`) VALUES
(1, 1, 1, '2026-04-01 12:47:53', '2026-04-10', '10:00:00', 2, 132.00, 'confirmee'),
(2, 1, 7, '2026-04-02 12:17:42', '2026-04-05', '10:00:00', 1, 66.00, 'confirmee'),
(3, 2, 7, '2026-04-02 12:28:31', '2026-04-10', '10:00:00', 2, 198.00, 'terminee'),
(4, 2, 7, '2026-04-07 20:38:25', '2026-04-18', '10:00:00', 2, 198.00, 'terminee'),
(5, 14, 7, '2026-04-12 19:27:21', '2026-04-17', '10:00:00', 2, 110.00, 'annulee'),
(6, 14, 15, '2026-04-12 19:40:32', '2026-04-23', '10:00:00', 1, 55.00, 'en_attente'),
(7, 11, 7, '2026-04-13 15:00:39', '2026-04-16', '10:00:00', 2, 154.00, 'annulee'),
(8, 16, 7, '2026-04-13 18:22:02', '2026-04-16', '10:00:00', 1, 104.50, 'annulee'),
(9, 2, 7, '2026-04-13 18:24:59', '2026-04-17', '10:00:00', 2, 198.00, 'confirmee'),
(10, 1, 7, '2026-04-13 18:50:47', '2026-04-17', '10:00:00', 2, 132.00, 'annulee'),
(11, 1, 7, '2026-04-13 18:54:12', '2026-04-16', '10:00:00', 2, 132.00, 'terminee'),
(12, 1, 16, '2026-04-14 10:53:30', '2026-04-18', '10:00:00', 1, 66.00, 'terminee'),
(13, 1, 7, '2026-04-14 11:36:26', '2026-04-16', '10:00:00', 2, 132.00, 'terminee'),
(14, 1, 7, '2026-04-14 13:09:29', '2026-04-15', '10:00:00', 4, 264.00, 'annulee'),
(15, 2, 7, '2026-04-14 13:11:19', '2026-04-18', '10:00:00', 2, 198.00, 'annulee'),
(16, 2, 7, '2026-04-14 13:13:44', '2026-04-17', '10:00:00', 2, 198.00, 'confirmee'),
(17, 1, 7, '2026-04-14 13:51:40', '2026-04-24', '10:00:00', 1, 66.00, 'terminee');

-- --------------------------------------------------------

--
-- Table structure for table `test_cards`
--

CREATE TABLE `test_cards` (
  `id_card` int(11) NOT NULL,
  `card_holder` varchar(120) NOT NULL,
  `card_number` varchar(19) NOT NULL,
  `exp_month` char(2) NOT NULL,
  `exp_year` char(4) NOT NULL,
  `cvv` varchar(4) NOT NULL,
  `card_brand` varchar(30) NOT NULL DEFAULT 'VISA',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `test_cards`
--

INSERT INTO `test_cards` (`id_card`, `card_holder`, `card_number`, `exp_month`, `exp_year`, `cvv`, `card_brand`, `is_active`, `created_at`) VALUES
(37, 'aziz mh', '4111111111110001', '02', '2030', '101', 'VISA', 1, '2026-04-13 19:21:29'),
(38, 'rania Rh', '4111111111110007', '08', '2030', '107', 'MASTERCARD', 1, '2026-04-13 19:21:29'),
(39, 'ramzi teber', '4111111111110014', '03', '2029', '114', 'VISA', 1, '2026-04-13 19:21:29'),
(40, 'bilel rh', '4111111111110015', '04', '2030', '115', 'DISCOVER', 1, '2026-04-13 19:21:29'),
(49, 'mayssa mh', '4111111111110016', '05', '2029', '116', 'VISA', 1, '2026-04-14 10:53:32');

-- --------------------------------------------------------

--
-- Table structure for table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id_utilisateur` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `role` enum('touriste','artisan','admin') DEFAULT 'touriste',
  `telephone` varchar(20) DEFAULT NULL,
  `date_inscription` datetime DEFAULT current_timestamp(),
  `statut` enum('actif','inactif','banni') DEFAULT 'actif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id_utilisateur`, `nom`, `prenom`, `email`, `mot_de_passe`, `role`, `telephone`, `date_inscription`, `statut`) VALUES
(1, 'mh', 'aziz', 'azizmh915@gmail.com', '$2y$10$lrH34hrC5.LqzBJVoPV3lee2xAZWxngLzXRRfuQJ01UMtjpW.PDhC', 'touriste', NULL, '2026-03-31 12:21:59', 'actif'),
(2, 'teber', 'islem', 'islem@gmail.com', '$2y$10$pMrsZpLl5XQcFrihO9XgjuEMvqjKNsXLLnVwXThRSO53ahaAtjbGC', 'artisan', NULL, '2026-03-31 13:11:46', 'actif'),
(3, 'tlili', 'nour', 'nour@gmail.com', '$2y$10$mzIZSQ0cGYidZNDjn1BLZOT/o71Ah4Jff95QsEzqT1k2CyeFYRqS.', 'artisan', NULL, '2026-03-31 13:38:43', 'actif'),
(5, 'Mhadhbi', 'Fedi', 'fedimhadhbi617@gmail.com', '$2y$10$DRiGXvtUgUrKehfVmFCBZ.k4Hc1fX88O6Kp3lfNDdpGnA/i/8suVG', 'admin', NULL, '2026-04-02 11:53:54', 'actif'),
(6, 'Toumi', 'Tharwa', 'harwa@gmail.com', '$2y$10$ShebMYQs6C0LkXWZiJF8KO0i.pZ0wAqK89PgcZntMuALok9HKj6jm', 'admin', NULL, '2026-04-02 11:53:54', 'actif'),
(7, 'Rh', 'rania', 'rania@gmail.com', '$2y$10$pH76NM9se1LPqdjl8hD/wesR9//ibv6GOBg7SNsNG2jBG184.qfsu', 'touriste', NULL, '2026-04-02 12:12:42', 'actif'),
(8, 'Mansour', 'Leila', 'leila@tuncover.tn', '$2y$10$7COa35r9/GKgZCePzcdGGe491v4o5aepuNPyM7RjN9dDIGaltVBAO', 'artisan', NULL, '2026-04-03 11:10:03', 'actif'),
(9, 'Hamdi', 'Youssef', 'youssef@tuncover.tn', '$2y$10$7COa35r9/GKgZCePzcdGGe491v4o5aepuNPyM7RjN9dDIGaltVBAO', 'artisan', NULL, '2026-04-03 11:10:03', 'actif'),
(10, 'Mabrouk', 'Khaled', 'khaled@tuncover.tn', '$2y$10$7COa35r9/GKgZCePzcdGGe491v4o5aepuNPyM7RjN9dDIGaltVBAO', 'artisan', NULL, '2026-04-03 11:10:03', 'actif'),
(11, 'Ben Ali', 'Fatma', 'fatma@tuncover.tn', '$2y$10$7COa35r9/GKgZCePzcdGGe491v4o5aepuNPyM7RjN9dDIGaltVBAO', 'artisan', NULL, '2026-04-03 11:10:03', 'actif'),
(12, 'Khelifi', 'Amira', 'amira@tuncover.tn', '$2y$10$7COa35r9/GKgZCePzcdGGe491v4o5aepuNPyM7RjN9dDIGaltVBAO', 'artisan', NULL, '2026-04-03 11:10:03', 'actif'),
(13, 'Salhi', 'Tahar', 'tahar@tuncover.tn', '$2y$10$7COa35r9/GKgZCePzcdGGe491v4o5aepuNPyM7RjN9dDIGaltVBAO', 'artisan', NULL, '2026-04-03 11:10:03', 'actif'),
(14, 'teber', 'ramzi', 'ramzi@gmail.com', '$2y$10$wqDPUfn9KgWNrUNVjdSmuuKiRoYCDuhDp2tClIO37chgbmwzXnQim', 'touriste', NULL, '2026-04-10 20:23:59', 'actif'),
(15, 'rh', 'bilel', 'bilel@gmail.com', '$2y$10$wkBWV0DQ65Hnpj5PyNYTleI4Nh3S3/Hp4b8ECi04ykSBUPWvzQraK', 'touriste', NULL, '2026-04-12 16:13:51', 'actif'),
(16, 'mh', 'mayssa', 'mayssa@gmail.com', '$2y$10$Vrqq4kDYcCpiVT6E4n7wxuyMY8CoqFWmXul/S48ub7JuA7W5WZYyW', 'touriste', NULL, '2026-04-14 10:52:52', 'actif');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activites`
--
ALTER TABLE `activites`
  ADD PRIMARY KEY (`id_activite`),
  ADD KEY `id_artisan` (`id_artisan`),
  ADD KEY `id_categorie` (`id_categorie`);

--
-- Indexes for table `artisans`
--
ALTER TABLE `artisans`
  ADD PRIMARY KEY (`id_artisan`),
  ADD UNIQUE KEY `id_utilisateur` (`id_utilisateur`);

--
-- Indexes for table `avis`
--
ALTER TABLE `avis`
  ADD PRIMARY KEY (`id_avis`),
  ADD UNIQUE KEY `id_reservation` (`id_reservation`),
  ADD KEY `id_touriste` (`id_touriste`),
  ADD KEY `id_activite` (`id_activite`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id_categorie`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id_chat`),
  ADD KEY `idx_chat_reservation` (`id_reservation`),
  ADD KEY `idx_chat_sender` (`id_sender`),
  ADD KEY `idx_chat_receiver_read` (`id_receiver`,`is_read`);

--
-- Indexes for table `evenements`
--
ALTER TABLE `evenements`
  ADD PRIMARY KEY (`id_evenement`),
  ADD KEY `id_artisan` (`id_artisan`);

--
-- Indexes for table `favoris`
--
ALTER TABLE `favoris`
  ADD PRIMARY KEY (`id_favori`),
  ADD UNIQUE KEY `unique_favori` (`id_touriste`,`id_activite`),
  ADD KEY `id_activite` (`id_activite`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id_notification`),
  ADD KEY `idx_notif_user_read` (`id_utilisateur`,`is_read`),
  ADD KEY `idx_notif_created` (`created_at`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id_payment`),
  ADD UNIQUE KEY `uniq_payment_reservation` (`id_reservation`),
  ADD KEY `idx_payment_artisan` (`id_artisan`),
  ADD KEY `idx_payment_status` (`payment_status`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id_reservation`),
  ADD KEY `id_activite` (`id_activite`),
  ADD KEY `id_touriste` (`id_touriste`);

--
-- Indexes for table `test_cards`
--
ALTER TABLE `test_cards`
  ADD PRIMARY KEY (`id_card`),
  ADD UNIQUE KEY `uniq_test_card_number` (`card_number`);

--
-- Indexes for table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id_utilisateur`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activites`
--
ALTER TABLE `activites`
  MODIFY `id_activite` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `artisans`
--
ALTER TABLE `artisans`
  MODIFY `id_artisan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `avis`
--
ALTER TABLE `avis`
  MODIFY `id_avis` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id_categorie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id_chat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `evenements`
--
ALTER TABLE `evenements`
  MODIFY `id_evenement` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `favoris`
--
ALTER TABLE `favoris`
  MODIFY `id_favori` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id_notification` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id_payment` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id_reservation` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `test_cards`
--
ALTER TABLE `test_cards`
  MODIFY `id_card` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=170;

--
-- AUTO_INCREMENT for table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id_utilisateur` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activites`
--
ALTER TABLE `activites`
  ADD CONSTRAINT `activites_ibfk_1` FOREIGN KEY (`id_artisan`) REFERENCES `artisans` (`id_artisan`) ON DELETE CASCADE,
  ADD CONSTRAINT `activites_ibfk_2` FOREIGN KEY (`id_categorie`) REFERENCES `categories` (`id_categorie`);

--
-- Constraints for table `artisans`
--
ALTER TABLE `artisans`
  ADD CONSTRAINT `artisans_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Constraints for table `avis`
--
ALTER TABLE `avis`
  ADD CONSTRAINT `avis_ibfk_1` FOREIGN KEY (`id_reservation`) REFERENCES `reservations` (`id_reservation`),
  ADD CONSTRAINT `avis_ibfk_2` FOREIGN KEY (`id_touriste`) REFERENCES `utilisateurs` (`id_utilisateur`),
  ADD CONSTRAINT `avis_ibfk_3` FOREIGN KEY (`id_activite`) REFERENCES `activites` (`id_activite`);

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `fk_chat_receiver` FOREIGN KEY (`id_receiver`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_chat_reservation` FOREIGN KEY (`id_reservation`) REFERENCES `reservations` (`id_reservation`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_chat_sender` FOREIGN KEY (`id_sender`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Constraints for table `evenements`
--
ALTER TABLE `evenements`
  ADD CONSTRAINT `evenements_ibfk_1` FOREIGN KEY (`id_artisan`) REFERENCES `artisans` (`id_artisan`);

--
-- Constraints for table `favoris`
--
ALTER TABLE `favoris`
  ADD CONSTRAINT `favoris_ibfk_1` FOREIGN KEY (`id_touriste`) REFERENCES `utilisateurs` (`id_utilisateur`),
  ADD CONSTRAINT `favoris_ibfk_2` FOREIGN KEY (`id_activite`) REFERENCES `activites` (`id_activite`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_artisan` FOREIGN KEY (`id_artisan`) REFERENCES `artisans` (`id_artisan`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payment_reservation` FOREIGN KEY (`id_reservation`) REFERENCES `reservations` (`id_reservation`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`id_activite`) REFERENCES `activites` (`id_activite`),
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`id_touriste`) REFERENCES `utilisateurs` (`id_utilisateur`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
