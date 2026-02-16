jQuery(document).ready(function($) {

   // --- MÁSCARA INTELIGENTE PARA BOLETA (VERSIÓN FINAL) ---
    $('#nro_boleta').on('input', function(e) {
        var input = this;
        var val = input.value.toUpperCase(); // Forzamos mayúsculas visuales
        var $errorMsg = $('#st-boleta-error');
        var $helpMsg = $('#st-boleta-help');
        
        // 1. DETECCIÓN DE FACTURA (Letra F al inicio)
        if (val.startsWith('F')) {
            input.value = ""; 
            $errorMsg.show(); 
            $helpMsg.hide();  
            return; 
        } else {
            $errorMsg.hide(); 
            $helpMsg.show();
        }

        // 2. AUTO-CORRECCIÓN DE INICIO (Si empieza con número, agrega B)
        if (/^[0-9]/.test(val)) {
            val = 'B' + val;
        }

        // 3. LIMPIEZA ESTRICTA (Aquí corregimos el problema de las BBB)
        // Separamos la primera letra del resto.
        var primerCaracter = val.charAt(0);
        var restoCadena = val.substring(1);

        // Si la cadena no está vacía y el primer caracter no es B, lo forzamos a ser B.
        // (Esto arregla si pegan texto raro o empiezan con letras no válidas)
        if (val.length > 0 && primerCaracter !== 'B') {
            primerCaracter = 'B';
        }

        // En el resto de la cadena, BORRAMOS cualquier cosa que no sea número o guión.
        // Al quitar la 'B' de la lista de permitidos en el regex (/[^0-9-]/g),
        // evitamos que el usuario pueda escribir "BBBB".
        var restoLimpio = restoCadena.replace(/[^0-9-]/g, '');

        var clean = primerCaracter + restoLimpio;

        // 4. LÓGICA DEL GUIÓN (Híbrida: Manual o Automática)
        
        // Si la longitud es mayor a 4 (Ej: B1234...) y NO tiene guion en la 5ta posición...
        if (clean.length > 4 && clean.charAt(4) !== '-') {
            // ...lo insertamos automáticamente: B123-4...
            clean = clean.substring(0, 4) + '-' + clean.substring(4);
        }

        // 5. VALIDACIÓN DE POSICIÓN DEL GUIÓN
        // Si el usuario puso un guion antes (ej: B-12), lo quitamos.
        // Si puso múltiples guiones, nos quedamos solo con el correcto.
        var partes = clean.split('-');
        if (partes.length > 1) {
            var serie = partes[0]; // La parte antes del primer guion
            var numero = partes.slice(1).join(''); // Todo lo demás junto (sin guiones extra)
            
            // Si la serie tiene menos de 4 chars (ej: B12-), borramos el guion porque es muy pronto.
            if (serie.length < 4) {
                clean = serie + numero; 
            } else {
                // Reconstruimos: Bxxx + - + resto (limitado a 8 números)
                clean = serie.substring(0, 4) + '-' + numero.substring(0, 8); 
            }
        }

        // 6. LÍMITE FINAL DE LONGITUD (13 caracteres: Bxxx-xxxxxxxx)
        if (clean.length > 13) {
            clean = clean.substring(0, 13);
        }

        // 7. ACTUALIZAR VALOR
        // Si el input está vacío, lo dejamos vacío (para que se vea el placeholder)
        if (val.length === 0) {
            input.value = "";
        } else if (input.value !== clean) {
            input.value = clean;
        }
    });
    
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