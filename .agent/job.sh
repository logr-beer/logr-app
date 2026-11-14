# logr-app job recipe (sourced by worker/run-job.sh; see its header for the hooks and variables).
# Laravel 12 + Livewire 3 (Volt) + Blade/Vite + Tailwind, SQLite, no service containers. Pint, PHPUnit, Playwright.

# Playwright (playwright.config.ts) targets the worker's app instead of the DDEV default.
export PLAYWRIGHT_BASE_URL=http://127.0.0.1:$APP_PORT

prepare() {
  local db="${SHARED_DIR:-$PWD/database}/${DB_NAME:-database}.sqlite"
  cp .env.example .env
  cat >> .env <<ENV

# ---- agent worker overrides (bead $JOB_ID, slot $SLOT)
APP_ENV=local
APP_DEBUG=true
APP_URL=http://$WORKER_IP:$APP_PORT
# Previews are plain HTTP: a secure-only session cookie would make login impossible.
SESSION_SECURE_COOKIE=false
QUEUE_CONNECTION=sync
MAIL_MAILER=log
# Staging and PR previews set SHARED_DIR (kept across deploys) and DB_NAME (pr<N> for previews),
# so each environment keeps its own SQLite file; jobs set neither and use database/database.sqlite.
DB_DATABASE=$db
ENV
  mkdir -p "$(dirname "$db")" && touch "$db"
}

setup() {
  step composer composer install --no-interaction --prefer-dist
  step migrate bash -c 'php artisan key:generate --force && php artisan migrate:fresh --force --seed'
  # The demo account (demo / password) and sample data, as the e2e specs expect. Best-effort.
  status running seed
  php artisan db:seed --class=DemoSeeder --force > "$JOBDIR/seed.log" 2>&1 || note_for_agent "# Environment caveat: demo data

\`php artisan db:seed --class=DemoSeeder\` failed, so the database has no demo user. Error: \`$(plain "$JOBDIR/seed.log" | grep -m1 -E 'Exception|ERROR')\`"
  step npm bash -c 'npm ci && npm run build'
}

# Staging and PR previews (instead of setup): keep the existing database across deploys.
staging_deploy() {
  composer install --no-interaction --prefer-dist || return 1
  if [ "${FIRST_DEPLOY:-}" = 1 ]; then
    php artisan key:generate --force || return 1
    php artisan migrate:fresh --force --seed || return 1
    # The demo account (demo / password) and sample data. Best-effort.
    php artisan db:seed --class=DemoSeeder --force || echo "DemoSeeder failed; the database has no demo user."
  else
    php artisan migrate --force || return 1
  fi
  npm ci && npm run build
}

serve_cmd() { echo "php artisan serve --host 0.0.0.0 --port $APP_PORT"; }

lint() {
  local php
  php=$(printf '%s\n' $CHANGED | grep '\.php$' || true)
  [ -n "$php" ] || return 0
  # shellcheck disable=SC2086
  ./vendor/bin/pint $php
  git add -A
  git diff --cached --quiet || git commit -qm "Apply Pint formatting"
  # shellcheck disable=SC2086
  ./vendor/bin/pint --test $php
}

run_tests() {
  php artisan config:clear >/dev/null 2>&1
  php artisan test
}

# The specs live in e2e/ (not tests/e2e/) and log in as the DemoSeeder account.
e2e() { E2E_DIR=e2e playwright_e2e resources/views resources/js resources/css; }

preview_build() {
  php artisan migrate --force
  npm run build
}
