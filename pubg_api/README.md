# PUBG Player Finder

PUBG Player Finder is a web application that allows users to search for PUBG player profiles, view match stats, and explore battleground legends across PC (Steam), PlayStation, Xbox, and Kakao platforms.

## Features

- Secure registration and login
- PUBG player search and profile lookup by username and platform
- Recent match summaries and detailed stats
- Full season breakdowns
- Modern, responsive UI with glass effect and themed backgrounds
- Essential security features (sanitization, CSRF protection, prepared statements)

## How to Use

1. Clone or download this repository.
2. Install a local server stack (such as [XAMPP](https://www.apachefriends.org/) or [MAMP](https://www.mamp.info/en/)).
3. Create a MySQL database and the following table:

    ```
    CREATE TABLE users (
      id INT AUTO_INCREMENT PRIMARY KEY,
      username VARCHAR(32) NOT NULL UNIQUE,
      email VARCHAR(120) NOT NULL UNIQUE,
      password VARCHAR(255) NOT NULL
    );
    ```

4. Place your background image file in the project folder and update file paths in the CSS.
5. Set up your PUBG API key and replace the placeholder in `api_functions.php`.
6. Edit `db.php` to configure your database connection (`$pdo`).

## Security Highlights

- All user inputs are sanitized and validated on the server
- CSRF token protection on login and registration forms
- Sessions are securely managed
- Passwords are securely hashed via bcrypt

## File Structure

- `login.php` — User login with CSRF and input protection
- `register.php` — User registration with email validation and CSRF
- `dashboard.php` — Main player search area and stats display
- `csrf.php` — CSRF helper functions (include in both login and register)
- `db.php` — Database connection (edit with your credentials)
- `api_functions.php` — API integration for fetching PUBG data
- `README.md` — Project documentation (this file)
- `image.jpg` — Your background image (`img-og-pubg.jpg` or chosen file)

## Credits

- Powered by [PUBG API](https://developer.pubg.com/)
- UI inspired by PUBG esports, glass morphism, and gamer dashboards

## License

This project is provided for educational and personal use. You may modify and extend it as needed.

