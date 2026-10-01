-- phpMyAdmin SQL Dump
-- Host: 127.0.0.1
-- Database: `db_manajemen_projek`

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Struktur Tabel `projects`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_projek` varchar(150) NOT NULL,
  `klien` varchar(100) NOT NULL,
  `budget` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tanggal_mulai` date NOT NULL,
  `deadline` date NOT NULL,
  `status` enum('Planning','In Progress','Completed','On Hold') DEFAULT 'Planning',
  `client_token` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `projects` (`id`, `nama_projek`, `klien`, `budget`, `tanggal_mulai`, `deadline`, `status`, `client_token`, `created_at`) VALUES
(1, 'Website Landing Page Parking', 'PT Lynx Indonesia', 100000000.00, '2026-10-01', '2027-02-01', 'In Progress', '26f1e159ec6b0e3202a71cacbba58238', '2026-10-01 03:29:58'),
(2, 'Hostel Website Barbershop', 'Hostel Corp.', 10000000.00, '2026-10-01', '2027-07-01', 'In Progress', '2d51e0031c5cacc1c6edaf9c6d3e82cf', '2026-10-01 03:32:50'),
(3, 'landing Page FanTravel', 'FV Travel', 50000000.00, '2026-10-01', '2027-01-01', 'On Hold', '273351badf952c6ed043c535ba4ebb39', '2026-10-01 07:35:58');

-- --------------------------------------------------------
-- Struktur Tabel `users`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','karyawan') DEFAULT 'karyawan',
  `jabatan` varchar(100) DEFAULT 'Developer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `jabatan`, `created_at`) VALUES
(1, 'Administrator', 'admin@projek.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1f1Ols/Kz1L4iVwXo6AIsC', 'admin', 'Project Manager', '2026-10-01 03:16:39'),
(2, 'Rajsfano Priariya Barisky', 'fanowp@gmail.com', '$2y$10$RU383sWR9DxBbNr0XeOllOm/n/1v1HnyuCzmYyP2UZeA5w7MIR/fy', 'karyawan', 'IT intern', '2026-10-01 03:40:10'),
(3, 'Muhammad Fachri', 'fahri@gmail.com', '$2y$10$XsNBuYKTtDyOjhaji2XgMe8j/Mr7ZRsCToMo6sVtyjbv5mE1TaBIS', 'karyawan', 'software Development', '2026-10-01 07:04:03');

-- --------------------------------------------------------
-- Struktur Tabel `contracts`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contracts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `nomor_kontrak` varchar(100) NOT NULL,
  `durasi_bulan` int(11) NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `nilai_kontrak` decimal(15,2) NOT NULL,
  `file_pdf` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `contracts_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `contracts` (`id`, `project_id`, `nomor_kontrak`, `durasi_bulan`, `tanggal_mulai`, `tanggal_selesai`, `nilai_kontrak`, `file_pdf`, `created_at`) VALUES
(7, 2, '001/CTC-SPK/X/2026', 3, '2026-10-01', '2027-07-01', 10000000.00, NULL, '2026-10-01 08:58:14');

-- --------------------------------------------------------
-- Struktur Tabel `expenses`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `kategori` varchar(100) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `jumlah_biaya` decimal(15,2) NOT NULL,
  `tanggal_pengeluaran` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `expenses` (`id`, `project_id`, `kategori`, `keterangan`, `jumlah_biaya`, `tanggal_pengeluaran`) VALUES
(1, 2, 'Infrastruktur & Cloud', 'Untuk Kebutuhan Infrastruktur pembangunan Hosting Website', 4500000.00, '2026-10-07');

-- --------------------------------------------------------
-- Struktur Tabel `invoices`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `nomor_invoice` varchar(100) NOT NULL,
  `termin_ke` int(11) DEFAULT 1,
  `keterangan_termin` varchar(200) NOT NULL,
  `jumlah_tagihan` decimal(15,2) NOT NULL,
  `tanggal_tagihan` date NOT NULL,
  `jatuh_tempo` date NOT NULL,
  `status` enum('Unpaid','Partially Paid','Paid') DEFAULT 'Unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `invoices` (`id`, `project_id`, `nomor_invoice`, `termin_ke`, `keterangan_termin`, `jumlah_tagihan`, `tanggal_tagihan`, `jatuh_tempo`, `status`, `created_at`) VALUES
(1, 2, 'INV/CTC/2026/10/001', 1, 'DP 50% Penandatanganan SPK', 5000000.00, '2026-10-01', '2026-10-15', 'Partially Paid', '2026-10-01 07:56:14');

-- --------------------------------------------------------
-- Struktur Tabel `tasks`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `nama_tugas` varchar(200) NOT NULL,
  `prioritas` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `status` enum('To Do','In Progress','Done') DEFAULT 'To Do',
  `deadline` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `user_id` (`user_id`),
  KEY `fk_tasks_assigned_to` (`assigned_to`),
  CONSTRAINT `fk_tasks_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tasks` (`id`, `project_id`, `assigned_to`, `user_id`, `nama_tugas`, `prioritas`, `status`, `deadline`) VALUES
(1, 1, NULL, 1, 'UI/UX design', 'High', 'Done', '2026-10-05'),
(2, 2, NULL, 2, 'UI/UX design', 'High', 'In Progress', '2026-10-09'),
(3, 2, NULL, 3, 'Software development', 'Urgent', 'In Progress', '2026-10-07'),
(4, 3, NULL, 2, 'web development', 'High', 'In Progress', '2026-10-07'),
(5, 3, NULL, 3, 'ERD LRS', 'Medium', 'To Do', '2026-10-02'),
(6, 3, NULL, 3, 'Membuat RAB', 'Medium', 'Done', '2026-10-07');

COMMIT;