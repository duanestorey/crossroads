DROP TABLE IF EXISTS "taxonomy";
CREATE TABLE "taxonomy"
(
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "type" VARCHAR,
    "tax" VARCHAR,
    "term" VARCHAR,
    "content_id" INTEGER FOREIGH
);
CREATE INDEX tax_index ON taxonomy( tax );
CREATE INDEX term_index ON taxonomy( term );
CREATE INDEX content_id_index ON taxonomy( content_id );
