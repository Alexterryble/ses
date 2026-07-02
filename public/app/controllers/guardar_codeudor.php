<?php
require_once(__DIR__ . '/../db/conexion.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $solicitud_id = $_POST['solicitud_id'] ?? null;
    if (!$solicitud_id) {
        echo json_encode(['status' => 'error', 'error' => 'Falta el ID de solicitud']);
        exit;
    }

    try {
        // Preparar parámetros
        $params = [
            $_POST['form_co_nombre'] ?? null,
            $_POST['form_co_parentesco'] ?? null,
            $_POST['form_co_apellido_paterno'] ?? null,
            $_POST['form_co_apellido_materno'] ?? null,
            $_POST['form_co_correo'] ?? null,
            $_POST['form_co_genero'] ?? null,
            $_POST['form_co_nacimiento'] ?? null,
            $_POST['form_co_entidad'] ?? null,
            $_POST['form_co_dependientes'] ?? null,
            $_POST['form_co_nacionalidad'] ?? null,
            $_POST['form_co_pais_nacimiento'] ?? null,
            $_POST['form_co_rfc'] ?? null,
            $_POST['form_co_curp'] ?? null,
            $_POST['form_co_direccion'] ?? null,
            $_POST['form_co_entre_calles'] ?? null,
            $_POST['form_co_colonia'] ?? null,
            $_POST['form_co_cp'] ?? null,
            $_POST['form_co_municipio'] ?? null,
            $_POST['form_co_estado'] ?? null,
            $_POST['form_co_pais'] ?? null,
            $_POST['form_co_tiempo'] ?? null,
            $_POST['form_co_tel'] ?? null,
            $_POST['form_co_cel'] ?? null,
            $_POST['form_co_mejor_hora'] ?? null
        ];

        // Verificar si ya existe un registro
        $check = $conn->prepare("SELECT id FROM codeudores WHERE solicitud_id = ?");
        $check->execute([$solicitud_id]);

        if ($check->rowCount() > 0) {
            // UPDATE
            $stmt = $conn->prepare("
                UPDATE codeudores SET
                    nombre = ?, parentesco = ?, apellido_paterno = ?, apellido_materno = ?,
                    correo = ?, genero = ?, fecha_nacimiento = ?, entidad_federativa = ?, dependientes = ?,
                    nacionalidad = ?, pais_nacimiento = ?, rfc = ?, curp = ?, direccion_actual = ?,
                    entre_calles = ?, colonia = ?, codigo_postal = ?, municipio = ?, estado = ?,
                    pais = ?, tiempo_domicilio = ?, telefono = ?, celular = ?, horario_contacto = ?
                WHERE solicitud_id = ?
            ");
            $stmt->execute([...$params, $solicitud_id]);
        } else {
            // INSERT
            $stmt = $conn->prepare("
                INSERT INTO codeudores (
                    solicitud_id, nombre, parentesco, apellido_paterno, apellido_materno,
                    correo, genero, fecha_nacimiento, entidad_federativa, dependientes,
                    nacionalidad, pais_nacimiento, rfc, curp, direccion_actual,
                    entre_calles, colonia, codigo_postal, municipio, estado,
                    pais, tiempo_domicilio, telefono, celular, horario_contacto
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ");
            $stmt->execute([$solicitud_id, ...$params]);
        }

        echo json_encode(['status' => 'ok']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'error' => $e->getMessage()]);
    }
}
?>
