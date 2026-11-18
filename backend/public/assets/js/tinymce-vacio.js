/*
 * main.js de NiceAdmin llama a tinymce.init() sin comprobar que TinyMCE esté cargado, y falla con un ReferenceError.
 * TinyMCE está inactivo en este proyecto; este objeto vacío evita el error sin modificar main.js. Debe cargarse antes que él.
 */
window.tinymce = { init: function () {} };
