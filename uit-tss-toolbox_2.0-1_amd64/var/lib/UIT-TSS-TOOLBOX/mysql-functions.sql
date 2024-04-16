DROP PROCEDURE iterateDate;
DELIMITER //
CREATE PROCEDURE iterateDate(date1 date, date2 date)
DETERMINISTIC
BEGIN
DROP TEMPORARY TABLE IF EXISTS tbl_results;
CREATE TEMPORARY TABLE IF NOT EXISTS tbl_results (date DATE);

label1: LOOP
    IF date1 < date2 THEN
        INSERT INTO tbl_results (date) VALUES(date1);
        SET date1 = ADDDATE(date1, INTERVAL 1 DAY);
        ITERATE label1;
    END IF;
    LEAVE label1;
END LOOP label1;

SELECT date FROM tbl_results;

END; //

DELIMITER ;