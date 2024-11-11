DROP TABLE IF EXISTS "images";
CREATE TABLE "images"
(
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "filename" VARCHAR(256),
    "resp_filename" VARCHAR(256),
    "width" INTEGER,
    "height" INTEGER,
    "mod_time" TIMESTAMP,
    "content_id" INTEGER
);
CREATE INDEX filename_index ON images( filename );
CREATE INDEX content_index ON images( content_id );