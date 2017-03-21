PRAGMA foreign_keys=OFF;
BEGIN TRANSACTION;
CREATE TABLE "models" ("id" integer not null primary key autoincrement, "name" varchar not null);
INSERT INTO "models" VALUES(1,'dump1');
DELETE FROM sqlite_sequence;
INSERT INTO "sqlite_sequence" VALUES('models',1);
COMMIT;
