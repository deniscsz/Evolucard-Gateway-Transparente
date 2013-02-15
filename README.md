Evolucard-Gateway-Transparente
==============================

Instruções

Recomendamos que primeiramente realize backup completo do seu site, banco de dados e arquivos. A Evolucard não se responsabiliza por quaisquer danos ou prejuízos financeiros decorrentes da má utilização ou instalação desse módulo.

Requerimentos

• Magento 1.4.2.0 ou superior
•	PHP 5.2.0 ou superior

Instalações

Faça o download do arquivo .zip do módulo (provavelmente já o fez).
Descompactar os arquivos para uma pasta qualquer em seu computador, por exemplo, nova pasta.
Envie via FTP todos os arquivos e pastas que foram descompactados em sua pasta (ex: nova pasta).

Configuração

Limpar o cachê do Magento através do menu SISTEMA >  GERENCIAMENTO DE CACHE
Clique em SISTEMA > CONFIGURAÇÃO
Clique na seção VENDAS no subitem MÉTODOS DE PAGAMENTOS e abra a seção EVOLUCARD.
Coloque nos devidos campos as informações referentes a códigos de integração, números de parcelas e responsável pelo parcelamento que serão fornecidos pela Evolucard.
Atenção para dois campos: CAPTURAR AUTOMATICAMENTE e HABILITAR CADASTRO 1-CLICK. Para capturar automaticamente, além de ativar o campo Capturar Automaticamente, você deve requerer a integração com SUP, na qual a Evolucard se comunicará com sua loja sempre que uma transação mudar de status, fazendo a alteração na plataforma. A url de integração é http://<urldaloja>/evolucardgateway/standard/captura
A funcionalidade 1-Click, por hora não está disponível e deve permanecer desativada, porém ela da oportunidade ao consumidor de salvar suas informações para um checkout expresso nas próximas compras através da tecnologia Evolucard 1-Click.
Para colocar o módulo em Produção, escolha a opção PRODUÇÃO no campo AMBIENTE.

Customização

É possível efetuar modificações visuais via CSS (skin/frontend/default/default/css/evolucardgateway.css) e modificando o HTML em (app/design/frontend/base/default/template/xpd/evolucardgateway/form/cc.phtml).
