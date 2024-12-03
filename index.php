<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Votación</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #2c3e50;
            color: #ecf0f1;
            text-align: center;
            padding: 20px;
        }
        form {
            background-color: #34495e;
            padding: 20px;
            border-radius: 10px;
            display: inline-block;
            text-align: left;
        }
        select, button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: none;
            border-radius: 5px;
        }
        input{
            width: 98%;
            padding: 10px;
            margin: 10px 0;
            border: none;
            border-radius: 5px;
        }
        button {
            background-color: #27ae60;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background-color: #2ecc71;
        }
    </style>
</head>
<body>
    <h2>Formulario de Votación</h2>
    <form id="votacionForm">
        <label for="consulta">Cédula:</label>
        <input type="text" id="consulta" name="consulta" required placeholder="Ingresa tu cédula">

        <p><strong>Criterios:</strong> Puntualidad, Responsabilidad, Liderazgo</p>

        <label for="servicio">Selecciona un servicio:</label>
        <select id="servicio" name="servicio" required>
            <option value="">Seleccione un servicio</option>
            <?php
            // Obtener los servicios únicos
            $conn = new mysqli('localhost', 'root', '', 'base_hsjd');
            if ($conn->connect_error) {
                die("Conexión fallida: " . $conn->connect_error);
            }
            $query = "SELECT DISTINCT servicio FROM postulados";
            $resultado = $conn->query($query);
            if ($resultado->num_rows > 0) {
                while ($row = $resultado->fetch_assoc()) {
                    echo "<option value='{$row['servicio']}'>{$row['servicio']}</option>";
                }
            }
            $conn->close();
            ?>
        </select>

        <label for="postulado">Selecciona un postulado:</label>
        <select id="postulado" name="postulado" required disabled>
            <option value="">Seleccione un postulado</option>
        </select>

        <button type="submit">Votar</button>
    </form>

    <script>
        document.getElementById('servicio').addEventListener('change', function() {
            const servicio = this.value;
            const postuladoSelect = document.getElementById('postulado');
            postuladoSelect.innerHTML = '<option value="">Cargando...</option>';
            postuladoSelect.disabled = true;

            if (servicio) {
                fetch(`procesar.php?servicio=${encodeURIComponent(servicio)}`)
                    .then(response => response.json())
                    .then(data => {
                        postuladoSelect.innerHTML = '<option value="">Seleccione un postulado</option>';
                        data.forEach(postulado => {
                            postuladoSelect.innerHTML += `<option value="${postulado.id}">${postulado.nombre} (${postulado.cargo})</option>`;
                        });
                        postuladoSelect.disabled = false;
                    })
                    .catch(error => {
                        alert('Error al cargar postulados: ' + error);
                        postuladoSelect.innerHTML = '<option value="">Seleccione un postulado</option>';
                        postuladoSelect.disabled = true;
                    });
            }
        });

        document.getElementById('votacionForm').addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);

            fetch('procesar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                alert(data.mensaje);
                if (data.status) {
                    document.getElementById('votacionForm').reset();
                }
            })
            .catch(error => alert('Hubo un error en la solicitud.'));
        });
        
    </script>
</body>
</html>
