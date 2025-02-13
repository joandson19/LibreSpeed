<?php
session_start();
error_reporting(E_ALL); // Use E_ALL para desenvolvimento, ajuste conforme necessário
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, s-maxage=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Inclui configurações e funções necessárias
include_once("telemetry_settings.php");
require "idObfuscation.php";

// Função para conectar ao banco de dados MySQL
function connectToDatabase() {
    global $MySql_hostname, $MySql_username, $MySql_password, $MySql_databasename;

    $conn = new mysqli($MySql_hostname, $MySql_username, $MySql_password, $MySql_databasename);
    if ($conn->connect_error) {
        die("Erro na conexão MySQL: " . $conn->connect_error);
    }
    return $conn;
}

// Verifica se o usuário está logado
if ($stats_password == "PASSWORD") {
    echo "<div align='center'><p>Defina uma senha em telemetry_settings.php para ativar o acesso (\$stats_password).</p></div>";
} elseif ($_SESSION["logged"] === true) {
    if ($_GET["op"] == "logout") {
        session_unset();
        session_destroy();
        echo "<script type='text/javascript'>window.location=location.protocol+'//'+location.host+location.pathname;</script>";
    } else {
        $conn = connectToDatabase();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Testador de Velocidade - Resultados</title>
            <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable=no" />
            <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
            <style type="text/css">
                html, body {
                    margin: 0;
                    padding: 0;
                    border: none;
                    width: 100%;
                    min-height: 100%;
                    background: #FFFFFF;
                    color: #424242;
                }
                html {
                    font-size: 1em;
                    font-family: "OpenSans", sans-serif;
                }
                body {
                    box-sizing: border-box;
                    width: 100%;
                    max-width: 90em;
                    margin: 4em auto;
                    padding: 1em 1em 4em 1em;
                }
                h1, h2, h3, h4, h5, h6 {
                    font-weight: 300;
                    margin-bottom: 0.1em;
                }
                h1 {
                    text-align: center;
                }
				.table-container {
					width: 100%;
					max-width: 100%;
					overflow-x: auto; /* Adiciona rolagem horizontal se necessário */
					display: block;
				}

				.table {
					width: 100%;
					border-collapse: collapse;
					table-layout: fixed; /* Garante que as colunas tenham tamanhos definidos */
				}

				.table th, .table td {
					padding: 12px;
					border: 1px solid #E0E0E0;
					text-align: left;
					word-wrap: break-word; /* Quebra palavras grandes */
					white-space: normal; /* Permite quebra de linha */
					overflow: hidden;
					text-overflow: ellipsis; /* Adiciona "..." caso o texto fique muito longo */
				}

				.table th {
					background-color: #f8f9fa;
					font-weight: 600;
				}

				/* Define larguras para cada coluna */
				.table th:nth-child(1), .table td:nth-child(1) { width: 5%; } /* ID */
				.table th:nth-child(2), .table td:nth-child(2) { width: 8%; } /* Data Hora */
				.table th:nth-child(3), .table td:nth-child(3) { width: 25%; } /* IP / ISP Info */
				.table th:nth-child(4), .table td:nth-child(4) { width: 25%; } /* Dispositivo */
				.table th:nth-child(5), .table td:nth-child(5) { width: 10%; text-align: center; } /* Download */
				.table th:nth-child(6), .table td:nth-child(6) { width: 10%; text-align: center; } /* Upload */

				.table tbody tr:nth-child(odd) {
					background-color: #f9f9f9;
				}

				.table tbody tr:hover {
					background-color: #f1f1f1;
				}

				/* Ajuste de badges (valores de download/upload) */
				.badge {
					padding: 5px 10px;
					border-radius: 12px;
					font-size: 0.9em;
					display: inline-block;
					max-width: 100%;
					overflow: hidden;
					text-overflow: ellipsis;
				}

				.badge-success {
					background-color: #28a745;
					color: white;
				}

				.badge-danger {
					background-color: #dc3545;
					color: white;
				}
            </style>
        </head>
        <body>
            <h1>Testador de Velocidade - Resultados</h1>
            <form action="stats.php" method="GET" style="text-align: right;">
                <input type="hidden" name="op" value="logout" />
                <input type="submit" class="btn btn-danger" value="Sair" />
            </form>
            <form action="stats.php" method="GET">
                <h3>Localizar teste</h3>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <input type="hidden" name="op" value="id" />
                        <input type="text" class="form-control" name="id" id="id" placeholder="Teste ID" value="" />
                    </div>
                    <div class="form-group col-md-4">
                        <input type="submit" class="btn btn-primary" value="Buscar" />
                        <input type="submit" class="btn btn-info" onclick="document.getElementById('id').value=''" value="Mostrar últimos 100" />
                    </div>
                </div>
            </form>
            <?php
            if ($_GET["op"] == "id" && !empty($_GET["id"])) {
                $id = $_GET["id"];
                if ($enable_id_obfuscation) $id = deobfuscateId($id);
                $query = "SELECT id, timestamp, ip, ispinfo, ua, lang, dl, ul, ping, jitter, log, extra FROM speedtest_users WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->store_result();
                $stmt->bind_result($id, $timestamp, $ip, $ispinfo, $ua, $lang, $dl, $ul, $ping, $jitter, $log, $extra);
            } else {
                $query = "SELECT id, timestamp, ip, ispinfo, ua, lang, dl, ul, ping, jitter, log, extra FROM speedtest_users ORDER BY timestamp DESC LIMIT 100";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $stmt->store_result();
                $stmt->bind_result($id, $timestamp, $ip, $ispinfo, $ua, $lang, $dl, $ul, $ping, $jitter, $log, $extra);
            }

            echo '<div class="table-container">';
            echo '<table class="table">';
            echo '<thead>
                    <tr>
                        <th>ID</th>
                        <th>Data Hora</th>
                        <th>IP / ISP Info</th>
                        <th>Dispositivo</th>
                        <th>Download</th>
                        <th>Upload</th>
                        <th>Ping</th>
                        <th>Jitter</th>
                    </tr>
                </thead>
                <tbody>';

            while ($stmt->fetch()) {
                echo "<tr>
                        <td>" . htmlspecialchars($enable_id_obfuscation ? obfuscateId($id) . " (id: " . $id . ")" : $id, ENT_HTML5, 'UTF-8') . "</td>
                        <td>" . htmlspecialchars($timestamp, ENT_HTML5, 'UTF-8') . "</td>
                        <td>" . htmlspecialchars($ip, ENT_HTML5, 'UTF-8') . "<br/><small>" . htmlspecialchars($ispinfo, ENT_HTML5, 'UTF-8') . "</small></td>
                        <td>" . htmlspecialchars($ua, ENT_HTML5, 'UTF-8') . "<br/><small>" . htmlspecialchars($lang, ENT_HTML5, 'UTF-8') . "</small></td>
                        <td><span class='badge badge-success'>" . htmlspecialchars($dl, ENT_HTML5, 'UTF-8') . " Mbps</span></td>
                        <td><span class='badge badge-success'>" . htmlspecialchars($ul, ENT_HTML5, 'UTF-8') . " Mbps</span></td>
                        <td><span class='badge badge-danger'>" . htmlspecialchars($ping, ENT_HTML5, 'UTF-8') . " ms</span></td>
                        <td><span class='badge badge-danger'>" . htmlspecialchars($jitter, ENT_HTML5, 'UTF-8') . " ms</span></td>
                    </tr>";
            }

            echo '</tbody></table></div>';
            ?>
        </body>
        </html>
        <?php
    }
} else {
    if ($_GET["op"] == "login" && $_POST["password"] === $stats_password) {
        $_SESSION["logged"] = true;
        session_regenerate_id(true);
        echo "<script type='text/javascript'>window.location=location.protocol+'//'+location.host+location.pathname;</script>";
    } else {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Testador de Velocidade - Login</title>
            <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable=no" />
            <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
            <style type="text/css">
                /* Estilos omitidos para brevidade */
            </style>
        </head>
        <body>
            <h1>Testador de Velocidade - Login</h1>
            <form action="stats.php?op=login" method="POST">
                <h3>Login</h3>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <input type="password" class="form-control" name="password" placeholder="Senha" value="" />
                    </div>
                    <div class="form-group col-md-4">
                        <input type="submit" class="btn btn-primary" value="Entrar" />
                    </div>
                </div>
            </form>
        </body>
        </html>
        <?php
    }
}
?>