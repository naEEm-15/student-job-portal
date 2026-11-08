# Student-Job Recommendation and Skill Gap Analysis

A university web project that connects students with employers. Students get
rule-based job recommendations, a matching percentage and a list of missing skills.

**Stack:** PHP 8.2 (procedural, PDO), MariaDB/MySQL, HTML5, CSS3, Bootstrap 5, JavaScript.

## Setup (XAMPP on Windows)

1. Put this folder at `C:\xampp\htdocs\student-job-portal`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. In phpMyAdmin, import `database/schema.sql`, then `database/seed.sql`.
4. Copy `config/database.example.php` to `config/database.php` and enter your own
   database username and password. This file is ignored by Git. Never commit it.
5. Open http://localhost/student-job-portal/

## Status

Module 0 (project setup) in progress. See the development roadmap for later modules.