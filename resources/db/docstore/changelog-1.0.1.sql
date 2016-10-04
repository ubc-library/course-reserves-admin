-- liquibase formatted SQL

-- changeset skhanker:2

-- Created by Vertabelo (http://vertabelo.com)
-- Last modification date: 2016-07-22 18:47:19.678

-- tables
-- Table: copyright_determination
CREATE TABLE copyright_determination (
  copyright_id int(11) NOT NULL AUTO_INCREMENT,
  determination varchar(25) NOT NULL,
  determination_label varchar(60) NOT NULL,
  disclaimer text NOT NULL,
  CONSTRAINT docstore_licr_copyright_pk PRIMARY KEY (copyright_id)
) ENGINE InnoDB;

-- Table: currency
CREATE TABLE currency (
  id int NOT NULL AUTO_INCREMENT,
  code varchar(3) NOT NULL,
  label varchar(64) NOT NULL,
  UNIQUE INDEX currency_code_is_unique (code),
  CONSTRAINT currency_pk PRIMARY KEY (id)
);

-- Table: history
CREATE TABLE history (
  id bigint NOT NULL AUTO_INCREMENT,
  changed_tbl varchar(128) NOT NULL,
  changed_tbl_id bigint NOT NULL,
  changed_col varchar(128) NOT NULL,
  value_before text NOT NULL,
  value_after text NOT NULL,
  puid varchar(24) NOT NULL,
  timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT history_pk PRIMARY KEY (id)
) ENGINE InnoDB CHARACTER SET utf8;

CREATE INDEX item_id ON history (changed_tbl);

CREATE INDEX `user` ON history (puid);

-- Table: item
CREATE TABLE item (
  id bigint NOT NULL,
  hash varchar(60) NOT NULL,
  filename varchar(255) NOT NULL,
  copyright_id int(11) NOT NULL DEFAULT '1',
  UNIQUE INDEX docstore_licr_unique_key_hash (hash),
  CONSTRAINT docstore_licr_pk PRIMARY KEY (id)
) ENGINE InnoDB CHARACTER SET utf8;

CREATE INDEX hash ON item (hash);

CREATE INDEX filename ON item (filename);

CREATE INDEX copyright_id ON item (copyright_id);

-- Table: item_coversheet
CREATE TABLE item_coversheet (
  id int NOT NULL AUTO_INCREMENT,
  item_id bigint(20) NOT NULL,
  note text NOT NULL,
  CONSTRAINT item_coversheet_pk PRIMARY KEY (id)
);

-- Table: item_history
CREATE TABLE item_history (
  item_id bigint NOT NULL,
  history_id bigint NOT NULL,
  CONSTRAINT item_history_pk PRIMARY KEY (item_id,history_id)
);

-- Table: item_metadata
CREATE TABLE item_metadata (
  item_id bigint(20) NOT NULL,
  course_id bigint(20) NOT NULL,
  item_title varchar(255) NOT NULL,
  item_author varchar(255) NOT NULL,
  item_publisher varchar(128) NOT NULL,
  item_pubdate varchar(128) NOT NULL,
  item_incpages varchar(32) NOT NULL,
  course_title varchar(255) NOT NULL,
  course_code varchar(32) NOT NULL,
  course_term varchar(16) NOT NULL,
  course_dept varchar(16) NOT NULL,
  external_id varchar(255) NOT NULL,
  UNIQUE INDEX item_id (item_id),
  CONSTRAINT item_metadata_pk PRIMARY KEY (item_id)
) ENGINE InnoDB CHARACTER SET utf8;

CREATE INDEX course_id ON item_metadata (course_id);

-- Table: item_negotiation
CREATE TABLE item_negotiation (
  item_id bigint(20) NOT NULL,
  page_count int(3) NOT NULL,
  work_count int(5) NOT NULL,
  cost decimal(10,2) NOT NULL,
  amount_paid decimal(10,2) NOT NULL,
  date_paid date NOT NULL,
  currency_id int NOT NULL,
  license_id int NOT NULL,
  date_modified timestamp NOT NULL,
  CONSTRAINT docstore_licr_copyright_details_pk PRIMARY KEY (item_id)
) ENGINE InnoDB CHARACTER SET utf8;

-- Table: item_request
CREATE TABLE item_request (
  item_id bigint NOT NULL,
  course_id bigint NOT NULL,
  expires int(11) NOT NULL,
  UNIQUE INDEX item_id (item_id),
  CONSTRAINT item_request_pk PRIMARY KEY (item_id)
) ENGINE InnoDB CHARACTER SET utf8 COMMENT ''
  COMMENT 'must only ever have one request per item';

CREATE INDEX course_id ON item_request (course_id);

-- Table: license
CREATE TABLE license (
  id int NOT NULL AUTO_INCREMENT,
  rightsholder text NOT NULL,
  url varchar(512) NOT NULL,
  CONSTRAINT license_pk PRIMARY KEY (id)
);

-- Table: metrics
CREATE TABLE metrics (
  id bigint NOT NULL,
  hash varchar(60) NOT NULL,
  user_id bigint(20) NOT NULL,
  role_id bigint(2) NOT NULL,
  count int(11) NOT NULL,
  UNIQUE INDEX pk (hash,user_id,role_id),
  CONSTRAINT metrics_pk PRIMARY KEY (id)
) ENGINE InnoDB CHARACTER SET utf8;

-- Table: notes
CREATE TABLE notes (
  id int NOT NULL AUTO_INCREMENT,
  item_id bigint(20) NOT NULL,
  puid varchar(24) NOT NULL,
  `user` varchar(128) NOT NULL,
  note text NOT NULL,
  timestamp timestamp NOT NULL,
  UNIQUE INDEX item_id (item_id),
  CONSTRAINT notes_pk PRIMARY KEY (id)
) ENGINE InnoDB CHARACTER SET utf8;

-- foreign keys
-- Reference: ref_copyright_determination_item_copyright_id_copyright_id (table: item)
ALTER TABLE item ADD CONSTRAINT ref_copyright_determination_item_copyright_id_copyright_id FOREIGN KEY ref_copyright_determination_item_copyright_id_copyright_id (copyright_id)
REFERENCES copyright_determination (copyright_id)
  ON UPDATE CASCADE;

-- Reference: ref_currency_item_negotiation_id_currency_id (table: item_negotiation)
ALTER TABLE item_negotiation ADD CONSTRAINT ref_currency_item_negotiation_id_currency_id FOREIGN KEY ref_currency_item_negotiation_id_currency_id (currency_id)
REFERENCES currency (id);

-- Reference: ref_item_history_id_history_id (table: item_history)
ALTER TABLE item_history ADD CONSTRAINT ref_item_history_id_history_id FOREIGN KEY ref_item_history_id_history_id (history_id)
REFERENCES history (id);

-- Reference: ref_item_item_coversheet_id_item_id (table: item_coversheet)
ALTER TABLE item_coversheet ADD CONSTRAINT ref_item_item_coversheet_id_item_id FOREIGN KEY ref_item_item_coversheet_id_item_id (item_id)
REFERENCES item (id);

-- Reference: ref_item_item_history_id_item_id (table: item_history)
ALTER TABLE item_history ADD CONSTRAINT ref_item_item_history_id_item_id FOREIGN KEY ref_item_item_history_id_item_id (item_id)
REFERENCES item (id);

-- Reference: ref_item_item_metadata_id_item_id (table: item_metadata)
ALTER TABLE item_metadata ADD CONSTRAINT ref_item_item_metadata_id_item_id FOREIGN KEY ref_item_item_metadata_id_item_id (item_id)
REFERENCES item (id);

-- Reference: ref_item_item_negotiation_id_item_id (table: item_negotiation)
ALTER TABLE item_negotiation ADD CONSTRAINT ref_item_item_negotiation_id_item_id FOREIGN KEY ref_item_item_negotiation_id_item_id (item_id)
REFERENCES item (id);

-- Reference: ref_item_item_request_id_item_id (table: item_request)
ALTER TABLE item_request ADD CONSTRAINT ref_item_item_request_id_item_id FOREIGN KEY ref_item_item_request_id_item_id (item_id)
REFERENCES item (id);

-- Reference: ref_item_metrics_hash_hash (table: metrics)
ALTER TABLE metrics ADD CONSTRAINT ref_item_metrics_hash_hash FOREIGN KEY ref_item_metrics_hash_hash (hash)
REFERENCES item (hash);

-- Reference: ref_item_note_id_item_id (table: notes)
ALTER TABLE notes ADD CONSTRAINT ref_item_note_id_item_id FOREIGN KEY ref_item_note_id_item_id (item_id)
REFERENCES item (id);

-- Reference: ref_license_item_negotiation_id_license_id (table: item_negotiation)
ALTER TABLE item_negotiation ADD CONSTRAINT ref_license_item_negotiation_id_license_id FOREIGN KEY ref_license_item_negotiation_id_license_id (license_id)
REFERENCES license (id);

-- End of file.
