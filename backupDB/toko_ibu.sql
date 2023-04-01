-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 01 Apr 2023 pada 09.18
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
-- Struktur dari tabel `barang`
--

CREATE TABLE `barang` (
  `kd_barang` varchar(100) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `record_status` char(1) NOT NULL DEFAULT 'A' COMMENT 'A=Active, D=Deleted',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_barang`
--

CREATE TABLE `detail_barang` (
  `kd_detail_barang` varchar(100) NOT NULL,
  `kd_barang` varchar(100) NOT NULL,
  `varian` varchar(100) NOT NULL,
  `stok` int(11) DEFAULT NULL,
  `harga` int(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur dari tabel `file_barang`
--

CREATE TABLE `file_barang` (
  `kd_file` varchar(100) NOT NULL,
  `kd_barang` varchar(100) NOT NULL,
  `file` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori`
--

CREATE TABLE `kategori` (
  `kd_kategori` varchar(100) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori_barang`
--

CREATE TABLE `kategori_barang` (
  `kd_kategori` varchar(100) NOT NULL,
  `kd_barang` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur dari tabel `keranjang`
--

CREATE TABLE `keranjang` (
  `kd_keranjang` varchar(100) NOT NULL,
  `kd_user` int(10) NOT NULL,
  `kd_ukuran` varchar(100) NOT NULL,
  `jumlah_barang` int(11) NOT NULL,
  `record_status` char(1) NOT NULL DEFAULT 'A' COMMENT 'A=Active, D=Deleted	',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur dari tabel `orders`
--

CREATE TABLE `orders` (
  `kd_order` varchar(100) NOT NULL,
  `kd_user` int(10) NOT NULL,
  `total_akhir` int(11) NOT NULL,
  `tanggal_pembayaran` datetime NOT NULL,
  `status_pembayaran` varchar(100) NOT NULL,
  `record_status` char(1) NOT NULL DEFAULT 'A' COMMENT 'A=Active, D=Deleted	',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur dari tabel `order_detail`
--

CREATE TABLE `order_detail` (
  `kd_order` varchar(100) NOT NULL,
  `kd_detail_barang` varchar(100) NOT NULL,
  `jumlah_barang` int(10) NOT NULL,
  `total_harga` int(50) NOT NULL,
  `record_status` char(1) NOT NULL DEFAULT 'A' COMMENT 'A=Active, D=Deleted	',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktur dari tabel `user`
--

CREATE TABLE `user` (
  `kd_user` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `no_telepon` varchar(20) NOT NULL,
  `alamat` text DEFAULT NULL,
  `kode_pos` int(10) NOT NULL,
  `username` varchar(20) NOT NULL,
  `password` varchar(50) NOT NULL,
  `level` varchar(50) NOT NULL COMMENT 'Pembeli, Pemilik Toko',
  `record_status` char(1) NOT NULL DEFAULT 'A' COMMENT 'A=Active, D=Deleted	',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `user`
--

INSERT INTO `user` (`kd_user`, `nama`, `email`, `no_telepon`, `alamat`, `kode_pos`, `username`, `password`, `level`, `record_status`, `created_at`) VALUES
(1, 'dio', 'maudhioa@gmail.com', '0895704270480', 'Bogor', 16720, 'dio', 'dio', 'pembeli', 'A', '2023-03-31 09:36:19');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`kd_barang`);

--
-- Indeks untuk tabel `detail_barang`
--
ALTER TABLE `detail_barang`
  ADD PRIMARY KEY (`kd_detail_barang`),
  ADD KEY `kd_barang` (`kd_barang`);

--
-- Indeks untuk tabel `file_barang`
--
ALTER TABLE `file_barang`
  ADD PRIMARY KEY (`kd_file`),
  ADD KEY `kd_barang` (`kd_barang`);

--
-- Indeks untuk tabel `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`kd_kategori`);

--
-- Indeks untuk tabel `kategori_barang`
--
ALTER TABLE `kategori_barang`
  ADD PRIMARY KEY (`kd_kategori`,`kd_barang`),
  ADD KEY `kd_barang` (`kd_barang`);

--
-- Indeks untuk tabel `keranjang`
--
ALTER TABLE `keranjang`
  ADD PRIMARY KEY (`kd_keranjang`) USING BTREE,
  ADD KEY `kd_user` (`kd_user`,`kd_ukuran`) USING BTREE;

--
-- Indeks untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`kd_order`),
  ADD KEY `kd_user` (`kd_user`);

--
-- Indeks untuk tabel `order_detail`
--
ALTER TABLE `order_detail`
  ADD PRIMARY KEY (`kd_order`,`kd_detail_barang`) USING BTREE,
  ADD KEY `kd_detail_barang` (`kd_detail_barang`);

--
-- Indeks untuk tabel `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`kd_user`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `user`
--
ALTER TABLE `user`
  MODIFY `kd_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `detail_barang`
--
ALTER TABLE `detail_barang`
  ADD CONSTRAINT `detail_barang_ibfk_1` FOREIGN KEY (`kd_barang`) REFERENCES `barang` (`kd_barang`);

--
-- Ketidakleluasaan untuk tabel `file_barang`
--
ALTER TABLE `file_barang`
  ADD CONSTRAINT `file_barang_ibfk_1` FOREIGN KEY (`kd_barang`) REFERENCES `barang` (`kd_barang`);

--
-- Ketidakleluasaan untuk tabel `kategori_barang`
--
ALTER TABLE `kategori_barang`
  ADD CONSTRAINT `kategori_barang_ibfk_1` FOREIGN KEY (`kd_barang`) REFERENCES `barang` (`kd_barang`),
  ADD CONSTRAINT `kategori_barang_ibfk_2` FOREIGN KEY (`kd_kategori`) REFERENCES `kategori` (`kd_kategori`);

--
-- Ketidakleluasaan untuk tabel `keranjang`
--
ALTER TABLE `keranjang`
  ADD CONSTRAINT `keranjang_ibfk_1` FOREIGN KEY (`kd_user`) REFERENCES `user` (`kd_user`);

--
-- Ketidakleluasaan untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`kd_user`) REFERENCES `user` (`kd_user`);

--
-- Ketidakleluasaan untuk tabel `order_detail`
--
ALTER TABLE `order_detail`
  ADD CONSTRAINT `order_detail_ibfk_1` FOREIGN KEY (`kd_detail_barang`) REFERENCES `detail_barang` (`kd_detail_barang`),
  ADD CONSTRAINT `order_detail_ibfk_2` FOREIGN KEY (`kd_order`) REFERENCES `orders` (`kd_order`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
