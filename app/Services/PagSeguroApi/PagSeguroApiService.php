<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\PagSeguroApi;

use App\Repositories\Gerenciamento\DominioRepository;
use App\Repositories\Clinicas\PagSeguroConfigRepository;
use stdClass;
use App\Repositories\Clinicas\ConsultaRepository;

/**
 * Description of Activities
 *
 * @author ander
 */
class PagSeguroApiService {

    private $ambiente = 'producao'; //producao ou sandbox
    private $email = "";
    private $url_retorno = "";
    private $token;
    private $configAmbiente = array('producao' => array(
            'url_checkout' => 'https://ws.pagseguro.uol.com.br/v2/checkout/',
            'url_redirect' => 'https://pagseguro.uol.com.br/v2/checkout/payment.html?code=',
            'url_notificacao' => 'https://ws.pagseguro.uol.com.br/v2/transactions/notifications/',
            'url_transactions' => 'https://ws.pagseguro.uol.com.br/v3/transactions/',
            'url_transactions_cancels' => 'https://ws.pagseguro.uol.com.br/v2/transactions/cancels/',
            'url_transactions_refunds' => 'https://ws.pagseguro.uol.com.br/v2/transactions/refunds/',
            'url_transactions_abandoned' => 'https://ws.pagseguro.uol.com.br/v2/transactions/abandoned/',
        ),
        'sandbox' => array(
            'url_checkout' => 'https://ws.sandbox.pagseguro.uol.com.br/v2/checkout/',
            'url_redirect' => 'https://sandbox.pagseguro.uol.com.br/v2/checkout/payment.html?code=',
            'url_notificacao' => 'https://ws.sandbox.pagseguro.uol.com.br/v2/transactions/notifications/',
            'url_transactions' => 'https://ws.sandbox.pagseguro.uol.com.br/v3/transactions/',
            'url_transactions_cancels' => 'https://ws.sandbox.pagseguro.uol.com.br/v2/transactions/cancels/',
            'url_transactions_refunds' => 'https://ws.sandbox.pagseguro.uol.com.br/v2/transactions/refunds/',
            'url_transactions_abandoned' => 'https://ws.sandbox.pagseguro.uol.com.br/v2/transactions/abandoned/',
        ),
    );
    private $url;
    private $url_checkout;
    private $url_redirect;
    private $url_notificacao;
    private $url_transactions;
    private $url_transactions_cancels;
    private $url_transactions_abandoned;
    private $email_token = ""; //NÃO MODIFICAR
    private $statusCode = array(0 => "Pendente",
        1 => "Aguardando pagamento",
        2 => "Em análise",
        3 => "Pago",
        4 => "Disponível",
        5 => "Em disputa",
        6 => "Devolvida",
        7 => "Cancelada");
    private $tipoPagamento = array(
        1 => "Cartão de crédito",
        2 => "Boleto",
        3 => "Débito online (TEF)",
        4 => "Saldo PagSeguro",
        5 => "Oi Paggo",
        6 => "Depósito em conta");
    private $tipoPagamentoRecebimento = array(
        1 => "8",
        2 => "3",
        3 => "6",
        4 => "9",
        5 => "",
        6 => "4");

    function setAmbiente($ambiente) {
        $this->ambiente = $ambiente;
    }

    function setEmail($email) {
        $this->email = $email;
    }

    function setToken($token) {
        $this->token = $token;
    }

    function setUrl_retorno($url_retorno) {
        $this->url_retorno = $url_retorno;
    }

    function getStatusCode($code) {
        return $this->statusCode[(int) $code];
    }

    function getTipoPagamento($code) {
        return $this->tipoPagamento[(int) $code];
    }

    function getTipoPagamentoRecebimento($code) {
        return $this->tipoPagamentoRecebimento[(int) $code];
    }

    function getAmbiente() {
        return $this->ambiente;
    }

    public function __construct() {
        
    }

    private function verificaAmbiente() {
//        $this->url = $this->configAmbiente[$this->ambiente]['url'];
        $this->url_checkout = $this->configAmbiente[$this->ambiente]['url_checkout'];
        $this->url_redirect = $this->configAmbiente[$this->ambiente]['url_redirect'];
        $this->url_notificacao = $this->configAmbiente[$this->ambiente]['url_notificacao'];
        $this->url_transactions = $this->configAmbiente[$this->ambiente]['url_transactions'];
        $this->url_transactions_cancels = $this->configAmbiente[$this->ambiente]['url_transactions_cancels'];
        $this->url_transactions_abandoned = $this->configAmbiente[$this->ambiente]['url_transactions_abandoned'];
        $this->url_transactions_refunds = $this->configAmbiente[$this->ambiente]['url_transactions_refunds'];

        if ($this->ambiente == 'producao') {
            $this->email_token = "?email=" . $this->email . "&token=" . $this->token;
        } else {
            $this->email_token = "?email=" . $this->email . "&token=" . $this->token;
        }
        $this->url .= $this->email_token;
    }

    private function connect($url, $post = false, $dados = null) {


        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if ($post) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded; charset=UTF-8'));
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($dados));
        }

        $return = curl_exec($curl);

//        if ($return == 'Unauthorized') {
//            echo 'Não Autorizado!';
//            exit;
//        }

        return $return;
    }

    /**
     * Dados da notificação por id
     * @param type $CodTransaction
     * @return type
     */
    public function getNotificationByCode($codeNotification) {

        $this->verificaAmbiente();

        $url = $this->url_notificacao . $codeNotification . $this->email_token;
        $transaction = $this->connect($url);
        if ($checkout == 'Unauthorized') {
            return array('error' => 'Não Autorizado');
        }

        $notification = simplexml_load_string($transaction);
        if (count($notification->error) > 0) {
            echo 'erro';  //Insira seu código avisando que o sistema está com problemas
        }
        return $notification;
    }

    /**
     * Dados da transação por id
     * @param type $CodTransaction
     * @return type
     */
    public function getTransactionsByIdTransactions($CodTransaction) {

        $this->verificaAmbiente();

        $url = $this->url_transactions . $CodTransaction . $this->email_token;
        $transaction = $this->connect($url);

        $transaction_obj = simplexml_load_string($transaction);

        return $transaction_obj;
    }

    /**
     * PEgas as transações pela referencia
     * 
     * Obs: O período deve ser igual ou menor que 30 dias
     * @param type $initialDate
     * @param type $finalDate
     * @param type $page
     * @param type $maxPageResults
     * @return type
     */
    public function getTransactionsByReference($reference, $initialDate = null, $finalDate = mull, $page = 1, $maxPageResults = 100) {

        $this->verificaAmbiente();

        $url = $this->url_transactions . $this->email_token
                . "&reference=$reference";
        if (!empty($initialDate)) {
            $url .= "&initialDate=$initialDate"
                    . "&finalDate=$finalDate"
                    . "&page=$page"
                    . "&maxPageResults=$maxPageResults";
        }

        $transactions = $this->connect($url);

        $transactions_obj = simplexml_load_string($transactions);
        if (count($transactions_obj->error) > 0) {
            echo 'erro';  //Insira seu código avisando que o sistema está com problemas
        }
        return $transactions_obj;
    }

    public function getTransactionsByDateInterval($initialDate, $finalDate, $page = 1, $maxPageResults = 100) {


        $this->verificaAmbiente();
        $url = $this->url_transactions . $this->email_token
                . "&initialDate=$initialDate"
                . "&finalDate=$finalDate"
                . "&page=$page"
                . "&maxPageResults=$maxPageResults";
        $transactions = $this->connect($url);

        $transactions_obj = simplexml_load_string($transactions);
        if (count($transactions_obj->error) > 0) {
            echo 'erro';  //Insira seu código avisando que o sistema está com problemas
        }
        return $transactions_obj;
    }

    /**
     * Checkout
     * 
     * 
     * 
     * @return type
     *  code
     *   string
     *   Código identificador do pagamento criado. Este código deve ser usado para direcionar o comprador para o fluxo de pagamento. Formato: Uma sequência de 32 caracteres.
     * 
     *   date
     *  string
     *  Data de criação do código de pagamento. Formato: YYYY-MM-DDThh:mm:ss.sTZD
     */

    /**
     * 
     * @param type $dados  Array da classe PagSeguroItens ou uma única instância
     * @return \stdClass
     */
    public function checkout($dados, $idReferencia = null, $emailComprador = null, $duracaoLinkHora = null) {

        $this->verificaAmbiente();
        $data['email'] = $this->email;
        $data['token'] = $this->token;
        $data['currency'] = 'BRL';

        $retorno = null;
        if (is_array($dados)) {

            $i = 1;

            foreach ($dados as $obj) {
                $data['itemId' . $i] = $obj->getItemId();
                $data['itemDescription' . $i] = $obj->getItemDescription();
                $data['itemAmount' . $i] = $obj->getItemAmount();
                $data['itemQuantity' . $i] = $obj->getItemQuantity();
                $data['itemWeight' . $i] = $obj->getItemWeight();

                $i++;
            }
        } else {
            $data['itemId1'] = $dados->getItemId();
            $data['itemDescription1'] = $dados->getItemDescription();
            $data['itemAmount1'] = $dados->getItemAmount();
            $data['itemQuantity1'] = $dados->getItemQuantity();
            $data['itemWeight1'] = $dados->getItemWeight();
        }


///Timeout -       O tempo mínimo da duração do checkout é de 20 minutos e máximo de 100000 minutos.
//$data['timetout'] = '';
//MaxUses = Um número inteiro maior que 0 e menor ou igual a 999.
        $data['maxUses'] = 1;

//Determina o prazo (em segundos) durante o qual o código de pagamento criado pela chamada à API de Pagamentos poderá ser usado.
//Este parâmetro pode ser usado como um controle de segurança.
//Formato: Um número inteiro maior ou igual a 30 e menor ou igual a 999999999.
        if (!empty($duracaoLinkHora)) {
            $data['maxAge'] = 3600 * $duracaoLinkHora;
        }


        $data['reference'] = $idReferencia;
//        $data['senderName'] = 'Jose Comprador';
//        $data['senderAreaCode'] = '99';
//        $data['senderPhone'] = '99999999';
        if (!empty($emailComprador)) {
            $data['senderEmail'] = $emailComprador;
        }




        $url = $this->url_checkout . $this->email_token;
        $checkout = $this->connect($url, true, $data);

        if ($checkout == 'Unauthorized') {
            $retorno = new stdClass();
            $retorno->error = 'Não Autorizado';
            return $retorno;
        }


      
        $checkout = simplexml_load_string($checkout);

        $retorno = new stdClass();
        $retorno->code = $checkout->code;
        $retorno->date = substr($checkout->date, 0, 10);
        $retorno->hour = substr($checkout->date, 12, 5);
        $retorno->link_checkout = $this->url_redirect . $checkout->code;

        $retorno->error = (isset($checkout->error) and!empty($checkout->error)) ? $checkout->error : null;

//        $transactions_obj = simplexml_load_string($transactions);
//        if (count($transactions_obj->error) > 0) {
//             echo 'erro';  //Insira seu código avisando que o sistema está com problemas
//        }
        return $retorno;
    }

    /**
     * Cancela uma transação
     * 
     * @param type $transactionCode
     * @return type
     */
    public function cancelTransaction($transactionCode) {

        $this->verificaAmbiente();
        $url = $this->url_transactions_cancels . $this->email_token;
        $transactions = $this->connect($url, true, ['transactionCode' => $transactionCode]);
        if ($checkout == 'Unauthorized') {
            return array('error' => 'Não Autorizado');
        }

        $transactions_obj = simplexml_load_string($transactions);

        return $transactions_obj;
    }

    /**
     * Estorna uma transação
     * 
     * @param type $transactionCode
     * @return type
     */
    public function refundsTransaction($transactionCode) {

        $this->verificaAmbiente();
        $url = $this->url_transactions_refunds . $this->email_token;
        $transactions = $this->connect($url, true, ['transactionCode' => $transactionCode]);

        if ($checkout == 'Unauthorized') {
            return array('error' => 'Não Autorizado');
        }

        $transactions_obj = simplexml_load_string($transactions);

        return $transactions_obj;
    }

    /**
     * Transações abadonadas
     * 
     * @param type $initialDate
     * @param type $finalDate
     * @param type $page
     * @param type $maxPageResults
     * @return type
     */
    public function getTransactionsAbandoned($initialDate, $finalDate, $page = 1, $maxPageResults = 100) {

        $this->verificaAmbiente();
        $url = $this->url_transactions_abandoned . $this->email_token
                . "&initialDate=$initialDate"
                . "&finalDate=$finalDate"
                . "&page=$page"
                . "&maxPageResults=$maxPageResults";
        $transactions = $this->connect($url);
        if ($checkout == 'Unauthorized') {
            return array('error' => 'Não Autorizado');
        }

        $transactions_obj = simplexml_load_string($transactions);
        if (count($transactions_obj->error) > 0) {
            echo 'erro';  //Insira seu código avisando que o sistema está com problemas
        }
        return $transactions_obj;
    }

//
//    private function generateUrl($dados, $retorno) {
//        //Configurações
//        $data['email'] = $this->email;
//        $data['token'] = $this->token_oficial;
//        $data['currency'] = 'BRL';
//
//        //Itens
//        $data['itemId1'] = '0001';
//        $data['itemDescription1'] = $dados['descricao'];
//        $data['itemAmount1'] = number_format($dados['valor'], 2, ".", "");
//        $data['itemQuantity1'] = '1';
//        $data['itemWeight1'] = '0';
//
//        //Dados do pedido
//        $data['reference'] = $dados['codigo'];
//
//        //Dados do comprador
//        //Tratar telefone
//        $telefone = implode("", explode("-", substr($dados['telefone'], 5, strlen($dados['telefone']))));
//        $ddd = substr($dados['telefone'], 1, 2);
//
//        //Tratar CEP
//        $cep = implode("", explode("-", $dados['cep']));
//        $cep = implode("", explode(".", $cep));
//
//        $data['senderName'] = $dados['nome'];
//        $data['senderAreaCode'] = $ddd;
//        $data['senderPhone'] = $telefone;
//        $data['senderEmail'] = $dados['email'];
//        $data['shippingType'] = '3';
//        $data['shippingAddressStreet'] = $dados['rua'];
//        $data['shippingAddressNumber'] = $dados['numero'];
//        $data['shippingAddressComplement'] = " ";
//        $data['shippingAddressDistrict'] = $dados['bairro'];
//        $data['shippingAddressPostalCode'] = $cep;
//        $data['shippingAddressCity'] = $dados['cidade'];
//        $data['shippingAddressState'] = strtoupper($dados['estado']);
//        $data['shippingAddressCountry'] = 'BRA';
//        $data['redirectURL'] = $retorno;
//
//        return http_build_query($data);
//    }
//
//    public function executeCheckout($dados, $retorno) {
//
//        if ($dados['codigo_pagseguro'] != "" && $dados['codigo_pagseguro'] != null) {
//            header('Location: ' . $this->url_redirect . $dados['codigo_pagseguro']);
//        }
//
//        $dados = $this->generateUrl($dados, $retorno);
//
//        $curl = curl_init($this->url);
//        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
//        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($curl, CURLOPT_POST, true);
//        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
//        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded; charset=UTF-8'));
//        curl_setopt($curl, CURLOPT_POSTFIELDS, $dados);
//        $xml = curl_exec($curl);
//
//        if ($xml == 'Unauthorized') {
//            //Insira seu código de prevenção a erros
//            echo "Erro: Dados invalidos - Unauthorized";
//            exit; //Mantenha essa linha
//        }
//
//        curl_close($curl);
//        $xml_obj = simplexml_load_string($xml);
//        if (count($xml_obj->error) > 0) {
//            //Insira seu código de tratamento de erro, talvez seja útil enviar os códigos de erros.
//            echo $xml . "<br><br>";
//            echo "Erro-> " . var_export($xml_obj->errors, true);
//            exit;
//        }
//        header('Location: ' . $this->url_redirect . $xml_obj->code);
//    }
//
//    //RECEBE UMA NOTIFICAÇÃO DO PAGSEGURO
//    //RETORNA UM OBJETO CONTENDO OS DADOS DO PAGAMENTO
//    public function executeNotification($POST) {
//        $url = $this->url_notificacao . $POST['notificationCode'] . $this->email_token;
//
//        $curl = curl_init($url);
//        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
//        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
//
//        $transaction = curl_exec($curl);
//        if ($transaction == 'Unauthorized') {
//            //TRANSAÇÃO NÃO AUTORIZADA
//
//            exit;
//        }
//        curl_close($curl);
//        $transaction_obj = simplexml_load_string($transaction);
//        return $transaction_obj;
//    }
//
//    //Obtém o status de um pagamento com base no código do PagSeguro
//    //Se o pagamento existir, retorna um código de 1 a 7
//    //Se o pagamento não exitir, retorna NULL
//    public function getStatusByCode($code) {
//        $url = $this->url_transactions . $code . $this->email_token;
//        $curl = curl_init($url);
//        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
//        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
//
//        $transaction = curl_exec($curl);
//        if ($transaction == 'Unauthorized') {
//            //Insira seu código avisando que o sistema está com problemas
//            //sugiro enviar um e-mail avisando para alguém fazer a manutenção
//            exit; //Mantenha essa linha para evitar que o código prossiga
//        }
//        $transaction_obj = simplexml_load_string($transaction);
//
//        if (count($transaction_obj->error) > 0) {
//            //Insira seu código avisando que o sistema está com problemas
//            var_dump($transaction_obj);
//        }
//
//        if (isset($transaction_obj->status))
//            return $transaction_obj->status;
//        else
//            return NULL;
//    }
//
//    //Obtém o status de um pagamento com base na referência
//    //Se o pagamento existir, retorna um código de 1 a 7
//    //Se o pagamento não exitir, retorna NULL
//    public function getStatusByReference($reference) {
//        $url = $this->url_transactions . $this->email_token . "&reference=" . $reference;
//        $curl = curl_init($url);
//        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
//        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
//
//        $transaction = curl_exec($curl);
//        if ($transaction == 'Unauthorized') {
//            //Insira seu código avisando que o sistema está com problemas
//            exit; //Mantenha essa linha para evitar que o código prossiga
//        }
//        $transaction_obj = simplexml_load_string($transaction);
//        if (count($transaction_obj->error) > 0) {
//            //Insira seu código avisando que o sistema está com problemas
//            var_dump($transaction_obj);
//        }
//        //print_r($transaction_obj);
//        if (isset($transaction_obj->transactions->transaction->status))
//            return $transaction_obj->transactions->transaction->status;
//        else
//            return NULL;
//    }


    public function pagination($totalPages, $currentPage, $onclick) {
        echo '<div class="text-center">';

        for ($i = 1; $i < (int) $totalPages; $i++) {

            if ($currentPage == $i) {
                echo '<span class="pgoff text-danger bold" style="font-size:14px;margin:3px">' . $i . '</span>';
            } else {
                echo '<button href="#" class="pg btn btn-xs btn-default" data-pg="' . $i . '"  style="margin:3px;" onclick="' . $onclick . '">' . $i . '</button>';
            }
        }

        echo '</div>';
    }

    public function getPaymentMethodName($codeMethod) {

        return $this->tipoPagamento[$codeMethod];
    }

    public function gerarLinkPagamentoConsulta($idDominio, $idConsulta, $emailPaciente, $ambientePagSeguro, $itensPagSeguroAPI, $precoConsulta = null) {

        $PagSeguroConfigRepository = new PagSeguroConfigRepository;
        $rowPagSeguroConfig = $PagSeguroConfigRepository->getDados($idDominio);

        if ($rowPagSeguroConfig->habilitado == 1
                AND!empty($rowPagSeguroConfig->token)) {

            $PagSeguroAPI = new PagSeguroApiService;
            $PagSeguroAPI->setAmbiente($ambientePagSeguro);
            $PagSeguroAPI->setToken($rowPagSeguroConfig->token);
            $PagSeguroAPI->setEmail($rowPagSeguroConfig->email);

            if (is_array($itensPagSeguroAPI)) {
                $dados = $itensPagSeguroAPI;
            } else {
                $dados[] = $itensPagSeguroAPI;
            }


            $idReferencia = 'consv_' . $idConsulta;
            $checkout = $PagSeguroAPI->checkout($dados, $idReferencia, $emailPaciente, $rowPagSeguroConfig->duracao_link_pg);

       

            if (empty($checkout->error)) {
                $codPagseguro = $checkout->code;
                $linkPagseguro = $checkout->link_checkout;
                $dadosUpdatePagSeg['link_pagseguro'] = $linkPagseguro;
                $dadosUpdatePagSeg['cod_ref_pagseguro'] = $idReferencia;
                $dadosUpdatePagSeg['pag_seguro_status'] = 0;
                $dadosUpdatePagSeg['pagseguro_validade_link'] = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . " +$rowPagSeguroConfig->duracao_link_pg hour"));
                $ConsultaRepository = new ConsultaRepository;
                $ConsultaRepository->updateConsulta($idDominio, $idConsulta, $dadosUpdatePagSeg);
                return [
                    'link' => $linkPagseguro,
                    'code' => $codPagseguro
                ];
            } else {
                return false;
            }
        }
    }

}
