    // Evento de click en el boton importar
    $(document).on("click", "#import-upload", function() {
        // limpia las tablas de respuesta y resultado
        $('#resultados').empty();
        $('#errores').empty()

        // recoje los datos
        let url = "../controller/importController.php";
        let filedata = $("#import-file").prop("files")[0];
        let filename = $('#import-file').val();
        let extension = filename.split('.').pop();
        let delimiter = document.getElementById("delimiter").value;

        // los prepara para el envio
        let formData = new FormData();
        formData.append("fichero", filedata);
        formData.append("delimitador", delimiter);

        // llama ala funcion de comprobar la extension y enviar datos
        validacionExtension();
        enviarDatos();


        // envia los datos al servidor y pinta la respuesta
        function enviarDatos() {
            $.ajax({
                type: "POST",
                url: url,
                data: formData,
                dataType: 'script',
                cache: false,
                contentType: false,
                processData: false,
                success: function(response) {

                    let respuesta = JSON.parse(response);
                    $('#resultados').empty().append(respuesta.resultado);
                    $('#errores').empty().append(respuesta.fail);
                }
            });
        }

        // valida una extension en js  previo al servidor
        // posibles futuras extensiones ,"xls", "xlsm", "xlsb", "xltm", "xlam", "xlr", "xlw"
        function validacionExtension() {
            let formatos_Validos = ["txt", "xlsx", "csv"];
            let coincidencia = false;

            if (filename == "") {
                alert("Debe seleccionar un archivo");
            } else {
                formatos_Validos.forEach(element => {
                    if (extension == element) {
                        coincidencia = true;
                    }
                });
                if (!coincidencia) {
                    alert("No es un formato valido");
                }
            }
        }
    });