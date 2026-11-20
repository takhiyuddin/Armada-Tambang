-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 20, 2026 at 04:06 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `alatberat_3506`
--

-- --------------------------------------------------------

--
-- Table structure for table `alat`
--

CREATE TABLE `alat` (
  `id_alat` int(11) NOT NULL,
  `kode_unit` varchar(20) NOT NULL,
  `nama_alat` varchar(120) NOT NULL,
  `merek` varchar(60) NOT NULL,
  `id_kategori` int(11) NOT NULL,
  `nomor_seri` varchar(40) NOT NULL,
  `lokasi` varchar(100) NOT NULL,
  `jam_operasi` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `kepemilikan` enum('Milik Sendiri','Sewa') NOT NULL DEFAULT 'Milik Sendiri',
  `status` enum('Siap Operasi','Standby','Perbaikan','Rusak') NOT NULL DEFAULT 'Siap Operasi',
  `tanggal_perolehan` date NOT NULL,
  `catatan` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `alat`
--

INSERT INTO `alat` (`id_alat`, `kode_unit`, `nama_alat`, `merek`, `id_kategori`, `nomor_seri`, `lokasi`, `jam_operasi`, `kepemilikan`, `status`, `tanggal_perolehan`, `catatan`, `foto`, `created_at`) VALUES
(1, 'EX-2101', 'PC2000-8 Hydraulic Excavator', 'Komatsu', 1, 'KMT-PC2000-30412', 'Pit Utara - Blok A', 18420, 'Milik Sendiri', 'Siap Operasi', '2021-03-14', 'Service 500 jam terakhir pada 12 Okt 2026. Bucket 11 m3.', 'unit_20261119232246_d1c357fd.jpg', '2026-11-19 16:05:07'),
(2, 'EX-2102', '336 GC Excavator', 'Caterpillar', 1, 'CAT0336GCX-7781', 'Pit Selatan', 9310, 'Sewa', 'Perbaikan', '2023-07-02', 'Kebocoran pada silinder boom, menunggu seal kit dari vendor.', 'unit_20261119232437_b5e647b0.jpg', '2026-11-19 16:05:07'),
(3, 'DZ-1104', 'D85ESS-2 Bulldozer', 'Komatsu', 2, 'KMT-D85-11902', 'Disposal Timur', 12775, 'Milik Sendiri', 'Siap Operasi', '2020-11-20', 'Dipakai untuk perataan disposal dan pembuatan jalan akses.', 'unit_20261119232136_74ebdee0.jpg', '2026-11-19 16:05:07'),
(4, 'DT-3301', 'HD785-7 Rigid Dump Truck', 'Komatsu', 3, 'KMT-HD785-55210', 'Haul Road KM 4', 22140, 'Milik Sendiri', 'Standby', '2019-05-08', 'Standby menunggu jadwal shift malam.', 'unit_20261119231005_8434f8b3.jpg', '2026-11-19 16:05:07'),
(5, 'DT-3302', '777E Off-Highway Truck', 'Caterpillar', 3, 'CAT0777EK-2093', 'Workshop Utama', 30588, 'Sewa', 'Rusak', '2018-02-17', 'Transmisi rusak, diajukan overhaul. Tidak boleh dioperasikan.', 'unit_20261119231248_de55e7ef.png', '2026-11-19 16:05:07'),
(6, 'WL-4401', 'WA600-8 Wheel Loader', 'Komatsu', 4, 'KMT-WA600-80113', 'Stockpile ROM', 8044, 'Milik Sendiri', 'Siap Operasi', '2022-09-30', NULL, 'unit_20261119232538_2973fb7d.jpg', '2026-11-19 16:05:07'),
(7, 'MG-5501', '16M3 Motor Grader', 'Caterpillar', 5, 'CAT016M3R-4410', 'Haul Road KM 2', 6120, 'Sewa', 'Siap Operasi', '2024-01-15', 'Perawatan jalan tambang setiap pergantian shift.', 'unit_20261119232405_23068400.jpg', '2026-11-19 16:05:07'),
(8, 'DR-6601', 'SmartROC D65 Drill Rig', 'Epiroc', 6, 'EPR-D65-002871', 'Pit Utara - Blok C', 4385, 'Milik Sendiri', 'Standby', '2025-04-03', 'Menunggu desain peledakan minggu depan.', 'unit_20261119230909_d06cfaf7.webp', '2026-11-19 16:05:07');

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id_kategori` int(11) NOT NULL,
  `nama_kategori` varchar(80) NOT NULL,
  `deskripsi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `deskripsi`) VALUES
(1, 'Excavator', 'Alat gali dengan bucket dan lengan hidrolik untuk penggalian dan pemuatan material.'),
(2, 'Bulldozer', 'Traktor rantai dengan blade untuk mendorong tanah, land clearing, dan perataan.'),
(3, 'Dump Truck', 'Truk pengangkut material hasil tambang dari front kerja ke stockpile.'),
(4, 'Wheel Loader', 'Pemuat beroda untuk memindahkan material curah ke truk atau hopper.'),
(5, 'Motor Grader', 'Perata jalan tambang (haul road) dengan blade panjang.'),
(6, 'Drilling Rig', 'Alat bor untuk lubang ledak dan eksplorasi.');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` char(64) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `username`, `password`, `nama_lengkap`) VALUES
(1, 'admin', '8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918', 'Administrator');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alat`
--
ALTER TABLE `alat`
  ADD PRIMARY KEY (`id_alat`),
  ADD UNIQUE KEY `kode_unit` (`kode_unit`),
  ADD KEY `fk_alat_kategori` (`id_kategori`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id_kategori`),
  ADD UNIQUE KEY `nama_kategori` (`nama_kategori`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alat`
--
ALTER TABLE `alat`
  MODIFY `id_alat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id_kategori` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `alat`
--
ALTER TABLE `alat`
  ADD CONSTRAINT `fk_alat_kategori` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
