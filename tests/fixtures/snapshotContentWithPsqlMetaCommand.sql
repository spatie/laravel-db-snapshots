PRAGMA foreign_keys=OFF;

\restrict 8DaynFUYihru3xOL8jLGTKv9fHYdQKexhDbep2NXltZaqrent0pTe69YZyuEi7J

BEGIN TRANSACTION;
DROP TABLE IF EXISTS "models";
CREATE TABLE "models" ("id" integer not null primary key autoincrement, "name" varchar not null);
INSERT INTO "models" VALUES(1,'%%modelName%%');
DELETE FROM sqlite_sequence;
INSERT INTO "sqlite_sequence" VALUES('models',1);
COMMIT;

\unrestrict 8DaynFUYihru3xOL8jLGTKv9fHYdQKexhDbep2NXltZaqrent0pTe69YZyuEi7J
