<?php
/**
 * Plugin Name: Sorteo Tanta
 * Description: Sistema de sorteo con validación de boletas mediante carga de CSV masivo.
 * Version: 1.1.1
 * Author: Darwin Roa
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

// Definir constantes para rutas (opcional, pero buena práctica)
define( 'ST_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// 1. Cargar lógica de Base de Datos
require_once ST_PLUGIN_DIR . 'includes/st-db.php';

// 2. Cargar lógica del Frontend (Formulario y AJAX)
require_once ST_PLUGIN_DIR . 'includes/st-frontend.php';

// 3. Cargar lógica del Admin (Solo si estamos en el dashboard)
if ( is_admin() ) {
    require_once ST_PLUGIN_DIR . 'includes/st-admin.php';
}