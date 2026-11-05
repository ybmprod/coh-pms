USE `coh_pms`;

-- Expand the event-type catalogue with common alternative wordings.
-- Descriptions explain what happens during each event type.
-- This migration only inserts missing rows and never overwrites staff edits.

INSERT IGNORE INTO `event_types` (`event_name`, `charge_type`, `charge_value`, `status`, `description`) VALUES
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

-- Verification: show how many wordings share each charge band.
SELECT `charge_type`, `charge_value`, COUNT(*) AS `event_type_count`
FROM `event_types`
GROUP BY `charge_type`, `charge_value`
ORDER BY `charge_value` ASC, `charge_type` ASC;
