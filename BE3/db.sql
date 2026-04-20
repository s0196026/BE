-- заявки
CREATE TABLE applications (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    fio VARCHAR(200) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    birthdate DATE DEFAULT NULL,
    gender ENUM('male', 'female') DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    contract_agreed BOOLEAN NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- яп
CREATE TABLE programming_languages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- список яп
INSERT INTO programming_languages (name) VALUES 
('Pascal'), ('C'), ('C++'), ('JavaScript'), ('PHP'), 
('Python'), ('Java'), ('Haskel'), ('Clojure'), ('Prolog'), 
('Scala'), ('Go');

-- заявки + яп
CREATE TABLE application_languages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    application_id INT UNSIGNED NOT NULL,
    language_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (language_id) REFERENCES programming_languages(id) ON DELETE CASCADE,
    UNIQUE KEY (application_id, language_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
