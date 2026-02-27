CREATE DATABASE IF NOT EXISTS penjualan_db;
USE penjualan_db;

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE sales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  year INT NOT NULL,
  month INT NOT NULL,
  amount INT NOT NULL,
  UNIQUE KEY uniq_data (category_id, year, month),
  FOREIGN KEY (category_id) REFERENCES categories(id)
);
