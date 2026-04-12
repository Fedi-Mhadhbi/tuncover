-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 12, 2026 at 05:32 PM
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
(1, 2, 'tradionall', 'tunis', NULL, 'valide', 0.00, 'https://images.unsplash.com/photo-1607799279861-4dd421887fb3?w=200&q=80'),
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
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id_message` int(11) NOT NULL,
  `id_expediteur` int(11) NOT NULL,
  `id_destinataire` int(11) NOT NULL,
  `contenu` text NOT NULL,
  `date_envoi` datetime DEFAULT current_timestamp(),
  `lu` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `paiements`
--

CREATE TABLE `paiements` (
  `id_paiement` int(11) NOT NULL,
  `id_reservation` int(11) NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `date_paiement` datetime DEFAULT current_timestamp(),
  `methode` enum('carte','virement','especes') DEFAULT 'carte',
  `statut` enum('en_attente','paye','rembourse','echoue') DEFAULT 'en_attente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(3, 2, 7, '2026-04-02 12:28:31', '2026-04-10', '10:00:00', 2, 198.00, 'confirmee'),
(4, 2, 7, '2026-04-07 20:38:25', '2026-04-18', '10:00:00', 2, 198.00, 'confirmee');

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
(15, 'rh', 'bilel', 'bilel@gmail.com', '$2y$10$wkBWV0DQ65Hnpj5PyNYTleI4Nh3S3/Hp4b8ECi04ykSBUPWvzQraK', 'touriste', NULL, '2026-04-12 16:13:51', 'actif');

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
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id_message`),
  ADD KEY `id_expediteur` (`id_expediteur`),
  ADD KEY `id_destinataire` (`id_destinataire`);

--
-- Indexes for table `paiements`
--
ALTER TABLE `paiements`
  ADD PRIMARY KEY (`id_paiement`),
  ADD UNIQUE KEY `id_reservation` (`id_reservation`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id_reservation`),
  ADD KEY `id_activite` (`id_activite`),
  ADD KEY `id_touriste` (`id_touriste`);

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
  MODIFY `id_avis` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id_categorie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id_message` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `paiements`
--
ALTER TABLE `paiements`
  MODIFY `id_paiement` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id_reservation` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id_utilisateur` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

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
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`id_expediteur`) REFERENCES `utilisateurs` (`id_utilisateur`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`id_destinataire`) REFERENCES `utilisateurs` (`id_utilisateur`);

--
-- Constraints for table `paiements`
--
ALTER TABLE `paiements`
  ADD CONSTRAINT `paiements_ibfk_1` FOREIGN KEY (`id_reservation`) REFERENCES `reservations` (`id_reservation`);

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
