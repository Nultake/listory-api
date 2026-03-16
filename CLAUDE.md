# Project: Listory

## Overview

Listory is a media rating and commenting platform. Users can rate and review video games, series, and films. Users can create **collections** — curated groups of media items — and invite friends as co-users to those collections, allowing everyone to see each other's ratings and comments for items within the collection. Items can also exist independently outside of any collection.

**Repository:** `listory-api` (backend only)
**Related repos:** `listory-mobile` (Flutter, separate team member)

## Tech Stack

### Backend (this repository)

- **Framework:** Laravel 12.x (latest)
- **PHP Version:** 8.4+
- **Database:** PostgreSQL via Supabase
- **Authentication:** Laravel Sanctum (token-based for mobile API)
- **Containerization:** Laravel Sail (Docker)
- **API Style:** RESTful JSON API
- **CI/CD:** GitHub Actions (tests + Larastan static analysis)
- **Static Analysis:** Larastan (PHPStan for Laravel) — level 6 minimum
- **External APIs (future integration):**
    - TMDB (The Movie Database) — films & series metadata
    - IGDB (Internet Game Database via Twitch) — video game metadata

## Architecture Principles

### Code Organization

- Follow Laravel conventions strictly (no custom folder structures unless necessary)
- Use Form Requests for ALL validation — never validate in controllers
- Use API Resources (JsonResource) for ALL API responses — never return models directly
- Use Service classes for business logic — keep controllers thin
- Use Action classes for single-purpose operations (e.g., `CreateReviewAction`, `InviteToCollectionAction`)
- Repository pattern is NOT used — use Eloquent directly in Services
- Fill all phpdoc property to all model classes with related relations

### Naming Conventions

- Controllers: `{Model}Controller` (e.g., `ReviewController`)
- Form Requests: `{Action}{Model}Request` (e.g., `StoreReviewRequest`, `UpdateReviewRequest`)
- Resources: `{Model}Resource` (e.g., `ReviewResource`, `MediaItemResource`)
- Services: `{Model}Service` (e.g., `ReviewService`, `MediaItemService`)
- Actions: `{Action}{Model}Action` (e.g., `CreateReviewAction`)
- Policies: `{Model}Policy` (e.g., `ReviewPolicy`)
- Database tables: plural snake_case (e.g., `media_items`, `collection_user`)
- API routes: plural kebab-case (e.g., `/api/v1/media-items`)

### API Design Rules

- ALL endpoints are prefixed with `/api/v1/`
- Use proper HTTP methods: GET (read), POST (create), PUT/PATCH (update), DELETE (delete)
- Use HTTP status codes correctly: 200, 201, 204, 400, 401, 403, 404, 422, 500
- Paginate all list endpoints (default 15 per page)
- Use consistent error response format:

```json
{
    "message": "Human readable message",
    "errors": {
        "field": ["Validation error"]
    }
}
```

- Use consistent success response via API Resources
- Support `?include=` parameter for eager loading relations (e.g., `?include=reviews,genres`)
- Support `?filter[type]=game` for filtering
- Support `?sort=-created_at` for sorting

### Authentication Flow

- Registration: POST `/api/v1/auth/register` → returns user + token
- Login: POST `/api/v1/auth/login` → returns user + token
- All protected routes require `Authorization: Bearer {token}` header
- Use Sanctum's `auth:sanctum` middleware

### Database Design Philosophy

- All tables use UUIDs as primary keys (better for distributed systems, looks professional)
- Use `external_id` and `external_source` nullable columns on `media_items` for future API integration
- Use `metadata` JSON column on `media_items` for flexible external API data storage
- Soft deletes on `users`, `reviews`, and `media_items`
- Always add proper indexes on foreign keys and frequently queried columns

### Testing

- Write Feature tests for ALL API endpoints
- Write Unit tests for Services and Actions
- Use `RefreshDatabase` trait in tests
- Factory for every model
- Minimum test structure: test happy path, test validation, test authorization
- Tests MUST pass before merge (enforced by CI)

### Static Analysis

- Larastan level 6 minimum
- No ignored errors without justification in `phpstan-baseline.neon`
- Run `./vendor/bin/phpstan analyse` before committing

## Key Domain Concepts

### Media Items

- Three types: `game`, `film`, `series`
- Can be created manually by users (for now)
- Future: auto-populated from TMDB/IGDB via search
- Have genres, cover images, release dates, descriptions
- `external_id` + `external_source` fields ready for API integration
- Items can exist independently — they do NOT require a collection

### Reviews

- A user can review a media item once (unique constraint: user_id + media_item_id)
- Contains: rating (1-10 scale), comment (optional text), spoiler flag
- Shown in user's personal library
- Also visible within collections to co-users

### Collections

- A user creates a collection (e.g., "Anime We Watched", "Co-op Games 2025")
- Collections have a name, description, cover image, and optional visibility setting
- Collections contain many media items (many-to-many)
- Collections have members/co-users (many-to-many with users via `collection_user` pivot)
- The creator is the **owner** with full control (edit, delete, manage members)
- Co-users can: view all members' reviews, add items, add their own reviews
- When viewing a collection, each item shows ALL members' reviews side by side
- A user can be invited to join a collection via invitation system

### Collection Invitations

- Owner invites another user to join a collection
- Invitation has: status (pending/accepted/declined), optional message
- When accepted, user becomes a member of the collection
- Members can then see each other's reviews for items in that collection

### User Library

- Aggregate view of all media items a user has reviewed
- Shows personal rating + comment
- Filterable by media type (games/films/series)
- Separate from collections — library shows ALL reviewed items regardless of collections

## CI/CD Pipeline (GitHub Actions)

### On every push/PR to `main`:

1. **Lint & Static Analysis:** Larastan (PHPStan level 6)
2. **Tests:** PHPUnit/Pest with PostgreSQL service container
3. **Both must pass** before merge is allowed

### Branch Strategy

- `main` — production-ready code
- Feature branches: `feature/{description}` (e.g., `feature/collection-crud`)
- Bugfix branches: `fix/{description}`
- PR required to merge into `main`

## Environment Setup

### Prerequisites

- Docker Desktop installed
- Composer installed locally (for initial setup)

## File Structure (Expected)

```
app/
├── Actions/
│   ├── Review/
│   │   ├── CreateReviewAction.php
│   │   └── UpdateReviewAction.php
│   ├── Collection/
│   │   ├── CreateCollectionAction.php
│   │   ├── AddItemToCollectionAction.php
│   │   └── InviteToCollectionAction.php
│   └── MediaItem/
│       └── CreateMediaItemAction.php
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── V1/
│   │           ├── AuthController.php
│   │           ├── MediaItemController.php
│   │           ├── ReviewController.php
│   │           ├── CollectionController.php
│   │           ├── CollectionItemController.php
│   │           ├── CollectionMemberController.php
│   │           ├── CollectionInvitationController.php
│   │           └── UserLibraryController.php
│   ├── Requests/
│   │   ├── Auth/
│   │   │   ├── RegisterRequest.php
│   │   │   └── LoginRequest.php
│   │   ├── MediaItem/
│   │   │   ├── StoreMediaItemRequest.php
│   │   │   └── UpdateMediaItemRequest.php
│   │   ├── Review/
│   │   │   ├── StoreReviewRequest.php
│   │   │   └── UpdateReviewRequest.php
│   │   └── Collection/
│   │       ├── StoreCollectionRequest.php
│   │       ├── UpdateCollectionRequest.php
│   │       ├── AddItemRequest.php
│   │       └── InviteMemberRequest.php
│   └── Resources/
│       ├── MediaItemResource.php
│       ├── ReviewResource.php
│       ├── CollectionResource.php
│       ├── CollectionDetailResource.php
│       ├── CollectionInvitationResource.php
│       └── UserLibraryResource.php
├── Models/
│   ├── User.php
│   ├── MediaItem.php
│   ├── Review.php
│   ├── Collection.php
│   ├── CollectionInvitation.php
│   └── Genre.php
├── Policies/
│   ├── ReviewPolicy.php
│   ├── MediaItemPolicy.php
│   ├── CollectionPolicy.php
│   └── CollectionInvitationPolicy.php
├── Services/
│   ├── MediaItemService.php
│   ├── ReviewService.php
│   └── CollectionService.php
├── Enums/
│   ├── MediaType.php          (game, film, series)
│   ├── InvitationStatus.php   (pending, accepted, declined)
│   └── CollectionRole.php     (owner, member)
database/
├── migrations/
│   ├── create_users_table.php
│   ├── create_media_items_table.php
│   ├── create_genres_table.php
│   ├── create_genre_media_item_table.php
│   ├── create_reviews_table.php
│   ├── create_collections_table.php
│   ├── create_collection_media_item_table.php
│   ├── create_collection_user_table.php
│   └── create_collection_invitations_table.php
├── factories/
│   ├── MediaItemFactory.php
│   ├── ReviewFactory.php
│   ├── CollectionFactory.php
│   └── CollectionInvitationFactory.php
└── seeders/
    ├── GenreSeeder.php
    └── DatabaseSeeder.php
routes/
└── api.php
tests/
├── Feature/
│   ├── Auth/
│   │   ├── RegisterTest.php
│   │   └── LoginTest.php
│   ├── MediaItem/
│   │   └── MediaItemTest.php
│   ├── Review/
│   │   └── ReviewTest.php
│   └── Collection/
│       ├── CollectionCrudTest.php
│       ├── CollectionItemTest.php
│       ├── CollectionMemberTest.php
│       └── CollectionInvitationTest.php
└── Unit/
    ├── Services/
    └── Actions/
```

## Development Phases

### Phase 1: Foundation

- [x] Project setup with Sail (Docker)
- [x] GitHub Actions CI/CD pipeline
- [x] Larastan configuration
- [x] Database migrations (all tables)
- [x] Models with relationships
- [x] Enums (MediaType, InvitationStatus, CollectionRole)
- [x] Auth endpoints (register, login, logout, me, verify-email, resend-verification)
- [x] Tests for auth

### Phase 2: Core Features

- [ ] Media Items CRUD
- [ ] Reviews CRUD (with unique constraint)
- [ ] User Library endpoint
- [ ] Genre seeder + association
- [ ] Tests for all CRUD

### Phase 3: Collections System

- [ ] Collections CRUD (create, update, delete)
- [ ] Add/remove items to collections
- [ ] Collection invitation send/accept/decline
- [ ] Collection member management
- [ ] Collection detail view (items with all members' reviews)
- [ ] Tests for collection flow

### Phase 4: External API Integration

- [ ] TMDB service (films + series search & import)
- [ ] IGDB service (game search & import)
- [ ] Media search endpoint (searches external APIs)
- [ ] Auto-populate media item from external source

### Phase 5: Enhancements

- [ ] User profiles & avatars
- [ ] Activity feed
- [ ] Statistics (average ratings, most reviewed, etc.)
- [ ] Push notification infrastructure
- [ ] Advanced filtering & search

## Coding Preferences

- **Use double quotes `"` for all PHP strings** — never use single quotes `'`
- **Use Eloquent models for all database queries** — never use the `DB` facade unless necessary. All database interactions must go through model classes.
- **Use `assertModelExists` / `assertModelMissing`** in tests instead of `assertDatabaseHas` / `assertDatabaseMissing`. Fetch the model via Eloquent query when needed.

## Important Notes

- This is a BACKEND-ONLY project. No Blade views, no frontend.
- Every response must go through API Resources.
- Always think about the mobile client consuming this API.
- Keep the Flutter teammate in mind — API must be well-documented.
- When in doubt, follow Laravel's official conventions.
- Use PHP 8.4 features: enums, readonly properties, named arguments, match expressions, property hooks.
- ALL code must pass Larastan level 6 and all tests before merging.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5.3
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v11

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `vendor/bin/sail npm run build`, `vendor/bin/sail npm run dev`, or `vendor/bin/sail composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan Commands

- Run Artisan commands directly via the command line (e.g., `vendor/bin/sail artisan route:list`, `vendor/bin/sail artisan tinker --execute "..."`).
- Use `vendor/bin/sail artisan list` to discover available commands and `vendor/bin/sail artisan [command] --help` to check parameters.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Debugging

- Use the `database-query` tool when you only need to read from the database.
- Use the `database-schema` tool to inspect table structure before writing migrations or models.
- To execute PHP code for debugging, run `vendor/bin/sail artisan tinker --execute "your code here"` directly.
- To read configuration values, read the config files directly or run `vendor/bin/sail artisan config:show [key]`.
- To inspect routes, run `vendor/bin/sail artisan route:list` directly.
- To check environment variables, read the `.env` file directly.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results will be returned first.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.

## Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - `public function __construct(public GitHub $github) { }`
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<!-- Explicit Return Types and Method Params -->
```php
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
```

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

## Comments

- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless the logic is exceptionally complex.

## PHPDoc Blocks

- Add useful array shape type definitions when appropriate.

=== sail rules ===

# Laravel Sail

- This project runs inside Laravel Sail's Docker containers. You MUST execute all commands through Sail.
- Start services using `vendor/bin/sail up -d` and stop them with `vendor/bin/sail stop`.
- Open the application in the browser by running `vendor/bin/sail open`.
- Always prefix PHP, Artisan, Composer, and Node commands with `vendor/bin/sail`. Examples:
    - Run Artisan Commands: `vendor/bin/sail artisan migrate`
    - Install Composer packages: `vendor/bin/sail composer install`
    - Execute Node commands: `vendor/bin/sail npm run dev`
    - Execute PHP scripts: `vendor/bin/sail php [script]`
- View all available Sail commands by running `vendor/bin/sail` without arguments.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `vendor/bin/sail artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `vendor/bin/sail artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `vendor/bin/sail artisan list` and check their parameters with `vendor/bin/sail artisan [command] --help`.
- If you're creating a generic PHP class, use `vendor/bin/sail artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `vendor/bin/sail artisan make:model --help` to check the available options.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

## Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

## Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `vendor/bin/sail artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `vendor/bin/sail npm run build` or ask the user to run `vendor/bin/sail npm run dev` or `vendor/bin/sail composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/sail bin pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/sail bin pint --test --format agent`, simply run `vendor/bin/sail bin pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `vendor/bin/sail artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `vendor/bin/sail artisan test --compact`.
- To run all tests in a file: `vendor/bin/sail artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `vendor/bin/sail artisan test --compact --filter=testName` (recommended after making a change to a related file).

</laravel-boost-guidelines>
