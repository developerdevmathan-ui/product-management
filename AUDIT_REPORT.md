# Laravel Technical Assessment Audit Report

Audit date: 2026-06-22

## Scope Reviewed

Reviewed first-party Laravel project files under:

- `app/`
- `bootstrap/`
- `config/`
- `database/`
- `docker/`
- `public/`
- `resources/`
- `routes/`
- `scripts/`
- `tests/`
- Root project files including `.env`, `.env.example`, `.gitignore`, `composer.json`, `package.json`, `phpunit.xml`, `docker-compose.yml`

Excluded manual review of generated dependency trees:

- `vendor/`
- `node_modules/`

Those dependency trees were assessed through package audit commands instead.

## Verification Commands

```bash
vendor/bin/pint --test
php artisan test
composer audit --locked --format=json
npm.cmd audit --json
```

Results:

- `php artisan test`: Passed, 109 tests, 344 assertions.
- `composer audit --locked`: Passed, no advisories, no abandoned packages.
- `npm audit`: Passed, 0 vulnerabilities.
- `vendor/bin/pint --test`: Failed on one PSR/style issue in `app/Http/Middleware/EnsureUserHasRole.php`.

## Compliance Scores

| Area | Score |
| --- | ---: |
| PSR Compliance | 98% |
| Security | 78% |
| Architecture | 76% |
| Overall Assessment | 84% |

## Executive Summary

The project is a solid Laravel Breeze-style application with strong baseline authentication, form request validation, policies, route model binding, Pest coverage, service usage for products, and HTML purification for rich text. The most important issues are not widespread code defects; they are concentrated around production hardening, mass-assignment boundaries, migration portability, controller responsibility creep, and missing repository abstraction relative to the stated assessment requirement.

## Issues

### 1. PSR-12 Formatting Failure

- File: `app/Http/Middleware/EnsureUserHasRole.php`
- Severity: Low
- Current Problem: `vendor/bin/pint --test` reports `blank_line_before_statement` for the `return $next($request);` statement.
- Why it violates the requirement: The assessment requires PSR-12 compliance. A failing Pint check means the codebase is not fully style-compliant.
- Recommended Fix: Run Pint or add the missing blank line:

```php
abort_unless($request->user()?->role === UserRole::tryFrom($role), 403);

return $next($request);
```

### 2. `User` Model Allows Mass Assignment of `role`

- File: `app/Models/User.php`
- Severity: High
- Current Problem: The model uses `#[Fillable(['name', 'email', 'password', 'role'])]`.
- Why it violates the requirement: Mass assignment protection should minimize privilege-sensitive fields. Even though current registration code only passes selected fields, allowing `role` in general fillable state creates a future privilege escalation risk if a later controller calls `$user->fill($request->validated())` or `User::create($request->all())` with role-bearing input.
- Recommended Fix: Remove `role` from general fillable fields and set role through explicit, authorized code paths such as `forceFill()` inside `UserRoleController`.

### 3. Derived Product Field Is Mass Assignable

- File: `app/Models/Product.php`
- Severity: Medium
- Current Problem: `stock_status` is included in `#[Fillable([...])]` even though stock status is derived from `quantity`.
- Why it violates the requirement: Mass assignment protection should not expose derived server-controlled fields. The model currently corrects the value in the `saving` hook, but keeping it fillable leaves an unnecessary writable surface.
- Recommended Fix: Remove `stock_status` from product fillable attributes. Keep stock derivation in one server-side path.

### 4. Local `.env` Contains Real Secrets

- File: `.env`
- Severity: High
- Current Problem: The local environment file contains a real-looking Gmail username/password and an application key:
  - `MAIL_USERNAME=developerdevmathan@gmail.com`
  - `MAIL_PASSWORD=...`
  - `APP_KEY=base64:...`
- Why it violates the requirement: Secure configuration requires secrets to be kept out of files that may be copied, shared, backed up, or exposed during support. `.env` is ignored by Git in this repository, but the secret exists in the project directory.
- Recommended Fix: Rotate the mail password immediately if real. Use a secrets manager or machine-local secret provisioning. Keep `.env.example` placeholder-only.

### 5. Local `.env` Has Debug Enabled

- File: `.env`
- Severity: Medium
- Current Problem: `APP_DEBUG=true`.
- Why it violates the requirement: Debug mode can expose stack traces, SQL, configuration paths, and environment details if this file is reused outside local development.
- Recommended Fix: Ensure production and staging `.env` files use `APP_DEBUG=false`. Add deployment validation that refuses `APP_ENV=production` with debug enabled.

### 6. Default Docker Credentials Are Predictable

- Files:
  - `.env.example`
  - `docker-compose.yml`
- Severity: Medium
- Current Problem: Docker defaults include predictable credentials:
  - `DOCKER_DB_PASSWORD=secret`
  - `DB_ROOT_PASSWORD=root_secret`
  - `${DOCKER_DB_PASSWORD:-secret}`
  - `${DB_ROOT_PASSWORD:-root_secret}`
- Why it violates the requirement: Secure deployment defaults should avoid reusable credentials. If copied into a shared or internet-accessible environment, these defaults are weak.
- Recommended Fix: Use placeholder values in `.env.example`, require explicit secret generation before deployment, and document `openssl rand` or platform secret manager usage.

### 7. Session Encryption Is Disabled by Default

- Files:
  - `config/session.php`
  - `.env`
  - `.env.example`
- Severity: Medium
- Current Problem: `SESSION_ENCRYPT=false`, and `config/session.php` defaults to `env('SESSION_ENCRYPT', false)`.
- Why it violates the requirement: Database or Redis-backed sessions can contain sensitive state. Unencrypted session payloads increase impact if the backing store is exposed.
- Recommended Fix: Set `SESSION_ENCRYPT=true` for production and consider making the app default true unless explicitly disabled for local debugging.

### 8. Secure Cookie Flag Is Not Enforced

- File: `config/session.php`
- Severity: Medium
- Current Problem: Session cookie security depends on `SESSION_SECURE_COOKIE`; no production guard enforces it.
- Why it violates the requirement: Secure authentication requires session cookies to be sent only over HTTPS in production.
- Recommended Fix: Set `SESSION_SECURE_COOKIE=true` in production. Add deployment checks for `APP_ENV=production`, `APP_URL=https://...`, and secure cookies enabled.

### 9. Nginx Configuration Lacks Production Security Headers

- File: `docker/nginx/default.conf`
- Severity: Medium
- Current Problem: The config sets `X-Frame-Options` and `X-Content-Type-Options`, but not HSTS, Content Security Policy, Referrer Policy, or Permissions Policy.
- Why it violates the requirement: XSS and transport security posture is incomplete for production. The application also loads fonts from `https://fonts.bunny.net`, so CSP should be explicit rather than absent.
- Recommended Fix: Terminate TLS upstream or in nginx and add production headers such as `Strict-Transport-Security`, `Content-Security-Policy`, `Referrer-Policy`, and `Permissions-Policy`.

### 10. Docker App Container Does Not Declare a Non-Root Runtime User

- Files:
  - `docker/php/Dockerfile`
  - `docker-compose.yml`
- Severity: Medium
- Current Problem: The Dockerfile changes ownership to `www-data`, but does not set `USER www-data`; compose services also do not specify `user:`.
- Why it violates the requirement: Production containers should run application processes with least privilege. The entrypoint performs `chown`, which implies root execution at container start.
- Recommended Fix: Split ownership setup into build-time steps where possible, then run PHP-FPM, queue, and scheduler as `www-data` or a dedicated unprivileged user.

### 11. Migration Is Not SQLite Portable

- File: `database/migrations/2026_06_20_000002_replace_is_admin_with_role_on_users_table.php`
- Severity: Medium
- Current Problem: The migration drops `is_admin` without first dropping the index created in `2026_06_20_000001_add_is_admin_to_users_table.php`. Running tests with SQLite produced: `error in index users_is_admin_index after drop column: no such column: is_admin`.
- Why it violates the requirement: Testability and maintainability suffer when migrations only work on one database engine without being declared engine-specific.
- Recommended Fix: Drop the index before dropping the column:

```php
$table->dropIndex(['is_admin']);
$table->dropColumn('is_admin');
```

Also add a migration test or CI matrix if SQLite remains a supported test database.

### 12. Product Controller Contains Listing Validation and Normalization Logic

- File: `app/Http/Controllers/ProductController.php`
- Severity: Medium
- Current Problem: `index()` builds dynamic validation rules, validates filter input, normalizes legacy aliases, invokes the service, and prepares view options.
- Why it violates the requirement: Thin controllers and separation of concerns are weakened. Listing filter validation and normalization are not reusable and are harder to test in isolation.
- Recommended Fix: Move listing validation into a dedicated `ProductFilterRequest` and move normalization into a filter DTO or value object consumed by `ProductService`.

### 13. Repository Pattern Is Not Implemented

- Files:
  - `app/Services/ProductService.php`
  - `app/Http/Controllers/ProductController.php`
  - `app/Http/Controllers/Admin/UserRoleController.php`
- Severity: Medium
- Current Problem: Services and controllers call Eloquent models directly. There is no repository interface or concrete repository implementation.
- Why it violates the requirement: The assessment explicitly includes Repository Pattern. Current code is testable through feature tests, but persistence is coupled to Eloquent.
- Recommended Fix: Introduce repository interfaces only where useful, for example:
  - `ProductRepositoryInterface`
  - `EloquentProductRepository`
  - bind the interface in a service provider
  - inject the repository into `ProductService`

Avoid adding repositories mechanically for trivial auth flows unless the project standard requires it.

### 14. Product Model Contains Business Workflow Logic

- File: `app/Models/Product.php`
- Severity: Medium
- Current Problem: The model generates SKUs, mutates stock status during lifecycle events, defines search/filter/sort scopes, and contains persistence-adjacent business behavior.
- Why it violates the requirement: Eloquent scopes are acceptable, but SKU generation and inventory status computation make the model carry workflow responsibility. This weakens separation of concerns as the product domain grows.
- Recommended Fix: Move SKU generation to a dedicated `ProductSkuGenerator` and stock derivation to a small domain service or method invoked by `ProductService`. Keep simple query scopes on the model.

### 15. SKU Collision Detection Uses Driver-Specific String Matching

- File: `app/Services/ProductService.php`
- Severity: Low
- Current Problem: `isSkuUniqueConstraintViolation()` checks SQL states, driver code `1062`, and substrings such as `products_sku_unique` and `products.sku`.
- Why it violates the requirement: This is maintainable enough for MariaDB/MySQL, but brittle across database drivers and schema name changes.
- Recommended Fix: Prefer deterministic SKU reservation under a dedicated sequence/counter table, or catch known SQLSTATE values only and keep database-driver-specific handling behind a small adapter.

### 16. `ProductRequest` Validates But Drops `stock_status`

- File: `app/Http/Requests/ProductRequest.php`
- Severity: Low
- Current Problem: `stock_status` has validation rules but is omitted from the overridden `validated()` return value.
- Why it violates the requirement: This is intentional because status is derived from quantity, but it is confusing and can mislead maintainers into thinking submitted stock status matters.
- Recommended Fix: Remove `stock_status` from the request rules, or add a comment explaining that it is accepted only for compatibility/UI but ignored by persistence.

### 17. Admin User Query Uses Raw Ordering

- File: `app/Http/Controllers/Admin/UserRoleController.php`
- Severity: Low
- Current Problem: The user listing uses `orderByRaw('role = ? desc', [UserRole::Admin->value])`.
- Why it violates the requirement: It is parameter-bound and not SQL-injection vulnerable, but raw SQL reduces portability and readability.
- Recommended Fix: Use a query expression abstraction where available, or encapsulate the ordering in a named scope such as `User::adminsFirst()`.

### 18. Product Search Uses Raw SQL for Case-Insensitive LIKE

- File: `app/Models/Product.php`
- Severity: Low
- Current Problem: Search uses `whereRaw("LOWER(...) LIKE ? ESCAPE '\\\\'", [$like])`.
- Why it violates the requirement: It is parameter-bound and escapes LIKE wildcards, so SQL injection protection is present. However, raw SQL is less portable and harder to index efficiently.
- Recommended Fix: Use database collation/index strategy for case-insensitive search, or use Laravel Scout/full-text search if search requirements grow.

### 19. `ConfirmablePasswordController` Does Not Validate Password Field Before Guard Validation

- File: `app/Http/Controllers/Auth/ConfirmablePasswordController.php`
- Severity: Low
- Current Problem: The controller directly reads `$request->password` in `Auth::guard('web')->validate(...)`.
- Why it violates the requirement: Secure authentication is functionally present, but explicit request validation provides better error consistency and clearer input contracts.
- Recommended Fix: Add validation before authentication:

```php
$request->validate([
    'password' => ['required', 'string'],
]);
```

### 20. Duplicate Test Styles Increase Maintenance Cost

- Files:
  - `tests/Feature/Auth/*Test.php`
  - `tests/Feature/Auth/*PestTest.php`
  - `tests/Feature/ExampleTest.php`
  - `tests/Unit/ExampleTest.php`
- Severity: Low
- Current Problem: The test suite contains both default PHPUnit-style tests and Pest-style tests for overlapping auth behavior, plus generated example tests.
- Why it violates the requirement: Duplication increases maintenance cost and can make future behavior changes require edits in multiple places.
- Recommended Fix: Standardize on Pest for this project and remove redundant generated PHPUnit examples once equivalent Pest coverage exists.

### 21. `manageProducts` Gate Is Defined But Not Used

- File: `app/Providers/AppServiceProvider.php`
- Severity: Low
- Current Problem: `Gate::define('manageProducts', ...)` exists, while product authorization uses `ProductPolicy`.
- Why it violates the requirement: Unused authorization paths reduce maintainability and can confuse future contributors about the source of truth.
- Recommended Fix: Remove the unused gate or use it consistently in policy methods if it represents a domain concept.

### 22. Deployment Script Does Not Enforce Production Safety Checks

- File: `scripts/deploy.sh`
- Severity: Medium
- Current Problem: The script runs migrations and caches config/routes/views, but does not verify `APP_ENV`, `APP_DEBUG`, HTTPS URL, secure cookies, or non-default Docker secrets.
- Why it violates the requirement: Secure production deployment should fail fast on unsafe configuration.
- Recommended Fix: Add guard checks before `docker compose up`, for example:
  - reject `APP_DEBUG=true` when `APP_ENV=production`
  - reject default DB/root passwords
  - require `APP_URL` to start with `https://`
  - require `SESSION_SECURE_COOKIE=true`

## Positive Findings

- Controllers consistently use route model binding for product and user resources.
- Product write operations use `ProductRequest`.
- Product persistence is mediated by `ProductService`.
- Product policies and admin role middleware enforce role-based access.
- Authentication uses Laravel session auth, rate-limited login/registration/password flows, email verification, session regeneration, and password hashing.
- SQL injection protection is generally strong: raw query locations use bound parameters, filter values are validated, and sort options are whitelisted.
- XSS protection is generally strong: Blade escaped output is used by default, and the one unescaped product description output is sanitized through `RichTextSanitizer`.
- CSRF coverage is present on all reviewed POST/PUT/PATCH/DELETE Blade forms.
- Feature tests cover product CRUD, validation, inventory status, search, filtering, sorting, and authorization.
- Composer and npm audits report no known dependency vulnerabilities.

## Recommended Priority Plan

1. Fix the Pint failure in `EnsureUserHasRole`.
2. Remove `role` from `User` fillable and `stock_status` from `Product` fillable.
3. Rotate the local mail credential found in `.env` if it is real.
4. Add deployment safety checks for debug mode, HTTPS, secure cookies, and default secrets.
5. Extract product listing validation into `ProductFilterRequest`.
6. Decide whether the project truly requires repositories; if yes, introduce a focused product repository interface.
7. Fix SQLite migration portability or document MariaDB-only test execution.

