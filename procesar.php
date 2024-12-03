<?php
// Configuración de la base de datos
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'base_hsjd';

// Crear la conexión
$conn = new mysqli($host, $user, $password, $database);

// Verificar si hay un error en la conexión
if ($conn->connect_error) {
    die(json_encode(["status" => false, "mensaje" => "Conexión fallida: " . $conn->connect_error]));
}

// Función para verificar si la cédula está autorizada y si ya votó
function verificarCedula($conn, $cedula) {
    $query_autorizado = "SELECT nombre, votado FROM autorizados WHERE cedula = ?";
    $stmt = $conn->prepare($query_autorizado);
    $stmt->bind_param("s", $cedula);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {
        return ["status" => false, "mensaje" => "No estás autorizado a votar."];
    }

    $datos = $resultado->fetch_assoc();
    if ($datos['votado'] == 1) {
        return ["status" => false, "mensaje" => "{$datos['nombre']}, ya has votado, no puedes votar de nuevo."];
    }

    return ["status" => true, "nombre" => $datos['nombre']];
}

// Cargar postulados por servicio
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['servicio'])) {
    $servicio = $conn->real_escape_string($_GET['servicio']);
    $query = "SELECT cedula, nombre, cargo FROM postulados WHERE servicio = '$servicio'";
    $resultado = $conn->query($query);

    $postulados = [];
    if ($resultado->num_rows > 0) {
        while ($row = $resultado->fetch_assoc()) {
            $postulados[] = $row;
        }
    }
    echo json_encode($postulados);
    exit;
}

// Procesar solicitud de voto
$response = [];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $consulta = $_POST['consulta'];
    $cedula_postulado = $_POST['postulado'];


    // Validar cédula
    $verificacion = verificarCedula($conn, $consulta);
    if (!$verificacion["status"]) {
        $response = $verificacion;
    } else {
        // Obtener datos del postulado
        $query_postulado = "SELECT nombre, cargo FROM postulados WHERE cedula = ?";
        $stmt_postulado = $conn->prepare($query_postulado);
        $stmt_postulado->bind_param("s", $cedula_postulado);
        $stmt_postulado->execute();
        $resultado_postulado = $stmt_postulado->get_result();
        $datos_postulado = $resultado_postulado->fetch_assoc();

        
        // Registrar el voto
        $query_insert = "INSERT INTO voto (cedula_votante, nombre_votante, nombre_postulado, cedula_postulado) 
                          VALUES (?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($query_insert);
        $stmt_insert->bind_param(
            "ssss",
            $consulta,
            $verificacion["nombre"],
            $datos_postulado["cargo"],
            $cedula_postulado
        );
        if ($stmt_insert->execute()) {
            // Marcar al votante como que ya votó
            $query_update = "UPDATE autorizados SET votado = 1 WHERE cedula = ?";
            $stmt_update = $conn->prepare($query_update);
            $stmt_update->bind_param("s", $consulta);
            $stmt_update->execute();

            $response = ["status" => true, "mensaje" => "Gracias por votar, {$verificacion['nombre']}. Tu voto ha sido registrado."];
        } else {
            $response = ["status" => false, "mensaje" => "Error al registrar el voto.". $stmt_insert->error];
        }
    }
}

// Devolver respuesta en formato JSON
echo json_encode($response);
$conn->close();
?>
