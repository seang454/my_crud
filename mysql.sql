-- 1️⃣ Books table
CREATE TABLE books(
    book_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    PAGE SMALLINT,
    publish_year SMALLINT,
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
-- 2️⃣ Authors table
CREATE TABLE authors(
    author_id INT AUTO_INCREMENT PRIMARY KEY,
    NAME VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    address VARCHAR(255)
);
-- 3️⃣ Junction table
CREATE TABLE book_details(
    book_id INT NOT NULL,
    author_id INT NOT NULL,
    is_deleted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY(book_id, author_id),
    FOREIGN KEY(book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    FOREIGN KEY(author_id) REFERENCES authors(author_id) ON DELETE CASCADE
);
