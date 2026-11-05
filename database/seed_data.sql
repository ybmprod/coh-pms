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
('Mai Musodzi Hall, Mbare', 'Community Hall', 'Mbare, Harare', 250, 'Stage lighting, seating, PA system, dressing rooms', 'Historic City of Harare municipal community hall in Mbare, established for cultural events, civic meetings, and social gatherings.', 100.00, 'Available', 'mai-musodzi-hall-mbare.png'),
('Holland Community Centre, Kuwadzana', 'Community Centre', 'Kuwadzana, Harare', 200, 'Meeting rooms, projector facility, kitchen area, perimeter security', 'Prominent City of Harare community centre in Kuwadzana offering flexible hall facilities for civic, educational, and community functions.', 120.00, 'Available', 'holland-community-centre-kuwadzana.png'),
('Rufaro Stadium', 'Stadium', 'Mbare, Harare', 35000, 'Full turf pitch, floodlighting, spectator grandstands, player dressing rooms, media box', 'Landmark municipal football and major events stadium in Mbare, owned and managed by the City of Harare.', 800.00, 'Available', 'rufaro-stadium.png'),
('Harare Gardens Open Space', 'Open Space', 'Harare Central, Harare', 2500, 'Landscaped grounds, promenades, public lighting, open-air lawns, access gates', 'Expansive municipal open garden and public park in downtown Harare, managed by City of Harare for open-air cultural and public events.', 220.00, 'Available', 'harare-gardens-open-space.png'),
('Mufakose Community Hall', 'Community Hall', 'Mufakose, Harare', 220, 'Indoor stage, bench seating, ventilation, restrooms', 'City of Harare community hall facility serving social, civic, and educational events in Mufakose.', 150.00, 'Available', 'mufakose-community-hall.png'),
('Highfield Sports Grounds', 'Other', 'Highfield, Harare', 5000, 'Multi-sport playing fields, pavilion shelter, open grass courts, parking', 'Municipal sports complex and community playing grounds in Highfield, Harare, managed by the City of Harare.', 180.00, 'Available', 'highfield-sports-grounds.png'),
('Gwanzura Stadium', 'Stadium', 'Highfield, Harare', 10000, 'Main pitch, spectator terracing, floodlight towers, changing rooms', 'Historic City of Harare sports and assembly arena in Highfield, hosting sporting competitions and public functions.', 650.00, 'Available', 'gwanzura-stadium.png'),
('Harare City Sports Centre', 'Stadium', 'Coventry Road, Harare', 4000, 'Indoor hardwood arena, tiered seating, acoustics, electronic scoreboard', 'Iconic indoor municipal arena in Harare Central managed by the City of Harare for indoor sports, exhibitions, and musical festivals.', 750.00, 'Available', 'harare-city-sports-centre.png'),
('Magamba Hall', 'Community Hall', 'Stand No 3477 2nd Avenue, Warren Park 1 Shops, Harare', 250, 'Indoor stage, bench seating, PA system, restrooms', 'City of Harare municipal hall in Warren Park 1 (Tel: 04-223122) available for public and private community functions.', 110.00, 'Available', NULL),
('Mt Pleasant Hall', 'Community Hall', 'Parkway Street, Bond Shopping Centre, Mt Pleasant, Harare', 300, 'Stage lighting, cushioned seating, kitchen area, secure parking, restrooms', 'Municipal community hall at Bond Shopping Centre in Mt Pleasant suited for weddings, seminars, and civic functions.', 160.00, 'Available', NULL),
('Rutendo Hall', 'Community Hall', 'Murara Road, Mufakose, Harare', 220, 'Indoor stage, hall seating, ventilation, restrooms', 'City of Harare community hall on Murara Road in Mufakose serving local social, cultural, and religious gatherings.', 100.00, 'Available', NULL),
('Tafara Hall', 'Community Hall', 'Muswewadepa Road, New Tafara, Harare', 220, 'Main hall floor, raised stage, bench seating, restrooms', 'Council community hall on Muswewadepa Road in New Tafara for public meetings, weddings, and community events.', 100.00, 'Available', NULL),
('Cyril Jennings Hall', 'Community Hall', 'Stand No 7256 Jabavu Drive, Old Highfield, Harare', 280, 'Historic main stage, hall seating, dressing rooms, restrooms', 'Historic City of Harare municipal hall on Jabavu Drive in Old Highfield hosting civic, cultural, and social gatherings.', 120.00, 'Available', NULL),
('Zimbabwe Hall', 'Community Hall', 'Stand No 7264 Main Street, Old Highfield, Harare', 350, 'Large auditorium stage, gallery seating, PA system, dressing rooms', 'Landmark municipal hall on Main Street in Old Highfield (Tel: 04-2932711) renowned for major community and cultural events.', 130.00, 'Available', NULL),
('Dzivarasekwa Hall', 'Community Hall', 'Stand No 3852 Nherera Street, Dzivarasekwa 3, Harare', 240, 'Indoor stage, seating, utility power, restrooms', 'City of Harare community hall in Dzivarasekwa 3 (Tel: 04-216416) available for public and private functions.', 100.00, 'Available', NULL),
('Glen View New Hall', 'Community Hall', 'Stand No 8053 Corner Patrenda & Glen View Way, Glen View, Harare', 260, 'Main hall, stage area, seating, perimeter fencing, restrooms', 'Council community hall at the corner of Patrenda and Glen View Way (Tel: 04-690000) serving Glen View residents.', 115.00, 'Available', NULL),
('Sophie Hall', 'Community Hall', 'Stand No 2207, Glen View 1, Harare', 200, 'Hall floor, stage platform, bench seating, restrooms', 'Municipal community hall in Glen View 1 suited for community meetings, church services, and family functions.', 95.00, 'Available', NULL),
('Waterfalls Hall', 'Community Hall', 'Lot 1 Parktown, Masotsha Ndlovu Way, Waterfalls, Harare', 280, 'Main stage, reception hall, kitchen prep area, parking, restrooms', 'City of Harare municipal hall in Parktown, Waterfalls (Tel: 04-2932714) for weddings, conferences, and public events.', 140.00, 'Available', NULL),
('Lusaka Hall', 'Community Hall', 'Stand No 7258 Mangwende Drive East, Lusaka, Highfield, Harare', 220, 'Indoor stage, seating, ventilation, restrooms', 'Council community hall along Mangwende Drive East in Lusaka, Highfield for community and social gatherings.', 100.00, 'Available', NULL),
('Old Highfield Hall', 'Community Hall', 'Stand No 7251 Mangwende Drive East, Old Highfield, Harare', 240, 'Raised stage, hall seating, restrooms, perimeter security', 'Municipal community hall on Mangwende Drive East in Old Highfield supporting public and private bookings.', 105.00, 'Available', NULL),
('Glen Norah Hall', 'Community Hall', 'Stand No 7563 Spaceman Shopping Centre, Glen Norah, Harare', 250, 'Main hall, stage, bench seating, restrooms', 'City of Harare community hall at Spaceman Shopping Centre in Glen Norah for civic and social events.', 105.00, 'Available', NULL),
('Chembira Hall', 'Community Hall', 'Stand No 7056, Glen Norah A, Harare', 220, 'Hall floor, stage, seating, restrooms', 'Municipal community hall in Glen Norah A available for public meetings, workshops, and private functions.', 100.00, 'Available', NULL),
('Stodart Hall', 'Community Hall', 'Stand No 397 No 1 Pazarangu / Peterstat Avenue, Mbare, Harare', 400, 'Large auditorium stage, tiered & floor seating, dressing rooms, restrooms', 'Iconic City of Harare municipal hall in Mbare (Tel: 04-2932715/696) hosting large civic assemblies, concerts, and community functions.', 150.00, 'Available', NULL),
('Mbare Gymnastics Hall', 'Community Hall', 'Corner Pazarangu / Peterstat Avenue, Mbare, Harare', 250, 'Indoor open floor, high ceiling, changing rooms, restrooms', 'Municipal indoor gymnastics and multi-purpose hall in Mbare (Tel: 04-2932715/696) for indoor sports, training, and events.', 110.00, 'Available', NULL),
('Hatfield Hall', 'Community Hall', 'Stand No 278 Block B, Corner Elgin Rd / Alexander Rd, Hatfield, Harare', 280, 'Main hall, stage lighting, kitchen area, parking, restrooms', 'City of Harare municipal hall in Hatfield (Tel: 04-2932715/696) ideal for weddings, seminars, and civic meetings.', 145.00, 'Available', NULL),
('Sunningdale Hall', 'Community Hall', 'Stand No 16632 Second Rd off Boshoff Drive, Sunningdale, Harare', 250, 'Indoor stage, hall seating, kitchen prep space, restrooms', 'Council community hall in Sunningdale (Tel: 04-576820) available for community gatherings, weddings, and workshops.', 120.00, 'Available', NULL),
('Mbare Flats Hall', 'Community Hall', 'Corner Adam Chigwida / Ardbennie Rd, Mbare, Harare', 200, 'Hall floor, stage platform, bench seating, restrooms', 'City of Harare community hall at Corner Adam Chigwida and Ardbennie Road in Mbare (Tel: 04-2932715 / 04-2932696).', 95.00, 'Available', NULL),
('Mabvuku Hall', 'Community Hall', 'Mabvuku Drive, Old Mabvuku, Harare', 250, 'Indoor stage, hall seating, ventilation, restrooms', 'Municipal community hall on Mabvuku Drive in Old Mabvuku (Tel: 04-491876) serving public and private functions.', 105.00, 'Available', NULL),
('Mbare Netball Complex Hall', 'Community Hall', 'Corner Pazarangu / Peterstat Avenue, Mbare, Harare', 350, 'Courtside hall space, changing rooms, spectator seating, restrooms', 'City of Harare Mbare Netball Complex Hall (Tel: 04-2932715/696) for sporting tournaments, youth programmes, and public gatherings.', 130.00, 'Available', NULL),
('Glen Norah Community Centre 2', 'Community Centre', 'Stand No 8544, Glen Norah B, Harare', 200, 'Multi-purpose meeting rooms, main hall, restrooms, fenced yard', 'City of Harare community centre in Glen Norah B (Tel: 04-2932712) for workshops, training sessions, and social functions.', 110.00, 'Available', NULL),
('Kaunda Plot Community Centre', 'Community Centre', 'Muonde Road, Mufakose, Harare', 180, 'Community hall room, activity yard, seating, restrooms', 'Council community centre on Muonde Road in Mufakose supporting local clubs, youth activities, and meetings.', 90.00, 'Available', NULL),
('Kambuzuma 2 Community Centre', 'Community Centre', 'Rukudzo Road, Kambuzuma / Mufakose, Harare', 200, 'Meeting hall, training rooms, seating, restrooms', 'City of Harare community centre on Rukudzo Road serving residents for educational, civic, and social events.', 100.00, 'Available', NULL),
('Matapi Community Centre', 'Community Centre', '20th Street, Harare Road South, Mbare, Harare', 200, 'Community hall, meeting rooms, utility area, restrooms', 'Municipal community centre on 20th Street off Harare Road South in Mbare (Tel: 04-2932715/696).', 95.00, 'Available', NULL),
('Chizhanje Community Centre', 'Community Centre', 'Chizhanje Road, Old Mabvuku, Harare', 180, 'Activity hall, meeting rooms, seating, restrooms', 'City of Harare community centre on Chizhanje Road in Old Mabvuku for neighbourhood gatherings and training.', 95.00, 'Available', NULL),
('Mabvuku Soup Kitchen Community Venue', 'Community Centre', 'Off Mabvuku Drive & Chaminuka Road, Mabvuku, Harare', 150, 'Dining & assembly hall, kitchen facility, water supply, restrooms', 'Municipal community facility off Mabvuku Drive and Chaminuka Road supporting outreach, catering, and community meetings.', 85.00, 'Available', NULL),
('Old Mabvuku Community Centre', 'Community Centre', 'Corner Chinhamhora Road & Derere Road, Old Mabvuku, Harare', 200, 'Main activity room, meeting spaces, seating, restrooms', 'Council community centre at the corner of Chinhamhora and Derere Roads in Old Mabvuku.', 95.00, 'Available', NULL),
('Area D Community Centre', 'Community Centre', 'Inside Mabvuku Full Day Care Centre, New Mabvuku, Harare', 160, 'Community room, covered veranda, seating, restrooms', 'City of Harare Area D Community Centre located inside the Mabvuku Full Day Care Centre complex in New Mabvuku.', 90.00, 'Available', NULL),
('Budiriro Youth Training Centre', 'Community Centre', 'Stand No 2702 Chidamba Crescent, Budiriro 2, Harare', 220, 'Training workshops, lecture hall, seating, perimeter security, restrooms', 'Municipal youth training and community centre on Chidamba Crescent in Budiriro 2 for skills programmes and seminars.', 110.00, 'Available', NULL),
('Budiriro Community Centre', 'Community Centre', 'Stand No 680, Budiriro 1, Harare', 220, 'Main community hall, meeting rooms, seating, restrooms', 'City of Harare community centre at Stand No 680 in Budiriro 1 (Tel: 04-690137) for public and private functions.', 110.00, 'Available', NULL),
('Chinembiri Training Centre', 'Community Centre', '8 Munaku Way, Corner Mavyani Street, Mbare, Harare', 200, 'Training classrooms, assembly room, utility area, restrooms', 'Municipal vocational and community training centre at 8 Munaku Way in Mbare (Tel: 04-664397 / 04-2932713).', 105.00, 'Available', NULL),
('Old Farm House Community Centre', 'Community Centre', 'Stand No 585, 18 Crescent, Glen View 1, Harare', 180, 'Multipurpose rooms, outdoor courtyard, seating, restrooms', 'City of Harare Old Farm House Community Centre at Stand No 585, 18 Crescent in Glen View 1.', 95.00, 'Available', NULL),
('Glen View 3 Community Centre', 'Community Centre', 'Corner 11 Street / Patrenda Way, Glen View 3, Harare', 200, 'Community hall, meeting rooms, seating, restrooms', 'Council community centre at the corner of 11 Street and Patrenda Way in Glen View 3 (Tel: 04-690290).', 105.00, 'Available', NULL),
('Tariro Community Centre', 'Community Centre', 'Area 8, Glen View, Harare', 200, 'Activity hall, meeting rooms, courtyard, restrooms', 'City of Harare Tariro Community Centre in Area 8, Glen View available for public meetings, church services, and workshops.', 100.00, 'Available', NULL),
('Area E Community Centre', 'Community Centre', 'Nhoro Street, Mufakose, Harare', 180, 'Community room, seating, water & power, restrooms', 'Municipal Area E Community Centre on Nhoro Street in Mufakose (Tel: 04-698030) for local events and meetings.', 95.00, 'Available', NULL),
('Area A Community Centre', 'Community Centre', 'Mukamba Street, Mufakose, Harare', 180, 'Community hall space, seating, restrooms', 'City of Harare Area A Community Centre on Mukamba Street in Mufakose.', 90.00, 'Available', NULL),
('Area H Community Centre', 'Community Centre', 'Nyati Street, Mufakose, Harare', 180, 'Community hall space, seating, restrooms', 'City of Harare Area H Community Centre on Nyati Street in Mufakose.', 90.00, 'Available', NULL),
('Area J Community Centre', 'Community Centre', 'Chiguyakuya Street, Mufakose, Harare', 180, 'Community hall space, seating, restrooms', 'City of Harare Area J Community Centre on Chiguyakuya Street in Mufakose.', 90.00, 'Available', NULL),
('Dzivarasekwa Stadium', 'Stadium', 'Stand No 1504 Tangai Street, Dzivarasekwa 1 Shopping Centre, Harare', 8000, 'Football pitch, spectator terraces, changing rooms, perimeter wall, gates', 'City of Harare municipal stadium at Dzivarasekwa 1 Shopping Centre (Tel: 04-216041) for football matches, tournaments, and large rallies.', 500.00, 'Available', NULL),
('Warren Park Stadium', 'Stadium', '3rd Avenue, Warren Park 1, Harare', 5000, 'Sports pitch, open spectator embankments, changing rooms, parking area', 'Municipal sports stadium on 3rd Avenue in Warren Park 1 available for sporting fixtures, athletics, and open-air public gatherings.', 450.00, 'Available', NULL);

INSERT INTO `event_types` (`event_name`, `charge_type`, `charge_value`, `status`, `description`) VALUES
('Wedding', 'Fixed Amount', 50.00, 'Active', 'A marriage ceremony, usually followed by a reception with guests, food and decoration.'),
('Community Meeting', 'Fixed Amount', 0.00, 'Active', 'A meeting where residents, ward members or civic groups discuss local matters.'),
('Sports Event', 'Fixed Amount', 100.00, 'Active', 'An organised match, tournament or athletics event with players and spectators.'),
('Public Fair', 'Fixed Amount', 75.00, 'Active', 'An open event with stalls, displays or exhibitions that attracts the public.'),
('Church Gathering', 'Fixed Amount', 0.00, 'Active', 'A church service, crusade or fellowship where people gather to worship.'),
('School Event', 'Fixed Amount', 0.00, 'Active', 'A school assembly, prize-giving or educational function for learners and teachers.'),
('Youth Workshop', 'Fixed Amount', 25.00, 'Active', 'A training or workshop session for young people, often using extra equipment.'),
('Music Concert', 'Fixed Amount', 100.00, 'Active', 'A live music performance with an audience, sound equipment and security.'),
('Drama Rehearsal', 'Fixed Amount', 0.00, 'Active', 'A practice session for a play or stage performance.'),
('Educational Seminar', 'Fixed Amount', 30.00, 'Active', 'A talk, lecture or seminar where people learn about a subject.'),
('Athletics Training', 'Fixed Amount', 20.00, 'Active', 'A training session for running, field events or other athletics activities.'),
('Flea Market', 'Fixed Amount', 50.00, 'Active', 'An informal market where traders sell goods from stalls.'),
('Traditional Wedding', 'Fixed Amount', 50.00, 'Active', 'A wedding that follows traditional marriage customs and family rituals.'),
('Wedding Reception', 'Fixed Amount', 50.00, 'Active', 'A celebration after a wedding, with guests, food, music and speeches.'),
('Marriage Ceremony', 'Fixed Amount', 50.00, 'Active', 'A civil or religious ceremony where two people are joined in marriage.'),
('Football Match', 'Fixed Amount', 100.00, 'Active', 'A football game between two teams, with supporters watching.'),
('Netball Match', 'Fixed Amount', 100.00, 'Active', 'A netball game between two teams, with supporters watching.'),
('Sports Tournament', 'Fixed Amount', 100.00, 'Active', 'A sports competition where several teams play a series of matches.'),
('Athletics Meet', 'Fixed Amount', 100.00, 'Active', 'A track and field event with races and field competitions.'),
('Trade Fair', 'Fixed Amount', 75.00, 'Active', 'A commercial event where businesses show and sell their products.'),
('Exhibition', 'Fixed Amount', 75.00, 'Active', 'A public display of products, art or information.'),
('Agricultural Show', 'Fixed Amount', 75.00, 'Active', 'An event showing farm produce, livestock and agricultural equipment.'),
('Church Service', 'Fixed Amount', 0.00, 'Active', 'A regular church meeting for worship and teaching.'),
('Crusade', 'Fixed Amount', 0.00, 'Active', 'A religious gathering for preaching, worship and prayer.'),
('Prayer Meeting', 'Fixed Amount', 0.00, 'Active', 'A small gathering for prayer and fellowship.'),
('School Assembly', 'Fixed Amount', 0.00, 'Active', 'A school gathering for announcements, worship or presentations.'),
('Graduation Ceremony', 'Fixed Amount', 0.00, 'Active', 'A ceremony to celebrate learners completing their studies.'),
('Training Workshop', 'Fixed Amount', 25.00, 'Active', 'A practical training session where people learn new skills.'),
('Live Concert', 'Fixed Amount', 100.00, 'Active', 'A live music performance with an audience and sound equipment.'),
('Music Festival', 'Fixed Amount', 100.00, 'Active', 'A larger music event with several performers and a public audience.'),
('Play Rehearsal', 'Fixed Amount', 0.00, 'Active', 'A practice session for actors preparing a stage play.'),
('Conference', 'Fixed Amount', 30.00, 'Active', 'A formal meeting where speakers and delegates discuss a topic.'),
('Sports Training', 'Fixed Amount', 20.00, 'Active', 'A team practice session for a sport.'),
('Market Day', 'Fixed Amount', 50.00, 'Active', 'A day when local traders sell goods to the community.'),
('Craft Market', 'Fixed Amount', 50.00, 'Active', 'A market where people sell handmade crafts and art.'),
('Ward Meeting', 'Fixed Amount', 0.00, 'Active', 'A meeting for residents of a ward to discuss local issues.'),
('Public Meeting', 'Fixed Amount', 0.00, 'Active', 'An open meeting for the public to discuss community matters.'),
('Funeral Service', 'Fixed Amount', 0.00, 'Active', 'A gathering to honour and mourn someone who has died.'),
('Memorial Service', 'Fixed Amount', 0.00, 'Active', 'A gathering to remember someone who has died.'),
('Corporate Function', 'Fixed Amount', 50.00, 'Active', 'A company event such as a staff gathering, launch or presentation.'),
('Cultural Festival', 'Fixed Amount', 75.00, 'Active', 'A celebration of culture through music, dance, food or art.'),
('Political Rally', 'Fixed Amount', 100.00, 'Active', 'A public gathering for a political party to share its message.');

INSERT INTO `event_type_venue_types` (`event_type_id`, `venue_type`)
SELECT `event_type_id`, 'Community Hall'
  FROM `event_types`
 WHERE `event_name` IN (
    'Wedding', 'Traditional Wedding', 'Wedding Reception', 'Marriage Ceremony',
    'Community Meeting', 'Ward Meeting', 'Public Meeting',
    'Church Gathering', 'Church Service', 'Prayer Meeting',
    'School Event', 'School Assembly', 'Graduation Ceremony',
    'Youth Workshop', 'Training Workshop', 'Educational Seminar', 'Conference',
    'Drama Rehearsal', 'Play Rehearsal',
    'Flea Market', 'Market Day', 'Craft Market',
    'Public Fair', 'Trade Fair', 'Exhibition',
    'Funeral Service', 'Memorial Service', 'Corporate Function',
    'Cultural Festival', 'Music Concert', 'Live Concert'
 );

INSERT INTO `event_type_venue_types` (`event_type_id`, `venue_type`)
SELECT `event_type_id`, 'Community Centre'
  FROM `event_types`
 WHERE `event_name` IN (
    'Wedding', 'Traditional Wedding', 'Wedding Reception', 'Marriage Ceremony',
    'Community Meeting', 'Ward Meeting', 'Public Meeting',
    'Church Gathering', 'Church Service', 'Prayer Meeting',
    'School Event', 'School Assembly', 'Graduation Ceremony',
    'Youth Workshop', 'Training Workshop', 'Educational Seminar', 'Conference',
    'Drama Rehearsal', 'Play Rehearsal',
    'Flea Market', 'Market Day', 'Craft Market',
    'Public Fair', 'Trade Fair', 'Exhibition',
    'Funeral Service', 'Memorial Service', 'Corporate Function',
    'Cultural Festival', 'Music Concert', 'Live Concert'
 );

INSERT INTO `event_type_venue_types` (`event_type_id`, `venue_type`)
SELECT `event_type_id`, 'Stadium'
  FROM `event_types`
 WHERE `event_name` IN (
    'Sports Event', 'Football Match', 'Netball Match', 'Sports Tournament',
    'Athletics Meet', 'Athletics Training', 'Sports Training',
    'Music Concert', 'Live Concert', 'Music Festival',
    'Public Fair', 'Trade Fair', 'Exhibition', 'Agricultural Show',
    'Community Meeting', 'Ward Meeting', 'Public Meeting',
    'Church Gathering', 'Crusade',
    'School Event', 'School Assembly', 'Graduation Ceremony',
    'Political Rally', 'Cultural Festival', 'Corporate Function'
 );

INSERT INTO `event_type_venue_types` (`event_type_id`, `venue_type`)
SELECT `event_type_id`, 'Open Space'
  FROM `event_types`
 WHERE `event_name` IN (
    'Wedding', 'Traditional Wedding', 'Wedding Reception', 'Marriage Ceremony',
    'Community Meeting', 'Ward Meeting', 'Public Meeting',
    'Sports Event', 'Football Match', 'Netball Match', 'Sports Tournament',
    'Athletics Meet', 'Athletics Training', 'Sports Training',
    'Public Fair', 'Trade Fair', 'Exhibition', 'Agricultural Show',
    'Church Gathering', 'Church Service', 'Crusade', 'Prayer Meeting',
    'School Event', 'School Assembly', 'Graduation Ceremony',
    'Music Concert', 'Live Concert', 'Music Festival',
    'Flea Market', 'Market Day', 'Craft Market',
    'Funeral Service', 'Memorial Service',
    'Corporate Function', 'Cultural Festival', 'Political Rally'
 );

INSERT INTO `event_type_venue_types` (`event_type_id`, `venue_type`)
SELECT `event_type_id`, 'Other'
  FROM `event_types`
 WHERE `event_name` IN (
    'Community Meeting', 'Ward Meeting', 'Public Meeting',
    'Sports Event', 'Football Match', 'Netball Match', 'Sports Tournament',
    'Athletics Meet', 'Athletics Training', 'Sports Training',
    'Public Fair', 'Trade Fair', 'Exhibition', 'Agricultural Show',
    'Church Gathering', 'Crusade',
    'School Event', 'School Assembly',
    'Music Concert', 'Live Concert', 'Music Festival',
    'Corporate Function', 'Cultural Festival', 'Political Rally'
 );

INSERT INTO `pricing_rules` (`rule_name`, `venue_type`, `rule_type`, `adjustment_type`, `adjustment_value`, `days_of_week`, `min_hours`, `start_date`, `end_date`, `priority`, `rule_status`) VALUES
('Weekday Discount', 'All', 'Weekday Discount', 'Percentage', 10.00, 'Mon,Tue,Wed,Thu', NULL, '2026-01-01', '2027-12-31', 10, 'Active'),
('Weekend Surcharge', 'All', 'Weekend Surcharge', 'Percentage', 20.00, 'Fri,Sat,Sun', NULL, '2026-01-01', '2027-12-31', 10, 'Active'),
('Off-Peak Month Discount', 'All', 'Off-Peak Discount', 'Percentage', 15.00, NULL, NULL, '2026-01-01', '2027-12-31', 9, 'Active'),
('Long Booking Discount', 'All', 'Long Booking Discount', 'Percentage', 5.00, NULL, 8.0, '2026-01-01', '2027-12-31', 11, 'Active'),
('Peak Demand Surcharge', 'All', 'Peak Demand Surcharge', 'Percentage', 12.00, NULL, NULL, '2026-11-01', '2027-12-31', 7, 'Active');

INSERT INTO `bookings` (`booking_reference`, `customer_id`, `venue_id`, `event_type_id`, `pricing_rule_id`, `booking_date`, `start_time`, `end_time`, `event_type`, `expected_attendance`, `standard_charge`, `event_type_charge`, `adjustment_amount`, `total_charge`, `booking_status`, `reviewed_by`) VALUES
('COH-20261005-0001', 5, 1, 2, 1, '2026-10-05', '09:00:00', '15:00:00', 'Community Meeting', 80, 100.00, 0.00, -10.00, 90.00, 'Pending', 2),
('COH-20261011-0001', 6, 2, 1, 2, '2026-10-11', '10:00:00', '14:00:00', 'Wedding', 120, 120.00, 50.00, 24.00, 194.00, 'Approved', 2),
('COH-20261008-0001', 7, 3, 3, 4, '2026-10-08', '08:00:00', '18:00:00', 'Sports Event', 160, 800.00, 100.00, -40.00, 860.00, 'Rejected', 2),
('COH-20261004-0001', 5, 4, 4, 2, '2026-10-04', '09:00:00', '13:00:00', 'Public Fair', 300, 220.00, 75.00, 44.00, 339.00, 'Confirmed', 2),
('COH-20261001-0001', 6, 5, 5, 1, '2026-10-01', '09:00:00', '12:00:00', 'Church Gathering', 110, 150.00, 0.00, -15.00, 135.00, 'Completed', 2),
('COH-20261015-0001', 7, 6, 6, 1, '2026-10-15', '15:00:00', '17:00:00', 'School Event', 90, 180.00, 0.00, -18.00, 162.00, 'Cancelled', 2),
('COH-20261012-0001', 5, 2, 7, 1, '2026-10-12', '09:00:00', '13:00:00', 'Youth Workshop', 65, 120.00, 25.00, -12.00, 133.00, 'Pending', 2),
('COH-20261017-0001', 6, 3, 8, 2, '2026-10-17', '10:00:00', '14:00:00', 'Music Concert', 1200, 800.00, 100.00, 160.00, 1060.00, 'Approved', 2),
('COH-20261006-0001', 7, 1, 9, 1, '2026-10-06', '10:00:00', '14:00:00', 'Drama Rehearsal', 45, 100.00, 0.00, -10.00, 90.00, 'Confirmed', 2),
('COH-20261002-0001', 5, 5, 10, 2, '2026-10-02', '14:00:00', '18:00:00', 'Educational Seminar', 95, 150.00, 30.00, 30.00, 210.00, 'Completed', 2),
('COH-20261014-0001', 6, 6, 11, 1, '2026-10-14', '09:00:00', '12:00:00', 'Athletics Training', 150, 180.00, 20.00, -18.00, 182.00, 'Approved', 2),
('COH-20261020-0001', 7, 4, 12, 1, '2026-10-20', '08:00:00', '14:00:00', 'Flea Market', 200, 220.00, 50.00, -22.00, 248.00, 'Cancelled', 2);

INSERT INTO `payments` (`booking_id`, `amount_paid`, `payment_method`, `transaction_reference`, `payment_date`, `payment_status`, `verified_by`, `verification_date`, `receipt_number`) VALUES
(2, 194.00, 'Bank Transfer', 'TEST-POP-000001', '2026-10-04 11:30:00', 'Pending Verification', NULL, NULL, NULL),
(4, 339.00, 'Cash', 'TEST-POP-000002', '2026-10-03 18:00:00', 'Verified', 3, '2026-10-04 09:00:00', 'COH-PMS-VER-000002'),
(5, 135.00, 'Card', 'TEST-POP-000003', '2026-09-30 16:45:00', 'Verified', 3, '2026-10-01 10:30:00', 'COH-PMS-VER-000003'),
(8, 1060.00, 'Bank Transfer', 'TEST-POP-000004', '2026-10-04 14:20:00', 'Pending Verification', NULL, NULL, NULL),
(9, 90.00, 'Cash', 'TEST-POP-000005', '2026-10-03 11:00:00', 'Verified', 3, '2026-10-03 14:00:00', 'COH-PMS-VER-000005'),
(10, 210.00, 'Mobile Money', 'TEST-POP-000006', '2026-10-01 15:30:00', 'Verified', 3, '2026-10-02 09:00:00', 'COH-PMS-VER-000006'),
(11, 182.00, 'Card', 'TEST-POP-000007', '2026-10-03 09:00:00', 'Rejected', 3, '2026-10-03 10:00:00', NULL);
