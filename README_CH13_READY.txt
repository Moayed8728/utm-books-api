Ch13-ready backend made from Lab10_Book.

Routes included:
- GET /
- POST /auth/login
- POST /auth/register
- GET /auth/me
- GET /api/books
- GET /api/books/{id}
- POST /api/books  (Bearer token required)
- PUT /api/books/{id}  (owner or admin)
- DELETE /api/books/{id}  (admin only)

Seeded users:
- member@books.test / password
- admin@books.test / password

Setup:
1. Extract this folder.
2. Open HeidiSQL and run sql/schema.sql.
3. Check .env DB_PASS. Laragon often uses root or empty password.
4. Run: composer install
5. Run: php -S localhost:8000 -t public
6. Open: http://localhost:8000/
