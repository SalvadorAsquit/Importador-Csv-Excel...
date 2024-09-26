<?php

/**
 * @author SalvadorAsquit
 * conecta con la base de datos
 */
class Connection
{
    public $ip;
    public $usuario;
    public $password;
    public $bd;


    /**
     * Funcion De la coneccion con la base de datos
     * @param servidor $String es la direccion ip del servidor
     * @param usuario $String es el login de la base de datos
     * @param password $String es la contraseña del usuario
     * @param bd  $String es el nombre d ela base de datos
     */
    function __construct($ip, $usuario, $password, $bd)
    {
        $this->ip = $ip;
        $this->usuario = $usuario;
        $this->password = $password;
        $this->bd = $bd;
    }


    function coneccion_Mysqli()
    {
        $mysqli = new mysqli($this->ip, $this->usuario, $this->password, $this->bd);

        // comprueba que no falle la coneccion
        if ($mysqli->connect_error) {
            die("Connection failed: " . $mysqli->connect_error);
        }

        return $mysqli;
    }
}
