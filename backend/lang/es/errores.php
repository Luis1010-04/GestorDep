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

    // Mensaje propio de cada ReglaDeNegocioViolada, por su codigo; sin entrada se usa regla_de_negocio_violada.
    'reglas' => [
        'contrasena_muy_corta' => 'La contraseña debe tener al menos 10 caracteres.',
        'correo_duplicado' => 'Ya existe un usuario con ese correo.',
        'rol_obligatorio' => 'El usuario debe tener al menos un rol.',
        'rol_inexistente' => 'El rol indicado no existe.',
    ],
];
