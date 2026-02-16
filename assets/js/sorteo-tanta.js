jQuery(document).ready(function($) {
    
    $('#form-sorteo-tanta').on('submit', function(e) {
        e.preventDefault(); 

        var $form = $(this);
        var $mensaje = $('#st-mensaje');
        
        // Limpiar mensaje previo
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

        // 3. Envío AJAX usando las variables localizadas (SorteoData)
        // SorteoData.ajax_url y SorteoData.security vienen desde PHP
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