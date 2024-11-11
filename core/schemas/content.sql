DROP TABLE IF EXISTS "content";
CREATE TABLE "content"
(
    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    "title" VARCHAR(256),
    "description" VARCHAR(1024),
    "modified_at" DATETIME,
    "created_at" DATETIME,
    "type" VARCHAR(32),
    "hash" VARCHAR(64),
    "rel_url" VARCHAR(256),
    "slug" VARCHAR(256),
    "content_slug" VARCHAR(256),
    "featured" VARCHAR(256),
    "markdown" VARCHAR,
    "original_html" VARCHAR,
    "html" VARCHAR
);
CREATE INDEX created_at_index ON content( created_at );
CREATE INDEX hash_index ON content( hash );
CREATE INDEX type_index ON content( type );
