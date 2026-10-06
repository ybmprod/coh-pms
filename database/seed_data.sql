USE `coh_pms`;

-- Run this seed file once after importing coh_pms.sql. Re-running it creates duplicate test records.

INSERT INTO `users` (`full_name`, `email`, `phone_number`, `password_hash`, `role`, `account_status`) VALUES
('System Administrator', 'admin@coh.co.zw', '+263771000001', '$2y$10$RfV2WLhrnVz6tvl/06n2beRrGdfjJeuVWvNuCyIzUQGywE4MpKQQu', 'Administrator', 'Active'),
('Talent Moyo', 'booking.officer@coh.co.zw', '+263771000002', '$2y$10$xjhAi/6JLikreeAF38Yw9u3evCqTMRWsgspX7GqhL4qWqyr.BL0ta', 'Booking Officer', 'Active'),
('Netsai Chikomba', 'revenue.officer@coh.co.zw', '+263771000003', '$2y$10$gshR.Sg0ubCCvQOuFlaAJevOyMe.GTxjAK6ARgNefGhnj9dxUl5rm', 'Revenue Officer', 'Active'),
('Council Secretary', 'management@coh.co.zw', '+263771000004', '$2y$10$W3.hCdkbzaj.j.Ny8zFqQOEgTG.YMH3or.bFd.yRXD6UnQs.fL0gq', 'Council Management', 'Active'),
('Anesu Ncube', 'customer1@coh.co.zw', '+263771000005', '$2y$10$T.uZRKenLJsLHBYnTTtc1eWSOb1SqUV7XK4J2Vcecrih5hXSCi2nK', 'Customer', 'Active'),
('Kudzanai Mapuranga', 'customer2@coh.co.zw', '+263771000006', '$2y$10$T.uZRKenLJsLHBYnTTtc1eWSOb1SqUV7XK4J2Vcecrih5hXSCi2nK', 'Customer', 'Active'),
('Tinotenda Dube', 'customer3@coh.co.zw', '+263771000007', '$2y$10$T.uZRKenLJsLHBYnTTtc1eWSOb1SqUV7XK4J2Vcecrih5hXSCi2nK', 'Customer', 'Active');

INSERT INTO `venues` (`venue_name`, `venue_type`, `location`, `capacity`, `facilities`, `description`, `standard_price`, `venue_status`, `image_path`) VALUES
('Mbare Community Hall', 'Community Hall', 'Mbare, Harare', 180, 'Stage lighting, seating, PA system', 'Central community hall used for weddings, meetings, and local events.', 100.00, 'Available', NULL),
('Kuwadzana Community Centre', 'Community Centre', 'Kuwadzana, Harare', 140, 'Projector, meeting room, kitchen', 'Popular centre for school meetings and cultural gatherings.', 120.00, 'Available', NULL),
('Rufaro Stadium', 'Stadium', 'Mbare, Harare', 2000, 'Floodlights, changing rooms, parking', 'Large stadium venue for sport events and concerts.', 800.00, 'Available', NULL),
('Sakubva Open Space', 'Open Space', 'Sakubva, Harare', 500, 'Open field, access road, lighting', 'Open-air space for fairs and public gatherings.', 220.00, 'Available', NULL),
('Mufakose Community Hall', 'Community Hall', 'Mufakose, Harare', 220, 'Sound system, chairs, stage', 'Multi-purpose venue for community functions.', 150.00, 'Available', NULL),
('Highfield Sports Grounds', 'Other', 'Highfield, Harare', 300, 'Field markings, wash rooms, seating', 'Versatile sports and public event venue.', 180.00, 'Available', NULL);

INSERT INTO `pricing_rules` (`rule_name`, `venue_type`, `rule_type`, `adjustment_type`, `adjustment_value`, `days_of_week`, `min_hours`, `start_date`, `end_date`, `priority`, `rule_status`) VALUES
('Weekday Discount', 'All', 'Weekday Discount', 'Percentage', 10.00, 'Mon,Tue,Wed,Thu', NULL, '2026-01-01', '2027-12-31', 10, 'Active'),
('Weekend Surcharge', 'All', 'Weekend Surcharge', 'Percentage', 20.00, 'Fri,Sat,Sun', NULL, '2026-01-01', '2027-12-31', 10, 'Active'),
('Off-Peak Month Discount', 'All', 'Off-Peak Discount', 'Percentage', 15.00, NULL, NULL, '2026-01-01', '2027-12-31', 9, 'Active'),
('Long Booking Discount', 'All', 'Long Booking Discount', 'Percentage', 5.00, NULL, 8.0, '2026-01-01', '2027-12-31', 11, 'Active'),
('Peak Demand Surcharge', 'All', 'Peak Demand Surcharge', 'Percentage', 12.00, NULL, NULL, '2026-11-01', '2027-12-31', 7, 'Active');

INSERT INTO `bookings` (`booking_reference`, `customer_id`, `venue_id`, `pricing_rule_id`, `booking_date`, `start_time`, `end_time`, `event_type`, `number_of_attendees`, `standard_charge`, `adjustment_amount`, `total_charge`, `booking_status`, `reviewed_by`) VALUES
('COH-20261005-0001', 5, 1, 1, '2026-10-05', '09:00:00', '15:00:00', 'Community Meeting', 80, 100.00, -10.00, 90.00, 'Pending', 2),
('COH-20261011-0001', 6, 2, 2, '2026-10-11', '10:00:00', '14:00:00', 'Wedding', 120, 120.00, 24.00, 144.00, 'Approved', 2),
('COH-20261008-0001', 7, 3, 4, '2026-10-08', '08:00:00', '18:00:00', 'Sports Event', 160, 800.00, -40.00, 760.00, 'Rejected', 2),
('COH-20261004-0001', 5, 4, 2, '2026-10-04', '09:00:00', '13:00:00', 'Public Fair', 300, 220.00, 44.00, 264.00, 'Confirmed', 2),
('COH-20261001-0001', 6, 5, 1, '2026-10-01', '09:00:00', '12:00:00', 'Church Gathering', 110, 150.00, -15.00, 135.00, 'Completed', 2),
('COH-20261015-0001', 7, 6, 1, '2026-10-15', '15:00:00', '17:00:00', 'School Event', 90, 180.00, -18.00, 162.00, 'Cancelled', 2),
('COH-20261012-0001', 5, 2, 1, '2026-10-12', '09:00:00', '13:00:00', 'Youth Workshop', 65, 120.00, -12.00, 108.00, 'Pending', 2),
('COH-20261017-0001', 6, 3, 2, '2026-10-17', '10:00:00', '14:00:00', 'Music Concert', 1200, 800.00, 160.00, 960.00, 'Approved', 2),
('COH-20261006-0001', 7, 1, 1, '2026-10-06', '10:00:00', '14:00:00', 'Drama Rehearsal', 45, 100.00, -10.00, 90.00, 'Confirmed', 2),
('COH-20261002-0001', 5, 5, 2, '2026-10-02', '14:00:00', '18:00:00', 'Educational Seminar', 95, 150.00, 30.00, 180.00, 'Completed', 2),
('COH-20261014-0001', 6, 6, 1, '2026-10-14', '09:00:00', '12:00:00', 'Athletics Training', 150, 180.00, -18.00, 162.00, 'Approved', 2),
('COH-20261020-0001', 7, 4, 1, '2026-10-20', '08:00:00', '14:00:00', 'Flea Market', 200, 220.00, -22.00, 198.00, 'Cancelled', 2);

INSERT INTO `payments` (`booking_id`, `amount_paid`, `payment_method`, `transaction_reference`, `payment_date`, `payment_status`, `verified_by`, `verification_date`, `receipt_number`) VALUES
(2, 144.00, 'Bank Transfer', 'BT-10001', '2026-10-04 11:30:00', 'Pending Verification', NULL, NULL, NULL),
(4, 264.00, 'Cash', 'CASH-10002', '2026-10-03 18:00:00', 'Verified', 3, '2026-10-04 09:00:00', 'RCT-20261004-0001'),
(5, 135.00, 'Card', 'CARD-10004', '2026-09-30 16:45:00', 'Verified', 3, '2026-10-01 10:30:00', 'RCT-20261001-0001'),
(8, 960.00, 'Bank Transfer', 'BT-10005', '2026-10-04 14:20:00', 'Pending Verification', NULL, NULL, NULL),
(9, 90.00, 'Cash', 'CASH-10006', '2026-10-03 11:00:00', 'Verified', 3, '2026-10-03 14:00:00', 'RCT-20261003-0001'),
(10, 180.00, 'Mobile Money', 'MM-10007', '2026-10-01 15:30:00', 'Verified', 3, '2026-10-02 09:00:00', 'RCT-20261002-0001'),
(11, 162.00, 'Card', 'CARD-10008', '2026-10-03 09:00:00', 'Rejected', 3, '2026-10-03 10:00:00', NULL);
