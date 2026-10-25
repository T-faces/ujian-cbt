-- Apply to an existing CBT database before deploying password-hash changes.
-- Take a verified backup first. This changes column capacity only; it does not alter stored passwords.
ALTER TABLE admin MODIFY password VARCHAR(255) NULL;
ALTER TABLE guru MODIFY password VARCHAR(255) NOT NULL;
ALTER TABLE siswa MODIFY password VARCHAR(255) NOT NULL;

-- Keep two decimal places for percentage scores calculated by the server.
ALTER TABLE ikut_ujian MODIFY nilai DECIMAL(5,2) NULL;
