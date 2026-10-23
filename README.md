# RS SMM BD — starter panel

## Included
- PHP/MySQL starter user panel
- Registration and login
- Service import from an SMM provider API
- Admin page for provider settings, service import, service markup, orders, and payment requests
- Manual bKash/Nagad payment request workflow (admin approval required)
- SQL schema

## Setup
1. Create a MySQL database and import `database.sql`.
2. Copy `config.example.php` to `config.php` and enter database credentials.
3. Upload all files to PHP hosting (PHP 8.1+ and MySQL/MariaDB).
4. Set `BASE_URL` in `config.php` to your site URL.
5. Register your first user, then make that account admin in MySQL:
   `UPDATE users SET role='admin' WHERE email='YOUR_EMAIL';`
6. In Admin → Settings, enter your provider API URL and key. Keep the key secret.
7. Enter your bKash/Nagad receiving number in Admin → Settings.

## Important
- This is a starter implementation, not a production-audited payment platform.
- Manual payment requests are not automatic mobile-money verification. Verify each transaction in your official merchant/account app before approving.
- Never commit `config.php`, live API keys, passwords, or payment secrets to a public GitHub repository.
- Render does not provide a built-in MySQL service. Use a separately managed MySQL-compatible database or PHP/MySQL hosting. Deploy PHP on Render using Docker.
- Provider API must be compatible with the common SMM API format (`key`, `action=services`, `action=add`, `action=status`). Check your provider's documentation.
