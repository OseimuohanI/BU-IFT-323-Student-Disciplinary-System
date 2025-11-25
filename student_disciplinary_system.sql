-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3305
-- Generation Time: Nov 25, 2025 at 02:50 PM
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
-- Database: `student_disciplinary_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `appeal`
--

CREATE TABLE `appeal` (
  `AppealID` int(11) NOT NULL,
  `IncidentID` int(11) DEFAULT NULL,
  `AppealDate` datetime DEFAULT NULL,
  `AppealStatus` enum('Pending','Approved','Rejected') DEFAULT NULL,
  `Outcome` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appeal`
--

INSERT INTO `appeal` (`AppealID`, `IncidentID`, `AppealDate`, `AppealStatus`, `Outcome`) VALUES
(1, 6, '2025-09-20 08:06:22', 'Approved', 'Penalty reduced'),
(2, 22, '2025-10-05 09:45:33', 'Pending', NULL),
(3, 29, '2025-11-11 22:38:06', 'Approved', 'Penalty reduced'),
(4, 38, '2025-10-10 15:47:51', 'Pending', NULL),
(5, 59, '2025-08-14 06:09:56', 'Rejected', 'Appeal dismissed');

-- --------------------------------------------------------

--
-- Table structure for table `attachment`
--

CREATE TABLE `attachment` (
  `AttachmentID` int(11) NOT NULL,
  `IncidentID` int(11) DEFAULT NULL,
  `FileName` varchar(255) DEFAULT NULL,
  `FilePath` varchar(512) DEFAULT NULL,
  `UploadedBy` int(11) DEFAULT NULL,
  `UploadedDateTime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attachment`
--

INSERT INTO `attachment` (`AttachmentID`, `IncidentID`, `FileName`, `FilePath`, `UploadedBy`, `UploadedDateTime`) VALUES
(1, 2, 'evidence_2.jpg', '/uploads/evidence_2.jpg', 1, '2025-06-14 01:09:32'),
(2, 13, 'evidence_13.jpg', '/uploads/evidence_13.jpg', 5, '2025-02-26 13:29:20'),
(3, 14, 'evidence_14.jpg', '/uploads/evidence_14.jpg', 5, '2025-08-22 08:59:13'),
(4, 20, 'evidence_20.jpg', '/uploads/evidence_20.jpg', 2, '2025-07-05 19:33:40'),
(5, 26, 'evidence_26.jpg', '/uploads/evidence_26.jpg', 1, '2024-12-17 13:24:36'),
(6, 28, 'evidence_28.jpg', '/uploads/evidence_28.jpg', 1, '2025-05-14 02:38:25'),
(7, 36, 'evidence_36.jpg', '/uploads/evidence_36.jpg', 2, '2025-09-05 00:22:14'),
(8, 39, 'evidence_39.jpg', '/uploads/evidence_39.jpg', 4, '2025-11-11 07:03:49'),
(9, 42, 'evidence_42.jpg', '/uploads/evidence_42.jpg', 5, '2025-11-18 09:12:35'),
(10, 47, 'evidence_47.jpg', '/uploads/evidence_47.jpg', 1, '2024-11-21 04:06:57'),
(11, 49, 'evidence_49.jpg', '/uploads/evidence_49.jpg', 4, '2025-07-03 18:58:27'),
(12, 51, 'evidence_51.jpg', '/uploads/evidence_51.jpg', 1, '2025-06-19 10:48:14'),
(13, 53, 'evidence_53.jpg', '/uploads/evidence_53.jpg', 2, '2025-07-19 11:47:59'),
(14, 59, 'evidence_59.jpg', '/uploads/evidence_59.jpg', 5, '2025-01-21 05:22:59'),
(15, 60, 'evidence_60.jpg', '/uploads/evidence_60.jpg', 1, '2025-05-20 20:10:33');

-- --------------------------------------------------------

--
-- Table structure for table `disciplinaryaction`
--

CREATE TABLE `disciplinaryaction` (
  `ActionID` int(11) NOT NULL,
  `IncidentID` int(11) DEFAULT NULL,
  `ActionType` varchar(100) DEFAULT NULL,
  `ActionDate` date DEFAULT NULL,
  `DurationDays` int(11) DEFAULT NULL,
  `DecisionMakerID` int(11) DEFAULT NULL,
  `Notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `disciplinaryaction`
--

INSERT INTO `disciplinaryaction` (`ActionID`, `IncidentID`, `ActionType`, `ActionDate`, `DurationDays`, `DecisionMakerID`, `Notes`) VALUES
(1, 9, 'Warning', '2025-02-01', 0, 2, 'Action: Warning'),
(2, 10, 'Warning', '2025-05-31', 0, 5, 'Action: Warning'),
(3, 15, 'Warning', '2025-11-03', 0, 4, 'Action: Warning'),
(4, 17, 'Suspension', '2025-05-18', 11, 4, 'Action: Suspension'),
(5, 20, 'Warning', '2025-04-25', 0, 4, 'Action: Warning'),
(6, 21, 'Warning', '2025-07-27', 0, 4, 'Action: Warning'),
(7, 26, 'Counseling', '2025-06-23', 0, 3, 'Action: Counseling'),
(8, 33, 'Counseling', '2025-09-11', 0, 4, 'Action: Counseling'),
(9, 35, 'Expulsion', '2025-03-18', 0, 1, 'Action: Expulsion'),
(10, 38, 'Counseling', '2025-03-03', 0, 3, 'Action: Counseling'),
(11, 51, 'Counseling', '2025-11-18', 0, 1, 'Action: Counseling'),
(12, 57, 'Counseling', '2025-05-10', 0, 5, 'Action: Counseling'),
(13, 58, 'Counseling', '2025-06-07', 0, 5, 'Action: Counseling');

-- --------------------------------------------------------

--
-- Table structure for table `hearing`
--

CREATE TABLE `hearing` (
  `HearingID` int(11) NOT NULL,
  `IncidentID` int(11) DEFAULT NULL,
  `HearingDate` datetime DEFAULT NULL,
  `Outcome` varchar(200) DEFAULT NULL,
  `HearingNotes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hearing`
--

INSERT INTO `hearing` (`HearingID`, `IncidentID`, `HearingDate`, `Outcome`, `HearingNotes`) VALUES
(1, 10, '2025-04-12 02:30:31', 'Guilty', 'Hearing held: outcome Guilty.'),
(2, 12, '2025-04-13 16:25:05', 'Not Guilty', 'Hearing held: outcome Not Guilty.'),
(3, 16, '2025-11-22 00:03:52', 'Guilty', 'Hearing held: outcome Guilty.'),
(4, 19, '2025-06-28 23:45:09', 'Not Guilty', 'Hearing held: outcome Not Guilty.'),
(5, 27, '2025-11-05 21:09:24', 'Guilty', 'Hearing held: outcome Guilty.'),
(6, 29, '2025-09-09 06:07:25', 'No Action', 'Hearing held: outcome No Action.'),
(7, 30, '2025-07-16 21:52:13', 'No Action', 'Hearing held: outcome No Action.'),
(8, 40, '2025-09-24 05:32:19', 'Guilty', 'Hearing held: outcome Guilty.'),
(9, 41, '2025-04-28 06:33:47', 'Not Guilty', 'Hearing held: outcome Not Guilty.'),
(10, 42, '2025-08-29 20:00:19', 'No Action', 'Hearing held: outcome No Action.'),
(11, 51, '2025-11-20 01:30:28', 'Guilty', 'Hearing held: outcome Guilty.'),
(12, 53, '2025-07-15 17:42:01', 'No Action', 'Hearing held: outcome No Action.'),
(13, 54, '2025-03-24 16:31:56', 'Not Guilty', 'Hearing held: outcome Not Guilty.'),
(14, 57, '2025-09-27 19:15:58', 'Not Guilty', 'Hearing held: outcome Not Guilty.'),
(15, 59, '2025-04-10 18:46:44', 'Not Guilty', 'Hearing held: outcome Not Guilty.'),
(16, 60, '2025-03-21 18:06:02', 'Guilty', 'Hearing held: outcome Guilty.');

-- --------------------------------------------------------

--
-- Table structure for table `incidentreport`
--

CREATE TABLE `incidentreport` (
  `IncidentID` int(11) NOT NULL,
  `ReportDate` datetime DEFAULT NULL,
  `Location` varchar(200) DEFAULT NULL,
  `ReporterStaffID` int(11) DEFAULT NULL,
  `StudentID` int(11) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Status` enum('Pending','In Review','Closed') DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `incidentreport`
--

INSERT INTO `incidentreport` (`IncidentID`, `ReportDate`, `Location`, `ReporterStaffID`, `StudentID`, `Description`, `Status`, `CreatedAt`) VALUES
(0, '2025-11-24 19:21:00', 'Classroom  202', 1, 40, 'Gangsterism', 'In Review', '2025-11-24 18:22:00'),
(1, '2024-12-17 06:37:56', 'Playground', 4, 11, 'Auto-generated incident #1 — student misbehavior observed at Playground.', 'Closed', '2025-11-23 18:14:18'),
(2, '2025-06-07 04:07:48', 'Dormitory', 1, 29, 'Auto-generated incident #2 — student misbehavior observed at Dormitory.', 'Closed', '2025-11-23 18:14:18'),
(3, '2024-11-12 10:11:21', 'Playground', 3, 10, 'Auto-generated incident #3 — student misbehavior observed at Playground.', 'Pending', '2025-11-23 18:14:18'),
(4, '2025-10-12 02:37:06', 'Main Hall', 1, 2, 'Auto-generated incident #4 — student misbehavior observed at Main Hall.\nType: Suspension; Duration(days): 60; Effective: 2025-11-25; Notes: Goo; IssuedBy: lecturer @ 2025-11-25 02:20:01', '', '2025-11-25 01:20:01'),
(5, '2025-06-13 20:55:11', 'Cafeteria', 1, 23, 'Auto-generated incident #5 — student misbehavior observed at Cafeteria.', 'Closed', '2025-11-23 18:14:18'),
(6, '2025-05-03 11:33:04', 'Library', 4, 13, 'Auto-generated incident #6 — student misbehavior observed at Library.', 'Pending', '2025-11-23 18:14:18'),
(7, '2024-11-01 03:56:41', 'Classroom 101', 2, 35, 'Auto-generated incident #7 — student misbehavior observed at Classroom 101.', 'Pending', '2025-11-23 18:14:18'),
(8, '2025-06-25 07:04:02', 'Library', 1, 29, 'Auto-generated incident #8 — student misbehavior observed at Library.', 'Pending', '2025-11-23 18:14:18'),
(9, '2025-04-27 18:06:11', 'Gym', 5, 19, 'Auto-generated incident #9 — student misbehavior observed at Gym.', 'Closed', '2025-11-23 18:14:18'),
(10, '2025-08-05 06:28:23', 'Library', 2, 35, 'Auto-generated incident #10 — student misbehavior observed at Library.', 'Closed', '2025-11-23 18:14:18'),
(11, '2025-11-21 15:53:00', 'Classroom 101', 3, 31, 'Auto-generated incident #11 — student misbehavior observed at Classroom 101.', 'In Review', '2025-11-23 21:48:27'),
(12, '2025-10-09 02:24:19', 'Classroom 101', 3, 15, 'Auto-generated incident #12 — student misbehavior observed at Classroom 101.', 'In Review', '2025-11-23 18:14:18'),
(13, '2025-05-12 07:48:25', 'Gym', 1, 12, 'Auto-generated incident #13 — student misbehavior observed at Gym.', 'Closed', '2025-11-23 18:14:18'),
(14, '2025-05-05 05:49:44', 'Classroom 101', 5, 1, 'Auto-generated incident #14 — student misbehavior observed at Classroom 101.', 'Closed', '2025-11-23 18:14:18'),
(15, '2024-11-15 22:19:28', 'Cafeteria', 3, 6, 'Auto-generated incident #15 — student misbehavior observed at Cafeteria.', 'Pending', '2025-11-23 18:14:18'),
(16, '2025-05-13 00:21:53', 'Main Hall', 3, 19, 'Auto-generated incident #16 — student misbehavior observed at Main Hall.', 'In Review', '2025-11-23 18:14:18'),
(17, '2025-08-26 10:05:18', 'Main Hall', 3, 34, 'Auto-generated incident #17 — student misbehavior observed at Main Hall.', 'In Review', '2025-11-23 18:14:18'),
(18, '2025-03-20 21:15:10', 'Gym', 2, 1, 'Auto-generated incident #18 — student misbehavior observed at Gym.', 'Closed', '2025-11-23 18:14:18'),
(19, '2024-11-15 15:18:23', 'Classroom 101', 1, 13, 'Auto-generated incident #19 — student misbehavior observed at Classroom 101.', 'Pending', '2025-11-23 18:14:18'),
(20, '2025-10-05 00:24:29', 'Playground', 5, 7, 'Auto-generated incident #20 — student misbehavior observed at Playground.', 'In Review', '2025-11-23 18:14:18'),
(21, '2025-05-24 17:41:06', 'Main Hall', 5, 8, 'Auto-generated incident #21 — student misbehavior observed at Main Hall.', 'Pending', '2025-11-23 18:14:18'),
(22, '2025-05-23 20:27:52', 'Gym', 4, 6, 'Auto-generated incident #22 — student misbehavior observed at Gym.', 'Closed', '2025-11-23 18:14:18'),
(23, '2024-11-10 13:06:14', 'Playground', 1, 2, 'Auto-generated incident #23 — student misbehavior observed at Playground.', 'Closed', '2025-11-23 18:14:18'),
(24, '2025-10-25 04:43:32', 'Dormitory', 4, 10, 'Auto-generated incident #24 — student misbehavior observed at Dormitory.\nType: Suspension; Duration(days): 30; Effective: 2025-11-25; IssuedBy: admin @ 2025-11-25 00:50:04', '', '2025-11-24 23:50:04'),
(25, '2024-12-07 03:58:46', 'Main Hall', 5, 23, 'Auto-generated incident #25 — student misbehavior observed at Main Hall.', 'Closed', '2025-11-23 18:14:18'),
(26, '2025-10-15 08:48:30', 'Playground', 2, 5, 'Auto-generated incident #26 — student misbehavior observed at Playground.', 'Closed', '2025-11-23 18:14:18'),
(27, '2024-11-19 12:10:19', 'Main Hall', 2, 13, 'Auto-generated incident #27 — student misbehavior observed at Main Hall.', 'In Review', '2025-11-23 18:14:18'),
(28, '2025-03-11 18:06:20', 'Library', 5, 1, 'Auto-generated incident #28 — student misbehavior observed at Library.', 'Closed', '2025-11-23 18:14:18'),
(29, '2024-12-06 22:52:21', 'Library', 2, 20, 'Auto-generated incident #29 — student misbehavior observed at Library.', 'In Review', '2025-11-23 18:14:18'),
(30, '2024-11-21 23:09:10', 'Main Hall', 4, 20, 'Auto-generated incident #30 — student misbehavior observed at Main Hall.', 'In Review', '2025-11-23 18:14:18'),
(31, '2025-08-29 03:19:10', 'Cafeteria', 4, 38, 'Auto-generated incident #31 — student misbehavior observed at Cafeteria.', 'Pending', '2025-11-23 18:14:18'),
(32, '2024-11-17 15:54:07', 'Classroom 101', 5, 37, 'Auto-generated incident #32 — student misbehavior observed at Classroom 101.', 'Pending', '2025-11-23 18:14:18'),
(33, '2025-07-06 10:20:53', 'Playground', 3, 13, 'Auto-generated incident #33 — student misbehavior observed at Playground.', 'Pending', '2025-11-23 18:14:18'),
(34, '2025-04-12 18:15:44', 'Dormitory', 1, 31, 'Auto-generated incident #34 — student misbehavior observed at Dormitory.', 'Pending', '2025-11-23 18:14:18'),
(35, '2025-04-03 11:31:21', 'Playground', 2, 1, 'Auto-generated incident #35 — student misbehavior observed at Playground.', 'In Review', '2025-11-23 18:14:18'),
(36, '2025-06-04 01:30:09', 'Library', 5, 7, 'Auto-generated incident #36 — student misbehavior observed at Library.', 'In Review', '2025-11-23 18:14:18'),
(37, '2025-04-12 19:54:13', 'Classroom 101', 2, 24, 'Auto-generated incident #37 — student misbehavior observed at Classroom 101.', 'In Review', '2025-11-23 18:14:18'),
(38, '2024-11-26 21:12:52', 'Dormitory', 3, 2, 'Auto-generated incident #38 — student misbehavior observed at Dormitory.', 'Pending', '2025-11-23 18:14:18'),
(39, '2025-02-24 07:20:45', 'Dormitory', 2, 25, 'Auto-generated incident #39 — student misbehavior observed at Dormitory.', 'Closed', '2025-11-23 18:14:18'),
(40, '2025-07-09 07:45:21', 'Playground', 4, 24, 'Auto-generated incident #40 — student misbehavior observed at Playground.\nType: Warning; Duration(days): 1; Effective: 2025-11-25; Notes: Dish; IssuedBy: admin @ 2025-11-25 00:52:55', '', '2025-11-24 23:52:55'),
(41, '2024-11-28 04:28:21', 'Gym', 3, 21, 'Auto-generated incident #41 — student misbehavior observed at Gym.', 'In Review', '2025-11-23 18:14:18'),
(42, '2025-05-21 06:23:34', 'Dormitory', 2, 10, 'Auto-generated incident #42 — student misbehavior observed at Dormitory.', 'In Review', '2025-11-23 18:14:18'),
(43, '2025-10-18 00:24:31', 'Dormitory', 1, 11, 'Auto-generated incident #43 — student misbehavior observed at Dormitory.', 'Closed', '2025-11-23 18:14:18'),
(44, '2025-02-28 08:09:50', 'Dormitory', 5, 36, 'Auto-generated incident #44 — student misbehavior observed at Dormitory.', 'Closed', '2025-11-23 18:14:18'),
(45, '2025-09-20 18:10:00', 'Library', 5, 34, 'Auto-generated incident #45 — student misbehavior observed at Library.', 'In Review', '2025-11-25 01:40:46'),
(46, '2025-09-06 08:04:31', 'Library', 5, 10, 'Auto-generated incident #46 — student misbehavior observed at Library.', 'Closed', '2025-11-23 18:14:18'),
(47, '2025-03-20 07:07:05', 'Library', 2, 13, 'Auto-generated incident #47 — student misbehavior observed at Library.', 'Pending', '2025-11-23 18:14:18'),
(48, '2025-02-18 05:10:39', 'Main Hall', 1, 29, 'Auto-generated incident #48 — student misbehavior observed at Main Hall.', 'In Review', '2025-11-23 18:14:18'),
(49, '2024-12-22 18:45:30', 'Playground', 5, 21, 'Auto-generated incident #49 — student misbehavior observed at Playground.', 'Pending', '2025-11-23 18:14:18'),
(50, '2024-11-16 08:09:31', 'Playground', 2, 20, 'Auto-generated incident #50 — student misbehavior observed at Playground.', 'Pending', '2025-11-23 18:14:18'),
(51, '2025-01-16 17:47:00', 'Main Hall', 4, 19, 'Auto-generated incident #51 — student misbehavior observed at Main Hall.', 'Pending', '2025-11-23 18:14:18'),
(52, '2025-01-12 06:38:04', 'Library', 4, 32, 'Auto-generated incident #52 — student misbehavior observed at Library.', 'Closed', '2025-11-23 18:14:18'),
(53, '2025-11-06 19:23:48', 'Playground', 2, 9, 'Auto-generated incident #53 — student misbehavior observed at Playground.', 'Closed', '2025-11-23 18:14:18'),
(54, '2025-09-24 16:51:41', 'Main Hall', 3, 11, 'Auto-generated incident #54 — student misbehavior observed at Main Hall.', 'Closed', '2025-11-23 18:14:18'),
(55, '2024-11-28 04:14:03', 'Classroom 101', 5, 10, 'Auto-generated incident #55 — student misbehavior observed at Classroom 101.', 'Closed', '2025-11-23 18:14:18'),
(56, '2025-02-09 03:17:54', 'Classroom 101', 1, 32, 'Auto-generated incident #56 — student misbehavior observed at Classroom 101.', 'Pending', '2025-11-23 18:14:18'),
(57, '2024-12-24 22:13:11', 'Cafeteria', 3, 8, 'Auto-generated incident #57 — student misbehavior observed at Cafeteria.', 'In Review', '2025-11-23 18:14:18'),
(58, '2025-09-11 19:58:11', 'Main Hall', 4, 15, 'Auto-generated incident #58 — student misbehavior observed at Main Hall.', 'In Review', '2025-11-23 18:14:18'),
(59, '2024-12-07 12:45:29', 'Dormitory', 5, 14, 'Auto-generated incident #59 — student misbehavior observed at Dormitory.', 'Closed', '2025-11-23 18:14:18'),
(60, '2025-09-17 07:50:22', 'Main Hall', 3, 15, 'Auto-generated incident #60 — student misbehavior observed at Main Hall.', 'Pending', '2025-11-23 18:14:18'),
(61, '2025-06-12 13:50:00', 'Classroom', 2, 24, 'Misbehaving', 'Closed', '2025-11-24 23:40:25');

-- --------------------------------------------------------

--
-- Table structure for table `offensetype`
--

CREATE TABLE `offensetype` (
  `OffenseTypeID` int(11) NOT NULL,
  `Code` varchar(20) DEFAULT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `SeverityLevel` tinyint(4) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offensetype`
--

INSERT INTO `offensetype` (`OffenseTypeID`, `Code`, `Description`, `SeverityLevel`, `CreatedAt`) VALUES
(1, 'OT01', 'Cheating on exam', 3, '2025-11-23 18:14:18'),
(2, 'OT02', 'Classroom disruption', 1, '2025-11-23 18:14:18'),
(3, 'OT03', 'Vandalism', 2, '2025-11-23 18:14:18'),
(4, 'OT04', 'Bullying', 3, '2025-11-23 18:14:18'),
(5, 'OT05', 'Late submission', 1, '2025-11-23 18:14:18');

-- --------------------------------------------------------

--
-- Table structure for table `reportoffense`
--

CREATE TABLE `reportoffense` (
  `ReportOffenseID` int(11) NOT NULL,
  `IncidentID` int(11) DEFAULT NULL,
  `OffenseTypeID` int(11) DEFAULT NULL,
  `Notes` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reportoffense`
--

INSERT INTO `reportoffense` (`ReportOffenseID`, `IncidentID`, `OffenseTypeID`, `Notes`) VALUES
(1, 1, 4, 'Auto note: offense 4 for incident 1.'),
(2, 2, 1, 'Auto note: offense 1 for incident 2.'),
(3, 3, 3, 'Auto note: offense 3 for incident 3.'),
(4, 4, 4, 'Auto note: offense 4 for incident 4.'),
(5, 5, 3, 'Auto note: offense 3 for incident 5.'),
(6, 5, 4, 'Auto note: offense 4 for incident 5.'),
(7, 6, 4, 'Auto note: offense 4 for incident 6.'),
(8, 7, 4, 'Auto note: offense 4 for incident 7.'),
(9, 8, 2, 'Auto note: offense 2 for incident 8.'),
(10, 8, 1, 'Auto note: offense 1 for incident 8.'),
(11, 9, 3, 'Auto note: offense 3 for incident 9.'),
(12, 9, 1, 'Auto note: offense 1 for incident 9.'),
(13, 10, 3, 'Auto note: offense 3 for incident 10.'),
(14, 10, 5, 'Auto note: offense 5 for incident 10.'),
(15, 11, 4, 'Auto note: offense 4 for incident 11.'),
(16, 11, 5, 'Auto note: offense 5 for incident 11.'),
(17, 12, 5, 'Auto note: offense 5 for incident 12.'),
(18, 12, 1, 'Auto note: offense 1 for incident 12.'),
(19, 13, 5, 'Auto note: offense 5 for incident 13.'),
(20, 14, 5, 'Auto note: offense 5 for incident 14.'),
(21, 14, 1, 'Auto note: offense 1 for incident 14.'),
(22, 15, 5, 'Auto note: offense 5 for incident 15.'),
(23, 15, 5, 'Auto note: offense 5 for incident 15.'),
(24, 16, 1, 'Auto note: offense 1 for incident 16.'),
(25, 16, 2, 'Auto note: offense 2 for incident 16.'),
(26, 17, 3, 'Auto note: offense 3 for incident 17.'),
(27, 17, 1, 'Auto note: offense 1 for incident 17.'),
(28, 18, 5, 'Auto note: offense 5 for incident 18.'),
(29, 18, 4, 'Auto note: offense 4 for incident 18.'),
(30, 19, 2, 'Auto note: offense 2 for incident 19.'),
(31, 20, 4, 'Auto note: offense 4 for incident 20.'),
(32, 20, 5, 'Auto note: offense 5 for incident 20.'),
(33, 21, 5, 'Auto note: offense 5 for incident 21.'),
(34, 22, 1, 'Auto note: offense 1 for incident 22.'),
(35, 22, 4, 'Auto note: offense 4 for incident 22.'),
(36, 23, 5, 'Auto note: offense 5 for incident 23.'),
(37, 23, 4, 'Auto note: offense 4 for incident 23.'),
(38, 24, 4, 'Auto note: offense 4 for incident 24.'),
(39, 24, 2, 'Auto note: offense 2 for incident 24.'),
(40, 25, 3, 'Auto note: offense 3 for incident 25.'),
(41, 25, 5, 'Auto note: offense 5 for incident 25.'),
(42, 26, 4, 'Auto note: offense 4 for incident 26.'),
(43, 26, 5, 'Auto note: offense 5 for incident 26.'),
(44, 27, 4, 'Auto note: offense 4 for incident 27.'),
(45, 27, 3, 'Auto note: offense 3 for incident 27.'),
(46, 28, 5, 'Auto note: offense 5 for incident 28.'),
(47, 29, 3, 'Auto note: offense 3 for incident 29.'),
(48, 29, 3, 'Auto note: offense 3 for incident 29.'),
(49, 30, 3, 'Auto note: offense 3 for incident 30.'),
(50, 31, 3, 'Auto note: offense 3 for incident 31.'),
(51, 31, 5, 'Auto note: offense 5 for incident 31.'),
(52, 32, 1, 'Auto note: offense 1 for incident 32.'),
(53, 32, 2, 'Auto note: offense 2 for incident 32.'),
(54, 33, 5, 'Auto note: offense 5 for incident 33.'),
(55, 33, 2, 'Auto note: offense 2 for incident 33.'),
(56, 34, 3, 'Auto note: offense 3 for incident 34.'),
(57, 35, 1, 'Auto note: offense 1 for incident 35.'),
(58, 35, 1, 'Auto note: offense 1 for incident 35.'),
(59, 36, 5, 'Auto note: offense 5 for incident 36.'),
(60, 36, 2, 'Auto note: offense 2 for incident 36.'),
(61, 37, 2, 'Auto note: offense 2 for incident 37.'),
(62, 38, 4, 'Auto note: offense 4 for incident 38.'),
(63, 39, 2, 'Auto note: offense 2 for incident 39.'),
(64, 39, 1, 'Auto note: offense 1 for incident 39.'),
(65, 40, 2, 'Auto note: offense 2 for incident 40.'),
(66, 40, 5, 'Auto note: offense 5 for incident 40.'),
(67, 41, 1, 'Auto note: offense 1 for incident 41.'),
(68, 41, 2, 'Auto note: offense 2 for incident 41.'),
(69, 42, 4, 'Auto note: offense 4 for incident 42.'),
(70, 42, 2, 'Auto note: offense 2 for incident 42.'),
(71, 43, 4, 'Auto note: offense 4 for incident 43.'),
(72, 44, 5, 'Auto note: offense 5 for incident 44.'),
(73, 44, 5, 'Auto note: offense 5 for incident 44.'),
(74, 45, 5, 'Auto note: offense 5 for incident 45.'),
(75, 45, 2, 'Auto note: offense 2 for incident 45.'),
(76, 46, 3, 'Auto note: offense 3 for incident 46.'),
(77, 47, 5, 'Auto note: offense 5 for incident 47.'),
(78, 48, 3, 'Auto note: offense 3 for incident 48.'),
(79, 49, 2, 'Auto note: offense 2 for incident 49.'),
(80, 50, 2, 'Auto note: offense 2 for incident 50.'),
(81, 51, 2, 'Auto note: offense 2 for incident 51.'),
(82, 51, 1, 'Auto note: offense 1 for incident 51.'),
(83, 52, 3, 'Auto note: offense 3 for incident 52.'),
(84, 53, 3, 'Auto note: offense 3 for incident 53.'),
(85, 53, 4, 'Auto note: offense 4 for incident 53.'),
(86, 54, 2, 'Auto note: offense 2 for incident 54.'),
(87, 54, 4, 'Auto note: offense 4 for incident 54.'),
(88, 55, 3, 'Auto note: offense 3 for incident 55.'),
(89, 55, 3, 'Auto note: offense 3 for incident 55.'),
(90, 56, 1, 'Auto note: offense 1 for incident 56.'),
(91, 56, 1, 'Auto note: offense 1 for incident 56.'),
(92, 57, 1, 'Auto note: offense 1 for incident 57.'),
(93, 57, 5, 'Auto note: offense 5 for incident 57.'),
(94, 58, 5, 'Auto note: offense 5 for incident 58.'),
(95, 58, 1, 'Auto note: offense 1 for incident 58.'),
(96, 59, 2, 'Auto note: offense 2 for incident 59.'),
(97, 59, 3, 'Auto note: offense 3 for incident 59.'),
(98, 60, 5, 'Auto note: offense 5 for incident 60.'),
(99, 60, 2, 'Auto note: offense 2 for incident 60.');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `StaffID` int(11) NOT NULL,
  `StaffNo` varchar(50) DEFAULT NULL,
  `Name` varchar(150) DEFAULT NULL,
  `Role` varchar(100) DEFAULT NULL,
  `Email` varchar(150) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`StaffID`, `StaffNo`, `Name`, `Role`, `Email`, `CreatedAt`) VALUES
(1, 'S001', 'Alice Johnson', 'Teacher', 'alice@example.com', '2025-11-23 18:14:18'),
(2, 'S002', 'Bob Smith', 'Dean', 'bob@example.com', '2025-11-23 18:14:18'),
(3, 'S003', 'Cathy Lee', 'Counselor', 'cathy@example.com', '2025-11-23 18:14:18'),
(4, 'S004', 'David Park', 'Teacher', 'david@example.com', '2025-11-23 18:14:18'),
(5, 'S005', 'Eve Turner', 'Registrar', 'eve@example.com', '2025-11-23 18:14:18');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `StudentID` int(11) NOT NULL,
  `EnrollmentNo` varchar(50) DEFAULT NULL,
  `FirstName` varchar(100) DEFAULT NULL,
  `LastName` varchar(100) DEFAULT NULL,
  `DOB` date DEFAULT NULL,
  `Gender` enum('Male','Female','Other') DEFAULT NULL,
  `Email` varchar(150) DEFAULT NULL,
  `Phone` varchar(50) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`StudentID`, `EnrollmentNo`, `FirstName`, `LastName`, `DOB`, `Gender`, `Email`, `Phone`, `CreatedAt`) VALUES
(0, 'EN00042', 'Oseimuohan', 'Itua', '2006-12-03', 'Male', 'me@example.com', '8076629696', '2025-11-24 22:36:49'),
(1, 'EN00001', 'Olivia', 'Williams', '2003-09-05', 'Male', 'olivia.williams1@example.com', '0710667937', '2025-11-23 18:14:18'),
(2, 'EN00002', 'Noah', 'Miller', '2003-01-11', 'Female', 'noah.miller2@example.com', '0798123163', '2025-11-23 18:14:18'),
(3, 'EN00003', 'Amelia', 'Davis', '2006-05-29', 'Female', 'amelia.davis3@example.com', '0778267431', '2025-11-23 18:14:18'),
(4, 'EN00004', 'James', 'Miller', '2006-09-30', 'Other', 'james.miller4@example.com', '0793839985', '2025-11-23 18:14:18'),
(5, 'EN00005', 'Evelyn', 'Smith', '2000-06-01', 'Female', 'evelyn.smith5@example.com', '0799825111', '2025-11-23 18:14:18'),
(6, 'EN00006', 'Emma', 'Martinez', '2005-05-12', 'Female', 'emma.martinez6@example.com', '0773979557', '2025-11-23 18:14:18'),
(7, 'EN00007', 'Zoe', 'Miller', '2003-08-31', 'Male', 'zoe.miller7@example.com', '0787682513', '2025-11-23 18:14:18'),
(8, 'EN00008', 'Zoe', 'Davis', '2004-08-03', 'Other', 'zoe.davis8@example.com', '0739757239', '2025-11-23 18:14:18'),
(9, 'EN00009', 'Emma', 'Martinez', '2002-03-31', 'Female', 'emma.martinez9@example.com', '0744942653', '2025-11-23 18:14:18'),
(10, 'EN00010', 'Liam', 'Johnson', '2003-06-04', 'Other', 'liam.johnson10@example.com', '0788356146', '2025-11-23 18:14:18'),
(11, 'EN00011', 'Sophia', 'Smith', '2000-03-23', 'Other', 'sophia.smith11@example.com', '0746936575', '2025-11-23 18:14:18'),
(12, 'EN00012', 'Amelia', 'Miller', '2002-12-17', 'Male', 'amelia.miller12@example.com', '0731946555', '2025-11-23 18:14:18'),
(13, 'EN00013', 'Lucas', 'Jones', '2007-10-14', 'Female', 'lucas.jones13@example.com', '0798055761', '2025-11-23 18:14:18'),
(14, 'EN00014', 'Zoe', 'Davis', '2007-09-30', 'Male', 'zoe.davis14@example.com', '0798346219', '2025-11-23 18:14:18'),
(15, 'EN00015', 'James', 'Miller', '2008-07-15', 'Other', 'james.miller15@example.com', '0784787362', '2025-11-23 18:14:18'),
(16, 'EN00016', 'Noah', 'Davis', '2004-07-01', 'Male', 'noah.davis16@example.com', '0730415071', '2025-11-23 18:14:18'),
(17, 'EN00017', 'Logan', 'Johnson', '2001-04-18', 'Male', 'logan.johnson17@example.com', '0744535180', '2025-11-23 18:14:18'),
(18, 'EN00018', 'Mason', 'Smith', '2008-11-04', 'Female', 'mason.smith18@example.com', '0735586536', '2025-11-23 18:14:18'),
(19, 'EN00019', 'Logan', 'Smith', '2008-07-06', 'Female', 'logan.smith19@example.com', '0771293285', '2025-11-23 18:14:18'),
(20, 'EN00020', 'Ava', 'Williams', '2006-08-15', 'Male', 'ava.williams20@example.com', '0782277007', '2025-11-23 18:14:18'),
(21, 'EN00021', 'Lucas', 'Miller', '2005-06-26', 'Male', 'lucas.miller21@example.com', '0737498605', '2025-11-23 18:14:18'),
(22, 'EN00022', 'Ethan', 'Davis', '2000-02-15', 'Female', 'ethan.davis22@example.com', '0743476656', '2025-11-23 18:14:18'),
(23, 'EN00023', 'Lucas', 'Smith', '2001-05-27', 'Other', 'lucas.smith23@example.com', '0754343947', '2025-11-23 18:14:18'),
(24, 'EN00024', 'Ava', 'Brown', '2001-01-15', 'Female', 'ava.brown24@example.com', '0744364397', '2025-11-24 22:52:11'),
(25, 'EN00025', 'Noah', 'Martinez', '2003-01-13', 'Male', 'noah.martinez25@example.com', '0756653865', '2025-11-23 18:14:18'),
(26, 'EN00026', 'Sophia', 'Garcia', '2005-10-14', 'Other', 'sophia.garcia26@example.com', '0714987352', '2025-11-23 18:14:18'),
(27, 'EN00027', 'Evelyn', 'Miller', '2002-06-21', 'Male', 'evelyn.miller27@example.com', '0796224736', '2025-11-23 18:14:18'),
(28, 'EN00028', 'Ethan', 'Miller', '2006-07-17', 'Male', 'ethan.miller28@example.com', '0710940583', '2025-11-23 18:14:18'),
(29, 'EN00029', 'Harper', 'Brown', '2001-09-26', 'Other', 'harper.brown29@example.com', '0767956922', '2025-11-23 18:14:18'),
(30, 'EN00030', 'Lucas', 'Johnson', '2000-11-26', 'Other', 'lucas.johnson30@example.com', '0712326917', '2025-11-23 18:14:18'),
(31, 'EN00031', 'Evelyn', 'Johnson', '2004-06-26', 'Female', 'evelyn.johnson31@example.com', '0733355463', '2025-11-23 18:14:18'),
(32, 'EN00032', 'Ava', 'Smith', '2006-02-16', 'Other', 'ava.smith32@example.com', '0761913546', '2025-11-23 18:14:18'),
(33, 'EN00033', 'Mason', 'Martinez', '2008-06-08', 'Other', 'mason.martinez33@example.com', '0756549349', '2025-11-23 18:14:18'),
(34, 'EN00034', 'James', 'Rodriguez', '2002-05-05', 'Female', 'james.rodriguez34@example.com', '0745452302', '2025-11-23 18:14:18'),
(35, 'EN00035', 'Zoe', 'Jones', '2002-06-26', 'Female', 'zoe.jones35@example.com', '0769925992', '2025-11-23 18:14:18'),
(36, 'EN00036', 'Ava', 'Davis', '2001-09-29', 'Other', 'ava.davis36@example.com', '0727344637', '2025-11-23 18:14:18'),
(37, 'EN00037', 'Amelia', 'Johnson', '2005-12-07', 'Female', 'amelia.johnson37@example.com', '0798545052', '2025-11-23 18:14:18'),
(38, 'EN00038', 'Olivia', 'Davis', '2006-02-02', 'Other', 'olivia.davis38@example.com', '0777776738', '2025-11-23 18:14:18'),
(39, 'EN00039', 'Mia', 'Davis', '2007-03-04', 'Other', 'mia.davis39@example.com', '0742739808', '2025-11-23 18:14:18'),
(40, 'EN00040', 'Mason', 'Brown', '2005-08-29', 'Male', 'mason.brown40@example.com', '0774949758', '2025-11-23 18:14:18'),
(41, 'EN00041', 'Fil', 'Taker', NULL, 'Other', 'filtaker@example.com', '9838202374', '2025-11-23 21:44:31');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` int(11) NOT NULL,
  `Username` varchar(100) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `FullName` varchar(200) DEFAULT NULL,
  `Role` varchar(100) DEFAULT NULL,
  `Email` varchar(150) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `Username`, `PasswordHash`, `FullName`, `Role`, `Email`, `CreatedAt`) VALUES
(1, 'admin', '$2y$12$uKYEvDCi2ndYFnZeMLk6n.2M48I3HNZ3hbny73HaucTLU5V61MmQW', 'Admin User', 'admin', NULL, '2025-11-24 17:48:59'),
(2, 'lecturer', '$2y$12$nIkcxHWv5dnNFaNhinDnTug0Qr7eQRFyU9ck2z1Xn7dPdnlAGCwU6', 'Lecturer User', 'lecturer', NULL, '2025-11-25 00:02:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appeal`
--
ALTER TABLE `appeal`
  ADD PRIMARY KEY (`AppealID`),
  ADD KEY `IncidentID` (`IncidentID`);

--
-- Indexes for table `attachment`
--
ALTER TABLE `attachment`
  ADD PRIMARY KEY (`AttachmentID`),
  ADD KEY `IncidentID` (`IncidentID`),
  ADD KEY `UploadedBy` (`UploadedBy`);

--
-- Indexes for table `disciplinaryaction`
--
ALTER TABLE `disciplinaryaction`
  ADD PRIMARY KEY (`ActionID`),
  ADD KEY `IncidentID` (`IncidentID`),
  ADD KEY `DecisionMakerID` (`DecisionMakerID`);

--
-- Indexes for table `hearing`
--
ALTER TABLE `hearing`
  ADD PRIMARY KEY (`HearingID`),
  ADD KEY `IncidentID` (`IncidentID`);

--
-- Indexes for table `incidentreport`
--
ALTER TABLE `incidentreport`
  ADD PRIMARY KEY (`IncidentID`),
  ADD KEY `ReporterStaffID` (`ReporterStaffID`),
  ADD KEY `StudentID` (`StudentID`);

--
-- Indexes for table `offensetype`
--
ALTER TABLE `offensetype`
  ADD PRIMARY KEY (`OffenseTypeID`);

--
-- Indexes for table `reportoffense`
--
ALTER TABLE `reportoffense`
  ADD PRIMARY KEY (`ReportOffenseID`),
  ADD KEY `IncidentID` (`IncidentID`),
  ADD KEY `OffenseTypeID` (`OffenseTypeID`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`StaffID`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`StudentID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Username` (`Username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appeal`
--
ALTER TABLE `appeal`
  ADD CONSTRAINT `appeal_ibfk_1` FOREIGN KEY (`IncidentID`) REFERENCES `incidentreport` (`IncidentID`);

--
-- Constraints for table `attachment`
--
ALTER TABLE `attachment`
  ADD CONSTRAINT `attachment_ibfk_1` FOREIGN KEY (`IncidentID`) REFERENCES `incidentreport` (`IncidentID`),
  ADD CONSTRAINT `attachment_ibfk_2` FOREIGN KEY (`UploadedBy`) REFERENCES `staff` (`StaffID`);

--
-- Constraints for table `disciplinaryaction`
--
ALTER TABLE `disciplinaryaction`
  ADD CONSTRAINT `disciplinaryaction_ibfk_1` FOREIGN KEY (`IncidentID`) REFERENCES `incidentreport` (`IncidentID`),
  ADD CONSTRAINT `disciplinaryaction_ibfk_2` FOREIGN KEY (`DecisionMakerID`) REFERENCES `staff` (`StaffID`);

--
-- Constraints for table `hearing`
--
ALTER TABLE `hearing`
  ADD CONSTRAINT `hearing_ibfk_1` FOREIGN KEY (`IncidentID`) REFERENCES `incidentreport` (`IncidentID`);

--
-- Constraints for table `incidentreport`
--
ALTER TABLE `incidentreport`
  ADD CONSTRAINT `incidentreport_ibfk_1` FOREIGN KEY (`ReporterStaffID`) REFERENCES `staff` (`StaffID`),
  ADD CONSTRAINT `incidentreport_ibfk_2` FOREIGN KEY (`StudentID`) REFERENCES `student` (`StudentID`);

--
-- Constraints for table `reportoffense`
--
ALTER TABLE `reportoffense`
  ADD CONSTRAINT `reportoffense_ibfk_1` FOREIGN KEY (`IncidentID`) REFERENCES `incidentreport` (`IncidentID`),
  ADD CONSTRAINT `reportoffense_ibfk_2` FOREIGN KEY (`OffenseTypeID`) REFERENCES `offensetype` (`OffenseTypeID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
