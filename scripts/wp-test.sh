#!/usr/bin/env bash
set -euo pipefail

compose_file="docker-compose.yml"

if ! docker compose -f "$compose_file" ps >/dev/null 2>&1; then
  echo "Docker compose not available or not running"
  exit 1
fi

docker compose -f "$compose_file" up -d db wordpress

docker compose -f "$compose_file" exec -T wordpress bash -lc "apt-get update -y >/dev/null && apt-get install -y subversion unzip wget git >/dev/null"

docker compose -f "$compose_file" exec -T wordpress bash -lc "if ! command -v phpunit >/dev/null 2>&1; then wget -q https://phar.phpunit.de/phpunit-9.phar -O /usr/local/bin/phpunit && chmod +x /usr/local/bin/phpunit; fi"

docker compose -f "$compose_file" exec -T wordpress bash -lc "if [ ! -d /tmp/wordpress-tests-lib ]; then mkdir -p /tmp/wordpress-tests-lib; fi && if [ ! -d /tmp/wordpress-tests-lib/includes ]; then svn co https://develop.svn.wordpress.org/trunk/tests/phpunit/includes/ /tmp/wordpress-tests-lib/includes >/dev/null; fi && if [ ! -d /tmp/wordpress-tests-lib/data ]; then svn co https://develop.svn.wordpress.org/trunk/tests/phpunit/data/ /tmp/wordpress-tests-lib/data >/dev/null; fi && if [ ! -f /tmp/wordpress-tests-lib/wp-tests-config-sample.php ]; then svn export https://develop.svn.wordpress.org/trunk/wp-tests-config-sample.php /tmp/wordpress-tests-lib/wp-tests-config-sample.php >/dev/null; fi"

docker compose -f "$compose_file" exec -T wordpress bash -lc "if [ ! -f /tmp/wordpress-tests-lib/wp-tests-config.php ]; then cp /tmp/wordpress-tests-lib/wp-tests-config-sample.php /tmp/wordpress-tests-lib/wp-tests-config.php; fi"

docker compose -f "$compose_file" exec -T wordpress bash -lc "sed -i \"s/define( 'DB_NAME', '.*' )/define( 'DB_NAME', 'wordpress' )/\" /tmp/wordpress-tests-lib/wp-tests-config.php && sed -i \"s/define( 'DB_USER', '.*' )/define( 'DB_USER', 'wp' )/\" /tmp/wordpress-tests-lib/wp-tests-config.php && sed -i \"s/define( 'DB_PASSWORD', '.*' )/define( 'DB_PASSWORD', 'wp' )/\" /tmp/wordpress-tests-lib/wp-tests-config.php && sed -i \"s/define( 'DB_HOST', '.*' )/define( 'DB_HOST', 'db' )/\" /tmp/wordpress-tests-lib/wp-tests-config.php"

docker compose -f "$compose_file" exec -T wordpress bash -lc "if [ ! -d /tmp/wp-phpunit-polyfills ]; then git clone --depth=1 https://github.com/Yoast/PHPUnit-Polyfills.git /tmp/wp-phpunit-polyfills >/dev/null; fi"

docker compose -f "$compose_file" exec -T wordpress bash -lc "if [ ! -d /tmp/wordpress-tests-lib/src ]; then svn export https://develop.svn.wordpress.org/trunk/src/ /tmp/wordpress-tests-lib/src >/dev/null; fi"

docker compose -f "$compose_file" exec -T wordpress bash -lc "grep -q \"WP_TESTS_DIR\" /tmp/wordpress-tests-lib/wp-tests-config.php || echo \"define('WP_TESTS_DIR', '/tmp/wordpress-tests-lib');\" >> /tmp/wordpress-tests-lib/wp-tests-config.php"
docker compose -f "$compose_file" exec -T wordpress bash -lc "grep -q \"WP_CORE_DIR\" /tmp/wordpress-tests-lib/wp-tests-config.php || echo \"define('WP_CORE_DIR', '/tmp/wordpress-tests-lib/src');\" >> /tmp/wordpress-tests-lib/wp-tests-config.php"

plugins=(
  "/var/www/html/wp-content/plugins/toll-comments"
  "/var/www/html/wp-content/plugins/ddns-accelerator"
  "/var/www/html/wp-content/plugins/ddns-optin"
  "/var/www/html/wp-content/plugins/ddns-node"
  "/var/www/html/wp-content/plugins/ddns-compat-orchestrator"
  "/var/www/html/wp-content/plugins/ddns-ai-admin"
  "/var/www/html/wp-content/plugins/contributor-bounties"
)

for plugin_dir in "${plugins[@]}"; do
  if docker compose -f "$compose_file" exec -T wordpress bash -lc "test -f ${plugin_dir}/phpunit.xml.dist"; then
    docker compose -f "$compose_file" exec -T wordpress bash -lc "cd ${plugin_dir} && phpunit -c phpunit.xml.dist"
  else
    echo "Skipping tests for ${plugin_dir} (no phpunit.xml.dist)"
  fi
done
