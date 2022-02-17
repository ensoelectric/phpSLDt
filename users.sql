CREATE USER IF NOT EXISTS 'phpsldt_tester'@'%' IDENTIFIED BY 'pass';
CREATE USER IF NOT EXISTS 'phpsldt_electrician'@'%' IDENTIFIED BY 'pass';
CREATE USER IF NOT EXISTS 'phpsldt_manager'@'%' IDENTIFIED BY 'pass';

--READ AND WRITE
GRANT SELECT,UPDATE,INSERT, DELETE ON phpSLDt_dev.applications TO 'phpsldt_tester'@'%';
GRANT SELECT,UPDATE,INSERT ON phpSLDt_dev.switchgears TO 'phpsldt_tester'@'%';

GRANT SELECT,UPDATE,INSERT, DELETE ON phpSLDt_dev.applications TO 'phpsldt_electrician'@'%';
GRANT SELECT,UPDATE,INSERT ON phpSLDt_dev.switchgears TO 'phpsldt_electrician'@'%';

--READ ONLY
GRANT SELECT ON phpSLDt_dev.applications TO 'phpsldt_manager'@'%';
GRANT SELECT ON phpSLDt_dev.switchgears TO 'phpsldt_manager'@'%';

FLUSH PRIVILEGES;