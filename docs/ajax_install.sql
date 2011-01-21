CREATE TABLE IF NOT EXISTS ajax_blocks (
  id int(11) NOT NULL auto_increment,
  block varchar(255) NOT NULL,
  parent_id int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY  (id)
) TYPE=MyISAM;