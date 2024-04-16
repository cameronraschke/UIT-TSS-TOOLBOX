DROP PROCEDURE iterateDate;
DELIMITER //
CREATE PROCEDURE iterateDate(date1 date, date2 date)
DETERMINISTIC
BEGIN
DROP TEMPORARY TABLE IF EXISTS tblResults;
CREATE TEMPORARY TABLE IF NOT EXISTS tblResults (date DATE);

label1: LOOP
    IF date1 < date2 THEN
        INSERT INTO tblResults (date) VALUES(date1);
        SET date1 = ADDDATE(date1, INTERVAL 1 DAY);
        ITERATE label1;
    END IF;
    LEAVE label1;
END LOOP label1;

END; //

DELIMITER ;