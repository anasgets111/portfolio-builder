---
paths:
  - bootstrap/app.php
---

# Bootstrap

## Keep middleware initialization
The empty withMiddleware() hook is load-bearing in Laravel 13: it initializes the default web middleware group. Removing it causes HTTP routes to fail with `Target class [web] does not exist`.
