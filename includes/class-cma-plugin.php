<?php
if (!defined('ABSPATH')) exit;

require_once CMA_PATH . 'includes/class-cma-gutenberg.php';

final class CMA_Plugin {
    private static ?CMA_Plugin $instance = null;

    public static function instance(): CMA_Plugin {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        if (is_admin()) {
            require_once CMA_PATH . 'includes/class-cma-admin.php';
            require_once CMA_PATH . 'includes/class-cma-scanner.php';
            require_once CMA_PATH . 'includes/class-cma-analyzer.php';
            new CMA_Admin();
        }

        // Le module Gutenberg doit aussi être initialisé pendant les requêtes REST.
        new CMA_Gutenberg();
    }
}
