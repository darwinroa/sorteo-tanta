<?php
if ( ! defined( 'ABSPATH' ) ) exit;

register_activation_hook( dirname( __DIR__ ) . '/sorteo-tanta.php', 'st_activar_plugin' );

function st_activar_plugin() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'sorteo_boletas';
    $charset_collate = $wpdb->get_charset_collate();

    // Agregamos: aceptacion_terminos, autorizacion_datos, autorizacion_imagen
    $sql = "CREATE TABLE $tabla (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        nombre varchar(100) NOT NULL,
        apellido varchar(100) NOT NULL,
        tipo_documento varchar(20) NOT NULL DEFAULT 'DNI',
        dni varchar(20) NOT NULL,
        fecha_nacimiento date NOT NULL,
        telefono varchar(20) NOT NULL,
        email varchar(100) NOT NULL,
        nro_boleta varchar(50) NOT NULL UNIQUE,
        aceptacion_terminos tinyint(1) NOT NULL DEFAULT 0,
        autorizacion_datos tinyint(1) NOT NULL DEFAULT 0,
        autorizacion_imagen tinyint(1) NOT NULL DEFAULT 0,
        estado varchar(20) DEFAULT 'pendiente', 
        fecha_registro datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}