<?php

// Textos de las vistas web y de sus scripts. Las vistas los leen con __(); los scripts, del atributo data-textos del <body>.
return [
    'aplicacion' => 'GestorDep',
    'navegacion' => 'Navegación principal',

    'ingreso' => [
        'titulo' => 'Iniciar sesión',
        'correo' => 'Correo',
        'contrasena' => 'Contraseña',
        'entrar' => 'Entrar',
    ],

    'menu' => [
        'panel' => 'Panel',
        'cambiar_contrasena' => 'Cambiar contraseña',
        'cerrar_sesion' => 'Cerrar sesión',
    ],

    'contrasena' => [
        'titulo' => 'Cambiar contraseña',
        'actual' => 'Contraseña actual',
        'nueva' => 'Contraseña nueva',
        'guardar' => 'Guardar la contraseña nueva',
        'nota' => 'Al cambiarla se cierran sus demás sesiones abiertas; esta sesión sigue activa.',
    ],

    'panel' => [
        'titulo' => 'Panel',
        'bienvenida' => 'Bienvenido',
        'roles' => 'Roles',
        'sin_secciones' => 'Aún no hay secciones habilitadas en esta versión.',
    ],

    'pie' => 'GestorDep: seguimiento académico y deportivo de futbolistas',

    // Mensajes que muestran los scripts (public/assets/js).
    'js' => [
        'cargando' => 'Procesando…',
        'entrar' => 'Entrar',
        'error_generico' => 'No se pudo completar la operación. Intente de nuevo.',
        'error_de_sesion' => 'No se pudo abrir la sesión desde este origen. Recargue la página e intente de nuevo.',
        'sin_conexion' => 'Hay problemas de conexión. Revise su internet e intente de nuevo.',
        'bloqueada' => 'Demasiados intentos. Intente de nuevo en :minutos minutos.',
        'sesion_expirada' => 'Su sesión terminó. Inicie sesión de nuevo.',
        'roles_ninguno' => 'Ninguno',
        'aceptar' => 'Aceptar',
        'cancelar' => 'Cancelar',
        'sin_autorizacion' => 'No tiene autorización para esta acción.',
        'no_disponible' => 'El recurso no existe o no está disponible para usted.',

        'contrasena' => [
            'confirmar_titulo' => '¿Cambiar la contraseña?',
            'confirmar_texto' => 'Se cerrarán sus demás sesiones abiertas. Esta sesión sigue activa.',
            'confirmar_boton' => 'Sí, cambiarla',
            'exito' => 'La contraseña se cambió y sus demás sesiones se cerraron.',
        ],
    ],
];
