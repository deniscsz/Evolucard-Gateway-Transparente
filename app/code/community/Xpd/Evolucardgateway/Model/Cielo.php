<?php

/**
 * Octagono Ecommerce
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.octagonoecommerce.com.br/eula-licenca-usuario-final.html
 *
 *
 * @category   Cielo
 * @package    Octagono_Cielo
 * @copyright  Copyright (c) 2009-2011 - Octagono Ecommerce - www.octagonoecommerce.com.br
 * @license    http://www.octagonoecommerce.com.br/eula-licenca-usuario-final.html
 */
class Xpd_Evolucardgateway_Model_Evolucardgateway extends Mage_Payment_Model_Method_Abstract {

    public $versao = "1.1.0";
    public $dadosEcNumero;
    public $dadosEcChave;
    public $urlEnviroment;
    public $dadosPortadorNumero;
    public $dadosPortadorVal;
    public $dadosPortadorInd;
    public $dadosPortadorCodSeg;
    public $dadosPortadorNome;
    public $dadosPedidoNumero;
    public $dadosPedidoValor;
    public $dadosPedidoMoeda = "986";
    public $dadosPedidoData;
    public $dadosPedidoDescricao;
    public $dadosPedidoIdioma = "PT";
    public $formaPagamentoBandeira;
    public $formaPagamentoProduto;
    public $formaPagamentoParcelas;
    public $urlRetorno;
    public $autorizar;
    public $capturar;
    public $tid;
    public $status;
    public $urlAutenticacao;
    public $ambiente = 1; //0 TESTE / 1 PRODUCAO

    const ENCODING = "ISO-8859-1";

    protected $_code = 'cielo';
    protected $_formBlockType = 'cielo/form_cc';
    protected $_infoBlockType = 'cielo/info';

    /**
     * A model to serialize attributes
     * @var Varien_Object
     */
    protected $_serializer = null;

    /**
     * Initialization
     */
    protected function _construct() {
        $this->_serializer = new Varien_Object ();
        parent :: _construct();
    }

    // Geradores de XML
    private function XMLHeader() {
        return '<?xml version="1.0" encoding="' . self::ENCODING . '" ?>';
    }

    private function XMLDadosEc() {
        $msg = '<dados-ec>' . "\n      " .
                '<numero>'
                . $this->dadosEcNumero .
                '</numero>' . "\n      " .
                '<chave>'
                . $this->dadosEcChave .
                '</chave>' . "\n   " .
                '</dados-ec>';

        return $msg;
    }

    private function XMLDadosPortador() {
        $msg = '<dados-portador>' . "\n      " .
                '<numero>'
                . $this->dadosPortadorNumero .
                '</numero>' . "\n      " .
                '<validade>'
                . $this->dadosPortadorVal .
                '</validade>' . "\n      " .
                '<indicador>'
                . $this->dadosPortadorInd .
                '</indicador>' . "\n      " .
                '<codigo-seguranca>'
                . $this->dadosPortadorCodSeg .
                '</codigo-seguranca>' . "\n   ";

        // Verifica se Nome do Portador foi informado
        if ($this->dadosPortadorNome != null && $this->dadosPortadorNome != "") {
            $msg .= '   <nome-portador>'
                    . $this->dadosPortadorNome .
                    '</nome-portador>' . "\n   ";
        }

        $msg .= '</dados-portador>';

        return $msg;
    }

    private function XMLDadosCartao() {
        $msg = '<dados-cartao>' . "\n      " .
                '<numero>'
                . $this->dadosPortadorNumero .
                '</numero>' . "\n      " .
                '<validade>'
                . $this->dadosPortadorVal .
                '</validade>' . "\n      " .
                '<indicador>'
                . $this->dadosPortadorInd .
                '</indicador>' . "\n      " .
                '<codigo-seguranca>'
                . $this->dadosPortadorCodSeg .
                '</codigo-seguranca>' . "\n   ";

        // Verifica se Nome do Portador foi informado
        if ($this->dadosPortadorNome != null && $this->dadosPortadorNome != "") {
            $msg .= '   <nome-portador>'
                    . $this->dadosPortadorNome .
                    '</nome-portador>' . "\n   ";
        }

        $msg .= '</dados-cartao>';

        return $msg;
    }

    private function XMLDadosPedido() {
        $this->dadosPedidoData = date("Y-m-d") . "T" . date("H:i:s");
        $msg = '<dados-pedido>' . "\n      " .
                '<numero>'
                . $this->dadosPedidoNumero .
                '</numero>' . "\n      " .
                '<valor>'
                . $this->dadosPedidoValor .
                '</valor>' . "\n      " .
                '<moeda>'
                . $this->dadosPedidoMoeda .
                '</moeda>' . "\n      " .
                '<data-hora>'
                . $this->dadosPedidoData .
                '</data-hora>' . "\n      ";
        if ($this->dadosPedidoDescricao != null && $this->dadosPedidoDescricao != "") {
            $msg .= '<descricao>'
                    . $this->dadosPedidoDescricao .
                    '</descricao>' . "\n      ";
        }
        $msg .= '<idioma>'
                . $this->dadosPedidoIdioma .
                '</idioma>' . "\n   " .
                '</dados-pedido>';

        return $msg;
    }

    private function XMLFormaPagamento() {
        $msg = '<forma-pagamento>' . "\n      " .
                '<bandeira>'
                . $this->formaPagamentoBandeira .
                '</bandeira>' . "\n      " .
                '<produto>'
                . $this->formaPagamentoProduto .
                '</produto>' . "\n      " .
                '<parcelas>'
                . $this->formaPagamentoParcelas .
                '</parcelas>' . "\n   " .
                '</forma-pagamento>';

        return $msg;
    }

    private function XMLUrlRetorno() {
        $msg = '<url-retorno>' . $this->urlRetorno . '</url-retorno>';

        return $msg;
    }

    private function XMLAutorizar() {
        $msg = '<autorizar>' . $this->autorizar . '</autorizar>';

        return $msg;
    }

    private function XMLCapturar() {
        $msg = '<capturar>' . $this->capturar . '</capturar>';

        return $msg;
    }

    // Envia Requisição
    public function Enviar($vmPost, $transacao) {

		/* Usar somente quando em modo TESTE
        if ($this->urlEnviroment == NULL) {
            $url = "https://qasecommerce.cielo.com.br/servicos/ecommwsec.do";
        } else {
            $url = $this->urlEnviroment;
        }*/
        
        $url = "https://ecommerce.cbmp.com.br/servicos/ecommwsec.do";
        //print_r($vmPost);

        try {
            // ENVIA REQUISIÇÃO SITE CIELO
            //$client = new Zend_Http_Client($url, array('timeout' => 40));
            $client = new Zend_Http_Client($url, array('timeout' => 40));
            $client->setHeaders(array('Host: ecommerce.cbmp.com.br', 'Content-Type:application/x-www-form-urlencoded', 'Content-Length: length'));
            $client->setParameterPost('mensagem', $vmPost);
            $vmResposta = $client->request(Zend_Http_Client::POST);
            $vmResposta = $vmResposta->getBody();
          	//print_r($vmResposta);
        } catch (Exception $e) {
            //URL Error
            $this->_throwError('urlerror', 'URL Error - ' . $e->getMessage(), __LINE__);
            return false;
        };


        return simplexml_load_string($vmResposta);
    }

    // Requisições
    public function RequisicaoTransacao($incluirPortador) {
        $msg = $this->XMLHeader() . "\n" .
                '<requisicao-transacao id="' . md5(date("YmdHisu")) . '" versao="' . $this->versao . '">' . "\n   "
                . $this->XMLDadosEc() . "\n   ";
        if ($incluirPortador == true) {
            $msg .= $this->XMLDadosPortador() . "\n   ";
        }
        $msg .= $this->XMLDadosPedido() . "\n   "
                . $this->XMLFormaPagamento() . "\n   "
                . $this->XMLUrlRetorno() . "\n   "
                . $this->XMLAutorizar() . "\n   "
                . $this->XMLCapturar() . "\n";

        $msg .= '</requisicao-transacao>';

        $objResposta = $this->Enviar($msg, "Transacao");
        return $objResposta;
    }

    public function RequisicaoTid() {
        $msg = $this->XMLHeader() . "\n" .
                '<requisicao-tid id="' . md5(date("YmdHisu")) . '" versao ="' . $this->versao . '">' . "\n   "
                . $this->XMLDadosEc() . "\n   "
                . $this->XMLFormaPagamento() . "\n" .
                '</requisicao-tid>';

        $objResposta = $this->Enviar($msg, "Requisicao Tid");
        return $objResposta;
    }

    public function RequisicaoAutorizacaoPortador() {
        $msg = $this->XMLHeader() . "\n" .
                '<requisicao-autorizacao-portador id="' . md5(date("YmdHisu")) . '" versao ="' . $this->versao . '">' . "\n"
                . '<tid>' . $this->tid . '</tid>' . "\n   "
                . $this->XMLDadosEc() . "\n   "
                . $this->XMLDadosCartao() . "\n   "
                . $this->XMLDadosPedido() . "\n   "
                . $this->XMLFormaPagamento() . "\n   "
                . '<capturar-automaticamente>' . $this->capturar . '</capturar-automaticamente>' . "\n" .
                '</requisicao-autorizacao-portador>';

        $objResposta = $this->Enviar($msg, "Autorizacao Portador");
        return $objResposta;
    }

    public function RequisicaoAutorizacaoTid() {
        $msg = $this->XMLHeader() . "\n" .
                '<requisicao-autorizacao-tid id="' . md5(date("YmdHisu")) . '" versao="' . $this->versao . '">' . "\n  "
                . '<tid>' . $this->tid . '</tid>' . "\n  "
                . $this->XMLDadosEc() . "\n" .
                '</requisicao-autorizacao-tid>';

        $objResposta = $this->Enviar($msg, "Autorizacao Tid");
        return $objResposta;
    }

    public function RequisicaoCaptura($tid, $loja, $chave, $PercentualCaptura, $anexo) {
        $msg = $this->XMLHeader() . "\n" .
                '<requisicao-captura id="' . md5(date("YmdHisu")) . '" versao="' . $this->versao . '">' . "\n   "
                . '<tid>' . $tid . '</tid>' . "\n   "
                . '<dados-ec>' . "\n      " .
                '<numero>'
                . $loja .
                '</numero>' . "\n      " .
                '<chave>'
                . $chave .
                '</chave>' . "\n   " .
                '</dados-ec>'
                . '<valor>' . $PercentualCaptura . '</valor>' . "\n";
        if ($anexo != null && $anexo != "") {
            $msg .= '   <anexo>' . $anexo . '</anexo>' . "\n";
        }
        $msg .= '</requisicao-captura>';

        $objResposta = $this->Enviar($msg, "Captura");
        return $objResposta;
    }

    public function RequisicaoCancelamento($tid, $loja, $chave) {
        $msg = $this->XMLHeader() . "\n" .
                '<requisicao-cancelamento id="' . md5(date("YmdHisu")) . '" versao="' . $this->versao . '">' . "\n   "
                . '<tid>' . $tid . '</tid>' . "\n   "
                . '<dados-ec>' . "\n      " .
                '<numero>'
                . $loja .
                '</numero>' . "\n      " .
                '<chave>'
                . $chave .
                '</chave>' . "\n   " .
                '</dados-ec>'. "\n   " .
                '</requisicao-cancelamento>';

        $objResposta = $this->Enviar($msg, "Cancelamento");
        return $objResposta;
    }

    public function RequisicaoConsulta($tid, $loja, $chave) {
        $msg = $this->XMLHeader() . "\n" .
                '<requisicao-consulta id="' . md5(date("YmdHisu")) . '" versao="' . $this->versao . '">' . "\n   "
                . '<tid>' . $tid . '</tid>' . "\n   "
                . '<dados-ec>' . "\n      " .
                '<numero>'
                . $loja .
                '</numero>' . "\n      " .
                '<chave>'
                . $chave .
                '</chave>' . "\n   " .
                '</dados-ec>'. "\n   " .
                '</requisicao-consulta>';

        $objResposta = $this->Enviar($msg, "Consulta");
        return $objResposta;
    }

    // Transforma em/lê string
    public function ConverteToString() {
        $msg = $this->XMLHeader() .
                '<objeto-pedido>'
                . '<tid>' . $this->tid . '</tid>'
                . '<status>' . $this->status . '</status>'
                . $this->XMLDadosEc()
                . $this->XMLDadosPedido()
                . $this->XMLFormaPagamento() .
                '</objeto-pedido>';

        return $msg;
    }

    public function FromString($Str) {
        $DadosEc = "dados-ec";
        $DadosPedido = "dados-pedido";
        $DataHora = "data-hora";
        $FormaPagamento = "forma-pagamento";

        $XML = simplexml_load_string($Str);

        $this->tid = $XML->tid;
        $this->status = $XML->status;
        $this->dadosEcChave = $XML->$DadosEc->chave;
        $this->dadosEcNumero = $XML->$DadosEc->numero;
        $this->dadosPedidoNumero = $XML->$DadosPedido->numero;
        $this->dadosPedidoData = $XML->$DadosPedido->$DataHora;
        $this->dadosPedidoValor = $XML->$DadosPedido->valor;
        $this->formaPagamentoProduto = $XML->$FormaPagamento->produto;
        $this->formaPagamentoParcelas = $XML->$FormaPagamento->parcelas;
    }

    // Traduz cógigo do Status
    public function getStatus() {
        $status;

        switch ($this->status) {
            case "0": $status = "Criada";
                break;
            case "1": $status = "Em andamento";
                break;
            case "2": $status = "Autenticada";
                break;
            case "3": $status = "Não autenticada";
                break;
            case "4": $status = "Autorizada";
                break;
            case "5": $status = "Não autorizada";
                break;
            case "6": $status = "Capturada";
                break;
            case "8": $status = "Não capturada";
                break;
            case "9": $status = "Cancelada";
                break;
            case "10": $status = "Em autenticação";
                break;
            default: $status = "n/a";
                break;
        }

        return $status;
    }

    // Verifica em Resposta XML a ocorrência de erros
    // Parâmetros: XML de envio, XML de Resposta
    public function VerificaErro($vmPost, $vmResposta) {
        $error_msg = null;

        try {
            if (stripos($vmResposta, "SSL certificate problem") !== false) {
                throw new Exception("CERTIFICADO INVÁLIDO - O certificado da transação não foi aprovado", "099");
            }

            $objResposta = simplexml_load_string($vmResposta, null, LIBXML_NOERROR);
            if ($objResposta == null) {
                throw new Exception("HTTP READ TIMEOUT - o Limite de Tempo da transação foi estourado", "099");
            }
        } catch (Exception $ex) {
            $error_msg = "     Código do erro: " . $ex->getCode() . "\n";
            $error_msg .= "     Mensagem: " . $ex->getMessage() . "\n";

            // Gera página HTML
            echo '<html><head><title>Erro na transação</title></head><body>';
            echo '<span style="font-weight:bold;">Detalhes do erro:</span>' . '<br />';
            echo '<pre>' . $error_msg . '<br /><br />';
            //echo "     XML de envio: " . "<br />" . htmlentities($vmPost);
            echo '</pre></body></html>';
            $error_msg .= "     XML de envio: " . "\n" . $vmPost;

            // Dispara o erro
            trigger_error($error_msg, E_USER_ERROR);

            return true;
        }
    }

}

