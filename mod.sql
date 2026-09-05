CREATE TABLE RequestLog (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    IpAddress      VARCHAR(45)  NOT NULL,
    RequestCreated DATETIME     DEFAULT CURRENT_TIMESTAMP
);
