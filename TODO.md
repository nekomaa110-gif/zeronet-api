# TODO - Audit & Fix Error Login API + Cleanup Backend-only

- [x] Update routes/api.php:
    - [x] Add POST /login route to AuthController@login
    - [x] Protect /logout and /me with auth:sanctum middleware
- [x] Enable API route loading in bootstrap/app.php (`withRouting(api: ...)`)
- [x] Verify route registration using route list
- [x] Re-test login endpoint using curl payload from user

- [ ] Cleanup file bawaan Laravel yang tidak dipakai (backend-only):
    - [ ] Remove resources/views/welcome.blade.php
    - [ ] Remove resources/css/app.css
    - [ ] Remove resources/js/app.js
    - [ ] Remove tests/Feature/ExampleTest.php
    - [ ] Remove tests/Unit/ExampleTest.php
    - [ ] Update bootstrap/app.php (remove web route registration)
    - [ ] Remove routes/web.php
- [ ] Verify routes after cleanup (`php artisan route:list`)
