<?php
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

if (!defined('DDNS_TOLL_COMMENTS_MOCK_MODE')) {
    define('DDNS_TOLL_COMMENTS_MOCK_MODE', true);
}

require_once $_tests_dir . '/includes/functions.php';

tests_add_filter('muplugins_loaded', function () {
    require dirname(__DIR__) . '/toll-comments.php';
});

require $_tests_dir . '/includes/bootstrap.php';
