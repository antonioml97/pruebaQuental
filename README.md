# Rick and Morty API

Initial Laravel backend for the Rick and Morty synchronization technical exercise. This phase contains project tooling and architectural boundaries only; synchronization and API behavior are not implemented yet.

## Requirements

- Docker
- Docker Compose
- Node.js and npm only for development tooling such as Husky

On Windows, run Git, Sail, and Docker commands from WSL2.

## Installation

```sh
git clone <repository-url>
cd <repository-directory>
cp .env.example .env
composer install
npm install
./vendor/bin/sail artisan key:generate
```

`npm install` runs the `prepare` script and configures Husky automatically.

## Running the application

```sh
./vendor/bin/sail up -d
```

## Stop

```sh
./vendor/bin/sail down
```

## Database migrations

```sh
./vendor/bin/sail artisan migrate
```

## Tests

```sh
./vendor/bin/sail artisan test
```

## Git hooks

Husky configures a `pre-push` hook. Every push runs the Laravel test suite through Sail; Git cancels the push when a test fails.

## Architecture

- `app/Domain` contains domain contracts and representations.
- `app/Services` contains external integration and application services.
- Controllers remain limited to HTTP transport concerns.
- Models represent persistence through Eloquent.
- External integration remains decoupled from controllers and persistence.

Only directories backed by current source files are committed. DTOs, exceptions, API controllers, requests, resources, commands, and their dedicated test directories will be added when their contracts or behavior are defined.
