<?php
require_once "connection.php";
require_once "../lib/PHPExcel/PHPExcel.php";
require_once "../lib/PHPExcel/PHPExcel/IOFactory.php";

/**
 * @author SalvadorAsquit
 */

class Import
{

    public $baseDeDatos; # base de datos 
    public $tablaDeLaBaseDeDatos; # Tabla de la base de datos
    public $usuario; # usuario dela base de datos
    public $password; # contraseña de la base de datos
    public $ip; # ip para conectar la base de datos
    public $mysqli; # objeto de tipo coneccion mysqli
    //---------------------------------------------------------------------
    public $file; # archivo csv, excel o txt
    public $extension; # extension del archivo
    public $headersbd; # cabeceras de la base de datos
    public $data; # los datos del archivo
    public $fail; # fallos del archivo
    public $insertados; # contidad de filas insertadas
    public $totaldefilas; # cantidad de filas del archivo
    public $fallosInsert; # fallos que no se han podido insertar
    public $delimitador; # delimitador para los  csv y los txt




    /**
     * constructor
     * @param file $Object el archivo que de hoja de calculo
     * @param extension $String la  extension del archivo
     * @param delimitador $String delimitador para los archivos csv y los txt
     * @param ip $String es la ip de la base de datos a la que nos conectaremos
     * @param usu $String es el usuario de la base de datos
     * @param pass $String es la contraseña para acceder a la base de datos
     * @param bd $String la base de datos para conectar
     * @param tabla $String la tabla en la que insertaremos los datos
     */
    public function __construct($file, $extension, $delimitador, $ip, $usu, $pass, $bd, $tabla)
    {
        $this->file = $file;
        $this->extension = $extension;
        $this->delimitador = $delimitador;

        $this->baseDeDatos = $bd;
        $this->tablaDeLaBaseDeDatos = $tabla;

        //coneccion para la base de datos
        $coneccion = new Connection($ip, $usu, $pass, $this->baseDeDatos);
        $this->mysqli = $coneccion->coneccion_Mysqli();
    }

    /**
     * Funcion de control de Excel si verificar datos nos devuelve en la array datos, el indice fail significa que hay fallos y no continuaremos, 
     * sino insertaremos los datos y veremos unos detalles
     */
    function Excel()
    {
        $datos = self::procesarExcel();
        if (isset($datos["fail"])) {
            return $datos;
        } else {
            self::insertar($datos, $this->headersbd);
            $resultado = self::ObtenerDetalle();
            return $resultado;
        }
    }

    /**
     * Funcion de control de Csv si verificar datos nos devuelve en la array datos, el indice fail significa que hay fallos y no continuaremos, 
     * sino insertaremos los datos y veremos unos detalles
     */
    function Csv()
    {
        $datos = self::procesarCsv();
        if (isset($datos["fail"])) {
            return $datos;
        } else {
            self::insertar($datos, $this->headersbd);
            $resultado = self::ObtenerDetalle();
            return $resultado;
        }
    }

    /**
     * Funcion de control de Txt si verificar datos nos devuelve en la array datos, el indice fail significa que hay fallos y no continuaremos, 
     * sino insertaremos los datos y veremos unos detalles
     */
    function Txt()
    {
        $datos = self::procesarTxt();
        if (isset($datos["fail"])) {
            return $datos;
        } else {
            self::insertar($datos, $this->headersbd);
            $resultado = self::ObtenerDetalle();
            return $resultado;
        }
    }

    /**
     * Procesamos los datos : haciendo una copia de seguridad, recorriendo el archivo, combinando los datos y las cabeceras, contando las filas y comprobando si las cabeceras coinciden
     */
    function procesarTxt()
    {
        // hacemos la copia de seguridad
        $file = self::copiarArchivoSeg();

        // lee el archivo
        $txt_file = fopen($file, 'r');

        // sacamos los datos
        while (($datos = fgetcsv($txt_file, 0, "{$this->delimitador}")) !== FALSE) {
            $array[] = $datos;
        }
        fclose($txt_file);

        // sacamos las cabeceras
        $cabeceras = array_shift($array);

        // combinamos los datos y las cabeceras
        foreach ($array as $fila => $campos) {
            $data[$fila] = array_combine($cabeceras, $campos);
        }

        // contamos las filas
        $this->data = $data;
        $this->totaldefilas = count($data);

        // comparamos las cabeceras
        $resultado = self::compararCabeceras($cabeceras);

        if ($resultado == "ok") {
            return $data;
        } else {
            return $resultado;
        }
    }

    /**
     * Procesamos los datos : haciendo una copia de seguridad, recorriendo el archivo, combinando los datos y las cabeceras, contando las filas y comprobando si las cabeceras coinciden
     */
    function procesarCsv()
    {
        // hacemos la copia de seguridad
        $file = self::copiarArchivoSeg();

        // lee el archivo
        $csv_file = fopen($file, 'r');

        // sacamos los datos 
        while (($datos = fgetcsv($csv_file, 0, "{$this->delimitador}")) !== FALSE) {
            $array[] = $datos;
        }
        fclose($csv_file);

        // sacamos las cabeceras
        $cabeceras = array_shift($array);

        // combinamos las cabeceras y los datos
        foreach ($array as $fila => $campos) {
            $data[$fila] = array_combine($cabeceras, $campos);
        }

        // contamos las filas
        $this->data = $data;
        $this->totaldefilas = count($data);

        // comprobamos las cabeceras
        $resultado = self::compararCabeceras($cabeceras);

        if ($resultado == "ok") {
            return $data;
        } else {
            return $resultado;
        }
    }

    /**
     * Procesamos los datos : haciendo una copia de seguridad, recorriendo el archivo, combinando los datos y las cabeceras, contando las filas y comprobando si las cabeceras coinciden
     */
    function procesarExcel()
    {
        // hacemos la copia de seguridad
        $file = self::copiarArchivoSeg();

        $reader = new PHPExcel_Reader_Excel2007();
        $reader->setReadDataOnly(true);
        $excel = $reader->load($file);

        // sacamos los datos y las cabeceras
        $worksheet = $excel->getActiveSheet()->toArray(null, false, false, false);
        $headers = array_shift($worksheet);

        $data = array();

        // Cruce de cabeceras y valores
        foreach ($worksheet as $fila => $campos) {
            $data[$fila] = array_combine($headers, $campos);
        }

        // contamos las filas
        $this->data = $data;
        $this->totaldefilas = count($data);

        // comparamos las cabeceras
        $resultado = self::compararCabeceras($headers);

        if ($resultado == "ok") {
            return $data;
        } else {
            return $resultado;
        }
    }

    /**
     * Creamos una copia de seguridad del archivo y es la que manipulamos con el resto de codigo
     */
    function copiarArchivoSeg()
    {
        $path = str_replace("model", "files", __DIR__);
        $archivoName = $_FILES["fichero"]["name"];
        $archivotemp = $_FILES["fichero"]["tmp_name"];
        $date = date("ymdHi");
        $nombre = "upload_{$archivoName}_{$date}.xlsx";

        copy($archivotemp, "$path/$nombre");
        $file = "$path/$nombre";

        return $file;
    }

    /** 
     * filtros generales trim , // y caracteres raros
     */
    function limpiaDatos($dato)
    {
        $dato = addslashes($dato);
        $dato = str_replace(",", ".", $dato);
        $dato = str_replace("&", "", $dato);
        $dato = str_replace("^", "", $dato);
        $dato = str_replace("Ç", "", $dato);
        $dato = str_replace(";", "", $dato);
        $dato = trim($dato);

        return $dato;
    }

    /**
     * Comprovamos la extension del archivo
     */
    public function comprobarExtension()
    {
        $csv = "text/csv";
        $txt = "text/plain";
        $xlsx = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
        $formatos_Validos = [$txt, $xlsx, $csv];
        $respuesta = "";

        if ((in_array($this->extension, $formatos_Validos))) {
            switch ($this->extension) {
                case $txt:
                    $respuesta = "txt";
                    break;

                case $xlsx:
                    $respuesta = "excel";
                    break;

                case $csv:
                    $respuesta = "csv";
                    break;
            }
        } else {
            $respuesta = "fail";
        }

        return $respuesta;
    }

    /**
     * comparamos que las cabeceras sean iguales sino es el caso no continuaremos y devolveremos una array con las cabeceras faltantes
     */
    function compararCabeceras($cabecerasArchivo)
    {

        $sql = "SHOW COLUMNS FROM {$this->tablaDeLaBaseDeDatos}";

        $result = $this->mysqli->query($sql);

        while ($row = $result->fetch_assoc()) {
            $cabecerasbd[] = $row["Field"];
        }
        $this->headersbd = $cabecerasbd;

        // comparacion de las cabeceras
        $fail = "";
        $error = "";

        if (count($cabecerasbd) > count($cabecerasArchivo)) {
            $fail .=  "<ul class='list-group list-group-flush'>";
            $fail .= "<li class='list-group-item d-flex justify-content-between align-items-center'><span class='badge bg-danger rounded-pill'>La Base de Datos tiene  mas Columnas que el Archivo</span></li></ul>";
            $error = "<ul class='list-group list-group-flush'>";
            $error .= "<li class='list-group-item d-flex justify-content-between align-items-center'>Fallo en las columnas<span class='badge bg-danger rounded-pill'> X </span></li></ul>";
            $fallos = array("resultado" => $error, "fail" => $fail);
        } else {
            if (count($cabecerasbd) < count($cabecerasArchivo)) {
                $fail .=  "<ul class='list-group list-group-flush'>";
                $fail .= "<li class='list-group-item d-flex justify-content-between align-items-center'><span class='badge bg-danger rounded-pill'>La Base de Datos tiene  menos Columnas que el Archivo</span></li></ul>";
                $error = "<ul class='list-group list-group-flush'>";
                $error .= "<li class='list-group-item d-flex justify-content-between align-items-center'>Fallo en las columnas<span class='badge bg-danger rounded-pill'> X </span></li></ul>";
                $fallos = array("resultado" => $error, "fail" => $fail);
            }
        }


        if (isset($fallos)) {
            return $fallos;
        }


        foreach ($cabecerasbd as $key => $value) {
            $esta = "no esta";

            foreach ($cabecerasArchivo as $key2 => $contenido) {

                if (strtolower($contenido) == strtolower($value)) {
                    $esta = "esta";
                }
            }

            if ($esta == "no esta") {

                $fail .=  "<ul class='list-group list-group-flush'>";
                $fail .= "<li class='list-group-item d-flex justify-content-between align-items-center'><span class='badge bg-danger rounded-pill'>Fail : la cabecera {$value} no esta en las cabeceras de la base de datos</span></li></ul>";
                $error = "<ul class='list-group list-group-flush'>";
                $error .= "<li class='list-group-item d-flex justify-content-between align-items-center'><span class='badge bg-danger rounded-pill'>Cabeceras no coinciden<span><span class='badge bg-danger rounded-pill'> X </span></li></ul>";
            }
        }
        $fallos = array("resultado" => $error, "fail" => $fail);

        // hay un error y salimos
        if (!isset($fallos)) {
            return $fallos;
        } else {
            $this->fail = 0;
            return "ok";
        }
    }

    /**
     * Insert en la base de datos y los filtramos
     */
    function insertar($data, $cabecerasbd)
    {
        $stringColumn = $this->mysqli->real_escape_string(implode(",", $cabecerasbd));

        $total = 0;
        $fila = 0;
        foreach ($data as  $arrayData) {

            foreach ($arrayData as $key => $value) {
                $aux[$key] = $this->mysqli->real_escape_string($value);
                $aux[$key] = trim($aux[$key]);
            }

            $stringData = "'" . implode("','", $aux) . "'";

            $sql =  "REPLACE INTO {$this->baseDeDatos}.{$this->tablaDeLaBaseDeDatos} ({$stringColumn}) VALUES ({$stringData});";
            $this->mysqli->query($sql);


            if ($this->mysqli->error) {

                $this->fallosInsert[] = "<strong>FILA {$fila} : </strong> " . strtoupper($this->mysqli->error);
            } else {
                $total = $total + 1;
            }
        }

        $this->insertados = $total;
    }

    /**
     * mostramos unos detalles generales de lo sucedido
     */
    public function ObtenerDetalle()
    {
        $erroneos = (isset($this->fallosInsert)) ? count($this->fallosInsert) : 0;
        $insertados = $this->insertados;

        $listResult = "<ul class='list-group list-group-flush'>";
        $listResult .= "<li class='list-group-item d-flex justify-content-between align-items-center'>Número de filas del fichero<span class='badge bg-primary rounded-pill'>$this->totaldefilas</span></li>";
        $listResult .= "<li class='list-group-item d-flex justify-content-between align-items-center'>Número de filas insertadas correctamente<span class='badge bg-success rounded-pill'>$insertados</span></li>";
        $listResult .= "<li class='list-group-item d-flex justify-content-between align-items-center'>Número de de filas con errores (no insertadas)<span class='badge bg-danger rounded-pill'>$erroneos</span></li></ul>";

        $listError = "<ul class='list-group list-group-flush text-start'>";
        if (isset($this->incoherenceData)) {

            foreach ($this->fallosInsert as $key => $value) {
                foreach ($value as $key => $value) {
                    $listError .= "<li class='list-group-item'>{$value}</li>";
                }
            }
        }
        $listError .= "</ul>";

        $detalle = array(
            "resultado" => $listResult,
            "fail" => $listError
        );

        return $detalle;
    }
}
