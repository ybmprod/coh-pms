USE `coh_pms`;

-- Link each event type to the venue types where it can realistically be held.
-- The customer booking form and BookingService both enforce this mapping.
-- Idempotent: rerunning the migration only adds missing rows.

CREATE TABLE IF NOT EXISTS `event_type_venue_types` (
    `event_type_id` INT NOT NULL,
    `venue_type` ENUM('Community Hall','Community Centre','Stadium','Open Space','Other') NOT NULL,
    PRIMARY KEY (`event_type_id`, `venue_type`),
    KEY `idx_event_type_venue_type` (`venue_type`),
    CONSTRAINT `fk_event_type_venue_types_event_type` FOREIGN KEY (`event_type_id`) REFERENCES `event_types` (`event_type_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `event_type_venue_types` (`event_type_id`, `venue_type`)
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

INSERT IGNORE INTO `event_type_venue_types` (`event_type_id`, `venue_type`)
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

INSERT IGNORE INTO `event_type_venue_types` (`event_type_id`, `venue_type`)
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

INSERT IGNORE INTO `event_type_venue_types` (`event_type_id`, `venue_type`)
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

INSERT IGNORE INTO `event_type_venue_types` (`event_type_id`, `venue_type`)
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

-- Custom event types created before this migration keep working until staff
-- choose a narrower mapping in Event Types.
INSERT IGNORE INTO `event_type_venue_types` (`event_type_id`, `venue_type`)
SELECT et.`event_type_id`, types.`venue_type`
  FROM `event_types` AS et
 CROSS JOIN (
    SELECT 'Community Hall' AS `venue_type`
    UNION ALL SELECT 'Community Centre'
    UNION ALL SELECT 'Stadium'
    UNION ALL SELECT 'Open Space'
    UNION ALL SELECT 'Other'
 ) AS types
 WHERE NOT EXISTS (
    SELECT 1
      FROM `event_type_venue_types` AS mapped
     WHERE mapped.`event_type_id` = et.`event_type_id`
 );

-- Verification: every event type should have at least one suitable venue type.
SELECT COUNT(*) AS `event_type_count`,
       SUM(CASE WHEN `suitable_types` = 0 THEN 1 ELSE 0 END) AS `unmapped_event_type_count`
  FROM (
    SELECT et.`event_type_id`, COUNT(m.`venue_type`) AS `suitable_types`
      FROM `event_types` AS et
      LEFT JOIN `event_type_venue_types` AS m ON m.`event_type_id` = et.`event_type_id`
     GROUP BY et.`event_type_id`
  ) AS coverage;
