<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 2. Renderizar el Formulario HTML
 */
add_shortcode('formulario_sorteo_tanta', 'st_renderizar_formulario');

function st_renderizar_formulario() {
    if(is_admin()) return;

    $img_boleta = plugins_url( '../assets/img/example-boleta.webp', __FILE__ );

    wp_enqueue_style('st-style-frontend', plugins_url( '../assets/css/sorteo-tanta.css', __FILE__ ), array(), '1.3'); // Versión 1.3
    wp_enqueue_script('st-script-frontend', plugins_url( '../assets/js/sorteo-tanta.js', __FILE__ ), array('jquery'), '1.3', true);

    wp_localize_script('st-script-frontend', 'SorteoData', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'security' => wp_create_nonce('st_nonce_seguridad')
    ));

    ob_start(); 
    ?>
    <div class="st-form-container">
        <form id="form-sorteo-tanta" class="st-form">
            
            <div class="st-form-group"><label>Nombre</label><input type="text" name="nombre" required></div>
            <div class="st-form-group"><label>Apellido</label><input type="text" name="apellido" required></div>
            <div class="st-form-group"><label>Fecha de Nacimiento</label><input type="date" name="fecha_nacimiento" id="st_fecha_nacimiento" required></div>
            <div class="st-form-group"><label>Correo Electrónico</label><input type="email" name="email" required></div>
            <div class="st-form-group"><label>Teléfono (Celular)</label><input type="tel" name="telefono" required></div>
            
            <div class="st-form-group">
                <label style="display:flex; align-items:center; gap:5px;">
                    N° Boleta 
                    <span class="st-tooltip-wrapper">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg" style="cursor:help;">
                            <path d="M6.25828 3.47682H7.64901V4.86755H6.25828V3.47682ZM6.25828 6.25828H7.64901V10.4305H6.25828V6.25828ZM7 0C3.10596 0 0 3.10596 0 7C0 10.894 3.10596 14 7 14C10.894 14 14 10.894 14 7C14 3.10596 10.8477 0 7 0ZM7 12.5629C3.9404 12.5629 1.43709 10.0596 1.43709 7C1.43709 3.9404 3.9404 1.43709 7 1.43709C10.0596 1.43709 12.5629 3.9404 12.5629 7C12.5629 10.0596 10.0596 12.5629 7 12.5629Z" fill="#E73218"/>
                        </svg>
                        <span class="st-tooltip-content">
                            <img src="<?php echo esc_url($img_boleta); ?>" alt="Ejemplo de Boleta">
                        </span>
                    </span>
                </label>
                <input type="text" 
                       name="nro_boleta" 
                       id="nro_boleta"
                       placeholder="Ej: B001-00045678" 
                       required 
                       maxlength="13" 
                       oninput="this.value = this.value.toUpperCase()"
                       pattern="^B\d{3}-\d{8}$"
                       title="El formato debe ser B seguido de 3 números, un guión y 8 números (Ej: B001-12345678)">
                <small style="display:block; margin-top:5px; color:#666; font-size:12px;">Formato obligatorio: B001-12345678 (Solo Boletas)</small>
            </div>

            <div class="st-form-group">
                <label>Tipo de Documento</label>
                <select name="tipo_documento" style="width:100%; padding:9px; border:1px solid #ccc; border-radius:4px;">
                    <option value="DNI">DNI</option>
                    <option value="Pasaporte">Pasaporte</option>
                    <option value="CE">CE</option>
                </select>
            </div>
            <div class="st-form-group">
                <label>Documento</label>
                <input type="text" name="dni" required>
            </div>

            <div class="st-checkbox-group">
                <div class="st-checkbox-item">
                    <input type="checkbox" name="aceptacion_terminos" id="chk_terminos" value="1" required>
                    <label for="chk_terminos">Aceptación de términos y condiciones del sorteo. <span style="color:red">*</span></label>
                </div>
                <div class="st-checkbox-item">
                    <input type="checkbox" name="autorizacion_datos" id="chk_datos" value="1">
                    <label for="chk_datos">Autorización de datos personales.</label>
                </div>
                <div class="st-checkbox-item">
                    <input type="checkbox" name="autorizacion_imagen" id="chk_imagen" value="1" required>
                    <label for="chk_imagen">Autorización de uso de imagen. <span style="color:red">*</span></label>
                </div>
            </div>
            
            <input type="hidden" name="action" value="st_procesar_registro">
            <?php wp_nonce_field('st_nonce_seguridad', 'security'); ?>
            
            <button type="submit" class="st-btn">
                PARTICIPA AQUÍ
                <svg width="32" height="12" viewBox="0 0 32 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M31.5303 4.99261C31.8232 5.2855 31.8232 5.76038 31.5303 6.05327L26.7574 10.8262C26.4645 11.1191 25.9896 11.1191 25.6967 10.8262C25.4038 10.5333 25.4038 10.0585 25.6967 9.76558L29.9393 5.52294L25.6967 1.2803C25.4038 0.987408 25.4038 0.512535 25.6967 0.219641C25.9896 -0.0732521 26.4645 -0.0732521 26.7574 0.219641L31.5303 4.99261ZM0 5.52295L-2.10372e-07 4.77295L31 4.77294L31 5.52294L31 6.27294L2.10372e-07 6.27295L0 5.52295Z" fill="#E20E18"/>
                </svg>
            </button>
            <div id="st-mensaje"></div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * 3. Procesamiento AJAX (Backend)
 */
add_action('wp_ajax_st_procesar_registro', 'st_procesar_registro_ajax');
add_action('wp_ajax_nopriv_st_procesar_registro', 'st_procesar_registro_ajax');

function st_procesar_registro_ajax() {
    check_ajax_referer('st_nonce_seguridad', 'security');
    
    global $wpdb;
    $tabla = $wpdb->prefix . 'sorteo_boletas';

    // 1. Checks Obligatorios (Solo Términos e Imagen, ya que Datos es opcional)
    if ( !isset($_POST['aceptacion_terminos']) || !isset($_POST['autorizacion_imagen']) ) {
        wp_send_json_error("Es obligatorio aceptar los términos y la autorización de imagen.");
        wp_die();
    }

    // Sanitización
    $nombre = isset($_POST['nombre']) ? sanitize_text_field($_POST['nombre']) : '';
    $apellido = isset($_POST['apellido']) ? sanitize_text_field($_POST['apellido']) : '';
    $tipo_documento = isset($_POST['tipo_documento']) ? sanitize_text_field($_POST['tipo_documento']) : 'DNI';
    $fecha_nac = isset($_POST['fecha_nacimiento']) ? sanitize_text_field($_POST['fecha_nacimiento']) : '';
    $dni = isset($_POST['dni']) ? sanitize_text_field($_POST['dni']) : '';
    $telefono = isset($_POST['telefono']) ? sanitize_text_field($_POST['telefono']) : '';
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    
    // Convertimos a mayúsculas
    $nro_boleta = isset($_POST['nro_boleta']) ? strtoupper(sanitize_text_field($_POST['nro_boleta'])) : '';
    
    // Validación Regex Boleta
    if (!preg_match('/^B\d{3}-\d{8}$/', $nro_boleta)) {
        if (strpos($nro_boleta, 'F') === 0) {
            wp_send_json_error("Error: Las facturas (Serie F) no participan en este sorteo. Solo Boletas.");
        } else {
            wp_send_json_error("Formato de boleta incorrecto. Debe ser: Bxxx-xxxxxxxx (Ej: B001-12345678)");
        }
        wp_die();
    }

    // --- CORRECCIÓN AQUÍ ---
    // Como son obligatorios y pasaron la validación de arriba, estos son 1 fijo.
    $aceptacion_terminos = 1;
    $autorizacion_imagen = 1;
    
    // Como Datos es OPCIONAL, debemos verificar si el usuario lo marcó o no.
    // Si viene en el POST es 1, si no viene es 0.
    $autorizacion_datos  = isset($_POST['autorizacion_datos']) ? 1 : 0; 
    // -----------------------

    $datos = [
        'nombre' => $nombre,
        'apellido' => $apellido,
        'tipo_documento' => $tipo_documento,
        'fecha_nacimiento' => $fecha_nac,
        'dni' => $dni,
        'telefono' => $telefono,
        'email' => $email,
        'nro_boleta' => $nro_boleta,
        'aceptacion_terminos' => $aceptacion_terminos,
        'autorizacion_datos' => $autorizacion_datos, // Ahora guardará 0 o 1 real
        'autorizacion_imagen' => $autorizacion_imagen,
        'estado' => 'pendiente'
    ];

    // Validación Edad
    if($fecha_nac) {
        try {
            $f_nac = new DateTime($fecha_nac);
            $hoy = new DateTime();
            $edad = $hoy->diff($f_nac)->y;
            if($edad < 18) {
                wp_send_json_error("Lo sentimos, debes ser mayor de 18 años.");
                wp_die();
            }
        } catch (Exception $e) {
            wp_send_json_error("Fecha inválida.");
            wp_die();
        }
    }

    $insert = $wpdb->insert($tabla, $datos, [
        '%s','%s','%s','%s','%s','%s','%s','%s',
        '%d','%d','%d',
        '%s'
    ]);

    if($insert) {
        wp_send_json_success("¡Registro exitoso! Tu boleta será validada al finalizar el sorteo.");
    } else {
        if (strpos($wpdb->last_error, 'Duplicate entry') !== false) {
            wp_send_json_error("Este número de boleta ya ha sido registrado anteriormente.");
        } else {
            wp_send_json_error("Error técnico al guardar.");
        }
    }
    wp_die();
}