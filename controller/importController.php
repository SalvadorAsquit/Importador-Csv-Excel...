<?php
require_once "../model/importModel.php";

// recogemos el archivo si esta y recogemos los datos que necesitamos
if (empty($_FILES)) {
    echo json_encode('Debe seleccionar un archivo');
    exit;
} else {
    $fichero = $_FILES;
    $extension = $_FILES["fichero"]["type"];
    $delimitador = $_POST["delimitador"];
}

// Creamos el objeto importador
$importar = new Import($fichero, $extension, $delimitador, "localhost", "root", "", "test", "web_bo");

// comprobamos la extension
$respuesta = ($importar->comprobarExtension());

// dependiendo del tipo de archivo llamamos aun importador u otro
switch ($respuesta) {
    case 'excel':
        echo Json_encode($importar->Excel());
        break;
    case 'csv':
        echo Json_encode($importar->Csv());
        break;
    case 'txt':
        echo Json_encode($importar->Txt());
        break;
}
