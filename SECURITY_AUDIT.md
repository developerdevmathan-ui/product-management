# Laravel Security Audit

Audit date: 2026-06-22

## Scope

Reviewed first-party application code for:

- Controllers
- Models
- Requests
- Blade views
- Middleware/bootstrap configuration
- Repositories
- Database migrations/factories where security-relevant

Excluded third-party source under `vendor/` and `node_modules/`.

## Verification

Commands run:

```bash
rg -n "whereRaw|orderByRaw|selectRaw|havingRaw|groupByRaw|fromRaw|DB::raw|statement\(|unprepared\(" app routes resources database config bootstrap
rg -n "\{!!|@richText" resources app
rg -n "withoutMiddleware|ValidateCsrf|VerifyCsrf|except\(.*csrf|csrf" app routes bootstrap config tests
rg -n "type=.file|multipart/form-data|mimes:|mimetypes:|image\||file\|" app resources routes config
php artisan test
vendor/bin/pint app/Repositories/ProductRepository.php app/Http/Controllers/Admin/UserRoleController.php app/Models/User.php app/Providers/AppServiceProvider.php resources/views/products/show.blade.php
```

Results:

- Raw SQL helper scan: no remaining `whereRaw`, `orderByRaw`, `selectRaw`, `DB::raw`, `statement`, or `unprepared` usages in first-party app paths.
- Raw Blade output scan: no `{!! !!}` output remains; product rich text uses `@richText`.
- CSRF bypass scan: no CSRF middleware bypasses found.
- File upload scan: no file upload fields or multipart forms found.
- Tests: `109 passed`, `344 assertions`.
- Pint: passed for touched security files.

## Summary

The application now demonstrates protection against:

- SQL Injection: queries use Eloquent/query builder methods, validated inputs, whitelisted sorts, and no string-concatenated raw SQL.
- XSS: normal Blade escaping is used; product rich text rendering is sanitized through a centralized Blade directive.
- CSRF: POST/PUT/PATCH/DELETE forms include `@csrf`, Laravel web middleware is active, and no CSRF bypasses were found.
- Mass Assignment: sensitive `User::role` is no longer fillable; product fillable excludes derived `stock_status`.
- File Upload Security: no file upload surface currently exists. If uploads are introduced, dedicated Form Request rules must validate file type, MIME, size, and storage path.

## Issues And Fixes

### 1. Raw SQL In Product Search

- Risk Level: Medium
- Issue: `app/Repositories/ProductRepository.php` used `whereRaw("LOWER(...) LIKE ?")` for product search. The previous implementation used parameter binding, so immediate SQL injection risk was controlled, but raw SQL was still unnecessary and less portable.
- Fix: Replaced raw SQL conditions with Laravel query builder `whereLike()` / `orWhereLike()` calls.
- Code Changes:
  - Updated `ProductRepository::paginate()`.
  - Removed all product `whereRaw()` search clauses.

### 2. Raw SQL In Admin User Ordering

- Risk Level: Low
- Issue: `app/Http/Controllers/Admin/UserRoleController.php` used `orderByRaw('role = ? desc', [...])`. It was parameter-bound, but still raw SQL.
- Fix: Replaced with query builder ordering:

```php
->orderBy('role')
->orderBy('name')
```

- Code Changes:
  - Updated `UserRoleController::index()`.

### 3. Raw HTML Product Description Rendering

- Risk Level: High
- Issue: `resources/views/products/show.blade.php` previously rendered product description using raw Blade output and called the sanitizer inline.
- Fix: Added a centralized `@richText` Blade directive that always passes content through `RichTextSanitizer`.
- Code Changes:
  - Added directive in `app/Providers/AppServiceProvider.php`.
  - Replaced inline raw output with:

```blade
@richText($product->description)
```

### 4. User Role Mass Assignment

- Risk Level: High
- Issue: `app/Models/User.php` allowed `role` in the fillable list. This creates future privilege-escalation risk if any endpoint later mass assigns request data to `User`.
- Fix: Removed `role` from fillable.
- Code Changes:

```php
#[Fillable(['name', 'email', 'password'])]
```

- Authorized role changes still use explicit `forceFill()` in `UserRoleController`.

### 5. Product Derived Field Mass Assignment

- Risk Level: Medium
- Issue: `stock_status` is a derived inventory field and should not be mass assignable.
- Fix: Product fillable excludes `stock_status`; `ProductService` derives stock status from quantity and passes the controlled value to the repository.
- Code State:

```php
#[Fillable(['sku', 'title', 'description', 'price', 'quantity', 'date_available'])]
```

### 6. Product Repository Uses `forceFill()`

- Risk Level: Low
- Issue: `ProductRepository` uses `forceFill()` to persist service-shaped data, including derived `stock_status`.
- Fix: Kept `forceFill()` only inside the repository boundary. The data passed into it is restricted by `ProductService::payload()` and Form Requests.
- Recommended Ongoing Control: Do not call repository `create()` / `update()` directly from controllers with request input. Keep writes routed through `ProductService`.

### 7. File Upload Validation

- Risk Level: Informational
- Issue: No file upload fields, multipart forms, upload controllers, or file validation rules exist.
- Fix: No code change required.
- Required Future Control: If uploads are added, use Form Request rules like:

```php
'file' => ['required', 'file', 'mimes:jpg,png,pdf', 'max:2048']
```

Store files using Laravel disks, generate server-side names, and never trust client filenames or MIME alone.

## CSRF Review

All reviewed non-GET Blade forms contain `@csrf`, including:

- Authentication forms
- Email verification notification and logout forms
- Profile update/password/delete forms
- Product create/update/delete forms
- Admin role update forms

No `withoutMiddleware()` or CSRF exception bypass was found in first-party application code.

## XSS Review

Safe patterns found:

- Standard Blade escaped output `{{ ... }}` is used across views.
- Product rich text is sanitized with `RichTextSanitizer`.
- `RichTextSanitizer` allows a constrained tag/attribute list and restricts URI schemes to `http`, `https`, and `mailto`.
- CKEditor link configuration adds safe `target`/`rel` behavior for external links.

Implemented hardening:

- Removed raw `{!! ... !!}` product description output from Blade.
- Added `@richText` directive to centralize safe rich-text rendering.

## SQL Injection Review

Safe patterns found:

- Product sorting uses a whitelist in repository code.
- Search/filter values are validated by Form Requests.
- Product filtering uses query builder methods.
- SKU checks use query builder equality conditions.
- No raw query helper usage remains after refactor.

Implemented hardening:

- Replaced product search raw SQL with `whereLike()`.
- Replaced admin user raw ordering with `orderBy()`.

## Mass Assignment Review

Safe patterns found:

- `User` fillable no longer includes `role`.
- `Product` fillable no longer includes `stock_status`.
- Product writes are routed through `ProductService`, which restricts payload fields.
- Admin role writes are explicitly authorized and use explicit `forceFill()`.

Remaining controlled `forceFill()` usage:

- `UserRoleController`: authorized role update only.
- `NewPasswordController`: password reset callback only.
- `ProductRepository`: controlled product persistence from `ProductService`.

## Production Recommendations

1. Keep `APP_DEBUG=false` in production.
2. Use HTTPS and `SESSION_SECURE_COOKIE=true` in production.
3. Keep secrets out of `.env` files shared across machines.
4. Add security headers such as CSP, HSTS, Referrer-Policy, and Permissions-Policy at the web server layer.
5. Add CI checks for:
   - `vendor/bin/pint --test`
   - `php artisan test`
   - raw SQL scan
   - raw Blade output scan
   - dependency audit

