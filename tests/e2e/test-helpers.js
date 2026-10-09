const { execSync } = require('child_process');

/**
 * Czyści tabelę flood w Drupalu, aby zapobiec blokadom Flood Control
 * podczas wielokrotnych uruchomień testów na tym samym środowisku.
 */
function clearFloodTable() {
  try {
    execSync('docker compose exec -T drupal vendor/bin/drush php:eval "\\Drupal::database()->truncate(\'flood\')->execute();"', {
      stdio: 'ignore',
      timeout: 5000,
    });
  } catch {
    // Cicho ignorujemy, jeśli testy są uruchamiane na środowisku zewnętrznym bez dostępu do dockera
  }
}

module.exports = {
  clearFloodTable,
};
