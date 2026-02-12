-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 12, 2026 at 07:58 AM
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
-- Database: `kasir`
--

-- --------------------------------------------------------

--
-- Table structure for table `detailpenjualan`
--

CREATE TABLE `detailpenjualan` (
  `DetailID` int(11) NOT NULL,
  `PenjualanID` int(11) NOT NULL,
  `ProdukID` int(11) NOT NULL,
  `JumlahProduk` int(11) NOT NULL,
  `Subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detailpenjualan`
--

INSERT INTO `detailpenjualan` (`DetailID`, `PenjualanID`, `ProdukID`, `JumlahProduk`, `Subtotal`) VALUES
(1, 1, 6, 3, 6000.00),
(2, 1, 1, 2, 30000.00),
(3, 1, 5, 5, 15000.00),
(4, 1, 2, 1, 30000.00),
(5, 2, 5, 5, 15000.00),
(6, 2, 6, 1, 2000.00),
(7, 2, 1, 1, 15000.00),
(8, 3, 1, 1, 15000.00),
(9, 3, 6, 1, 2000.00),
(10, 3, 2, 5, 150000.00),
(11, 3, 5, 10, 30000.00),
(12, 4, 1, 1, 15000.00),
(13, 4, 6, 1, 2000.00),
(14, 4, 2, 1, 30000.00),
(15, 4, 5, 1, 3000.00),
(16, 5, 1, 4, 60000.00),
(17, 5, 6, 3, 6000.00),
(18, 6, 1, 9, 135000.00),
(19, 6, 6, 4, 8000.00),
(20, 6, 2, 4, 120000.00),
(21, 7, 4, 1, 3000.00),
(22, 7, 5, 9, 27000.00),
(23, 7, 1, 2, 30000.00),
(24, 8, 7, 3, 15000.00),
(25, 9, 2, 1, 30000.00),
(26, 10, 2, 5, 150000.00);

-- --------------------------------------------------------

--
-- Table structure for table `pelanggan`
--

CREATE TABLE `pelanggan` (
  `PelangganID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `NamaPelanggan` varchar(255) NOT NULL,
  `Alamat` text NOT NULL,
  `NomorTelepon` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pelanggan`
--

INSERT INTO `pelanggan` (`PelangganID`, `UserID`, `NamaPelanggan`, `Alamat`, `NomorTelepon`) VALUES
(1, 7, 'Caelus', 'Jl. A Express', '087654564327'),
(2, 8, 'Naufal', 'Pekanbaru', '0812871489754'),
(5, 13, 'Abc', 'Abc', '089765432345');

-- --------------------------------------------------------

--
-- Table structure for table `penjualan`
--

CREATE TABLE `penjualan` (
  `PenjualanID` int(11) NOT NULL,
  `TanggalPenjualan` date NOT NULL,
  `TotalHarga` decimal(10,2) NOT NULL,
  `PelangganID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `penjualan`
--

INSERT INTO `penjualan` (`PenjualanID`, `TanggalPenjualan`, `TotalHarga`, `PelangganID`) VALUES
(1, '2026-02-12', 81000.00, 1),
(2, '2026-02-12', 32000.00, 1),
(3, '2026-02-12', 197000.00, 2),
(4, '2026-02-12', 50000.00, NULL),
(5, '2026-02-12', 66000.00, 1),
(6, '2026-02-12', 263000.00, NULL),
(7, '2026-02-12', 60000.00, 5),
(8, '2026-02-12', 15000.00, 1),
(9, '2026-02-12', 30000.00, 1),
(10, '2026-02-12', 150000.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `produk`
--

CREATE TABLE `produk` (
  `ProdukID` int(11) NOT NULL,
  `NamaProduk` varchar(255) NOT NULL,
  `Harga` decimal(10,2) NOT NULL,
  `Stok` int(11) NOT NULL,
  `Status` enum('Tersedia','Dihapus') DEFAULT 'Tersedia'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `produk`
--

INSERT INTO `produk` (`ProdukID`, `NamaProduk`, `Harga`, `Stok`, `Status`) VALUES
(1, 'Sarung Tangan', 15000.00, 80, 'Tersedia'),
(2, 'Ikat Pinggang', 30000.00, 53, 'Tersedia'),
(3, 'Sepatu', 250000.00, 10, 'Tersedia'),
(4, 'Buku', 3000.00, 4, 'Tersedia'),
(5, 'Pena', 3000.00, 40, 'Tersedia'),
(6, 'Penghapus', 2000.00, 87, 'Tersedia'),
(7, 'Jangka', 5000.00, 20, 'Tersedia');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `UserID` int(11) NOT NULL,
  `Username` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Nama` varchar(255) NOT NULL,
  `Alamat` text NOT NULL,
  `Role` enum('admin','petugas','pelanggan') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`UserID`, `Username`, `Password`, `Nama`, `Alamat`, `Role`) VALUES
(1, 'admin1', '123', 'Administrator', 'Kantor Pusat', 'admin'),
(2, 'petugas1', '123', 'Budi Kasir', 'Toko Cabang', 'petugas'),
(5, 'Penguinistrator', 'the factory must grow', 'Endmin', 'Valley IV', 'admin'),
(6, 'Indra', 'budi123', 'Pak Indra Kasir', 'Jl. Anggrek', 'petugas'),
(7, 'trailblaze', 'pentung', 'Natan', 'Jl. A Express', 'pelanggan'),
(8, 'Astra', 'astra', 'Naufal', 'Pekanbaru', 'pelanggan'),
(11, 'Dani', 'dani', 'Pak Dani', 'Jakarta', 'petugas'),
(12, 'Dian', 'dian', 'Dian', 'Solo', 'admin'),
(13, 'Abc', 'abc', 'Abc', 'Abc', 'pelanggan');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `detailpenjualan`
--
ALTER TABLE `detailpenjualan`
  ADD PRIMARY KEY (`DetailID`),
  ADD KEY `PenjualanID` (`PenjualanID`),
  ADD KEY `ProdukID` (`ProdukID`);

--
-- Indexes for table `pelanggan`
--
ALTER TABLE `pelanggan`
  ADD PRIMARY KEY (`PelangganID`),
  ADD KEY `fk_user_pelanggan` (`UserID`);

--
-- Indexes for table `penjualan`
--
ALTER TABLE `penjualan`
  ADD PRIMARY KEY (`PenjualanID`),
  ADD KEY `PelangganID` (`PelangganID`);

--
-- Indexes for table `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`ProdukID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`UserID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `detailpenjualan`
--
ALTER TABLE `detailpenjualan`
  MODIFY `DetailID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `pelanggan`
--
ALTER TABLE `pelanggan`
  MODIFY `PelangganID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `penjualan`
--
ALTER TABLE `penjualan`
  MODIFY `PenjualanID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `produk`
--
ALTER TABLE `produk`
  MODIFY `ProdukID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detailpenjualan`
--
ALTER TABLE `detailpenjualan`
  ADD CONSTRAINT `detailpenjualan_ibfk_1` FOREIGN KEY (`PenjualanID`) REFERENCES `penjualan` (`PenjualanID`),
  ADD CONSTRAINT `detailpenjualan_ibfk_2` FOREIGN KEY (`ProdukID`) REFERENCES `produk` (`ProdukID`);

--
-- Constraints for table `pelanggan`
--
ALTER TABLE `pelanggan`
  ADD CONSTRAINT `fk_user_pelanggan` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `penjualan`
--
ALTER TABLE `penjualan`
  ADD CONSTRAINT `penjualan_ibfk_1` FOREIGN KEY (`PelangganID`) REFERENCES `pelanggan` (`PelangganID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
