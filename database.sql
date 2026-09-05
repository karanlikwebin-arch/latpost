CREATE TABLE User (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    UserAvatar    VARCHAR(255) DEFAULT 'https://p1-cdn.latpost.com/photo/default.png',
    NameSurname   VARCHAR(100) NOT NULL,
    Mail          VARCHAR(150) NOT NULL UNIQUE,
    Password      VARCHAR(255) NOT NULL,
    UserActive    TINYINT(1)   DEFAULT 0,
    UserDeleted   TINYINT(1)   DEFAULT 0,
    UserCreated   DATETIME     DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE UserOtp (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    UserId        INT         NOT NULL,
    OtpCode       VARCHAR(6)  NOT NULL UNIQUE,
    OtpCreated    DATETIME    DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE UserToken (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    UserId        INT          NOT NULL,
    UserToken     VARCHAR(255) NOT NULL,
    TokenCreated  DATETIME     DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Post (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    UserId        INT          NOT NULL,
    PostCategory  TINYINT(1)   DEFAULT 0,
    PostContent   TEXT         NOT NULL,
    Postdeleted   TINYINT(1)   DEFAULT 0,
    PostCreated   DATETIME     DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE PostPicture (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    PostId         INT          NOT NULL,
    PostPicture    VARCHAR(255) NOT NULL,
    PictureCreated DATETIME     DEFAULT CURRENT_TIMESTAMP
);
