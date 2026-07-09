<?php
// Configurações de erro para desenvolvimento (remover em produção)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Autoload do Composer para usar o PHPMailer
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Função para registrar logs de erro
function logError($message) {
    $logFile = 'email_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitização e validação dos dados
    $nome = trim($_POST['nome'] ?? '');
    $idade = trim($_POST['idade'] ?? '');
    $numero = trim($_POST['numero'] ?? '');
    $disponibilidade = trim($_POST['disponibilidade'] ?? '');
    
    // Validação básica
    if (empty($nome) || empty($numero)) {
        logError("Dados obrigatórios não preenchidos - Nome: '$nome', Telefone: '$numero'");
        echo "Por favor, preencha todos os campos obrigatórios.";
        exit;
    }

    $mail = new PHPMailer(true);

    try {
        // Log do início da tentativa de envio
        logError("Iniciando envio de email para contato: $nome ($numero)");
        
        // Configurações do servidor SMTP
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = 'email-ssl.com.br';
        $mail->Port = 587;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->SMTPAuth = true;
        $mail->Username = 'webmail@graciebarravilasiam.com.br';
        $mail->Password = 'Pauloortega123@';
        
        // Configurações de timeout e SSL
        $mail->Timeout = 30;
        $mail->SMTPKeepAlive = false;
        
        // Configurações SSL para evitar problemas de certificado
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
                'cafile' => false,
                'capath' => false,
                'ciphers' => 'DEFAULT'
            )
        );
        
        // Configurações de charset
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        // Remetente (quem envia)
        $mail->setFrom('webmail@graciebarravilasiam.com.br', 'Site Gracie Barra Vila Siam');
        $mail->addReplyTo('webmail@graciebarravilasiam.com.br', 'Site Gracie Barra Vila Siam');
        
        // Destinatário (quem recebe)
        $mail->addAddress('gb.vilasiam@gmail.com');

        // Conteúdo do email
        $mail->isHTML(true);
        $mail->Subject = 'Novo contato pelo site - ' . $nome;
        $mail->Body = "
            <h2>Novo contato recebido pelo site</h2>
            <p><strong>Nome:</strong> " . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . "</p>
            <p><strong>Idade:</strong> " . htmlspecialchars($idade, ENT_QUOTES, 'UTF-8') . "</p>
            <p><strong>Telefone:</strong> " . htmlspecialchars($numero, ENT_QUOTES, 'UTF-8') . "</p>
            <p><strong>Disponibilidade:</strong><br>" . nl2br(htmlspecialchars($disponibilidade, ENT_QUOTES, 'UTF-8')) . "</p>
            <hr>
            <p><small>Email enviado em: " . date('d/m/Y H:i:s') . "</small></p>
        ";

        // Versão em texto puro para clientes que não suportam HTML
        $mail->AltBody = "Novo contato recebido pelo site\n\n" .
                        "Nome: $nome\n" .
                        "Idade: $idade\n" .
                        "Telefone: $numero\n" .
                        "Disponibilidade: $disponibilidade\n\n" .
                        "Email enviado em: " . date('d/m/Y H:i:s');

        // Tentativa de envio
        $mail->send();
        
        // Log de sucesso
        logError("Email enviado com sucesso usando email-ssl.com.br:587 para: $nome ($numero)");
        
        // Redirecionamento após sucesso
        header("Location: obrigado.html");
        exit;
        
    } catch (Exception $e) {
        // Log detalhado do erro
        $errorMsg = "Erro ao enviar email - Nome: $nome, Telefone: $numero, Erro: " . $mail->ErrorInfo;
        logError($errorMsg);
        
        // Log da exceção completa
        logError("Exception details: " . $e->getMessage());
        
        // Resposta para o usuário (sem expor detalhes técnicos)
        echo "Desculpe, ocorreu um erro ao enviar sua mensagem. Tente novamente em alguns minutos ou entre em contato diretamente conosco.";
        
        // Em desenvolvimento, você pode descomentar a linha abaixo para ver o erro detalhado
        // echo "<br><br>Detalhes do erro (remover em produção): " . $mail->ErrorInfo;
    }
} else {
    // Log de tentativa de acesso inválido
    logError("Tentativa de acesso direto ao script de envio de email");
    echo "Acesso não autorizado.";
}
