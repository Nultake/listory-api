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

- [ ] Project setup with Sail (Docker)
- [ ] GitHub Actions CI/CD pipeline
- [ ] Larastan configuration
- [ ] Database migrations (all tables)
- [ ] Models with relationships
- [ ] Enums (MediaType, InvitationStatus, CollectionRole)
- [ ] Auth endpoints (register, login, logout, me)
- [ ] Tests for auth

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

## Important Notes

- This is a BACKEND-ONLY project. No Blade views, no frontend.
- Every response must go through API Resources.
- Always think about the mobile client consuming this API.
- Keep the Flutter teammate in mind — API must be well-documented.
- When in doubt, follow Laravel's official conventions.
- Use PHP 8.4 features: enums, readonly properties, named arguments, match expressions, property hooks.
- ALL code must pass Larastan level 6 and all tests before merging.
