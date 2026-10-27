<?php

// Mensajes del formato de error (docs/convenciones-api.md, seccion 3). La clave es el codigo estable.
return [
    'no_autenticado' => 'Se requiere iniciar sesión.',
    'sin_permiso' => 'No tiene permiso para realizar esta acción.',
    'recurso_no_encontrado' => 'El recurso solicitado no existe.',
    'metodo_no_permitido' => 'El método no está permitido para esta ruta.',
    'conflicto' => 'La operación entra en conflicto con los datos existentes.',
    'validacion_fallida' => 'Los datos enviados no son válidos.',
    'regla_de_negocio_violada' => 'La operación no cumple una regla de negocio.',
    'solicitud_no_valida' => 'La solicitud no es válida.',
    'error_interno' => 'Ocurrió un error inesperado.',
    'servicio_no_disponible' => 'El servicio no está disponible en este momento.',

    // Mensaje del error de campo "cuerpo" (PATCH sin ningún campo): no es un código de error sino un detalle de validación.
    'cuerpo_sin_campos' => 'Debe enviar al menos uno de: nombres, apellidos, fecha_nacimiento',

    // Mensaje propio de cada ReglaDeNegocioViolada, por su codigo; sin entrada se usa regla_de_negocio_violada.
    'reglas' => [
        'credenciales_invalidas' => 'Las credenciales no son válidas.',
        'cuenta_bloqueada' => 'La cuenta está bloqueada temporalmente por intentos fallidos.',
        'contrasena_actual_incorrecta' => 'La contraseña actual no es correcta.',
        'contrasena_muy_corta' => 'La contraseña debe tener al menos 10 caracteres.',
        'correo_duplicado' => 'Ya existe un usuario con ese correo.',
        'rol_obligatorio' => 'El usuario debe tener al menos un rol.',
        'rol_inexistente' => 'El rol indicado no existe.',
        'categoria_duplicada' => 'Ya existe una categoría con ese nombre.',
        'equipo_duplicado' => 'Ya existe un equipo con ese nombre en la categoría.',
        'categoria_inexistente' => 'La categoría indicada no existe.',
        'matricula_duplicada' => 'Ya existe un jugador con esa matrícula.',
        'posicion_invalida' => 'La posición indicada no existe.',
        'jugador_ya_en_equipo' => 'El jugador ya pertenece a ese equipo.',
        'jugador_en_baja' => 'El jugador está en baja y no se puede asignar.',
        'jugador_ya_en_baja' => 'El jugador ya está en baja.',
        'motivo_obligatorio' => 'La baja requiere un motivo.',
        'periodo_duplicado' => 'Ya existe un periodo escolar con ese nombre.',
        'rango_de_fechas_invalido' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
    ],
];
