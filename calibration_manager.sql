-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Mar 16, 2026 alle 14:22
-- Versione del server: 10.4.32-MariaDB
-- Versione PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `calibration_manager`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `admin`
--

CREATE TABLE `admin` (
  `id` int(10) UNSIGNED NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `giorni_preavviso` tinyint(3) UNSIGNED NOT NULL DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `admin`
--

INSERT INTO `admin` (`id`, `password_hash`, `email`, `giorni_preavviso`) VALUES
(1, '$2y$10$YvPgWMkcZ5zJHth6NrPgtuCCppRNqf5EUox1nH3SjKIKOX/Id17Ue', 'admin@esempio.local', 30);

-- --------------------------------------------------------

--
-- Struttura della tabella `macchinari`
--

CREATE TABLE `macchinari` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(150) NOT NULL,
  `codice_seriale` varchar(100) NOT NULL,
  `reparto_id` int(10) UNSIGNED NOT NULL,
  `tipo_categoria` varchar(100) DEFAULT NULL,
  `unita_misura` varchar(50) DEFAULT NULL,
  `intervallo_mesi` tinyint(3) UNSIGNED NOT NULL DEFAULT 12,
  `qr_token` char(64) NOT NULL,
  `attivo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `macchinari`
--

INSERT INTO `macchinari` (`id`, `nome`, `codice_seriale`, `reparto_id`, `tipo_categoria`, `unita_misura`, `intervallo_mesi`, `qr_token`, `attivo`, `created_at`) VALUES
(1, 'Macchinario_1', 'REP1-001', 1, 'Categoria_A', 'bar', 12, 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2', 1, '2026-03-16 11:48:00'),
(2, 'Macchinario_2', 'REP1-002', 1, 'Categoria_B', 'N', 12, 'b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3', 1, '2026-03-16 11:48:00'),
(3, 'Macchinario_3', 'REP1-003', 1, 'Categoria_C', 'N·m', 6, 'c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4', 1, '2026-03-16 11:48:00'),
(4, 'Macchinario_4', 'REP1-004', 1, 'Categoria_A', 'mm', 12, 'd4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5', 1, '2026-03-16 11:48:00'),
(5, 'Macchinario_5', 'REP1-005', 1, 'Categoria_A', 'mm', 12, 'e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6', 1, '2026-03-16 11:48:00'),
(6, 'Macchinario_6', 'REP2-001', 2, 'Categoria_A', 'bar', 12, 'f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1', 1, '2026-03-16 11:48:00'),
(7, 'Macchinario_7', 'REP2-002', 2, 'Categoria_C', 'N·m', 6, '1a2b3c4d5e6f1a2b3c4d5e6f1a2b3c4d5e6f1a2b3c4d5e6f1a2b3c4d5e6f1a2b', 1, '2026-03-16 11:48:00'),
(8, 'Macchinario_8', 'REP2-003', 2, 'Categoria_D', '°C', 12, '2b3c4d5e6f1a2b3c4d5e6f1a2b3c4d5e6f1a2b3c4d5e6f1a2b3c4d5e6f1a2b3c', 1, '2026-03-16 11:48:00'),
(9, 'Macchinario_9', 'REP2-004', 2, 'Categoria_A', 'mm', 12, '3c4d5e6f1a2b3c4d5e6f1a2b3c4d5e6f1a2b3c4d5e6f1a2b3c4d5e6f1a2b3c4d', 1, '2026-03-16 11:48:00'),
(10, 'Macchinario_10', 'REP2-005', 2, 'Categoria_B', 'N', 12, '4d5e6f1a2b3c4d5e6f1a2b3c4d5e6f1a2b3c4d5e6f1a2b3c4d5e6f1a2b3c4d5e', 1, '2026-03-16 11:48:00'),
(11, 'Macchinario_11', 'REP3-001', 3, 'Categoria_E', 'V', 6, '5e6f1a2b3c4d5e6f1a2b3c4d5e6f1a2b3c4d5e6f1a2b3c4d5e6f1a2b3c4d5e6f', 1, '2026-03-16 11:48:00'),
(12, 'Macchinario_12', 'REP3-002', 3, 'Categoria_E', 'V', 6, '6f1a2b3c4d5e6f1a2b3c4d5e6f1a2b3c4d5e6f1a2b3c4d5e6f1a2b3c4d5e6f1a', 1, '2026-03-16 11:48:00'),
(13, 'Macchinario_13', 'REP3-003', 3, 'Categoria_F', 'Hz', 12, 'aa11223344556677889900aabb11223344556677889900aabb11223344556677', 1, '2026-03-16 11:48:00'),
(14, 'Macchinario_14', 'REP3-004', 3, 'Categoria_F', 'V/s', 12, 'bb22334455667788990011bbcc22334455667788990011bbcc22334455667788', 1, '2026-03-16 11:48:00'),
(15, 'Macchinario_15', 'REP3-005', 3, 'Categoria_E', 'A', 6, 'cc33445566778899001122ccdd33445566778899001122ccdd33445566778899', 1, '2026-03-16 11:48:00'),
(16, 'Macchinario_16', 'REP3-006', 3, 'Categoria_E', 'MΩ', 12, 'dd44556677889900112233ddee44556677889900112233ddee44556677889900', 1, '2026-03-16 11:48:00'),
(17, 'Macchinario_17', 'REP3-007', 3, 'Categoria_G', 'Hz', 24, 'ee55667788990011223344eeff55667788990011223344eeff55667788990011', 1, '2026-03-16 11:48:00'),
(18, 'Macchinario_18', 'REP4-001', 4, 'Categoria_H', 'kg', 12, 'ff66778899001122334455ff0066778899001122334455ff0066778899001122', 1, '2026-03-16 11:48:00'),
(19, 'Macchinario_19', 'REP4-002', 4, 'Categoria_H', 'kg', 12, 'aa77889900112233445566aa1177889900112233445566aa1177889900112233', 1, '2026-03-16 11:48:00'),
(20, 'Macchinario_20', 'REP5-001', 5, 'Categoria_D', '°C', 6, 'bb88990011223344556677bb2288990011223344556677bb2299001122334455', 1, '2026-03-16 11:48:00'),
(21, 'Macchinario_21', 'REP5-002', 5, 'Categoria_D', '°C', 12, 'cc99001122334455667788cc3399001122334455667788cc3399001122334455', 1, '2026-03-16 11:48:00'),
(22, 'Macchinario_22', 'REP5-003', 5, 'Categoria_I', 'mm/s', 12, 'dd00112233445566778899dd4400112233445566778899dd4400112233445566', 1, '2026-03-16 11:48:00'),
(23, 'Macchinario_23', 'REP5-004', 5, 'Categoria_D', '°C', 12, 'ee11223344556677889900ee5511223344556677889900ee5511223344556677', 1, '2026-03-16 11:48:00');

-- --------------------------------------------------------

--
-- Struttura della tabella `reparti`
--

CREATE TABLE `reparti` (
  `id` int(10) UNSIGNED NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descrizione` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `reparti`
--

INSERT INTO `reparti` (`id`, `nome`, `descrizione`) VALUES
(1, 'Reparto_1', 'Descrizione reparto 1'),
(2, 'Reparto_2', 'Descrizione reparto 2'),
(3, 'Reparto_3', 'Descrizione reparto 3'),
(4, 'Reparto_4', 'Descrizione reparto 4'),
(5, 'Reparto_5', 'Descrizione reparto 5');

-- --------------------------------------------------------

--
-- Struttura della tabella `tarature`
--

CREATE TABLE `tarature` (
  `id` int(10) UNSIGNED NOT NULL,
  `macchinario_id` int(10) UNSIGNED NOT NULL,
  `data_inserimento` date NOT NULL DEFAULT curdate(),
  `data_scadenza` date NOT NULL,
  `tecnico` varchar(150) NOT NULL,
  `ente_certificatore` varchar(150) DEFAULT NULL,
  `numero_certificato` varchar(100) DEFAULT NULL,
  `esito` enum('conforme','non_conforme') NOT NULL DEFAULT 'conforme',
  `note` text DEFAULT NULL,
  `pdf_path` varchar(500) NOT NULL,
  `notifica_inviata` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `tarature`
--

INSERT INTO `tarature` (`id`, `macchinario_id`, `data_inserimento`, `data_scadenza`, `tecnico`, `ente_certificatore`, `numero_certificato`, `esito`, `note`, `pdf_path`, `notifica_inviata`, `created_at`) VALUES
(1, 1, '2022-06-10', '2023-06-10', 'Tecnico_1', 'Ente_1', '2022/E1/0144', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2022-06-10 06:00:00'),
(2, 1, '2023-06-12', '2024-06-12', 'Tecnico_1', 'Ente_1', '2023/E1/0201', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2023-06-12 06:00:00'),
(3, 1, '2024-06-05', '2025-06-05', 'Tecnico_3', 'Ente_1', '2024/E1/0312', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2024-06-05 06:00:00'),
(4, 1, '2025-06-04', '2026-06-04', 'Tecnico_1', 'Ente_1', '2025/E1/0289', 'conforme', 'Nessuna anomalia. Valori nei limiti di tolleranza.', 'uploads/tarature/esempio.pdf', 0, '2025-06-04 06:00:00'),
(5, 2, '2023-04-05', '2024-04-05', 'Tecnico_2', 'Ente_2', '2023/E2/0056', 'conforme', 'Verificato su banco calibrazione interno.', 'uploads/tarature/esempio.pdf', 1, '2023-04-05 07:00:00'),
(6, 2, '2024-04-03', '2025-04-03', 'Tecnico_2', 'Ente_2', '2024/E2/0089', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2024-04-03 07:00:00'),
(7, 2, '2025-04-05', '2026-04-05', 'Tecnico_5', 'Ente_2', '2025/E2/0103', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2025-04-05 07:00:00'),
(8, 3, '2023-08-10', '2024-02-10', 'Tecnico_1', 'Ente_1', '2023/E1/0198', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2023-08-10 06:00:00'),
(9, 3, '2024-02-12', '2024-08-12', 'Tecnico_1', 'Ente_1', '2024/E1/0077', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2024-02-12 07:00:00'),
(10, 3, '2024-08-08', '2025-02-08', 'Tecnico_4', 'Ente_1', '2024/E1/0341', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2024-08-08 06:00:00'),
(11, 3, '2025-08-08', '2026-02-08', 'Tecnico_1', 'Ente_1', '2025/E1/0198', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2025-08-08 06:00:00'),
(12, 4, '2023-01-10', '2024-01-10', 'Tecnico_3', 'Ente_3', '2023/E3/0021', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2023-01-10 09:00:00'),
(13, 4, '2024-01-08', '2025-01-08', 'Tecnico_3', 'Ente_3', '2024/E3/0041', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2024-01-08 09:00:00'),
(14, 4, '2025-01-10', '2026-01-10', 'Tecnico_3', 'Ente_3', '2025/E3/0038', 'conforme', 'Calibrazione eseguita con campioni certificati.', 'uploads/tarature/esempio.pdf', 1, '2025-01-10 09:00:00'),
(15, 6, '2023-03-28', '2024-03-28', 'Tecnico_2', 'Ente_1', '2023/E1/0287', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2023-03-28 06:00:00'),
(16, 6, '2024-03-26', '2025-03-26', 'Tecnico_2', 'Ente_1', '2024/E1/0231', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2024-03-26 07:00:00'),
(17, 6, '2025-03-26', '2026-03-26', 'Tecnico_5', 'Ente_1', '2025/E1/0176', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2025-03-26 07:00:00'),
(18, 7, '2024-08-14', '2025-02-14', 'Tecnico_1', 'Ente_2', '2024/E2/0154', 'non_conforme', 'Errore sistematico oltre tolleranza. Strumento inviato a riparazione.', 'uploads/tarature/esempio.pdf', 1, '2024-08-14 07:00:00'),
(19, 7, '2025-02-14', '2025-08-14', 'Tecnico_1', 'Ente_2', '2025/E2/0012', 'conforme', 'Ricollaudo post-riparazione: tutti i valori rientrano nelle tolleranze.', 'uploads/tarature/esempio.pdf', 1, '2025-02-14 08:00:00'),
(20, 7, '2025-08-13', '2026-02-13', 'Tecnico_4', 'Ente_2', '2025/E2/0098', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2025-08-13 07:00:00'),
(21, 7, '2026-02-13', '2026-08-13', 'Tecnico_5', 'Ente_2', '2026/E2/0015', 'conforme', '', 'uploads/tarature/esempio.pdf', 0, '2026-02-13 08:00:00'),
(22, 8, '2023-03-12', '2024-03-12', 'Tecnico_3', 'Ente_4', '2023/E4/0067', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2023-03-12 07:00:00'),
(23, 8, '2024-03-10', '2025-03-10', 'Tecnico_3', 'Ente_4', '2024/E4/0091', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2024-03-10 07:00:00'),
(24, 8, '2025-03-10', '2026-03-10', 'Tecnico_2', 'Ente_4', '2025/E4/0082', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2025-03-10 07:00:00'),
(25, 9, '2024-05-15', '2025-05-15', 'Tecnico_4', 'Ente_3', '2024/E3/0188', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2024-05-15 08:00:00'),
(26, 9, '2025-05-14', '2026-05-14', 'Tecnico_4', 'Ente_3', '2025/E3/0201', 'conforme', '', 'uploads/tarature/esempio.pdf', 0, '2025-05-14 08:00:00'),
(27, 10, '2024-06-20', '2025-06-20', 'Tecnico_5', 'Ente_2', '2024/E2/0210', 'conforme', 'Verificato con masse campione certificate.', 'uploads/tarature/esempio.pdf', 1, '2024-06-20 07:00:00'),
(28, 10, '2025-06-18', '2026-06-18', 'Tecnico_5', 'Ente_2', '2025/E2/0198', 'conforme', '', 'uploads/tarature/esempio.pdf', 0, '2025-06-18 07:00:00'),
(29, 11, '2023-02-16', '2023-08-16', 'Tecnico_3', 'Ente_3', '2023/E3/0078', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2023-02-16 09:00:00'),
(30, 11, '2023-08-15', '2024-02-15', 'Tecnico_3', 'Ente_3', '2023/E3/0201', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2023-08-15 08:00:00'),
(31, 11, '2024-02-15', '2024-08-15', 'Tecnico_1', 'Ente_3', '2024/E3/0055', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2024-02-15 09:00:00'),
(32, 11, '2025-02-15', '2025-08-15', 'Tecnico_3', 'Ente_3', '2025/E3/0078', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2025-02-15 09:00:00'),
(33, 12, '2024-07-20', '2025-01-20', 'Tecnico_3', 'Ente_5', '2024/E5/0034', 'conforme', 'Calibrazione presso centro autorizzato.', 'uploads/tarature/esempio.pdf', 1, '2024-07-20 08:00:00'),
(34, 12, '2025-01-20', '2025-07-20', 'Tecnico_4', 'Ente_5', '2025/E5/0011', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2025-01-20 09:00:00'),
(35, 12, '2025-07-20', '2026-01-20', 'Tecnico_3', 'Ente_5', '2025/E5/0089', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2025-07-20 08:00:00'),
(36, 13, '2024-04-10', '2025-04-10', 'Tecnico_5', 'Ente_2', '2024/E2/0321', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2024-04-10 07:00:00'),
(37, 13, '2025-04-10', '2026-04-10', 'Tecnico_5', 'Ente_2', '2025/E2/0287', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2025-04-10 07:00:00'),
(38, 14, '2024-09-05', '2025-09-05', 'Tecnico_2', 'Ente_5', '2024/E5/0211', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2024-09-05 07:00:00'),
(39, 14, '2025-09-03', '2026-09-03', 'Tecnico_2', 'Ente_5', '2025/E5/0198', 'conforme', 'Verifica range esteso. Nessuna deriva rilevata.', 'uploads/tarature/esempio.pdf', 0, '2025-09-03 07:00:00'),
(40, 15, '2024-09-03', '2025-03-03', 'Tecnico_1', 'Ente_1', '2024/E1/0401', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2024-09-03 06:00:00'),
(41, 15, '2025-03-01', '2025-09-01', 'Tecnico_1', 'Ente_1', '2025/E1/0144', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2025-03-01 07:00:00'),
(42, 15, '2025-09-01', '2026-03-01', 'Tecnico_4', 'Ente_1', '2025/E1/0389', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2025-09-01 06:00:00'),
(43, 16, '2024-05-20', '2025-05-20', 'Tecnico_5', 'Ente_4', '2024/E4/0178', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2024-05-20 07:00:00'),
(44, 16, '2025-05-19', '2026-05-19', 'Tecnico_5', 'Ente_4', '2025/E4/0201', 'conforme', '', 'uploads/tarature/esempio.pdf', 0, '2025-05-19 07:00:00'),
(45, 17, '2022-06-01', '2024-06-01', 'Tecnico_3', 'Ente_3', '2022/E3/0301', 'conforme', 'Taratura biennale.', 'uploads/tarature/esempio.pdf', 1, '2022-06-01 08:00:00'),
(46, 17, '2024-06-03', '2026-06-03', 'Tecnico_3', 'Ente_3', '2024/E3/0412', 'conforme', 'Taratura biennale. Verifica assi X, Y, Z. Tutti i parametri conformi.', 'uploads/tarature/esempio.pdf', 0, '2024-06-03 08:00:00'),
(47, 18, '2024-07-10', '2025-07-10', 'Tecnico_2', 'Ente_2', '2024/E2/0099', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2024-07-10 07:00:00'),
(48, 18, '2025-07-09', '2026-07-09', 'Tecnico_2', 'Ente_2', '2025/E2/0311', 'conforme', '', 'uploads/tarature/esempio.pdf', 0, '2025-07-09 07:00:00'),
(49, 19, '2024-08-22', '2025-08-22', 'Tecnico_5', 'Ente_2', '2024/E2/0267', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2024-08-22 07:00:00'),
(50, 19, '2025-08-20', '2026-08-20', 'Tecnico_5', 'Ente_2', '2025/E2/0354', 'conforme', '', 'uploads/tarature/esempio.pdf', 0, '2025-08-20 07:00:00'),
(51, 20, '2024-03-30', '2024-09-30', 'Tecnico_4', 'Ente_2', '2024/E2/0134', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2024-03-30 08:00:00'),
(52, 20, '2024-09-28', '2025-03-28', 'Tecnico_4', 'Ente_2', '2024/E2/0289', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2024-09-28 07:00:00'),
(53, 20, '2025-09-28', '2026-03-28', 'Tecnico_1', 'Ente_2', '2025/E2/0401', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2025-09-28 07:00:00'),
(54, 21, '2024-10-14', '2025-10-14', 'Tecnico_2', 'Ente_4', '2024/E4/0344', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2024-10-14 07:00:00'),
(55, 21, '2025-10-13', '2026-10-13', 'Tecnico_2', 'Ente_4', '2025/E4/0388', 'conforme', '', 'uploads/tarature/esempio.pdf', 0, '2025-10-13 07:00:00'),
(56, 22, '2024-11-05', '2025-11-05', 'Tecnico_4', 'Ente_1', '2024/E1/0499', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2024-11-05 07:00:00'),
(57, 22, '2025-11-04', '2026-11-04', 'Tecnico_4', 'Ente_1', '2025/E1/0412', 'conforme', '', 'uploads/tarature/esempio.pdf', 0, '2025-11-04 07:00:00'),
(58, 23, '2023-02-10', '2024-02-10', 'Tecnico_1', 'Ente_1', '2023/E1/0112', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2023-02-10 07:00:00'),
(59, 23, '2024-02-09', '2025-02-09', 'Tecnico_1', 'Ente_1', '2024/E1/0098', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2024-02-09 07:00:00'),
(60, 23, '2025-02-09', '2026-02-09', 'Tecnico_1', 'Ente_1', '2025/E1/0112', 'conforme', '', 'uploads/tarature/esempio.pdf', 1, '2025-02-09 07:00:00');

-- --------------------------------------------------------

--
-- Struttura stand-in per le viste `v_tarature_in_scadenza`
-- (Vedi sotto per la vista effettiva)
--
CREATE TABLE `v_tarature_in_scadenza` (
`taratura_id` int(10) unsigned
,`macchinario_nome` varchar(150)
,`codice_seriale` varchar(100)
,`reparto` varchar(100)
,`data_scadenza` date
,`giorni_rimanenti` int(7)
,`notifica_inviata` tinyint(1)
,`email_admin` varchar(255)
,`giorni_preavviso` tinyint(3) unsigned
);

-- --------------------------------------------------------

--
-- Struttura stand-in per le viste `v_ultima_taratura`
-- (Vedi sotto per la vista effettiva)
--
CREATE TABLE `v_ultima_taratura` (
`macchinario_id` int(10) unsigned
,`macchinario_nome` varchar(150)
,`codice_seriale` varchar(100)
,`qr_token` char(64)
,`intervallo_mesi` tinyint(3) unsigned
,`tipo_categoria` varchar(100)
,`unita_misura` varchar(50)
,`reparto` varchar(100)
,`taratura_id` int(10) unsigned
,`data_inserimento` date
,`data_scadenza` date
,`tecnico` varchar(150)
,`ente_certificatore` varchar(150)
,`numero_certificato` varchar(100)
,`esito` enum('conforme','non_conforme')
,`note` text
,`pdf_path` varchar(500)
,`giorni_alla_scadenza` int(7)
,`stato_scadenza` varchar(11)
);

-- --------------------------------------------------------

--
-- Struttura per vista `v_tarature_in_scadenza`
--
DROP TABLE IF EXISTS `v_tarature_in_scadenza`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_tarature_in_scadenza`  AS SELECT `t`.`id` AS `taratura_id`, `m`.`nome` AS `macchinario_nome`, `m`.`codice_seriale` AS `codice_seriale`, `r`.`nome` AS `reparto`, `t`.`data_scadenza` AS `data_scadenza`, to_days(`t`.`data_scadenza`) - to_days(curdate()) AS `giorni_rimanenti`, `t`.`notifica_inviata` AS `notifica_inviata`, `a`.`email` AS `email_admin`, `a`.`giorni_preavviso` AS `giorni_preavviso` FROM (((`tarature` `t` join `macchinari` `m` on(`m`.`id` = `t`.`macchinario_id`)) join `reparti` `r` on(`r`.`id` = `m`.`reparto_id`)) join `admin` `a` on(`a`.`id` = 1)) WHERE `t`.`id` in (select max(`tarature`.`id`) from `tarature` group by `tarature`.`macchinario_id`) AND `t`.`data_scadenza` >= curdate() AND to_days(`t`.`data_scadenza`) - to_days(curdate()) <= `a`.`giorni_preavviso` AND `t`.`notifica_inviata` = 0 AND `m`.`attivo` = 1 ;

-- --------------------------------------------------------

--
-- Struttura per vista `v_ultima_taratura`
--
DROP TABLE IF EXISTS `v_ultima_taratura`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_ultima_taratura`  AS SELECT `m`.`id` AS `macchinario_id`, `m`.`nome` AS `macchinario_nome`, `m`.`codice_seriale` AS `codice_seriale`, `m`.`qr_token` AS `qr_token`, `m`.`intervallo_mesi` AS `intervallo_mesi`, `m`.`tipo_categoria` AS `tipo_categoria`, `m`.`unita_misura` AS `unita_misura`, `r`.`nome` AS `reparto`, `t`.`id` AS `taratura_id`, `t`.`data_inserimento` AS `data_inserimento`, `t`.`data_scadenza` AS `data_scadenza`, `t`.`tecnico` AS `tecnico`, `t`.`ente_certificatore` AS `ente_certificatore`, `t`.`numero_certificato` AS `numero_certificato`, `t`.`esito` AS `esito`, `t`.`note` AS `note`, `t`.`pdf_path` AS `pdf_path`, to_days(`t`.`data_scadenza`) - to_days(curdate()) AS `giorni_alla_scadenza`, CASE WHEN `t`.`data_scadenza` is null THEN NULL WHEN `t`.`data_scadenza` < curdate() THEN 'scaduta' WHEN to_days(`t`.`data_scadenza`) - to_days(curdate()) <= 30 THEN 'in_scadenza' ELSE 'valida' END AS `stato_scadenza` FROM ((`macchinari` `m` join `reparti` `r` on(`r`.`id` = `m`.`reparto_id`)) left join `tarature` `t` on(`t`.`id` = (select `tarature`.`id` from `tarature` where `tarature`.`macchinario_id` = `m`.`id` order by `tarature`.`data_inserimento` desc,`tarature`.`id` desc limit 1))) WHERE `m`.`attivo` = 1 ;

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `macchinari`
--
ALTER TABLE `macchinari`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_seriale` (`codice_seriale`),
  ADD UNIQUE KEY `uq_qr_token` (`qr_token`),
  ADD KEY `fk_macc_reparto` (`reparto_id`);

--
-- Indici per le tabelle `reparti`
--
ALTER TABLE `reparti`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_reparto_nome` (`nome`);

--
-- Indici per le tabelle `tarature`
--
ALTER TABLE `tarature`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_macchinario` (`macchinario_id`),
  ADD KEY `idx_scadenza` (`data_scadenza`),
  ADD KEY `idx_esito` (`esito`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT per la tabella `macchinari`
--
ALTER TABLE `macchinari`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT per la tabella `reparti`
--
ALTER TABLE `reparti`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT per la tabella `tarature`
--
ALTER TABLE `tarature`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `macchinari`
--
ALTER TABLE `macchinari`
  ADD CONSTRAINT `fk_macc_reparto` FOREIGN KEY (`reparto_id`) REFERENCES `reparti` (`id`) ON UPDATE CASCADE;

--
-- Limiti per la tabella `tarature`
--
ALTER TABLE `tarature`
  ADD CONSTRAINT `fk_tar_macchinario` FOREIGN KEY (`macchinario_id`) REFERENCES `macchinari` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
