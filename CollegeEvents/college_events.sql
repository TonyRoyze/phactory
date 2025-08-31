--
-- Database: `college_events`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'USER',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `location` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `rsvps`
--

CREATE TABLE `rsvps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `comment` text NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `rsvps`
--
ALTER TABLE `rsvps`
  ADD CONSTRAINT `rsvps_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`),
  ADD CONSTRAINT `rsvps_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`),
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`) VALUES
(1, 'admin', 'admin123', 'admin@college.edu', 'ADMIN'),
(2, 'john_doe', 'john123', 'john.doe@student.college.edu', 'USER'),
(3, 'jane_smith', 'jane123', 'jane.smith@student.college.edu', 'USER'),
(4, 'prof_wilson', 'wil123', 'wilson@college.edu', 'ADMIN'),
(5, 'sarah_jones', 'sarah123', 'sarah.jones@student.college.edu', 'USER');

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`title`, `description`, `location`, `date`, `user_id`) VALUES
('Welcome Week Orientation', 'Join us for an exciting orientation week to welcome new students to campus. Activities include campus tours, meet and greets, and information sessions.', 'Main Auditorium', '2024-01-15 09:00:00', 1),
('Computer Science Career Fair', 'Annual career fair featuring top tech companies looking to hire CS graduates and interns. Bring your resume and dress professionally.', 'Student Center Hall A', '2024-02-20 10:00:00', 4),
('Spring Concert Series', 'Live music performances by local bands and student musicians. Food trucks will be available on site.', 'Campus Quad', '2024-03-10 18:00:00', 2),
('Study Abroad Information Session', 'Learn about study abroad opportunities for the upcoming semester. Representatives from partner universities will be present.', 'Library Conference Room', '2024-02-05 14:00:00', 4),
('Intramural Basketball Tournament', 'Annual basketball tournament open to all students. Form teams of 5 players and compete for the championship trophy.', 'Recreation Center Gym', '2024-03-25 16:00:00', 3);

--
-- Dumping data for table `rsvps`
--

INSERT INTO `rsvps` (`id`, `event_id`, `user_id`) VALUES
(1, 1, 2);