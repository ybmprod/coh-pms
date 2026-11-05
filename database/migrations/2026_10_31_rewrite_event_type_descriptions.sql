USE `coh_pms`;

-- Replace charge comparisons and figures in event-type descriptions with
-- plain-English explanations of what happens during each event type.
-- Idempotent: rerunning the migration applies the same descriptions.

UPDATE `event_types`
SET `description` = CASE `event_name`
    WHEN 'Wedding' THEN 'A marriage ceremony, usually followed by a reception with guests, food and decoration.'
    WHEN 'Community Meeting' THEN 'A meeting where residents, ward members or civic groups discuss local matters.'
    WHEN 'Sports Event' THEN 'An organised match, tournament or athletics event with players and spectators.'
    WHEN 'Public Fair' THEN 'An open event with stalls, displays or exhibitions that attracts the public.'
    WHEN 'Church Gathering' THEN 'A church service, crusade or fellowship where people gather to worship.'
    WHEN 'School Event' THEN 'A school assembly, prize-giving or educational function for learners and teachers.'
    WHEN 'Youth Workshop' THEN 'A training or workshop session for young people, often using extra equipment.'
    WHEN 'Music Concert' THEN 'A live music performance with an audience, sound equipment and security.'
    WHEN 'Drama Rehearsal' THEN 'A practice session for a play or stage performance.'
    WHEN 'Educational Seminar' THEN 'A talk, lecture or seminar where people learn about a subject.'
    WHEN 'Athletics Training' THEN 'A training session for running, field events or other athletics activities.'
    WHEN 'Flea Market' THEN 'An informal market where traders sell goods from stalls.'
    WHEN 'Traditional Wedding' THEN 'A wedding that follows traditional marriage customs and family rituals.'
    WHEN 'Wedding Reception' THEN 'A celebration after a wedding, with guests, food, music and speeches.'
    WHEN 'Marriage Ceremony' THEN 'A civil or religious ceremony where two people are joined in marriage.'
    WHEN 'Football Match' THEN 'A football game between two teams, with supporters watching.'
    WHEN 'Netball Match' THEN 'A netball game between two teams, with supporters watching.'
    WHEN 'Sports Tournament' THEN 'A sports competition where several teams play a series of matches.'
    WHEN 'Athletics Meet' THEN 'A track and field event with races and field competitions.'
    WHEN 'Trade Fair' THEN 'A commercial event where businesses show and sell their products.'
    WHEN 'Exhibition' THEN 'A public display of products, art or information.'
    WHEN 'Agricultural Show' THEN 'An event showing farm produce, livestock and agricultural equipment.'
    WHEN 'Church Service' THEN 'A regular church meeting for worship and teaching.'
    WHEN 'Crusade' THEN 'A religious gathering for preaching, worship and prayer.'
    WHEN 'Prayer Meeting' THEN 'A small gathering for prayer and fellowship.'
    WHEN 'School Assembly' THEN 'A school gathering for announcements, worship or presentations.'
    WHEN 'Graduation Ceremony' THEN 'A ceremony to celebrate learners completing their studies.'
    WHEN 'Training Workshop' THEN 'A practical training session where people learn new skills.'
    WHEN 'Live Concert' THEN 'A live music performance with an audience and sound equipment.'
    WHEN 'Music Festival' THEN 'A larger music event with several performers and a public audience.'
    WHEN 'Play Rehearsal' THEN 'A practice session for actors preparing a stage play.'
    WHEN 'Conference' THEN 'A formal meeting where speakers and delegates discuss a topic.'
    WHEN 'Sports Training' THEN 'A team practice session for a sport.'
    WHEN 'Market Day' THEN 'A day when local traders sell goods to the community.'
    WHEN 'Craft Market' THEN 'A market where people sell handmade crafts and art.'
    WHEN 'Ward Meeting' THEN 'A meeting for residents of a ward to discuss local issues.'
    WHEN 'Public Meeting' THEN 'An open meeting for the public to discuss community matters.'
    WHEN 'Funeral Service' THEN 'A gathering to honour and mourn someone who has died.'
    WHEN 'Memorial Service' THEN 'A gathering to remember someone who has died.'
    WHEN 'Corporate Function' THEN 'A company event such as a staff gathering, launch or presentation.'
    WHEN 'Cultural Festival' THEN 'A celebration of culture through music, dance, food or art.'
    WHEN 'Political Rally' THEN 'A public gathering for a political party to share its message.'
    ELSE `description`
END
WHERE `event_name` IN (
    'Wedding',
    'Community Meeting',
    'Sports Event',
    'Public Fair',
    'Church Gathering',
    'School Event',
    'Youth Workshop',
    'Music Concert',
    'Drama Rehearsal',
    'Educational Seminar',
    'Athletics Training',
    'Flea Market',
    'Traditional Wedding',
    'Wedding Reception',
    'Marriage Ceremony',
    'Football Match',
    'Netball Match',
    'Sports Tournament',
    'Athletics Meet',
    'Trade Fair',
    'Exhibition',
    'Agricultural Show',
    'Church Service',
    'Crusade',
    'Prayer Meeting',
    'School Assembly',
    'Graduation Ceremony',
    'Training Workshop',
    'Live Concert',
    'Music Festival',
    'Play Rehearsal',
    'Conference',
    'Sports Training',
    'Market Day',
    'Craft Market',
    'Ward Meeting',
    'Public Meeting',
    'Funeral Service',
    'Memorial Service',
    'Corporate Function',
    'Cultural Festival',
    'Political Rally'
);
