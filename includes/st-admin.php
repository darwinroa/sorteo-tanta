<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('admin_menu', function() {
    add_menu_page('Sorteo Tanta', 'Sorteo Tanta', 'manage_options', 'st-gestion', 'st_renderizar_admin', 'dashicons-tickets', 6);
});

function st_renderizar_admin() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'sorteo_boletas';
    
    // Obtenemos conteos actualizados
    $total = $wpdb->get_var("SELECT COUNT(*) FROM $tabla");
    $validos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE estado = 'validado'");
    $pendientes = $total - $validos;
    ?>
    <div class="wrap">
        <h1>Gestión del Sorteo Tanta</h1>
        <hr>
        
        <div class="card" style="margin-bottom:20px; padding:20px; background:#fff; border-left:4px solid #E20E18;">
            <h3 style="margin-top:0;">Estadísticas en Tiempo Real</h3>
            <p style="font-size:1.2em;">
                <strong>Total Registrados:</strong> <?php echo $total; ?> &nbsp;|&nbsp; 
                <strong style="color:green;">Validados:</strong> <?php echo $validos; ?> &nbsp;|&nbsp;
                <strong style="color:orange;">Pendientes:</strong> <?php echo $pendientes; ?>
            </p>
        </div>

        <div style="display:flex; gap:20px; flex-wrap:wrap;">
            <div class="card" style="padding:20px; flex:1; min-width:300px;">
                <h2>1. Validar Boletas (Carga Masiva)</h2>
                <p>Sube el CSV del sistema de ventas. <strong>Importante:</strong> Boleta en la primera columna.</p>
                <form method="post" enctype="multipart/form-data">
                    <input type="file" name="st_csv_maestro" required accept=".csv">
                    <input type="hidden" name="st_accion_admin" value="validar_csv">
                    <?php wp_nonce_field('st_admin_nonce'); ?>
                    <p><button type="submit" class="button button-primary">Procesar CSV y Validar</button></p>
                </form>
            </div>

            <div class="card" style="padding:20px; flex:1; min-width:300px;">
                <h2>2. Descarga de Reportes</h2>
                <p>Selecciona el tipo de reporte que necesitas.</p>
                
                <form method="post" style="margin-bottom: 15px;">
                    <input type="hidden" name="st_accion_admin" value="descargar_csv">
                    <button type="submit" class="button button-secondary">
                        <span class="dashicons dashicons-yes-alt" style="margin-top:4px; color:green;"></span> 
                        Descargar Solo Válidos (Para Sorteo)
                    </button>
                </form>

                <form method="post">
                    <input type="hidden" name="st_accion_admin" value="descargar_todos_csv">
                    <button type="submit" class="button button-secondary">
                        <span class="dashicons dashicons-database" style="margin-top:4px; color:#555;"></span> 
                        Descargar TODO (Pendientes y Validados)
                    </button>
                </form>
            </div>
        </div>

        <div class="card" style="margin-top:30px; padding:20px; border:1px solid #dc3232;">
            <h2 style="color:#dc3232;">⚠ Zona de Peligro: Reiniciar Sorteo</h2>
            <p>Esta acción <strong>eliminará permanentemente</strong> todos los registros de la base de datos.</p>
            
            <form method="post" onsubmit="
                var palabra = prompt('⚠ ADVERTENCIA DE SEGURIDAD ⚠\n\nEsta acción borrará TODOS los datos y reiniciará el sorteo a cero.\nNo se puede deshacer.\n\nPara confirmar, escribe exactamente la palabra: delete');
                if (palabra === 'delete') { return true; } else { alert('Acción cancelada.'); return false; }
            ">
                <input type="hidden" name="st_accion_admin" value="eliminar_todo">
                <?php wp_nonce_field('st_admin_nonce'); ?>
                <button type="submit" class="button button-link-delete" style="color:white; background:#dc3232; border-color:#dc3232; text-decoration:none; padding:5px 10px; border-radius:3px;">Borrar Todos los Datos</button>
            </form>
        </div>
    </div>
    <?php
}

add_action('admin_init', 'st_procesar_acciones_admin');

function st_procesar_acciones_admin() {
    if(!isset($_POST['st_accion_admin'])) return;
    if(!current_user_can('manage_options')) return;

    global $wpdb;
    $tabla = $wpdb->prefix . 'sorteo_boletas';

    // 1. VALIDAR CSV
    if($_POST['st_accion_admin'] == 'validar_csv') {
        check_admin_referer('st_admin_nonce');
        
        if(!empty($_FILES['st_csv_maestro']['tmp_name'])) {
            $handle = fopen($_FILES['st_csv_maestro']['tmp_name'], "r");
            $boletas_validas = [];
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $boleta = trim($data[0]); 
                if(!empty($boleta)) $boletas_validas[] = $boleta;
            }
            fclose($handle);

            if(count($boletas_validas) > 0) {
                $lotes = array_chunk($boletas_validas, 500);
                $contador = 0;
                foreach($lotes as $lote) {
                    $placeholders = implode("','", array_map('esc_sql', $lote));
                    $sql = "UPDATE $tabla SET estado = 'validado' WHERE nro_boleta IN ('$placeholders')";
                    $contador += $wpdb->query($sql);
                }
                add_action('admin_notices', function() use ($contador) {
                    echo '<div class="notice notice-success is-dismissible"><p>Se han validado <strong>' . $contador . '</strong> participantes.</p></div>';
                });
            } else {
                add_action('admin_notices', function() { echo '<div class="notice notice-error"><p>CSV vacío o incorrecto.</p></div>'; });
            }
        }
    }

    // 2. DESCARGAR CSV: SOLO VÁLIDOS
    if($_POST['st_accion_admin'] == 'descargar_csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="sorteo_tanta_GANADORES.csv"'); // Nombre distintivo
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
        
        fputcsv($output, [
            'ID', 'Nombre', 'Apellido', 'Tipo Doc', 'Nro Documento', 
            'Email', 'Telefono', 'Boleta', 'Fecha Registro', 
            'Terminos', 'Datos', 'Imagen'
        ]);
        
        // Query FILTRADO
        $resultados = $wpdb->get_results("SELECT * FROM $tabla WHERE estado = 'validado'", ARRAY_A);
        foreach ($resultados as $row) {
            fputcsv($output, [
                $row['id'], $row['nombre'], $row['apellido'], $row['tipo_documento'],
                $row['dni'], $row['email'], $row['telefono'], $row['nro_boleta'],
                $row['fecha_registro'],
                ($row['aceptacion_terminos'] == 1) ? 'SI' : 'NO',
                ($row['autorizacion_datos'] == 1) ? 'SI' : 'NO',
                ($row['autorizacion_imagen'] == 1) ? 'SI' : 'NO'
            ]);
        }
        fclose($output);
        exit;
    }

    // 3. DESCARGAR CSV: TODOS (CRUDO) -- NUEVA FUNCIÓN
    if($_POST['st_accion_admin'] == 'descargar_todos_csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="sorteo_tanta_TOTAL_REGISTROS.csv"'); // Nombre distintivo
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
        
        // Cabeceras: Agregamos la columna ESTADO
        fputcsv($output, [
            'ID', 'Nombre', 'Apellido', 'Tipo Doc', 'Nro Documento', 
            'Email', 'Telefono', 'Boleta', 'Fecha Registro', 
            'Terminos', 'Datos', 'Imagen', 
            'ESTADO' // <--- Nueva columna
        ]);
        
        // Query SIN FILTRO WHERE
        $resultados = $wpdb->get_results("SELECT * FROM $tabla", ARRAY_A);
        
        foreach ($resultados as $row) {
            fputcsv($output, [
                $row['id'], $row['nombre'], $row['apellido'], $row['tipo_documento'],
                $row['dni'], $row['email'], $row['telefono'], $row['nro_boleta'],
                $row['fecha_registro'],
                ($row['aceptacion_terminos'] == 1) ? 'SI' : 'NO',
                ($row['autorizacion_datos'] == 1) ? 'SI' : 'NO',
                ($row['autorizacion_imagen'] == 1) ? 'SI' : 'NO',
                strtoupper($row['estado']) // Convertimos a mayúsculas: PENDIENTE / VALIDADO
            ]);
        }
        fclose($output);
        exit;
    }

    // 4. ELIMINAR TODO
    if($_POST['st_accion_admin'] == 'eliminar_todo') {
        check_admin_referer('st_admin_nonce');
        $wpdb->query("TRUNCATE TABLE $tabla");
        add_action('admin_notices', function() {
            echo '<div class="notice notice-warning is-dismissible"><p><strong>¡SISTEMA REINICIADO!</strong> Todos los registros han sido eliminados.</p></div>';
        });
    }
}