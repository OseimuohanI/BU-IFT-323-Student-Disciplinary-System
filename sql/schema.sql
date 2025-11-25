CREATE DATABASE IF NOT EXISTS student_disciplinary_system;
USE student_disciplinary_system;

CREATE TABLE offensetype (
    OffenseTypeID INT PRIMARY KEY,
    Code VARCHAR(20),
    Description VARCHAR(255),
    SeverityLevel TINYINT,
    CreatedAt TIMESTAMP
);

CREATE TABLE staff (
    StaffID INT PRIMARY KEY,
    StaffNo VARCHAR(50),
    Name VARCHAR(150),
    Role VARCHAR(100),
    Email VARCHAR(150),
    CreatedAt TIMESTAMP
);

CREATE TABLE student (
    StudentID INT PRIMARY KEY,
    EnrollmentNo VARCHAR(50),
    FirstName VARCHAR(100),
    LastName VARCHAR(100),
    DOB DATE,
    Gender ENUM('Male','Female','Other'),
    Email VARCHAR(150),
    Phone VARCHAR(50),
    CreatedAt TIMESTAMP
);

CREATE TABLE incidentreport (
    IncidentID INT PRIMARY KEY,
    ReportDate DATETIME,
    Location VARCHAR(200),
    ReporterStaffID INT,
    StudentID INT,
    Description TEXT,
    Status ENUM('Pending','In Review','Closed'),
    CreatedAt TIMESTAMP,
    FOREIGN KEY (ReporterStaffID) REFERENCES staff(StaffID),
    FOREIGN KEY (StudentID) REFERENCES student(StudentID)
);

CREATE TABLE attachment (
    AttachmentID INT PRIMARY KEY,
    IncidentID INT,
    FileName VARCHAR(255),
    FilePath VARCHAR(512),
    UploadedBy INT,
    UploadedDateTime DATETIME,
    FOREIGN KEY (IncidentID) REFERENCES incidentreport(IncidentID),
    FOREIGN KEY (UploadedBy) REFERENCES staff(StaffID)
);

CREATE TABLE reportoffense (
    ReportOffenseID INT PRIMARY KEY,
    IncidentID INT,
    OffenseTypeID INT,
    Notes VARCHAR(500),
    FOREIGN KEY (IncidentID) REFERENCES incidentreport(IncidentID),
    FOREIGN KEY (OffenseTypeID) REFERENCES offensetype(OffenseTypeID)
);

CREATE TABLE hearing (
    HearingID INT PRIMARY KEY,
    IncidentID INT,
    HearingDate DATETIME,
    Outcome VARCHAR(200),
    HearingNotes TEXT,
    FOREIGN KEY (IncidentID) REFERENCES incidentreport(IncidentID)
);

CREATE TABLE disciplinaryaction (
    ActionID INT PRIMARY KEY,
    IncidentID INT,
    ActionType VARCHAR(100),
    ActionDate DATE,
    DurationDays INT,
    DecisionMakerID INT,
    Notes TEXT,
    FOREIGN KEY (IncidentID) REFERENCES incidentreport(IncidentID),
    FOREIGN KEY (DecisionMakerID) REFERENCES staff(StaffID)
);

CREATE TABLE appeal (
    AppealID INT PRIMARY KEY,
    IncidentID INT,
    AppealDate DATETIME,
    AppealStatus ENUM('Pending','Approved','Rejected'),
    Outcome TEXT,
    FOREIGN KEY (IncidentID) REFERENCES incidentreport(IncidentID)
);