--
-- Table structure for table 'trends'
--

CREATE TABLE trends (
  itemid                int4            DEFAULT '0' NOT NULL,
  clock                 int4            DEFAULT '0' NOT NULL,
  value_min             float8          DEFAULT '0.0000' NOT NULL,
  value_avg             float8          DEFAULT '0.0000' NOT NULL,
  value_max             float8          DEFAULT '0.0000' NOT NULL,
  PRIMARY KEY (itemid,clock),
  FOREIGN KEY (itemid) REFERENCES items
);
