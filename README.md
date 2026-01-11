# ROBLE

A starter kit for developing monolithic web applications based on Laravel, Inertia.js, Vue.js, and Tailwind CSS.

## Language Notice

**Documentation**: English (for international audience)  
**User Interface**: Spanish (Venezuelan) - The UI is in Spanish as this project was originally developed for Venezuelan users and organizations. We welcome contributions to add multi-language support.

## Built With 🛠️

- [Laravel](https://laravel.com/docs)
- [Vue](https://vuejs.org)
- [shadcn-vue](https://www.shadcn-vue.com)
- [Inertia](https://inertiajs.com)
- [Tailwind CSS](https://tailwindcss.com/docs)
- [PostgreSQL](https://www.postgresql.org)

## Users and Roles 👥

In Roble, no users are created when the database is first seeded. Only the minimum necessary profiles (roles) are created, which are:

1. **Superuser**: Has access to any system route and can execute any action that does not violate system stability. It is a protected, read-only profile.
2. **System Administrator**: Manages basic data, security, and system monitoring. It is an editable and even deletable profile.

_The deletion of roles and permissions is irreversible_. Once deleted, roles or permissions cannot be recovered; they must be registered again.

From the created superuser, you can create new roles and users, as well as manage any system process.

It should be noted that users can also be created in a self-managed way by the institution's own active employees. However, they will be created without associated profiles, so they will only have access to the user's own menu in the system.

## Features 🤩

_Note_: If you prefer, understand the word 'management' as `CRUD` (create, read, update, and delete records or data). However, exporting data to files is also part of data management in ROBLE.

- Basic dashboard with summary charts of users, roles, and other basic data.
- Management of:
  - Basic organization data, as well as its respective administrative units
  - Permissions
  - Roles (user profiles)
  - Users
  - System maintenance mode
- Query and export of user activity traces.
- Query, clearing/deletion, and export of system debug logs.
- Real-time notifications of actions performed by users.

## Local Environment Installation 🚀

This guide covers installation using **Laravel Herd** (recommended for macOS and Windows) and **Laravel Sail** (Docker-based, for any operating system).

### Prerequisites

Make sure you have the software corresponding to your chosen environment installed:

| Software              | Herd Environment | Sail Environment |
| --------------------- | :--------------: | :--------------: |
| **Laravel Herd**      |        ✅        |                  |
| **PostgreSQL Server** |        ✅        |                  |
| **Node.js and npm**   |        ✅        |        ✅        |
| **Composer**          |        ✅        |                  |
| **Docker Engine**     |                  |        ✅        |

> **Note for Herd**: It is recommended to use [DBngin](https://dbngin.com/) to easily manage your PostgreSQL server.

### Step 1: Clone the Repository

```sh
git clone REPOSITORY_URL
cd roble
```

> **Note for Herd**: If you use Laravel Herd, clone the repository inside the folder that Herd is monitoring (normally `~/Herd`).

### Step 2: Configure Environment Variables (.env)

This project requires credentials for two databases and for the WebSocket server (Laravel Reverb).

**For Local Development (Herd or Sail):**

Simply copy the example environment file and edit it:

```sh
cp .env.example .env
```

Then edit the `.env` file to configure at least the `DB_*`, `DB_ORG_*`, and `REVERB_*` variables according to your local environment.

**For Production/Staging/QA Deployments:**

Use the interactive installation script:

```sh
./install.sh
```

This script will guide you through configuring all necessary variables for production environments.

### Step 3: Install Dependencies

**For Herd Environment:**

Run the following commands in your terminal:

```sh
composer install
npm install
```

**For Sail Environment:**

1. First, start the Sail containers. The first time may take several minutes while Docker images are downloaded.
   ```sh
   sail up -d
   ```
2. Once the containers are running, install the dependencies _inside_ them:
   ```sh
   sail composer install
   sail npm install
   ```

### Step 4: Run the Application Installer

This project includes a command to automate application preparation.

> **⚠️ VERY IMPORTANT WARNING ⚠️**  
> This command **will delete all data** from your main database and replace it with initial test data (`migrate:fresh --seed`). Use it only in the initial setup.

| Herd Environment          | Sail Environment           |
| ------------------------- | -------------------------- |
| `php artisan app:install` | `sail artisan app:install` |

This command will take care of:

- Generating the application key.
- Clearing and generating configuration caches.
- Creating the symbolic link to `storage`.
- Running migrations and database _seeders_.

### Step 5: Start Background Services

For real-time notifications and queued tasks to work, you must start two processes. It is recommended to open two separate terminals in the project root to run each one.

| Service            | Command for Herd           | Command for Sail            |
| :----------------- | :------------------------- | :-------------------------- |
| **Laravel Reverb** | `php artisan reverb:start` | `sail artisan reverb:start` |
| **Task Queue**     | `php artisan queue:listen` | `sail artisan queue:listen` |

### Step 6: Create the Initial Superuser

With the environment already configured and services running, the final step is to create the first user with the `Superuser` role.

1. Open your web browser.
2. Visit your project URL followed by `/su-install`.
   - **URL with Herd:** `http://roble.test/su-install`
   - **URL with Sail:** `http://localhost/su-install`
3. Follow the web wizard instructions to create your user.

### Ready!

Once the Superuser is created, the authentication system will be enabled. Now you can go to the `/login` route to log in with the credentials you just created.

## CI/CD Pipeline 🚀

This project uses **GitHub Actions** for continuous integration and deployment. The pipeline automatically runs tests, linters, and manages semantic versioning.

### Workflows

#### 1. **Tests** (`tests.yml`)

- **Triggers:** Push or Pull Request to `develop` or `main`
- **Actions:**
  - Runs PHPUnit/Pest tests
  - Builds frontend assets
  - Caches dependencies for faster execution

#### 2. **Linter** (`lint.yml`)

- **Triggers:** Push or Pull Request to `develop` or `main`
- **Actions:**
  - Runs PHP Pint (code style fixer)
  - Runs ESLint (JavaScript linter)
  - Runs Prettier (code formatter)

#### 3. **Auto-Versioning** (`version.yml`)

- **Triggers:** Push to `main` branch only
- **Actions:**
  - Analyzes commits since last tag
  - Calculates next semantic version
  - Creates Git tag automatically
  - Updates `CHANGELOG.md` and `.env.example`
  - Creates GitHub Release

### Semantic Versioning

This project follows [Semantic Versioning](https://semver.org/) and [Conventional Commits](https://www.conventionalcommits.org/).

**Commit types that increment version:**

- `fix:` → PATCH (1.0.0 → 1.0.1)
- `feat:` → MINOR (1.0.0 → 1.1.0)
- `feat!:` or `BREAKING CHANGE:` → MAJOR (1.0.0 → 2.0.0)

**Commit types that do NOT increment version:**

- `docs:`, `style:`, `refactor:`, `test:`, `chore:`, `perf:`

**Example commits:**

```bash
git commit -m "fix: correct login validation"
git commit -m "feat: implement PDF export module"
git commit -m "feat!: change database schema"
```

**Test versioning locally:**

```bash
npm run version:check  # Dry-run to see next version
```

For more details, see [docs/CONVENTIONAL_COMMITS.md](docs/CONVENTIONAL_COMMITS.md).

## Contributors ✒️

- Maikel Carballo - [GitHub](https://github.com/kimael-code) | [GitLab](https://gitlab.com/profemaik) | [Portfolio](https://maikel-dev.vercel.app)

## Contribute - Your Ideas Can Bring Significant Improvements 🤓

If you consider that this documentation is incomplete or could be improved:

1. Verify that you can have access to the repository
2. Clone it
3. Create a new branch
4. Make the corrections you deem pertinent to this file
5. Publish your new branch with `git push`

Or if you prefer, you can create an issue in the repository stating your corrections or improvements.
