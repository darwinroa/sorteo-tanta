jQuery(document).ready(function($) {
    
    $('#form-sorteo-tanta').on('submit', function(e) {
        e.preventDefault(); 

        var $form = $(this);
        var $mensaje = $('#st-mensaje');
        
        $mensaje.html('Procesando...').css('color', '#333');
        
        // 1. Validación de Fecha
        var fechaInput = $('#st_fecha_nacimiento').val();
        if(!fechaInput) {
            alert("Por favor ingrese una fecha.");
            $mensaje.html('');
            return;
        }

        // 2. Validación de Edad
        var nac = new Date(fechaInput);
        var hoy = new Date();
        var edad = hoy.getFullYear() - nac.getFullYear();
        var m = hoy.getMonth() - nac.getMonth();
        if (m < 0 || (m === 0 && hoy.getDate() < nac.getDate())) { 
            edad--; 
        }
        
        if(edad < 18) {
            alert("Debes ser mayor de edad para participar.");
            $mensaje.html('');
            return;
        }

        // --- 3. NUEVA VALIDACIÓN DE BOLETA (JS) ---
        var boleta = $('#nro_boleta').val().toUpperCase();
        // Regex: Inicia con B, 3 digitos, guion, 8 digitos.
        var regexBoleta = /^B\d{3}-\d{8}$/;

        if (!regexBoleta.test(boleta)) {
            if(boleta.startsWith('F')) {
                $mensaje.html('Las Facturas (F...) no participan. Solo Boletas.').css('color', 'red');
            } else {
                $mensaje.html('Formato de boleta inválido. Ejemplo correcto: B001-12345678').css('color', 'red');
            }
            return; // Detiene el envío
        }

        // 4. Envío AJAX
        var formData = $form.serialize();

        $.post(SorteoData.ajax_url, formData, function(response) {
            if(response.success) {
                $mensaje.html(response.data).css('color', '#E20E18');
                $form[0].reset();
            } else {
                $mensaje.html(response.data).css('color', 'red');
            }
        }).fail(function() {
            $mensaje.html('Error de conexión. Intente nuevamente.').css('color', 'red');
        });
    });

});