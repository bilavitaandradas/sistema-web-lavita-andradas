<?php

header("Content-Type: application/json; charset=UTF-8");

include_once '../../php/config.php';


// =====================================================
// 1. VALIDAÇÃO DO TOKEN DE AUTENTICAÇÃO
// =====================================================

$token = null;

$auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

if ($auth_header === null) {

    $all_headers = getallheaders();

    $auth_header = $all_headers['Authorization'] ?? null;
}

if (
    $auth_header &&
    preg_match('/Bearer\s(\S+)/', $auth_header, $matches)
) {

    $token = $matches[1];
}

if (!$token) {

    echo json_encode([
        'success' => false,
        'message' => 'Acesso não autorizado: token não fornecido.'
    ]);

    exit;
}


$stmt_user = $conn->prepare("
    SELECT id
    FROM usuarios
    WHERE TRIM(auth_token) = TRIM(?)
    AND token_expires_at > NOW()
");

$stmt_user->bind_param("s", $token);

$stmt_user->execute();

$result_user = $stmt_user->get_result();


if ($result_user->num_rows === 0) {

    echo json_encode([
        'success' => false,
        'message' => 'Acesso não autorizado: token inválido ou expirado.'
    ]);

    exit;
}


$user = $result_user->fetch_assoc();

$id_usuario_logado = $user['id'];

$stmt_user->close();


// =====================================================
// 2. BUSCAR OS QUESTIONÁRIOS PERMITIDOS PARA O USUÁRIO
// =====================================================

try {

    $query_q = "
        SELECT
            q.id_questionario,
            q.nome_questionario,
            q.descricao_questionario

        FROM questionarios q

        JOIN questionario_permissoes p
            ON q.id_questionario = p.id_questionario

        WHERE p.id_usuario = ?
    ";


    $stmt_q = $conn->prepare($query_q);

    $stmt_q->bind_param(
        'i',
        $id_usuario_logado
    );

    $stmt_q->execute();

    $result_q = $stmt_q->get_result();

    $questionarios =
        $result_q->fetch_all(MYSQLI_ASSOC);

    $stmt_q->close();


    // =================================================
    // 3. BUSCAR OS CAMPOS DOS QUESTIONÁRIOS
    // =================================================

    $campos = [];

    if (!empty($questionarios)) {

        $questionario_ids =
            array_column(
                $questionarios,
                'id_questionario'
            );


        $placeholders =
            implode(
                ',',
                array_fill(
                    0,
                    count($questionario_ids),
                    '?'
                )
            );


        $types =
            str_repeat(
                'i',
                count($questionario_ids)
            );


        $query_c = "
            SELECT
                id_campo,
                id_questionario,
                nome_campo,
                tipo_campo,
                opcoes

            FROM campos_questionario

            WHERE id_questionario IN ($placeholders)

            ORDER BY id_questionario, id_campo
        ";


        $stmt_c =
            $conn->prepare($query_c);


        $stmt_c->bind_param(
            $types,
            ...$questionario_ids
        );


        $stmt_c->execute();

        $result_c =
            $stmt_c->get_result();


        $campos =
            $result_c->fetch_all(
                MYSQLI_ASSOC
            );


        $stmt_c->close();


        // =============================================
        // 4. BUSCAR AS DEPENDÊNCIAS DOS CAMPOS
        // =============================================

        $dependencias = [];


        $query_d = "
            SELECT
                dc.id_campo_filho,
                dc.id_campo_pai,
                dc.valor

            FROM dependencias_campos dc

            INNER JOIN campos_questionario filho
                ON filho.id_campo = dc.id_campo_filho

            INNER JOIN campos_questionario pai
                ON pai.id_campo = dc.id_campo_pai

            WHERE filho.id_questionario IN ($placeholders)
            AND pai.id_questionario = filho.id_questionario

            ORDER BY
                dc.id_campo_filho,
                dc.id_campo_pai,
                dc.valor
        ";


        $stmt_d =
            $conn->prepare($query_d);


        $stmt_d->bind_param(
            $types,
            ...$questionario_ids
        );


        $stmt_d->execute();

        $result_d =
            $stmt_d->get_result();


        $dependencias =
            $result_d->fetch_all(
                MYSQLI_ASSOC
            );


        $stmt_d->close();

    } else {

        $dependencias = [];

    }


    // =================================================
    // 5. RESPOSTA FINAL
    // =================================================

    echo json_encode(
        [
            'success' => true,

            'questionarios' =>
                $questionarios,

            'campos' =>
                $campos,

            'dependencias' =>
                $dependencias
        ],

        JSON_UNESCAPED_UNICODE
    );


} catch (Exception $e) {

    echo json_encode([
        'success' => false,
        'message' =>
            'Erro no servidor: ' .
            $e->getMessage()
    ]);

}


$conn->close();

?>