<?php
if (!defined('ACCESS_GRANTED')) {
    define('ACCESS_GRANTED', true);
}


function obtenerConductores() {
    require_once("../.c0nn3ct/db_secure.php");

    $conductores = [];

    $sql = "SELECT clm_tra_id, clm_tra_nombres, clm_tra_dni
            FROM tb_trabajador
            WHERE clm_tra_tipo_trabajador = 'Conductor'
            ORDER BY clm_tra_nombres ASC";

    $res = $conn->query($sql);

    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $conductores[] = [
                'id' => $row['clm_tra_id'],
                'nombres' => $row['clm_tra_nombres'],
                'dni' => $row['clm_tra_dni']
            ];
        }
    }
    return $conductores;
}
?>
