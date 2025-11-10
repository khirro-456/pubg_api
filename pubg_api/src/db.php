<?php
// XAMPP default: user 'root', password '' (empty)
$pdo = new PDO(
    "mysql:host=localhost;dbname=pubg_api;charset=utf8mb4",
    "root",
    ""
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
