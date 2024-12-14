# Mercado Pago Split Payment

### README para o Plugin "Mercado Pago Split (WooCommerce + WCFM)"

---

# Mercado Pago Split (WooCommerce + WCFM)

**Versão:** 1.0.0  
**Autor:** Eli Silva  
**Descrição:** Plugin para integrar o Mercado Pago com WooCommerce e WCFM Marketplace, permitindo pagamentos divididos automáticos diretamente para os vendedores.

---

## **Recursos**

- **Pagamentos Divididos Automáticos:** Envia comissões diretamente para as contas do Mercado Pago dos vendedores.  
- **Integração com WCFM:** Adiciona o Mercado Pago como método de retirada no painel do WCFM.  
- **Autenticação Automática:** Atualiza o `access_token` e o `refresh_token` do Mercado Pago a cada 175 dias.  
- **Configurações Personalizadas:** 
  - Admin pode configurar o `Client ID` e `Client Secret` do Mercado Pago.
  - Vendedores podem conectar suas contas diretamente pelo painel.

---

## **Instalação**

1. Faça o upload do arquivo do plugin para a pasta `/wp-content/plugins/` ou instale diretamente pelo painel do WordPress.
2. Ative o plugin através do menu "Plugins" no WordPress.
3. Configure as credenciais do Mercado Pago (`Client ID` e `Client Secret`) no painel do administrador.

---

## **Configuração**

### **1. Para Administradores:**
- No painel do WCFM, configure:
  - **Client ID**: Obtido na sua conta do Mercado Pago.
  - **Client Secret**: Obtido na sua conta do Mercado Pago.

### **2. Para Vendedores:**
- No painel do vendedor, clique no botão **"Conectar ao Mercado Pago"** e siga as instruções para vincular sua conta.

---

## **Como Funciona**

1. **Configuração Inicial:**
   - Admin configura as credenciais do Mercado Pago.
   - Vendedores vinculam suas contas usando o botão de conexão.

2. **Pagamento Dividido:**
   - Ao concluir um pedido no WooCommerce, o plugin identifica os vendedores envolvidos e realiza a divisão do pagamento com base nas comissões definidas.
   - As comissões são enviadas diretamente para as contas dos vendedores.

3. **Atualização de Tokens:**
   - O `access_token` e o `refresh_token` do Mercado Pago são automaticamente atualizados.

---

## **Requisitos**

- WordPress 5.0 ou superior.  
- WooCommerce 4.0 ou superior.  
- WCFM Marketplace 3.0 ou superior.  
- Conta do Mercado Pago com as credenciais (`Client ID` e `Client Secret`).

---

## **Personalização**

O plugin foi desenvolvido para ser extensível e permite adicionar customizações específicas, como a lógica de cálculo das comissões ou outros comportamentos relacionados ao split de pagamento.

---

## **Suporte**

Para dúvidas e suporte, entre em contato com o autor no site [juntoaqui.com.br](https://juntoaqui.com.br).

---

**Nota:** Este plugin utiliza a API do Mercado Pago. Certifique-se de que as credenciais e configurações da conta do Mercado Pago estejam corretas. 
