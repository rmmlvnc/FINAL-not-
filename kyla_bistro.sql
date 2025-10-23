-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 23, 2025 at 03:37 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kyla_bistro`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `first_name`, `middle_name`, `last_name`, `contact_number`, `username`, `password`, `email`) VALUES
(1, 'Rommel Vince', 'Cortes', 'Pueblos', '09269294759', 'melro', '123123', 'rmmlvnc@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`category_id`, `category_name`) VALUES
(1, 'Appetizer'),
(2, 'Beef'),
(3, 'Chicken'),
(5, 'Fresh-Fruit-Shake'),
(6, 'Pasta'),
(7, 'Pizza'),
(8, 'Pork'),
(9, 'Sandwich'),
(10, 'Seafood'),
(11, 'Softdrinks');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `customer_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`customer_id`, `first_name`, `middle_name`, `last_name`, `username`, `password`, `email`, `phone_number`, `address`) VALUES
(2, 'Rommel Vince', 'Cortes', 'Pueblos', 'rommel', '123123', 'rmmlvnc@gmail.com', '09269294759', 'San Miguel, Iligan City'),
(3, 'Michelle', 'Gutierrez', 'Dungog', 'mgd', '0147', 'mgd@gmail.com', '09269294759', 'Villaverde, Iligan City'),
(5, 'Faith', 'Cortes', 'Pueblos', 'faith', 'faith123', 'faith@gmail.com', '09278354672', 'San Miguel, Iligan City');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `order_time` time NOT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `customer_id`, `order_date`, `order_time`, `total_amount`) VALUES
(3, 3, '2025-10-13', '04:07:14', 456.00),
(4, 3, '2025-10-16', '16:47:50', 318.00),
(5, 2, '2025-10-17', '00:00:00', 358.00),
(6, 3, '2025-10-17', '00:00:00', 606.00),
(7, 5, '2025-10-17', '00:00:00', 378.00),
(8, 5, '2025-10-17', '14:02:31', 378.00),
(9, 5, '2025-10-17', '15:32:03', 368.00),
(10, 2, '2025-10-19', '07:51:13', 514.00),
(11, 2, '2025-10-19', '07:54:57', 514.00),
(12, 3, '2025-10-19', '17:52:13', 598.00),
(13, 3, '2025-10-19', '13:04:56', 238.00),
(14, 3, '2025-10-19', '13:06:36', 798.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_item`
--

CREATE TABLE `order_item` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_item`
--

INSERT INTO `order_item` (`order_item_id`, `order_id`, `product_id`, `quantity`, `total_price`) VALUES
(1, 3, 5, 1, 318.00),
(2, 3, 11, 1, 138.00),
(5, 4, 5, 1, 318.00),
(8, 6, 7, 1, 238.00),
(9, 6, 2, 1, 368.00),
(11, 8, 1, 1, 378.00),
(12, 9, 2, 1, 368.00),
(13, 11, 11, 2, 276.00),
(14, 11, 7, 1, 238.00),
(15, 12, 6, 1, 598.00),
(16, 13, 7, 1, 238.00),
(17, 14, 18, 1, 798.00);

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_time` time NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` varchar(50) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`payment_id`, `order_id`, `payment_date`, `payment_time`, `payment_method`, `payment_status`, `total_amount`) VALUES
(1, 3, '2025-10-13', '04:07:14', 'Cash on Delivery', 'Pending', 456.00),
(2, 4, '2025-10-16', '16:47:50', 'Cash on Delivery', 'Pending', 268.00),
(3, 8, '2025-10-17', '14:02:31', 'Cash on Delivery', 'Paid', 378.00),
(4, 9, '2025-10-17', '15:32:03', 'Cash on Delivery', 'Pending', 368.00),
(5, 12, '2025-10-19', '17:52:13', 'PayPal', 'Paid', 598.00),
(6, 13, '2025-10-19', '13:04:56', 'PayPal', 'Paid', 2638.00),
(7, 14, '2025-10-19', '13:06:36', 'PayPal', 'Paid', 5798.00);

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`product_id`, `category_id`, `product_name`, `description`, `price`, `stock_quantity`, `image`) VALUES
(1, 1, 'Pork Sisig', 'Shimmered, grilled and sauted pork topped with egg.', 378.00, 15, 'pork-sisig.jpg'),
(2, 8, 'Baby Back Ribs', 'Pugon roasted baby back ribs in smokey barbeque sauce.', 368.00, 10, 'back-ribs.jpg'),
(3, 7, 'Kassy Kass', 'Heavy ground beef, pineapple, mushroom, black olive, and heavy cheese.', 378.00, 10, 'kassy-kass.jpg'),
(4, 10, 'Baked Prawns', 'Butterflied tiger prawns baked with special 3 cheese toppings broiled finish.', 468.00, 9, 'prawns.jpg'),
(5, 6, 'Carbonara', 'Rich and creamy mushroom sauce serve with fettucine, grilled chicken and parmesan cheese.', 318.00, 15, 'carbonara.jpg'),
(6, 10, 'Baked Salmon', 'Baked pink salmon fillet with special 3 cheese toppings thin broiled to perfection.', 598.00, 10, 'salmon.jpg'),
(7, 6, 'Lasagna', 'Oven baked layers of meat, rich bechamel and fresh tomato sauce.', 238.00, 20, 'lasagna.jpg'),
(8, 3, 'Chicken Fingers', 'Dreaded chicken finger with fries and gravy.', 268.00, 15, 'chiken-fingers.jpg'),
(9, 3, 'Country Club Chicken', 'Crispy fried breaded chicken with traditional gravy sauce.', 268.00, 20, 'country-club-chicken.jpg'),
(10, 3, 'Chicken Inasal', 'Chicken breast stuffed with creamy spinach with mushroom demi glaze', 278.00, 15, 'chicken-inasal.jpg'),
(11, 1, 'French Fries', 'Classic.', 138.00, 30, 'fries.jpg'),
(12, 1, 'Kinilaw', 'Fresh raw fish fillet steeped in Kylas special vinegar mix.', 378.00, 15, 'kinilaw.jpg'),
(13, 2, 'Beef Salpicao', 'A hearty stir-fry of meat, potatoes, garlic, and scallions in a rich, dark sauce.', 340.00, 15, 'salpicao.jpg'),
(14, 7, 'Kyla\'s Supreme', 'Everything on it without pork.', 438.00, 15, 'supreme.jpg'),
(15, 7, 'Carls Hawaiian', 'Ham, pineapple and cheese.', 358.00, 15, 'hawaiian.png'),
(16, 7, 'Robins Veggie Corner', 'Broccoli, cauli flower, lettuce, bell pepper, tomato, white onion, pineapple and cheese.', 378.00, 10, 'veggie-pizza.png'),
(17, 7, 'Rachelle\'s Mango', 'Creamy white sauce with fresh mango fruit, cheese and nata.', 378.00, 10, 'mango-pizza.jpg'),
(18, 8, 'Crispy Pata', 'Herb rubbed crispy pata with soy vinegar dip.', 798.00, 10, 'crispy-pata.jpg'),
(19, 8, 'Lechon Kawali', '(fried Pork Belly) Simmered till tender then deep fried.', 328.00, 20, 'lechon-kawali.jpg'),
(20, 8, 'Sizzling Pork Belly', 'Grilled pork belly topped with egg and brown sauce.', 328.00, 20, 'pork-belly.jpg'),
(21, 1, 'Calamares', 'Fried squid flash fried braded squid with spiced up native vinegar.', 348.00, 20, 'calamares.jpg'),
(22, 10, 'Nilasing na Hipon', 'A crispy fried shrimp topped with sliced green onions, crunchy, and full of flavor.', 358.00, 15, 'hipon.jpg'),
(23, 9, 'Clubhouse', 'Overload sandwich filled with fresh lettuce, tomato, cucumber, cheese, egg and chicken.', 318.00, 30, 'clubhouse.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `reservation`
--

CREATE TABLE `reservation` (
  `reservation_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `table_id` int(11) NOT NULL,
  `reservation_date` date NOT NULL,
  `reservation_time` time NOT NULL,
  `event_type` varchar(100) DEFAULT 'Regular Dining',
  `status` varchar(50) DEFAULT 'Pending',
  `total_hours` int(11) DEFAULT 2,
  `total_price` decimal(10,2) DEFAULT 0.00,
  `order_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservation`
--

INSERT INTO `reservation` (`reservation_id`, `customer_id`, `table_id`, `reservation_date`, `reservation_time`, `event_type`, `status`, `total_hours`, `total_price`, `order_id`) VALUES
(1, 2, 2, '2025-10-24', '00:01:00', 'Regular Dining', 'Pending', 2, 0.00, NULL),
(2, 2, 19, '2025-10-20', '18:31:00', 'Birthday Party', 'Pending', 3, 2400.00, NULL),
(3, 2, 23, '2025-10-20', '15:50:00', 'Meeting', 'Pending', 2, 2114.00, NULL),
(4, 2, 24, '2025-10-20', '15:54:00', 'Meeting', 'Pending', 2, 2914.00, 11),
(7, 3, 21, '2025-10-29', '22:06:00', 'Birthday Party', 'Pending', 2, 5798.00, 14);

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `first_name`, `middle_name`, `last_name`, `username`, `email`, `contact_number`, `address`, `password`) VALUES
(1, 'Rommel Vince', 'Cortes', 'Pueblos', 'staff1', 'rmmlvnc@gmail.com', '09269294759', 'San Miguel, Iligan City', '$2y$10$Q/JvWE9p8vZ7GzAfvvlsT.iY.new7QkM7PE113OpKWlS0VS2x0052'),
(2, 'Michelle', 'Gutierrez', 'Pueblos', 'staff2', 'mgd@gmail.com', '09269294759', 'Villaverde, Iligan City', '0147');

-- --------------------------------------------------------

--
-- Table structure for table `tables`
--

CREATE TABLE `tables` (
  `table_id` int(11) NOT NULL,
  `table_number` varchar(10) NOT NULL,
  `capacity` int(11) NOT NULL,
  `table_type` varchar(50) DEFAULT 'Regular',
  `description` text DEFAULT NULL,
  `price_per_hour` decimal(10,2) DEFAULT 0.00,
  `status` varchar(20) DEFAULT 'Available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tables`
--

INSERT INTO `tables` (`table_id`, `table_number`, `capacity`, `table_type`, `description`, `price_per_hour`, `status`) VALUES
(1, '1', 4, 'Regular', NULL, 0.00, 'Available'),
(2, '2', 2, 'Regular', NULL, 0.00, 'Available'),
(3, '3', 6, 'Regular', NULL, 0.00, 'Available'),
(4, 'O1', 4, 'Regular (Outside)', 'Outdoor patio table with garden view', 0.00, 'Available'),
(5, 'O2', 4, 'Regular (Outside)', 'Outdoor patio table near fountain', 0.00, 'Available'),
(6, 'O3', 4, 'Regular (Outside)', 'Outdoor terrace table with sunset view', 0.00, 'Available'),
(7, 'O4', 4, 'Regular (Outside)', 'Outdoor garden table under pergola', 0.00, 'Available'),
(8, 'O5', 4, 'Regular (Outside)', 'Outdoor balcony table with city view', 0.00, 'Available'),
(9, 'I1', 6, 'Regular (Inside)', 'Indoor dining table near window', 0.00, 'Available'),
(10, 'I2', 6, 'Regular (Inside)', 'Indoor dining table in main hall', 0.00, 'Available'),
(11, 'I3', 6, 'Regular (Inside)', 'Indoor dining table by the fireplace', 0.00, 'Available'),
(12, 'I4', 6, 'Regular (Inside)', 'Indoor dining table in cozy corner', 0.00, 'Available'),
(13, 'I5', 6, 'Regular (Inside)', 'Indoor dining table center location', 0.00, 'Available'),
(14, 'I6', 6, 'Regular (Inside)', 'Indoor dining table near bar area', 0.00, 'Available'),
(15, 'I7', 6, 'Regular (Inside)', 'Indoor dining table with booth seating', 0.00, 'Available'),
(16, 'I8', 6, 'Regular (Inside)', 'Indoor dining table in quiet section', 0.00, 'Available'),
(17, 'I9', 6, 'Regular (Inside)', 'Indoor dining table with TV view', 0.00, 'Available'),
(18, 'I10', 6, 'Regular (Inside)', 'Indoor dining table premium location', 0.00, 'Available'),
(19, 'B1', 15, 'Birthday Party Room', 'Small party room with decorations, sound system, and cake table', 800.00, 'Available'),
(20, 'B2', 25, 'Birthday Party Room', 'Medium party room with stage, karaoke, and party lights', 1500.00, 'Available'),
(21, 'B3', 40, 'Birthday Party Room', 'Large celebration hall with full party setup and entertainment area', 2500.00, 'Available'),
(22, 'M1', 8, 'Meeting Room', 'Small conference room with projector, whiteboard, and Wi-Fi', 500.00, 'Available'),
(23, 'M2', 12, 'Meeting Room', 'Medium boardroom with conference table, TV screen, and video conferencing', 800.00, 'Available'),
(24, 'M3', 20, 'Meeting Room', 'Large meeting hall with presentation equipment and breakout area', 1200.00, 'Available'),
(25, 'M4', 30, 'Meeting Room', 'Executive conference hall with premium A/V setup and catering area', 2000.00, 'Available');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `password` (`password`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `order_item`
--
ALTER TABLE `order_item`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `reservation`
--
ALTER TABLE `reservation`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `table_id` (`table_id`),
  ADD KEY `fk_reservation_order` (`order_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `tables`
--
ALTER TABLE `tables`
  ADD PRIMARY KEY (`table_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `order_item`
--
ALTER TABLE `order_item`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `reservation`
--
ALTER TABLE `reservation`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tables`
--
ALTER TABLE `tables`
  MODIFY `table_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`);

--
-- Constraints for table `order_item`
--
ALTER TABLE `order_item`
  ADD CONSTRAINT `order_item_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `order_item_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);

--
-- Constraints for table `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `product_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`);

--
-- Constraints for table `reservation`
--
ALTER TABLE `reservation`
  ADD CONSTRAINT `fk_reservation_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reservation_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`),
  ADD CONSTRAINT `reservation_ibfk_2` FOREIGN KEY (`table_id`) REFERENCES `tables` (`table_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
