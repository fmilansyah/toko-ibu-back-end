-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 29 Agu 2023 pada 09.19
-- Versi server: 10.4.13-MariaDB
-- Versi PHP: 7.2.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `toko_ibu`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `pesanan`
--

CREATE TABLE `pesanan` (
  `kd_order` varchar(20) NOT NULL,
  `kd_user` int(11) NOT NULL,
  `kd_detail_barang` varchar(20) NOT NULL,
  `jumlah_barang` int(10) NOT NULL,
  `tanggal_pembayaran` varchar(50) NOT NULL,
  `status_pembayaran` varchar(50) NOT NULL,
  `jasa_pengiriman` varchar(50) NOT NULL,
  `jenis_pengiriman` varchar(50) NOT NULL,
  `no_resi` varchar(100) NOT NULL,
  `status_order` varchar(50) NOT NULL,
  `midtrans_token` varchar(255) NOT NULL,
  `midtrans_order_id` varchar(255) NOT NULL,
  `ongkir` int(11) NOT NULL,
  `kode_jasa_pengiriman` varchar(50) NOT NULL,
  `total_akhir` int(11) NOT NULL,
  `record_status` char(1) NOT NULL DEFAULT 'A' COMMENT 'A=Active, D=Deleted',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`kd_order`,`kd_user`,`kd_detail_barang`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
