DELIMITER //
CREATE FUNCTION iterateDate(date1 date, date2 date)
RETURNS DATE
DETERMINISTIC
BEGIN
label1: LOOP
    IF date1 < date2 THEN
        SET date1 = ADDDATE(date1, INTERVAL 1 DAY);
        ITERATE label1;
    END IF;
    LEAVE label1;
END LOOP label1;

RETURN date1;

END; //

DELIMITER ;